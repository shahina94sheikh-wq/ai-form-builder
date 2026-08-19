# Live Demo URL

https://srv1910146.hstgr.cloud/forms

# Public Git Repo

https://github.com/shahina94sheikh-wq/ai-form-builder

# AI Form Builder

A Laravel + Livewire + MySQL form-builder application with:

-   Manual drag-and-drop form creation
-   Click-to-add fields
-   JSON-schema-based form definitions
-   AI form generation
-   AI editing of existing forms
-   DOCX and XLSX import
-   Import preview and mapping
-   Public form URLs
-   Server-side schema-derived validation
-   Submission management, search, pagination and CSV export
-   Form version history and rollback
-   Conditional logic / branching
-   Autosave
-   Undo / Redo

------------------------------------------------------------------------

## 1. Project Goals

The application is designed around a single architectural principle:

> **The JSON form schema is the single source of truth.**

The same schema is used by the builder, JSON editor, AI
generation/editing, document import, public form renderer, conditional
logic, server-side validation and version history.

This avoids maintaining separate representations of a form in the
builder and public form.

------------------------------------------------------------------------

# 2. Technology Stack

-   PHP 8.2+
-   Laravel
-   Livewire
-   MySQL
-   Laravel Queue
-   PhpSpreadsheet for Excel processing
-   PHPWord / DOCX processing
-   OpenAI integration for AI generation/editing
-   Bootstrap-based UI

------------------------------------------------------------------------

# 3. Application Architecture

``` text
                         +----------------------+
                         |       Browser        |
                         +----------+-----------+
                                    |
                                    v
                         +----------------------+
                         |   Livewire Components |
                         +----------+-----------+
                                    |
                +-------------------+-------------------+
                |                                       |
                v                                       v
        +---------------+                       +---------------+
        | Form Builder  |                       | Public Form  |
        +-------+-------+                       +-------+-------+
                |                                       |
                +-------------------+-------------------+
                                    |
                                    v
                         +----------------------+
                         |    JSON Form Schema  |
                         +----------+-----------+
                                    |
                 +------------------+------------------+
                 |                  |                 |
                 v                  v                 v
          Validation          Versioning         Submission
                 |
                 v
              MySQL
```

Long-running operations use the queue:

``` text
AI Prompt
   |
   v
AiGeneration
   |
   v
GenerateAiForm / EditAiForm
   |
   v
OpenAiFormService
   |
   v
Validate Schema
   |
   v
Validated Result


DOCX/XLSX
   |
   v
Import Record
   |
   v
ProcessFormImport
   |
   v
Parser
   |
   v
Preview / Mapping
   |
   v
Editable Form
```

------------------------------------------------------------------------

# 4. Core JSON Schema

A simplified form schema is:

``` json
{
  "version": "1.0",
  "title": "Internship Application",
  "description": "Internship application form",
  "sections": [
    {
      "id": "section_personal",
      "title": "Personal Information",
      "fields": [
        {
          "id": "field_name",
          "type": "text",
          "label": "Full Name",
          "key": "full_name",
          "placeholder": "Enter your full name",
          "help_text": "",
          "default": "",
          "required": true,
          "options": [],
          "validation": {
            "min_length": 2,
            "max_length": 100
          }
        }
      ]
    }
  ],
  "logic": [],
  "settings": {
    "success_message": "Thank you for your submission."
  }
}
```

The schema is stored as JSON in the database.

------------------------------------------------------------------------

# 5. Supported Field Types

The builder supports more than the required ten field types:

1.  Text
2.  Textarea
3.  Number
4.  Email
5.  Phone
6.  Date
7.  Select / Dropdown
8.  Radio
9.  Checkbox
10. File Upload
11. Section Heading
12. Rating

Choice-based fields support editable options.

------------------------------------------------------------------------

# 6. Field Configuration

Each field can contain:

-   Label
-   Unique key
-   Placeholder
-   Help text
-   Default value
-   Required flag
-   Options
-   Validation configuration

Supported validation capabilities include:

-   Required
-   Numeric min/max
-   Minimum length
-   Maximum length
-   Numeric validation
-   Email validation
-   URL validation
-   Regex validation
-   File type validation
-   File size validation

Validation values are read from the same schema used by the builder.

------------------------------------------------------------------------

# 7. Form Builder

The manual builder supports:

-   Click-to-add fields
-   Drag-and-drop fields
-   Field reordering
-   Field duplication
-   Inline editing
-   Field deletion
-   Sections
-   Moving fields between sections
-   Section title editing
-   Section deletion
-   Field configuration
-   Option management
-   Conditional logic
-   Raw JSON editing
-   Undo
-   Redo
-   Autosave
-   Save
-   Publish

The builder prevents publishing a form that does not contain a usable
form field.

------------------------------------------------------------------------

# 8. JSON Editor

The raw JSON editor provides two-way synchronization with the visual
builder.

### Builder → JSON

``` text
Canvas change
     |
     v
Schema
     |
     v
Raw JSON editor
```

### JSON → Builder

``` text
JSON edit
     |
     v
JSON decode
     |
     v
Schema validation
     |
     v
Builder
```

Invalid JSON is rejected and is not persisted.

The schema validator also detects invalid field types, invalid field
keys, duplicate keys, missing required structure and invalid
conditional-logic references.

------------------------------------------------------------------------

# 9. Schema Validation

`FormSchemaValidator` acts as a server-side schema boundary.

It validates:

-   Schema version
-   Form title
-   Sections
-   Section IDs
-   Section titles
-   Field IDs
-   Field keys
-   Supported field types
-   Labels
-   Required flags
-   Options
-   Validation configuration
-   Conditional logic

Additional checks include:

-   Duplicate field keys
-   Required options for select/radio/checkbox fields
-   Invalid conditional-logic source references
-   Invalid conditional-logic target references
-   Unsupported logic operators/actions

A broken schema is never intentionally persisted as a valid form
definition.

------------------------------------------------------------------------

# 10. Part A --- Core Form Builder

## Field creation

Fields can be created by:

-   Clicking a field type
-   Dragging a field type into the canvas

Fields can then be:

-   Reordered
-   Duplicated
-   Edited
-   Deleted

## Sections

Forms can be organized into sections.

The builder supports:

-   Adding sections
-   Editing section titles
-   Moving fields between sections
-   Deleting sections

The final remaining section is protected from deletion so a form always
retains a valid section container.

------------------------------------------------------------------------

# 11. Public Form

Every published form receives a public fill URL.

The public form is rendered from the stored JSON schema.

The browser is not trusted as the final validation layer.

Server-side validation is generated from the same schema.

``` text
Stored Schema
     |
     +--> Public Rendering
     |
     +--> Server Validation
```

This prevents users from bypassing validation by modifying browser-side
requests.

------------------------------------------------------------------------

# 12. Public Submission Flow

``` text
Public Form
     |
     v
Conditional Logic
     |
     v
Server-side Validation
     |
     +---- invalid ----> Stay on form + validation errors
     |
     v
Store Submission
     |
     v
Submission List
```

A successful submission redirects to the submission list.

Unexpected submission exceptions are caught and reported while keeping
the user on the form.

------------------------------------------------------------------------

# 13. Conditional Logic

Conditional logic allows fields to be shown or hidden based on another
field.

Example:

``` text
Currently Employed?
    |
    +-- Yes --> Show Company Name
    |
    +-- No  --> Hide Company Name
```

Rules are stored in the form schema.

Example:

``` json
"logic": [
  {
    "when": {
      "field": "currently_employed",
      "operator": "equals",
      "value": "Yes"
    },
    "action": "show",
    "target": "company_name"
  }
]
```

Supported comparison operators include:

-   equals
-   not_equals
-   contains
-   not_contains
-   greater_than
-   less_than
-   greater_or_equal
-   less_or_equal

The implementation supports both field IDs and field keys when resolving
logic references.

## Server-side behavior

Conditional visibility is not only a browser/UI feature.

If a field is hidden:

-   It is excluded from active validation.
-   A hidden required field does not block submission.
-   Hidden field data is removed before submission storage.
-   Hidden file uploads are not stored.

If a referenced field is deleted, related logic rules are automatically
removed.

------------------------------------------------------------------------

# 14. Submissions

The `submissions` table stores:

-   Form ID
-   Submission JSON data
-   IP address
-   User agent
-   Created/updated timestamps

The submission interface supports:

-   Pagination
-   Search
-   CSV export

The CSV export is implemented as a streaming/chunked operation so that
larger submission sets do not need to be loaded entirely into memory.

------------------------------------------------------------------------

# 15. Database Design

Core application tables include:

``` text
forms
form_versions
submissions
ai_generations
form_imports
```

Laravel queue tables are also used where configured.

## Forms indexes

``` text
UNIQUE(slug)
INDEX(user_id, status)
INDEX(created_at)
INDEX(ai_generated)
```

## Form versions indexes

``` text
UNIQUE(form_id, version)
INDEX(form_id, created_at)
```

## Submissions indexes

``` text
INDEX(form_id, created_at)
```

## AI generations

Generation records are indexed to support form/status lookups.

## Import records

Import records are indexed for user/status lookups.

### Scaling consideration

Flexible form values are stored as JSON because every form can have a
different structure.

For large-scale analytics/reporting, frequently queried submission
values could later be projected into dedicated indexed columns or an
analytics/read model.

------------------------------------------------------------------------

# 16. Part B --- AI Form Generation

AI generation converts a natural-language prompt into an editable form.

Example:

``` text
Create an internship application form with education history,
skills, work experience and resume upload.
```

The generated schema contains sensible:

-   Sections
-   Field types
-   Labels
-   Keys
-   Placeholders
-   Options
-   Required flags
-   Validations

The result must pass schema validation before it can become a form.

------------------------------------------------------------------------

# 17. AI Queue Architecture

AI requests do not block the normal web request.

``` text
User Prompt
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
Schema Validation
    |
    v
Completed / Failed
```

Generation status is tracked as:

``` text
queued
processing
completed
failed
```

AI metadata is stored where available:

-   Model
-   Input token count
-   Output token count
-   Latency
-   Result schema
-   Error

------------------------------------------------------------------------

# 18. AI Prompt Strategy

The AI system prompt establishes the output contract.

It defines:

1.  Allowed field types
2.  Expected JSON structure
3.  Field-key rules
4.  Section structure
5.  Validation structure
6.  Conditional-logic structure
7.  The requirement not to invent unsupported field types
8.  The requirement to return schema-compatible JSON

The AI is treated as a generator of a proposed schema.

The application remains responsible for final validation.

## Malformed JSON

The general flow is:

``` text
AI Response
    |
    v
Decode
    |
    +---- Invalid
    |       |
    |       v
    |     Repair / Retry
    |
    +---- Valid
            |
            v
       Schema Validation
            |
            +---- Invalid
            |       |
            |       v
            |     Repair / Retry
            |
            +---- Valid
                    |
                    v
                 Persist
```

The queue job is not configured for unlimited retries. Schema-level
recovery belongs in the AI service and permanent failures are recorded.

------------------------------------------------------------------------

# 19. AI Editing

AI editing works on existing forms, including forms created manually.

Example prompts:

``` text
Make phone number required.
```

``` text
Add an emergency contact section.
```

``` text
Add a resume upload field.
```

``` text
Translate the form labels to Hindi.
```

The current schema is provided to the AI editing service.

The resulting schema is:

1.  Validated
2.  Compared with the existing schema
3.  Applied if valid
4.  Stored as a new form version when changed

This keeps AI editing integrated with version history.

------------------------------------------------------------------------

# 20. Part C --- Word and Excel Import

Supported formats:

``` text
.docx
.xlsx
```

The import process supports:

-   Upload
-   Parsing
-   Queue processing for larger files
-   Preview
-   Mapping
-   User corrections
-   Final commit

------------------------------------------------------------------------

# 21. Word Import

Word documents are parsed deterministically first.

Typical mapping:

``` text
Heading
   ↓
Section

Question / prompt
   ↓
Field

Choice / checkbox list
   ↓
Options
```

The parser preserves the detected structure so the user can correct the
result before committing it.

Unparseable or ambiguous blocks are reported rather than silently
becoming invalid fields.

------------------------------------------------------------------------

# 22. Excel Import

A documented structured Excel layout is supported.

Example:

  Section    Label       Type     Key         Required   Options
  ---------- ----------- -------- ----------- ---------- ----------------
  Personal   Full Name   text     full_name   yes        
  Personal   Email       email    email       yes        
  Personal   Country     select   country     no         India\|USA\|UK

A plain header-row sheet is also supported.

The imported result becomes an editable form rather than a read-only
representation.

------------------------------------------------------------------------

# 23. Import Preview and Mapping

The import workflow intentionally requires a preview before committing.

The user can correct:

-   Field type
-   Field label
-   Required flag
-   Options
-   Mapping
-   Parser mistakes

This makes document import safer because document formatting can be
ambiguous.

------------------------------------------------------------------------

# 24. Import Strategy

The current implementation prioritizes deterministic extraction for
document structure.

That means:

-   Document structure is parsed directly.
-   Headings are detected directly.
-   Rows/cells are read directly.
-   Options are extracted directly where possible.
-   The user can correct ambiguous mappings in the preview.

This avoids unnecessary LLM dependence for information that can be
reliably extracted from the source document.

AI-based inference can be added later for ambiguous
field-type/validation inference without replacing the deterministic
parser.

------------------------------------------------------------------------

# 25. Defensive Import Handling

Import processing handles cases such as:

-   Missing files
-   Invalid paths
-   Empty rows
-   Empty documents
-   Missing values
-   Unsupported field types
-   Invalid structured layouts
-   Documents with no usable fields
-   Parser errors

A document that cannot produce a usable form should fail clearly rather
than silently creating a broken form.

------------------------------------------------------------------------

# 26. Sample Import Files

Test files are committed with the project.

Recommended location:

``` text
public/templates/form-import/
```

The repository includes sample:

``` text
sample_form_import_test.docx
sample_form_import_test.xlsx
```

These are useful for demonstrating and verifying the import workflow.

------------------------------------------------------------------------

# 27. Part D --- Differentiator 1: Versioning and Rollback

## User problem

Form authors need to experiment safely and recover previous versions
without losing history.

## Implementation

Each saved schema snapshot is stored in `form_versions`.

Example:

``` text
Version 1
    ↓
Version 2
    ↓
Version 3
```

Restoring Version 1 does not delete Versions 2 and 3.

Instead:

``` text
Version 1
Version 2
Version 3
Version 4 ← restored Version 1 schema
```

This creates an auditable history.

## Trade-off

Full schema snapshots consume more storage than storing only diffs.

The benefit is simple and reliable rollback.

## Future improvement

Use periodic full snapshots with JSON diffs between them for very large
schemas.

------------------------------------------------------------------------

# 28. Part D --- Differentiator 2: Conditional Logic

## User problem

Static forms can become unnecessarily long when follow-up questions are
irrelevant.

## Implementation

Rules are stored in the JSON schema and evaluated both by the public UI
and server-side validation.

Example:

``` text
Employment = Yes
       ↓
Show Employer Name
```

Deleting a referenced field also removes the stale logic rule.

## Trade-off

Conditional logic introduces additional runtime/schema complexity.

The current implementation intentionally keeps the rule model focused
instead of introducing an arbitrary expression language.

## Future improvement

Add:

-   AND/OR groups
-   Nested conditions
-   Section-level branching
-   Conditional validation
-   Visual logic graphs

------------------------------------------------------------------------

# 29. Part D --- Differentiator 3: Autosave and Undo/Redo

## User problem

Form authors can lose work or accidentally make destructive changes
during form editing.

## Implementation

The builder maintains a bounded history of schema snapshots.

Undo/Redo covers schema-changing operations such as:

-   Adding fields
-   Editing fields
-   Duplicating fields
-   Deleting fields
-   Reordering
-   Options changes
-   Conditional logic changes
-   JSON changes

Autosave is debounced to avoid a database write for every keystroke.

## Important design decision

Autosave and version history are deliberately separate.

``` text
Autosave
    =
Protect the current working state
```

``` text
Save / Version
    =
Create a deliberate recoverable snapshot
```

Therefore autosave does not create a new version for every edit.

## Trade-off

Undo/Redo snapshots consume Livewire component memory.

The history is bounded to prevent uncontrolled growth.

## Future improvement

Add persistent draft history and concurrent-edit conflict detection.

------------------------------------------------------------------------

# 30. Security and Validation

Important security principles:

-   Server-side validation
-   Schema validation before persistence
-   Conditional-logic validation
-   Field-key validation
-   Upload validation
-   File type validation
-   File size validation
-   CSRF protection through Laravel/Livewire
-   Authentication for builder operations
-   AI result validation
-   No persistence of intentionally invalid schemas

For production, additionally configure:

-   HTTPS
-   Secure cookies
-   Rate limiting
-   Upload limits
-   File scanning
-   Queue worker supervision
-   Authorization policies
-   Monitoring/logging

------------------------------------------------------------------------

# 31. Installation

## Requirements

-   PHP 8.2+
-   Composer
-   MySQL
-   Node.js/npm
-   A configured queue worker

## Install dependencies

``` bash
composer install
npm install
```

Build frontend assets:

``` bash
npm run build
```

Create environment file:

``` bash
cp .env.example .env
```

Generate application key:

``` bash
php artisan key:generate
```

Configure database and application settings in `.env`.

Run migrations:

``` bash
php artisan migrate
```

Clear application caches:

``` bash
php artisan optimize:clear
```

Start the application:

``` bash
php artisan serve
```

Start a queue worker in another terminal:

``` bash
php artisan queue:work --timeout=180
```

------------------------------------------------------------------------

# 32. Queue Configuration

AI generation/editing and larger document imports use queued jobs.

For local development:

``` bash
php artisan queue:work --timeout=180
```

For production, run workers under an appropriate process manager such as
Supervisor or a container orchestration system.

Failed jobs can be inspected with:

``` bash
php artisan queue:failed
```

------------------------------------------------------------------------

# 33. Testing Checklist

## Part A

-   [x] Click-to-add
-   [x] Drag-and-drop
-   [x] Reorder
-   [x] Duplicate
-   [x] Edit
-   [x] Delete
-   [x] 10+ field types
-   [x] Sections
-   [x] Field configuration
-   [x] Options
-   [x] Validation
-   [x] JSON editor
-   [x] Schema validation
-   [x] Public URL
-   [x] Server-side validation
-   [x] Submission storage
-   [x] Pagination
-   [x] Search
-   [x] CSV export

## Part B

-   [x] AI form creation
-   [x] Queued generation
-   [x] Visible generation status
-   [x] Schema validation
-   [x] AI editing
-   [x] Model/token/latency logging
-   [x] Failure handling
-   [x] Prompt strategy documented

## Part C

-   [x] DOCX import
-   [x] XLSX import
-   [x] Structured Excel layout
-   [x] Header-row Excel support
-   [x] Preview
-   [x] Mapping
-   [x] Field-type correction
-   [x] Required-field correction
-   [x] Options correction
-   [x] Queue processing
-   [x] Parser errors
-   [x] Defensive handling
-   [x] Sample DOCX
-   [x] Sample XLSX

## Part D

-   [x] Version history
-   [x] Rollback
-   [x] Conditional logic
-   [x] Hidden-field validation handling
-   [x] Hidden-field data removal
-   [x] Conditional-rule cleanup
-   [x] Autosave
-   [x] Undo
-   [x] Redo

------------------------------------------------------------------------

# 34. Recommended Demo Flow

For a reviewer with limited time:

``` text
Create Form
    ↓
Add / drag fields
    ↓
Configure validation
    ↓
Add sections
    ↓
Add conditional logic
    ↓
Edit JSON
    ↓
Save
    ↓
Version History
    ↓
Restore
    ↓
AI Edit
    ↓
Autosave / Undo / Redo
    ↓
Publish
    ↓
Public Form
    ↓
Submit
    ↓
Submission List
    ↓
CSV Export
```

Then demonstrate:

``` text
DOCX
 ↓
Import
 ↓
Preview / Mapping
 ↓
Editable Form
```

and:

``` text
XLSX
 ↓
Import
 ↓
Preview / Mapping
 ↓
Editable Form
```

------------------------------------------------------------------------

# 35. Database and Scaling Notes

The current database design intentionally keeps the form definition
flexible.

The most important indexes support:

-   Form lookup by slug
-   User/form status filtering
-   Version retrieval
-   Submission retrieval by form/date
-   AI generation status
-   Import status

At higher scale, possible improvements include:

-   Redis queues
-   Compiled/cached schemas
-   Dedicated submission search indexes
-   Analytics read models
-   Object storage for uploaded files
-   Database read replicas
-   Multi-tenant isolation

------------------------------------------------------------------------

# 36. Engineering Trade-offs

## JSON schema vs relational field tables

### Chosen

JSON schema.

### Benefits

-   Flexible
-   Easy to version
-   Easy to validate
-   Easy to send to AI
-   Easy to import/export
-   Single source of truth

### Trade-off

Arbitrary field-level reporting is more difficult than with normalized
field tables.

------------------------------------------------------------------------

## Database queue vs Redis

### Chosen

Laravel queue with the project's configured queue backend.

### Benefits

-   Simple local setup
-   Low infrastructure requirements
-   Easy development/testing

### Future

Redis would be preferable for higher-throughput production workloads.

------------------------------------------------------------------------

## Deterministic document parsing

### Chosen

Parse document structure deterministically first.

### Benefits

-   Reproducible
-   Easier to debug
-   Lower hallucination risk
-   No unnecessary AI cost

### Future

Use AI only where document semantics are genuinely ambiguous.

------------------------------------------------------------------------

## Full version snapshots

### Chosen

Store complete schema snapshots.

### Benefits

-   Simple rollback
-   Easy debugging
-   Reliable history
-   Straightforward implementation

### Trade-off

More storage than a diff-only system.

------------------------------------------------------------------------

# 37. Future Improvements

Potential next improvements include:

-   Redis-backed queues
-   Redis schema caching
-   Public submissions API
-   Webhooks
-   Rate limiting and spam protection
-   Multi-tenant isolation
-   Completion/drop-off analytics
-   Template library
-   Embeddable forms
-   QR sharing
-   Multi-language AI forms
-   Advanced conditional logic groups
-   Concurrent editing
-   Optimistic locking
-   Automated test coverage
-   CI/CD
-   Docker
-   Accessibility/WCAG audit

These are deliberately left as future work rather than adding partially
implemented features.

------------------------------------------------------------------------

# 38. Final Architecture Summary

The main architectural decision is:

``` text
                    JSON Schema
                         |
        +----------------+----------------+
        |                |                |
        v                v                v
     Builder           AI            Import
        |                |                |
        +----------------+----------------+
                         |
                         v
                  Schema Validation
                         |
          +--------------+--------------+
          |              |              |
          v              v              v
       Public         Version        Submission
        Form          History          Storage
          |
          v
    Conditional Logic
```

This means the application does not maintain separate incompatible form
definitions for manual forms, AI forms, imported forms and public forms.

The schema remains the authoritative definition throughout the form
lifecycle.
