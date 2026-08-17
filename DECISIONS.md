# Engineering Decisions

## AI Form Builder

This document records the main assumptions, architectural decisions,
Part D choices, trade-offs, known limitations, and possible next steps
for the AI Form Builder project.

------------------------------------------------------------------------

# 1. Assumptions

The following assumptions were made where the brief left implementation
details open.

### 1.1 JSON Schema is the source of truth

The form definition is stored as JSON and is shared by:

-   Manual builder
-   Raw JSON editor
-   AI generation
-   AI editing
-   DOCX/XLSX import
-   Public form rendering
-   Server-side validation
-   Conditional logic
-   Version history

This avoids maintaining separate field definitions in multiple places.

### 1.2 Forms are schema-driven rather than database-field-driven

Individual form fields are not stored as separate database rows.

The schema is stored as JSON because every user-created form can have a
different structure.

### 1.3 Server-side validation is authoritative

Browser validation improves user experience, but it is never trusted as
the final security/validation layer.

The server derives validation rules from the stored schema before
accepting a submission.

### 1.4 Imported documents are proposals, not final forms

A Word or Excel document can be ambiguous.

Therefore the importer produces an editable result and gives the user a
preview/mapping step before committing the form.

### 1.5 AI output is untrusted input

AI-generated JSON is treated similarly to external user input.

It must be decoded, validated and rejected/repaired if it does not
conform to the application's schema contract.

### 1.6 Long-running operations should not block HTTP requests

AI generation/editing and larger imports run through queued jobs.

The UI therefore receives a status such as:

``` text
queued
processing
completed
failed
```

rather than waiting for a long synchronous request.

------------------------------------------------------------------------

# 2. JSON Schema as the Single Source of Truth

This is the central architectural decision.

The system intentionally avoids having one definition for the builder
and another definition for the public form.

The flow is:

``` text
                    JSON Schema
                         |
        +----------------+----------------+
        |                |                |
        v                v                v
     Builder            AI             Import
        |                |                |
        +----------------+----------------+
                         |
                         v
                  Schema Validator
                         |
          +--------------+--------------+
          |              |              |
          v              v              v
       Public         Version        Submission
        Form          History          Storage
```

### Why

This makes the system easier to reason about and reduces synchronization
bugs.

It also makes AI and document import natural extensions because both
ultimately need to produce the same schema.

### Trade-off

JSON provides flexibility, but arbitrary reporting across unknown fields
is more difficult than with a normalized field table.

### Alternative considered

A normalized database model with:

``` text
forms
sections
fields
options
validations
logic
```

would make relational querying easier but would increase complexity for:

-   version snapshots
-   AI generation
-   schema import/export
-   arbitrary field types

The JSON approach was selected for this project.

------------------------------------------------------------------------

# 3. Part D Choice #1 --- Form Versioning and Rollback

## User problem

Form authors need to experiment without worrying about permanently
losing a previous working form.

## Implementation

The `form_versions` table stores complete schema snapshots.

Versions are unique per form:

``` text
form_id + version
```

Restoring a version does not delete later versions.

Example:

``` text
V1
V2
V3

Restore V1

V1
V2
V3
V4  <- V1 schema restored here
```

This preserves an audit trail.

## Why this choice

Full snapshots make rollback straightforward and reliable.

There is no need to reconstruct a form from a chain of potentially
complex JSON patches.

## Trade-off accepted

Full snapshots consume more storage than a diff-only approach.

For the expected project scale, reliability and simplicity were
considered more valuable than minimizing snapshot storage.

## With more time

I would introduce periodic full snapshots plus JSON diffs between
snapshots for very large forms.

------------------------------------------------------------------------

# 4. Part D Choice #2 --- Conditional Logic / Branching

## User problem

A static form can force users to answer questions that are irrelevant to
them.

Example:

``` text
Currently employed?
    |
    +-- Yes --> Company Name
    |
    +-- No  --> Hide Company Name
```

## Implementation

Conditional rules are stored with the form schema.

Rules define:

-   Source field
-   Operator
-   Comparison value
-   Action
-   Target field

The public form evaluates the rules dynamically.

The server also respects the same visibility rules.

Therefore a hidden required field does not incorrectly block submission.

Hidden conditional fields are also removed from persisted submission
data.

When a field referenced by a rule is deleted, related rules are cleaned
up.

## Why this choice

Keeping conditional logic in the schema means the builder and public
form can use the same definition.

It also makes conditional logic versionable together with the rest of
the form.

## Trade-off accepted

Conditional logic adds complexity to:

-   Schema validation
-   Public rendering
-   Submission validation
-   Field deletion
-   Undo/redo

The implementation intentionally uses a focused rule model instead of
creating a general-purpose expression language.

## With more time

I would add:

-   AND/OR rule groups
-   Nested conditions
-   Section-level branching
-   Conditional validation/messages
-   Visual logic diagrams

------------------------------------------------------------------------

# 5. Part D Choice #3 --- Autosave + Undo/Redo

## User problem

Form builders are editing-heavy applications. Losing a change or
accidentally deleting a field can be frustrating.

## Implementation

The builder keeps a bounded history of schema states.

Undo/Redo covers schema-changing operations including:

-   Add field
-   Edit field
-   Duplicate field
-   Delete field
-   Reorder field
-   Change options
-   Conditional logic changes
-   JSON changes

Autosave is debounced so the application does not write to the database
for every individual keystroke.

## Important distinction

Autosave and versioning have different purposes.

``` text
Autosave
    =
Protect the current working state
```

``` text
Save / Version
    =
Create an intentional recoverable snapshot
```

Therefore autosave does not create a new version for every edit.

## Why this choice

It gives the user a safer editing experience without polluting Version
History with hundreds of tiny snapshots.

## Trade-off accepted

Undo/Redo state consumes component memory.

A bounded history is therefore used instead of unlimited history.

## With more time

I would add:

-   Persistent undo history
-   Concurrent editing detection
-   Optimistic locking
-   Conflict resolution
-   Cross-device draft recovery

------------------------------------------------------------------------

# 6. AI Generation Architecture

## Decision

AI generation runs asynchronously through a queue.

The flow is:

``` text
Prompt
  |
  v
AiGeneration
  |
  v
Queue
  |
  v
GenerateAiForm
  |
  v
OpenAiFormService
  |
  v
Schema validation
  |
  v
Completed / Failed
```

## Why

An LLM request can take significantly longer than a normal form-builder
request.

Blocking the HTTP request would make the UI unreliable and increase
timeout risk.

The generation record also gives the UI a persistent place to display
status and errors.

------------------------------------------------------------------------

# 7. AI Editing Architecture

AI editing receives the existing form schema plus the user's
instruction.

Example:

``` text
Existing schema
      +
"Make phone required"
      |
      v
AI edit
      |
      v
New schema
      |
      v
Validate
      |
      v
Compare
      |
      v
Update form
      |
      v
Create version
```

A schema change is only applied after validation.

This prevents a failed AI response from replacing a working form.

------------------------------------------------------------------------

# 8. AI Prompt Strategy

The system prompt establishes a strict output contract.

It defines:

-   Allowed field types
-   Required schema structure
-   Field-key rules
-   Section structure
-   Validation structure
-   Conditional logic structure
-   Unsupported type handling
-   JSON-only/schema-oriented output

The model is treated as a schema proposal generator rather than the
application's validation authority.

## Hallucinated field types

Unsupported field types are rejected by the schema validator.

They are not silently persisted.

## Malformed or partial JSON

The service follows a validation/recovery flow:

``` text
AI response
    |
    v
Decode
    |
    +-- invalid --> repair/retry
    |
    v
Schema validation
    |
    +-- invalid --> repair/retry
    |
    v
Valid schema
    |
    v
Persist result
```

The queue job itself is not configured for unlimited retries.

This avoids repeatedly retrying permanent API or billing errors.

------------------------------------------------------------------------

# 9. DOCX/XLSX Import Decision

## Decision

Use deterministic parsing first.

AI is not required to interpret information that can be extracted
directly from the document.

### Word

``` text
Heading
  -> Section

Question
  -> Field

Choice/checkbox list
  -> Options
```

### Excel

The importer supports a documented structured layout and a header-row
layout.

## Why

Deterministic parsing provides:

-   Reproducibility
-   Easier debugging
-   Lower hallucination risk
-   Lower AI cost
-   More predictable imports

The preview/mapping screen is the final safety net for ambiguous
documents.

## Trade-off accepted

Pure deterministic parsing cannot infer every semantic field type from
arbitrary documents.

Instead of replacing the parser with AI, the architecture leaves room
for AI-assisted type/validation inference in a future iteration.

------------------------------------------------------------------------

# 10. Import Preview Decision

An import is not immediately committed as a final form.

The workflow is:

``` text
Upload
  |
  v
Parse
  |
  v
Preview
  |
  v
Mapping / correction
  |
  v
Commit
```

## Why

Documents are not necessarily designed as machine-readable forms.

A human review step avoids silently producing an incorrect form.

------------------------------------------------------------------------

# 11. Database Decisions

## Forms

The current form schema is stored as JSON.

The slug is unique because it is used for public form URLs.

Useful indexes include:

``` text
UNIQUE(slug)
INDEX(user_id, status)
INDEX(created_at)
INDEX(ai_generated)
```

## Form versions

Versions use:

``` text
UNIQUE(form_id, version)
INDEX(form_id, created_at)
```

The unique constraint prevents duplicate version numbers.

## Submissions

Submissions use:

``` text
INDEX(form_id, created_at)
```

because the most common administrative query is retrieving submissions
for a form in chronological order.

------------------------------------------------------------------------

# 12. Queue Decision

The application uses Laravel queued jobs for:

-   AI form generation
-   AI form editing
-   Large document imports

## Why

This keeps long-running operations outside the main HTTP request.

## Trade-off

The database queue is simple for local/development environments but is
not the ideal choice for very high throughput.

## With more time

Move production queue workloads to Redis and add worker monitoring.

------------------------------------------------------------------------

# 13. Autosave vs Version History

This deserves an explicit decision because the two systems could easily
become coupled incorrectly.

### Autosave

Stores the latest working schema.

### Version history

Stores intentional historical snapshots.

Therefore:

``` text
User edits
   |
   +--> Autosave → current form
   |
   +--> Save → version snapshot
```

This avoids creating dozens of versions while a user is typing.

------------------------------------------------------------------------

# 14. Server-side Validation Decision

The browser is treated as untrusted.

Validation is derived from the same stored schema.

The server handles:

-   Required values
-   Number rules
-   Length rules
-   Email
-   URL
-   Regex
-   File types
-   File sizes
-   Select/radio/checkbox options
-   Conditional visibility

Checkbox values are correctly treated as arrays, with each selected
option validated against the configured options.

## Why

This prevents users from bypassing the form UI and sending arbitrary
request values.

------------------------------------------------------------------------

# 15. Known Limitations

The current implementation intentionally has some limitations.

### 15.1 Advanced conditional expressions

The current logic model is focused on common field-to-field conditions.

Complex nested AND/OR expressions are not yet implemented.

### 15.2 Analytics

Submission storage exists, but advanced completion/drop-off analytics
are not part of the current implementation.

### 15.3 Multi-tenancy

The current project is not a full multi-tenant SaaS architecture.

### 15.4 Production-scale queue infrastructure

A Redis-based queue and worker monitoring setup would be more
appropriate for high-volume production use.

### 15.5 Large-scale submission analytics

JSON submission data is flexible but arbitrary cross-form analytics
would benefit from a dedicated reporting/read model.

### 15.6 Advanced AI import inference

Document structure is parsed deterministically. AI-assisted semantic
inference for highly ambiguous documents is left as a future
enhancement.

### 15.7 Concurrent editing

The current builder does not implement full multi-user concurrent
editing/conflict resolution.

------------------------------------------------------------------------

# 16. What I Would Build With Two More Weeks

If two additional weeks were available, I would prioritize the
following.

## Week 1

### 1. Automated test suite

Add comprehensive tests for:

-   Schema validation
-   Public form validation
-   Conditional logic
-   Checkbox/radio/select validation
-   AI schema validation
-   Version rollback
-   Import parsing
-   Submission storage
-   CSV export

### 2. Production infrastructure

Add:

-   Redis queue
-   Queue monitoring
-   Production logging
-   Health checks
-   Better upload handling
-   Object storage for files

### 3. Security hardening

Add:

-   Rate limiting
-   Spam protection
-   File scanning
-   Stronger authorization policies
-   Audit logging

------------------------------------------------------------------------

## Week 2

### 4. Advanced conditional logic

Add:

``` text
IF A = Yes AND B > 5
    THEN Show C
```

and nested groups.

### 5. Form analytics

Add:

-   Form views
-   Started forms
-   Completed forms
-   Drop-off points
-   Completion rate
-   Field-level interaction metrics

### 6. Concurrent editing

Add:

-   Optimistic locking
-   Draft revision IDs
-   Conflict detection
-   User-friendly conflict resolution

------------------------------------------------------------------------

# 17. Why These Part D Features Were Selected

The three Part D features were selected because they directly improve
the core form-building workflow.

### Versioning

Protects work and enables recovery.

### Conditional logic

Makes forms shorter and more relevant to respondents.

### Autosave/Undo/Redo

Makes the editor safer and easier to use.

Together they improve:

``` text
Authoring safety
       +
Form flexibility
       +
Recovery
```

rather than adding unrelated functionality.

------------------------------------------------------------------------

# 18. Final Engineering Position

The project intentionally favors:

``` text
Simple source of truth
        +
Strong validation
        +
Asynchronous long-running work
        +
Recoverable editing
        +
Deterministic imports
```

over adding a large number of partially completed features.

The core architectural principle remains:

> **Generate, import, edit and render the same validated JSON form
> schema.**

That design makes the manual builder, AI workflows, document import,
public forms, conditional logic and version history part of one coherent
system rather than separate implementations.
