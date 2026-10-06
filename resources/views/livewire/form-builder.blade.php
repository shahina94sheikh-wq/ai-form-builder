<div class="container py-4">

    {{-- =========================================================
        FORM INFORMATION
    ========================================================== --}}

    <div class="card mb-4 shadow-sm">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <h2 class="mb-1">
                        {{ $form->title }}
                    </h2>

                    <div class="text-muted">
                        Form Builder
                    </div>
                </div>

                <span class="badge bg-warning text-dark">
                    {{ ucfirst($form->status) }}
                </span>

            </div>

            <hr>

            <div>
                <strong>Public URL:</strong>

                <a
                    href="{{ route('forms.public', ['form' => $form->slug]) }}"
                    target="_blank"
                >
                    {{ route('forms.public', ['form' => $form->slug]) }}
                </a>
            </div>
              @if($form->status === 'published')
            <div class="d-flex flex-wrap gap-2 align-items-center">

                    <a
                        href="{{ route('forms.index') }}"
                        class="btn btn-outline-secondary"
                    >
                        ← Form List
                    </a>

                    <a
                        href="{{ route('forms.public', $form) }}"
                        target="_blank"
                        class="btn btn-outline-primary"
                    >
                        Preview Form
                    </a>

                    <a
                        href="{{ route('forms.submissions', $form) }}"
                        class="btn btn-outline-success"
                    >
                        Submissions
                    </a>

            </div>
            @endif



        </div>

    </div>


    {{-- =========================================================
        MAIN BUILDER
    ========================================================== --}}

    <div class="row g-3">


        {{-- =====================================================
            LEFT SIDE - FORM CANVAS
        ====================================================== --}}

        <div class="col-lg-8">

            <div class="card shadow-sm">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <strong>
                        Form Canvas
                    </strong>

                    <span class="text-muted small">
                        Drag fields to reorder
                    </span>

                </div>

                <div class="card-body bg-light">

                    @forelse($schema['sections'] ?? [] as $sectionIndex => $section)

                        <div
                            class="mb-4"
                            wire:key="section-{{ $section['id'] ?? $sectionIndex }}"
                        >

                            {{-- SECTION HEADER --}}

                            <div class="d-flex justify-content-between align-items-center mb-2">

                                <h5 class="mb-0">

                                    {{ $section['title'] ?? 'Untitled Section' }}

                                </h5>

                                <span class="badge bg-secondary">
                                    {{ count($section['fields'] ?? []) }}
                                    fields
                                </span>

                            </div>


                            {{-- SORTABLE FIELD AREA --}}

                            <div
                                class="sortable-fields border rounded p-2 bg-white"
                                data-section="{{ $section['id'] ?? $sectionIndex }}"
                                wire:key="sortable-{{ $section['id'] ?? $sectionIndex }}"
                            >

                                @forelse($section['fields'] ?? [] as $fieldIndex => $field)

                                    <div
                                        class="card mb-2 field-card
                                        {{ $selectedField === ($field['id'] ?? '') ? 'border-primary shadow-sm' : '' }}"
                                        data-id="{{ $field['id'] }}"
                                        wire:key="field-{{ $field['id'] }}"
                                        style="cursor: pointer;"
                                        wire:click="selectField('{{ $field['id'] }}')"
                                    >

                                        <div class="card-body">


                                            {{-- FIELD HEADER --}}

                                            <div class="d-flex justify-content-between align-items-center">

                                                <div>

                                                    <strong>
                                                        {{ $field['label'] ?? 'Untitled Field' }}
                                                    </strong>

                                                    <small class="text-muted ms-2">
                                                        {{ $field['type'] ?? 'text' }}
                                                    </small>

                                                </div>


                                                {{-- ACTIONS --}}

                                                <div class="btn-group">

                                                    {{-- DUPLICATE --}}

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        wire:click.stop="duplicateField('{{ $field['id'] }}')"
                                                        title="Duplicate"
                                                    >
                                                        ⧉
                                                    </button>


                                                    {{-- EDIT --}}

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-primary"
                                                        wire:click.stop="selectField('{{ $field['id'] }}')"
                                                    >
                                                        Edit
                                                    </button>


                                                    {{-- DELETE --}}

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-danger"
                                                        wire:click.stop="deleteField('{{ $field['id'] }}')"
                                                        title="Delete"
                                                    >
                                                        ×
                                                    </button>

                                                </div>

                                            </div>


                                            {{-- FIELD PREVIEW --}}

                                            <div class="mt-2">

                                                @switch($field['type'] ?? 'text')


                                                    {{-- TEXT --}}

                                                    @case('text')

                                                        <input
                                                            type="text"
                                                            class="form-control"
                                                            placeholder="{{ $field['placeholder'] ?? '' }}"
                                                            disabled
                                                        >

                                                        @break


                                                    {{-- TEXTAREA --}}

                                                    @case('textarea')

                                                        <textarea
                                                            class="form-control"
                                                            placeholder="{{ $field['placeholder'] ?? '' }}"
                                                            rows="3"
                                                            disabled
                                                        ></textarea>

                                                        @break


                                                    {{-- NUMBER --}}

                                                    @case('number')

                                                        <input
                                                            type="number"
                                                            class="form-control"
                                                            placeholder="{{ $field['placeholder'] ?? '' }}"
                                                            disabled
                                                        >

                                                        @break


                                                    {{-- EMAIL --}}

                                                    @case('email')

                                                        <input
                                                            type="email"
                                                            class="form-control"
                                                            placeholder="{{ $field['placeholder'] ?? 'Enter your email' }}"
                                                            disabled
                                                        >

                                                        @break


                                                    {{-- PHONE --}}

                                                    @case('phone')

                                                        <input
                                                            type="tel"
                                                            class="form-control"
                                                            placeholder="{{ $field['placeholder'] ?? 'Enter your phone number' }}"
                                                            disabled
                                                        >

                                                        @break


                                                    {{-- DATE --}}

                                                    @case('date')

                                                        <input
                                                            type="date"
                                                            class="form-control"
                                                            disabled
                                                        >

                                                        @break


                                                    {{-- SELECT --}}

                                                    @case('select')

                                                        <select
                                                            class="form-select"
                                                            disabled
                                                        >

                                                            <option>
                                                                Select an option
                                                            </option>

                                                            @foreach($field['options'] ?? [] as $option)

                                                                <option
                                                                    value="{{ $option['value'] ?? '' }}"
                                                                >
                                                                    {{ $option['label'] ?? '' }}
                                                                </option>

                                                            @endforeach

                                                        </select>

                                                        @break


                                                    {{-- RADIO --}}

                                                    @case('radio')

                                                        <div>

                                                            @foreach($field['options'] ?? [] as $option)

                                                                <div class="form-check">

                                                                    <input
                                                                        class="form-check-input"
                                                                        type="radio"
                                                                        disabled
                                                                    >

                                                                    <label class="form-check-label">

                                                                        {{ $option['label'] ?? '' }}

                                                                    </label>

                                                                </div>

                                                            @endforeach

                                                        </div>

                                                        @break


                                                    {{-- CHECKBOX --}}

                                                    @case('checkbox')

                                                        <div>

                                                            @foreach($field['options'] ?? [] as $option)

                                                                <div class="form-check">

                                                                    <input
                                                                        class="form-check-input"
                                                                        type="checkbox"
                                                                        disabled
                                                                    >

                                                                    <label class="form-check-label">

                                                                        {{ $option['label'] ?? '' }}

                                                                    </label>

                                                                </div>

                                                            @endforeach

                                                        </div>

                                                        @break


                                                    {{-- FILE --}}

                                                    @case('file')

                                                        <input
                                                            type="file"
                                                            class="form-control"
                                                            disabled
                                                        >

                                                        @break


                                                    {{-- RATING --}}

                                                    @case('rating')

                                                        <div class="fs-4">
                                                            ★ ★ ★ ★ ★
                                                        </div>

                                                        @break


                                                    {{-- HEADING --}}

                                                    @case('heading')

                                                        <h4>
                                                            {{ $field['label'] ?? 'Heading' }}
                                                        </h4>

                                                        @break


                                                    {{-- FALLBACK --}}

                                                    @default

                                                        <input
                                                            type="text"
                                                            class="form-control"
                                                            disabled
                                                        >

                                                @endswitch

                                            </div>


                                            {{-- HELP TEXT --}}

                                            @if(!empty($field['help']))

                                                <small class="text-muted d-block mt-2">

                                                    {{ $field['help'] }}

                                                </small>

                                            @endif


                                            {{-- REQUIRED --}}

                                            @if($field['required'] ?? false)

                                                <span class="badge bg-danger mt-2">

                                                    Required

                                                </span>

                                            @endif

                                        </div>

                                    </div>

                                @empty

                                    <div class="text-center text-muted py-5">

                                        <div class="fs-3 mb-2">
                                            +
                                        </div>

                                        <div>
                                            No fields yet.
                                        </div>

                                        <small>
                                            Select a field from the right side.
                                        </small>

                                    </div>

                                @endforelse

                            </div>

                        </div>

                    @empty

                        <div class="alert alert-warning">

                            No sections found in this form.

                        </div>

                    @endforelse

                </div>

            </div>

        </div>


        {{-- =====================================================
            RIGHT SIDE
        ====================================================== --}}

        <div class="col-lg-4">


            {{-- =================================================
                ADD FIELDS
            ================================================== --}}

            <div class="card shadow-sm mb-3">

                <div class="card-header">

                    <strong>
                        Add Fields
                    </strong>

                </div>

                <div class="card-body">

                    <div class="row g-2">

                        @foreach($fieldTypes as $type => $label)

                            <div class="col-6">

                                <button
                                    type="button"
                                    class="btn btn-outline-primary w-100"
                                    wire:click="addField('{{ $type }}')"
                                >

                                    {{ $label }}

                                </button>

                            </div>

                        @endforeach

                    </div>

                </div>

            </div>


            {{-- =================================================
                CONDITIONAL LOGIC
            ================================================== --}}

            <div class="card shadow-sm mb-3">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>
                        Conditional Logic
                    </strong>

                    <span class="badge bg-light text-dark">
                        {{ count($schema['logic'] ?? []) }}
                    </span>
                </div>

                <div class="card-body">

                    <p class="text-muted small mb-3">
                        Show or hide a field when another field has a specific value.
                        Logic is stored in the form JSON schema.
                    </p>

                    @php
                        $logicFields = [];

                        foreach ($schema['sections'] ?? [] as $logicSection) {
                            foreach ($logicSection['fields'] ?? [] as $logicField) {
                                if (
                                    !empty($logicField['id']) &&
                                    ($logicField['type'] ?? '') !== 'heading'
                                ) {
                                    $logicFields[] = $logicField;
                                }
                            }
                        }

                        $selectedLogicSource = null;

                        foreach ($logicFields as $logicField) {
                            if (
                                ($logicField['id'] ?? null) ===
                                ($this->logicSourceField ?? null)
                            ) {
                                $selectedLogicSource = $logicField;
                                break;
                            }
                        }
                    @endphp

                    @if(count($logicFields) >= 2)

                        <div class="mb-3">

                            <label class="form-label">
                                When
                            </label>

                            <select
                                class="form-select"
                                wire:model.live="logicSourceField"
                            >
                                <option value="">
                                    Select source field
                                </option>

                                @foreach($logicFields as $logicField)
                                    <option value="{{ $logicField['id'] }}">
                                        {{ $logicField['label'] ?? 'Untitled Field' }}
                                    </option>
                                @endforeach
                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Condition
                            </label>

                            <select
                                class="form-select"
                                wire:model.live="logicOperator"
                            >
                                <option value="equals">
                                    Equals
                                </option>

                                <option value="not_equals">
                                    Does not equal
                                </option>

                                <option value="contains">
                                    Contains
                                </option>

                                <option value="not_contains">
                                    Does not contain
                                </option>

                                <option value="greater_than">
                                    Greater than
                                </option>

                                <option value="less_than">
                                    Less than
                                </option>

                                <option value="greater_or_equal">
                                    Greater than or equal
                                </option>

                                <option value="less_or_equal">
                                    Less than or equal
                                </option>
                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Value
                            </label>

                            @if(
                                $selectedLogicSource &&
                                in_array(
                                    $selectedLogicSource['type'] ?? '',
                                    ['select', 'radio', 'checkbox'],
                                    true
                                ) &&
                                !empty($selectedLogicSource['options'])
                            )

                                <select
                                    class="form-select"
                                    wire:model.live="logicValue"
                                >
                                    <option value="">
                                        Select value
                                    </option>

                                    @foreach($selectedLogicSource['options'] as $logicOption)
                                        <option
                                            value="{{ $logicOption['value'] ?? '' }}"
                                        >
                                            {{ $logicOption['label'] ?? ($logicOption['value'] ?? '') }}
                                        </option>
                                    @endforeach
                                </select>

                            @else

                                <input
                                    type="text"
                                    class="form-control"
                                    wire:model.live="logicValue"
                                    placeholder="Value to compare"
                                >

                            @endif

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Then
                            </label>

                            <select
                                class="form-select"
                                wire:model.live="logicAction"
                            >
                                <option value="show">
                                    Show
                                </option>

                                <option value="hide">
                                    Hide
                                </option>
                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Target Field
                            </label>

                            <select
                                class="form-select"
                                wire:model.live="logicTargetField"
                            >
                                <option value="">
                                    Select target field
                                </option>

                                @foreach($logicFields as $logicField)
                                    <option value="{{ $logicField['id'] }}">
                                        {{ $logicField['label'] ?? 'Untitled Field' }}
                                    </option>
                                @endforeach
                            </select>

                        </div>


                        @error('logic')
                            <div class="alert alert-danger py-2 small">
                                {{ $message }}
                            </div>
                        @enderror


                        <button
                            type="button"
                            class="btn btn-primary w-100"
                            wire:click="addLogicRule"
                        >
                            + Add Condition
                        </button>

                    @else

                        <div class="alert alert-info small mb-0">
                            Add at least two fields before creating a
                            conditional logic rule.
                        </div>

                    @endif


                    @if(!empty($schema['logic']))

                        <hr class="my-4">

                        <h6 class="mb-3">
                            Existing Conditions
                        </h6>

                        @foreach($schema['logic'] as $logicIndex => $rule)

                            @php
                                $sourceLabel = $rule['when']['field'] ?? 'Unknown';
                                $targetLabel = $rule['target'] ?? 'Unknown';

                                foreach ($logicFields as $logicField) {
                                    if (($logicField['id'] ?? null) === ($rule['when']['field'] ?? null)) {
                                        $sourceLabel = $logicField['label'] ?? $sourceLabel;
                                    }

                                    if (($logicField['id'] ?? null) === ($rule['target'] ?? null)) {
                                        $targetLabel = $logicField['label'] ?? $targetLabel;
                                    }
                                }
                            @endphp

                            <div
                                class="border rounded p-3 mb-2 bg-light"
                                wire:key="logic-rule-{{ $logicIndex }}"
                            >

                                <div class="small">

                                    <strong>
                                        When
                                    </strong>

                                    {{ $sourceLabel }}

                                    <span class="badge bg-secondary">
                                        {{ str_replace('_', ' ', $rule['when']['operator'] ?? '') }}
                                    </span>

                                    <strong>
                                        {{ $rule['when']['value'] ?? '' }}
                                    </strong>

                                    <strong>
                                        then
                                    </strong>

                                    <span class="badge bg-primary">
                                        {{ ucfirst($rule['action'] ?? 'show') }}
                                    </span>

                                    <strong>
                                        {{ $targetLabel }}
                                    </strong>

                                </div>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger mt-2"
                                    wire:click="removeLogicRule({{ $logicIndex }})"
                                >
                                    Remove
                                </button>

                            </div>

                        @endforeach

                    @endif

                </div>

            </div>


            {{-- =================================================
                FIELD SETTINGS
            ================================================== --}}

            @if($selectedField)

                <div class="card shadow-sm border-primary">

                    <div class="card-header bg-primary text-white">

                        <div class="d-flex justify-content-between align-items-center">

                            <strong>
                                Field Settings
                            </strong>

                            <button
                                type="button"
                                class="btn btn-sm btn-light"
                                wire:click="$set('selectedField', null)"
                            >
                                ×
                            </button>

                        </div>

                    </div>


                    <div class="card-body">


                        {{-- FIELD TYPE --}}

                        <div class="mb-3">

                            <label class="form-label">
                                Field Type
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="{{ $selectedFieldData['type'] ?? '' }}"
                                disabled
                            >

                        </div>


                        {{-- LABEL --}}

                        <div class="mb-3">

                            <label class="form-label">
                                Label
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                wire:model.live="selectedFieldData.label"
                                placeholder="Field label"
                            >

                        </div>


                        {{-- FIELD KEY --}}

                        <div class="mb-3">

                            <label class="form-label">
                                Field Key
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                wire:model.live="selectedFieldData.key"
                                placeholder="field_key"
                            >

                            <small class="text-muted">
                                Used for storing submitted data.
                            </small>

                        </div>


                        {{-- PLACEHOLDER --}}

                        @if(
                            isset($selectedFieldData['type']) &&
                            !in_array(
                                $selectedFieldData['type'],
                                ['heading', 'rating']
                            )
                        )

                            <div class="mb-3">

                                <label class="form-label">
                                    Placeholder
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    wire:model.live="selectedFieldData.placeholder"
                                    placeholder="Enter placeholder..."
                                >

                            </div>

                        @endif


                        {{-- DEFAULT --}}

                        @if(
                            isset($selectedFieldData['type']) &&
                            !in_array(
                                $selectedFieldData['type'],
                                ['heading']
                            )
                        )

                            <div class="mb-3">

                                <label class="form-label">
                                    Default Value
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    wire:model.live="selectedFieldData.default"
                                    placeholder="Default value"
                                >

                            </div>

                        @endif


                        {{-- HELP TEXT --}}

                        <div class="mb-3">

                            <label class="form-label">
                                Help Text
                            </label>

                            <textarea
                                class="form-control"
                                wire:model.live="selectedFieldData.help"
                                rows="3"
                                placeholder="Additional instructions..."
                            ></textarea>

                        </div>


                        {{-- REQUIRED --}}

                        @if(
                            isset($selectedFieldData['type']) &&
                            $selectedFieldData['type'] !== 'heading'
                        )

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    wire:model.live="selectedFieldData.required"
                                    id="selected-field-required"
                                >

                                <label
                                    class="form-check-label"
                                    for="selected-field-required"
                                >
                                    Required
                                </label>

                            </div>

                        @endif


                        {{-- =================================================
                            OPTIONS
                        ================================================== --}}

                        @if(
                            isset($selectedFieldData['type']) &&
                            in_array(
                                $selectedFieldData['type'],
                                ['select', 'radio', 'checkbox']
                            )
                        )

                            <hr>

                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <h6 class="mb-0">
                                    Options
                                </h6>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary"
                                    wire:click="addOption"
                                >
                                    + Add
                                </button>

                            </div>


                            @foreach(
                                $selectedFieldData['options'] ?? []
                                as $index => $option
                            )

                                <div
                                    class="border rounded p-2 mb-2"
                                    wire:key="option-{{ $selectedField }}-{{ $index }}"
                                >

                                    <div class="row g-2">

                                        <div class="col-5">

                                            <label class="form-label small">
                                                Label
                                            </label>

                                            <input
                                                type="text"
                                                class="form-control form-control-sm"
                                                wire:model.live="selectedFieldData.options.{{ $index }}.label"
                                                placeholder="Label"
                                            >

                                        </div>


                                        <div class="col-5">

                                            <label class="form-label small">
                                                Value
                                            </label>

                                            <input
                                                type="text"
                                                class="form-control form-control-sm"
                                                wire:model.live="selectedFieldData.options.{{ $index }}.value"
                                                placeholder="value"
                                            >

                                        </div>


                                        <div class="col-2 d-flex align-items-end">

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger w-100"
                                                wire:click="removeOption({{ $index }})"
                                            >
                                                ×
                                            </button>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        @endif


                        {{-- =================================================
                            VALIDATION
                        ================================================== --}}

                        @if(
                            isset($selectedFieldData['type']) &&
                            in_array(
                                $selectedFieldData['type'],
                                [
                                    'text',
                                    'textarea',
                                    'number',
                                    'email',
                                    'phone',
                                    'date'
                                ]
                            )
                        )

                            <hr>

                            <h6 class="mb-3">
                                Validation
                            </h6>


                            {{-- MIN / MAX --}}

                            <div class="row g-2">

                                <div class="col-6">

                                    <label class="form-label">
                                        Min
                                    </label>

                                    <input
                                        type="number"
                                        class="form-control"
                                        wire:model.live="selectedFieldData.validation.min"
                                    >

                                </div>


                                <div class="col-6">

                                    <label class="form-label">
                                        Max
                                    </label>

                                    <input
                                        type="number"
                                        class="form-control"
                                        wire:model.live="selectedFieldData.validation.max"
                                    >

                                </div>

                            </div>


                            {{-- MIN LENGTH --}}

                            @if(
                                in_array(
                                    $selectedFieldData['type'],
                                    ['text', 'textarea', 'email', 'phone']
                                )
                            )

                                <div class="mb-3 mt-3">

                                    <label class="form-label">
                                        Minimum Length
                                    </label>

                                    <input
                                        type="number"
                                        class="form-control"
                                        wire:model.live="selectedFieldData.validation.min_length"
                                    >

                                </div>


                                {{-- MAX LENGTH --}}

                                <div class="mb-3">

                                    <label class="form-label">
                                        Maximum Length
                                    </label>

                                    <input
                                        type="number"
                                        class="form-control"
                                        wire:model.live="selectedFieldData.validation.max_length"
                                    >

                                </div>

                            @endif


                            {{-- EMAIL VALIDATION --}}

                            @if($selectedFieldData['type'] === 'email')

                                <div class="alert alert-info py-2 small">

                                    Email format validation will be applied
                                    automatically.

                                </div>

                            @endif


                            {{-- PHONE VALIDATION --}}

                            @if($selectedFieldData['type'] === 'phone')

                                <div class="mb-3">

                                    <label class="form-label">
                                        Phone Regex
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control font-monospace"
                                        wire:model.live="selectedFieldData.validation.regex"
                                        placeholder="/^[0-9+\-\s()]+$/"
                                    >

                                </div>

                            @endif


                            {{-- NUMBER VALIDATION --}}

                            @if($selectedFieldData['type'] === 'number')

                                <div class="alert alert-info py-2 small">

                                    Numeric validation will be applied
                                    automatically.

                                </div>

                            @endif


                            {{-- URL VALIDATION --}}

                            @if($selectedFieldData['type'] === 'text')

                                <div class="form-check mb-3">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        wire:model.live="selectedFieldData.validation.url"
                                        id="selected-field-url"
                                    >

                                    <label
                                        class="form-check-label"
                                        for="selected-field-url"
                                    >
                                        Validate as URL
                                    </label>

                                </div>

                            @endif


                            {{-- REGEX --}}

                            <div class="mb-3">

                                <label class="form-label">
                                    Regex
                                </label>

                                <input
                                    type="text"
                                    class="form-control font-monospace"
                                    wire:model.live="selectedFieldData.validation.regex"
                                    placeholder="/^[A-Za-z ]+$/"
                                >

                            </div>

                        @endif


                        {{-- =================================================
                            FILE VALIDATION
                        ================================================== --}}

                        @if(
                            isset($selectedFieldData['type']) &&
                            $selectedFieldData['type'] === 'file'
                        )

                            <hr>

                            <h6 class="mb-3">
                                File Validation
                            </h6>


                            <div class="mb-3">

                                <label class="form-label">
                                    Allowed File Types
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    wire:model.live="selectedFieldData.validation.file_types"
                                    placeholder="pdf,doc,docx,jpg,png"
                                >

                                <small class="text-muted">
                                    Example: pdf,doc,docx,jpg,png
                                </small>

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Maximum File Size (KB)
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    wire:model.live="selectedFieldData.validation.file_size"
                                    placeholder="10240"
                                >

                            </div>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>


    {{-- =========================================================
        JSON SCHEMA EDITOR
    ========================================================== --}}

    <div class="card mt-4 shadow-sm">

        <div class="card-header">

            <strong>
                JSON Schema
            </strong>

        </div>

        <div class="card-body">

            <p class="text-muted small">
                The JSON schema is the single source of truth for this form.
                Changes made here can be applied back to the builder.
            </p>

            <textarea
                class="form-control font-monospace"
                rows="22"
                wire:model.live="schemaJson"
                spellcheck="false"
            ></textarea>


            @error('schemaJson')

                <div class="alert alert-danger mt-2">
                    {{ $message }}
                </div>

            @enderror


            @if ($errors->has('publish'))
                <div class="alert alert-danger mt-3">
                    {{ $errors->first('publish') }}
                </div>
            @endif


            <div class="mt-3 d-flex gap-2">

                <button
                    type="button"
                    class="btn btn-secondary"
                    wire:click="updateFromJson"
                >
                    Apply JSON
                </button>


                <button
                    type="button"
                    class="btn btn-success"
                    wire:click="saveBuilder"
                >
                    Save Form
                </button>

                @if($form->status === 'draft')
                    <button
                        type="button"
                        class="btn btn-success"
                        wire:click="publish"
                        wire:loading.attr="disabled"
                        wire:target="publish"
                    >
                        <span wire:loading.remove wire:target="publish">
                            Publish Form
                        </span>

                        <span wire:loading wire:target="publish">
                            Publishing...
                        </span>
                    </button>
               @endif

            </div>


            @if(session()->has('success'))

                <div class="alert alert-success mt-3">

                    {{ session('success') }}

                </div>

            @endif

        </div>

    </div>


    {{-- =========================================================
        SORTABLE JS
    ========================================================== --}}

    @script

    <script>

        function initSortable() {

            document
                .querySelectorAll('.sortable-fields')
                .forEach(el => {

                    /*
                     * Prevent duplicate Sortable instances
                     */

                    if (el._sortable) {
                        return;
                    }


                    /*
                     * Get section ID
                     */

                    const sectionId =
                        el.dataset.section;


                    el._sortable = new Sortable(el, {

                        animation: 150,

                        handle: '.field-card',

                        ghostClass: 'bg-light',

                        onEnd() {

                            const ids = [
                                ...el.querySelectorAll('[data-id]')
                            ].map(
                                element =>
                                    element.dataset.id
                            );


                            /*
                             * IMPORTANT:
                             * Send section ID too.
                             */

                            $wire.reorder(
                                ids,
                                sectionId
                            );

                        }

                    });

                });

        }


        /*
         * Initial load
         */

        initSortable();


        /*
         * Reinitialize after Livewire updates
         */

        Livewire.hook(
            'morph.updated',
            () => {

                setTimeout(() => {

                    initSortable();

                }, 0);

            }
        );

    </script>

    @endscript

</div>