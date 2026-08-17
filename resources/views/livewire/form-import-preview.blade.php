<div class="container py-5">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="fw-bold mb-1">
                Import Preview
            </h1>

            <p class="text-muted mb-0">
                Review and correct the detected fields before creating your form.
            </p>
        </div>

        <a
            href="{{ route('forms.index') }}"
            class="btn btn-outline-secondary"
        >
            ← Back to Forms
        </a>

    </div>


    {{-- Success --}}
    @if (session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif


    {{-- Error --}}
    @error('form')

        <div class="alert alert-danger">
            {{ $message }}
        </div>

    @enderror


    {{-- Import information --}}
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body p-4">

            <div class="row">

                <div class="col-md-8">

                    <div class="text-muted small mb-1">
                        Imported file
                    </div>

                    <h5 class="fw-bold mb-0">
                        {{ $import->filename }}
                    </h5>

                </div>

                <div class="col-md-4 text-md-end">

                    <span class="badge bg-success">
                        Parsed successfully
                    </span>

                    <div class="small text-muted mt-2">
                        {{ count($sections) }} sections detected
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Parser errors --}}
    @if (!empty($parserErrors))

        <div class="alert alert-warning">

            <div class="fw-bold mb-2">
                Some parts of the document could not be parsed
            </div>

            @foreach ($parserErrors as $error)

                <div class="small mb-1">

                    {{ is_array($error)
                        ? ($error['message'] ?? json_encode($error))
                        : $error
                    }}

                </div>

            @endforeach

        </div>

    @endif


    {{-- Sections --}}
    @forelse ($sections as $sectionIndex => $section)

        <div class="card border-0 shadow-sm mb-4">

            {{-- Section header --}}
            <div class="card-header bg-white border-bottom p-4">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted small">
                            Section {{ $sectionIndex + 1 }}
                        </div>

                        <h4 class="fw-bold mb-0">
                            {{ $section['title'] ?? 'Untitled Section' }}
                        </h4>

                    </div>

                    <span class="badge bg-light text-dark border">
                        {{ count($section['fields'] ?? []) }} fields
                    </span>

                </div>

            </div>


            {{-- Fields --}}
            <div class="card-body p-0">

                @forelse ($section['fields'] ?? [] as $fieldIndex => $field)

                    <div class="border-bottom p-4">

                        <div class="row align-items-start g-3">

                            {{-- Field number --}}
                            <div class="col-md-1">

                                <div
                                    class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                    style="
                                        width:36px;
                                        height:36px;
                                        font-size:14px;
                                        font-weight:600;
                                    "
                                >
                                    {{ $fieldIndex + 1 }}
                                </div>

                            </div>


                            {{-- Field information --}}
                            <div class="col-md-5">

                                <div class="fw-semibold mb-1">
                                    {{ $field['label'] ?? 'Untitled Field' }}
                                </div>

                                <div class="text-muted small">

                                    Detected type:
                                    <span class="fw-semibold">
                                        {{ $field['type'] ?? 'text' }}
                                    </span>

                                </div>


                                {{-- Options --}}
                                @if (
                                    !empty($field['options']) &&
                                    in_array(
                                        $field['type'] ?? '',
                                        ['dropdown', 'radio', 'checkbox']
                                    )
                                )

                                    <div class="mt-2">

                                        <div class="text-muted small mb-1">
                                            Options
                                        </div>

                                        <div>

                                            @foreach ($field['options'] as $option)

                                                @php
                                                    if (is_array($option)) {
                                                        $optionLabel =
                                                            $option['label']
                                                            ?? $option['value']
                                                            ?? '';
                                                    } else {
                                                        $optionLabel = (string) $option;
                                                    }
                                                @endphp

                                                @if ($optionLabel !== '')
                                                    <span
                                                        class="badge bg-light text-dark border me-1 mb-1"
                                                    >
                                                        {{ $optionLabel }}
                                                    </span>
                                                @endif

                                            @endforeach

                                        </div>

                                    </div>

                                @endif

                            </div>


                            {{-- Field type --}}
                            <div class="col-md-3">

                                <label
                                    class="form-label small fw-semibold"
                                >
                                    Field Type
                                </label>

                                <select
                                    class="form-select"
                                    wire:change="updateFieldType(
                                        {{ $sectionIndex }},
                                        {{ $fieldIndex }},
                                        $event.target.value
                                    )"
                                >

                                    <option value="text"
                                        @selected(($field['type'] ?? '') === 'text')>
                                        Text
                                    </option>

                                    <option value="textarea"
                                        @selected(($field['type'] ?? '') === 'textarea')>
                                        Textarea
                                    </option>

                                    <option value="number"
                                        @selected(($field['type'] ?? '') === 'number')>
                                        Number
                                    </option>

                                    <option value="email"
                                        @selected(($field['type'] ?? '') === 'email')>
                                        Email
                                    </option>

                                    <option value="phone"
                                        @selected(($field['type'] ?? '') === 'phone')>
                                        Phone
                                    </option>

                                    <option value="date"
                                        @selected(($field['type'] ?? '') === 'date')>
                                        Date
                                    </option>

                                    <option value="dropdown"
                                        @selected(($field['type'] ?? '') === 'dropdown')>
                                        Dropdown
                                    </option>

                                    <option value="radio"
                                        @selected(($field['type'] ?? '') === 'radio')>
                                        Radio
                                    </option>

                                    <option value="checkbox"
                                        @selected(($field['type'] ?? '') === 'checkbox')>
                                        Checkbox
                                    </option>

                                    <option value="file"
                                        @selected(($field['type'] ?? '') === 'file')>
                                        File Upload
                                    </option>

                                    <option value="rating"
                                        @selected(($field['type'] ?? '') === 'rating')>
                                        Rating
                                    </option>

                                </select>

                            </div>


                            {{-- Required --}}
                            <div class="col-md-2">

                                <label
                                    class="form-label small fw-semibold"
                                >
                                    Required
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="required-{{ $sectionIndex }}-{{ $fieldIndex }}"
                                        wire:change="updateRequired(
                                            {{ $sectionIndex }},
                                            {{ $fieldIndex }},
                                            $event.target.checked
                                        )"
                                        @checked($field['required'] ?? false)
                                    >

                                    <label
                                        class="form-check-label"
                                        for="required-{{ $sectionIndex }}-{{ $fieldIndex }}"
                                    >
                                        Required
                                    </label>

                                </div>

                            </div>

                        </div>


                        {{-- Choice options editor --}}
                        @if (
                            in_array(
                                $field['type'] ?? '',
                                ['dropdown', 'radio', 'checkbox']
                            )
                        )

                            <div class="row mt-3">

                                <div class="col-md-11 offset-md-1">

                                    <label
                                        class="form-label small fw-semibold"
                                    >
                                        Options
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        value="{{ collect($field['options'] ?? [])->map(function ($option) {
                                            return is_array($option)
                                                ? ($option['label'] ?? $option['value'] ?? '')
                                                : (string) $option;
                                        })->implode(' | ') }}"
                                        placeholder="Example: Option 1 | Option 2 | Option 3"
                                        wire:change="updateOptions(
                                            {{ $sectionIndex }},
                                            {{ $fieldIndex }},
                                            $event.target.value
                                        )"
                                    >

                                    <div class="form-text">
                                        Separate options using
                                        <strong>|</strong>
                                    </div>

                                </div>

                            </div>

                        @endif


                        {{-- Remove field --}}
                        <div class="row mt-3">

                            <div class="col-md-11 offset-md-1">

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    wire:click="removeField(
                                        {{ $sectionIndex }},
                                        {{ $fieldIndex }}
                                    )"
                                    wire:confirm="Remove this field?"
                                >
                                    Remove field
                                </button>

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="p-4 text-muted">
                        No fields detected in this section.
                    </div>

                @endforelse

            </div>

        </div>

    @empty

        <div class="card border-0 shadow-sm">

            <div class="card-body p-5 text-center">

                <div style="font-size:48px;">
                    ⚠️
                </div>

                <h4 class="fw-bold mt-3">
                    No fields detected
                </h4>

                <p class="text-muted">
                    We couldn't detect any editable form fields
                    in this document.
                </p>

            </div>

        </div>

    @endforelse


    {{-- Bottom actions --}}
    @if (!empty($sections))

        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="fw-bold">
                            Ready to create your form?
                        </div>

                        <div class="text-muted small">
                            Review the detected field types above before importing.
                        </div>

                    </div>

                    <button
                        type="button"
                        wire:click="createForm"
                        wire:loading.attr="disabled"
                        wire:target="createForm"
                        class="btn btn-primary btn-lg px-5"
                    >

                        <span
                            wire:loading.remove
                            wire:target="createForm"
                        >
                            Create Form
                        </span>

                        <span
                            wire:loading
                            wire:target="createForm"
                        >
                            Creating...
                        </span>

                    </button>

                </div>

            </div>

        </div>

    @endif

</div>