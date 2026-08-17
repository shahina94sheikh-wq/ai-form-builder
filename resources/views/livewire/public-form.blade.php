<div class="container py-5">

    {{-- ==============================
        FORM HEADER
    =============================== --}}

    <div class="text-center mb-5">

        <h1 class="fw-bold">
            {{ $form->title }}
        </h1>

        @if(!empty($form->schema['description']))

            <p class="text-muted">
                {{ $form->schema['description'] }}
            </p>

        @endif

    </div>


    @php

        $sections =
            $form->schema['sections']
            ?? [];

        $totalSteps =
            count($sections);

        $currentSection =
            $sections[$currentStep]
            ?? null;

        $progress =
            $totalSteps > 0
                ? (($currentStep + 1) / $totalSteps) * 100
                : 0;

    @endphp


    @if($currentSection)

        {{-- ==============================
            STEP INDICATOR
        =============================== --}}

        <div class="mb-5">

            <div class="d-flex justify-content-between">

                @foreach($sections as $index => $section)

                    <div
                        class="text-center"
                        style="flex: 1;"
                    >

                        <div
                            class="
                                rounded-circle
                                mx-auto
                                mb-2
                                d-flex
                                align-items-center
                                justify-content-center
                                {{ $index <= $currentStep
                                    ? 'bg-primary text-white'
                                    : 'bg-light text-muted'
                                }}
                            "
                            style="
                                width: 40px;
                                height: 40px;
                            "
                        >

                            {{ $index + 1 }}

                        </div>

                        <small
                            class="
                                {{ $index === $currentStep
                                    ? 'fw-bold text-primary'
                                    : 'text-muted'
                                }}
                            "
                        >
                            {{ $section['title'] ?? 'Section ' . ($index + 1) }}
                        </small>

                    </div>

                @endforeach

            </div>


            {{-- Progress bar --}}

            <div class="progress mt-3">

                <div
                    class="progress-bar"
                    role="progressbar"
                    style="width: {{ $progress }}%"
                ></div>

            </div>

        </div>


        {{-- ==============================
            SUCCESS MESSAGE
        =============================== --}}

        @if(session()->has('success'))

            <div class="alert alert-success">

                {{ session('success') }}

            </div>

        @endif


        {{-- ==============================
            VALIDATION ERRORS
        =============================== --}}

        @if($errors->any())

            <div class="alert alert-danger">

                <strong>
                    Please fix the following errors:
                </strong>

                <ul class="mb-0">

                    @foreach($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- ==============================
            CURRENT STEP
        =============================== --}}

        <div
            class="card shadow-sm"
            wire:key="step-{{ $currentSection['id'] ?? $currentStep }}"
        >

            <div class="card-body p-4 p-md-5">

                <div class="mb-4">

                    <h3 class="fw-bold">
                        {{ $currentSection['title'] ?? 'Form Section' }}
                    </h3>

                    @if(!empty($currentSection['description']))

                        <p class="text-muted">
                            {{ $currentSection['description'] }}
                        </p>

                    @endif

                </div>


                {{-- ==============================
                    FIELDS
                =============================== --}}

                @foreach($currentSection['fields'] ?? [] as $field)

                    @php

                        /*
                        |--------------------------------------------------------------------------
                        | Field key
                        |--------------------------------------------------------------------------
                        |
                        | Manual forms:
                        |     key
                        |
                        | Imported forms:
                        |     id
                        |
                        */

                        $fieldKey =
                            $field['key']
                            ?? $field['id']
                            ?? null;


                        /*
                        |--------------------------------------------------------------------------
                        | Normalize imported field type
                        |--------------------------------------------------------------------------
                        */

                        $fieldType =
                            strtolower(
                                trim(
                                    $field['type']
                                    ?? 'text'
                                )
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Map imported field types to supported UI types
                        |--------------------------------------------------------------------------
                        */

                        $fieldType = match ($fieldType) {

                            'dropdown',
                            'drop-down',
                            'choice',
                            'choices',
                            'select'
                                => 'select',

                            'radio button',
                            'single choice'
                                => 'radio',

                            'checkboxes',
                            'multiple choice'
                                => 'checkbox',

                            'textbox',
                            'text box',
                            'text input',
                            'input'
                                => 'text',

                            'text area',
                            'long text'
                                => 'textarea',

                            'e-mail'
                                => 'email',

                            'numeric',
                            'integer'
                                => 'number',

                            'telephone',
                            'mobile'
                                => 'phone',

                            'upload',
                            'file upload'
                                => 'file',

                            default
                                => $fieldType,
                        };


                    @endphp


                    @if($fieldKey && $this->isFieldVisible($field))

                        <div
                            class="mb-4"
                            wire:key="field-{{ $currentSection['id'] ?? $currentStep }}-{{ $fieldKey }}"
                        >

                            {{-- Label --}}

                            <label class="form-label fw-semibold">

                                {{ $field['label'] ?? 'Field' }}

                                @if($field['required'] ?? false)

                                    <span class="text-danger">
                                        *
                                    </span>

                                @endif

                            </label>


                            {{-- Help text --}}

                            @if(!empty($field['help']))

                                <div class="form-text mb-2">
                                    {{ $field['help'] }}
                                </div>

                            @endif


                            {{-- ==========================
                                TEXT
                            =========================== --}}

                            @if($fieldType === 'text')

                                <input
                                    type="text"
                                    class="form-control @error('data.' . $fieldKey) is-invalid @enderror"
                                    wire:model.live="data.{{ $fieldKey }}"
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                >


                            {{-- ==========================
                                TEXTAREA
                            =========================== --}}

                            @elseif($fieldType === 'textarea')

                                <textarea
                                    class="form-control @error('data.' . $fieldKey) is-invalid @enderror"
                                    wire:model.live="data.{{ $fieldKey }}"
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                    rows="4"
                                ></textarea>


                            {{-- ==========================
                                EMAIL
                            =========================== --}}

                            @elseif($fieldType === 'email')

                                <input
                                    type="email"
                                    class="form-control @error('data.' . $fieldKey) is-invalid @enderror"
                                    wire:model.live="data.{{ $fieldKey }}"
                                    placeholder="{{ $field['placeholder'] ?? 'Enter your email' }}"
                                >


                            {{-- ==========================
                                PHONE
                            =========================== --}}

                            @elseif($fieldType === 'phone')

                                <input
                                    type="tel"
                                    class="form-control @error('data.' . $fieldKey) is-invalid @enderror"
                                    wire:model.live="data.{{ $fieldKey }}"
                                    placeholder="{{ $field['placeholder'] ?? 'Enter your phone number' }}"
                                >


                            {{-- ==========================
                                NUMBER
                            =========================== --}}

                            @elseif($fieldType === 'number')

                                <input
                                    type="number"
                                    class="form-control @error('data.' . $fieldKey) is-invalid @enderror"
                                    wire:model.live="data.{{ $fieldKey }}"
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                >


                            {{-- ==========================
                                DATE
                            =========================== --}}

                            @elseif($fieldType === 'date')

                                <input
                                    type="date"
                                    class="form-control @error('data.' . $fieldKey) is-invalid @enderror"
                                    wire:model.live="data.{{ $fieldKey }}"
                                >


                            {{-- ==========================
                                SELECT / DROPDOWN / CHOICE
                            =========================== --}}

                            @elseif($fieldType === 'select')

                                <select
                                    class="form-select @error('data.' . $fieldKey) is-invalid @enderror"
                                    wire:model.live="data.{{ $fieldKey }}"
                                >

                                    <option value="">
                                        Select an option
                                    </option>

                                    @foreach($field['options'] ?? [] as $option)

                                        @php

                                            if (is_array($option)) {

                                                $optionValue =
                                                    $option['value']
                                                    ?? $option['label']
                                                    ?? '';

                                                $optionLabel =
                                                    $option['label']
                                                    ?? $option['value']
                                                    ?? '';

                                            } else {

                                                $optionValue =
                                                    $option;

                                                $optionLabel =
                                                    $option;
                                            }

                                        @endphp

                                        @if($optionValue !== '')

                                            <option
                                                value="{{ $optionValue }}"
                                            >
                                                {{ $optionLabel }}
                                            </option>

                                        @endif

                                    @endforeach

                                </select>


                            {{-- ==========================
                                RADIO
                            =========================== --}}

                            @elseif($fieldType === 'radio')

                                @foreach($field['options'] ?? [] as $option)

                                    @php

                                        if (is_array($option)) {

                                            $optionValue =
                                                $option['value']
                                                ?? $option['label']
                                                ?? '';

                                            $optionLabel =
                                                $option['label']
                                                ?? $option['value']
                                                ?? '';

                                        } else {

                                            $optionValue =
                                                $option;

                                            $optionLabel =
                                                $option;
                                        }

                                    @endphp

                                    @if($optionValue !== '')

                                        <div
                                            class="form-check"
                                            wire:key="radio-{{ $fieldKey }}-{{ $loop->index }}"
                                        >

                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                wire:model.live="data.{{ $fieldKey }}"
                                                value="{{ $optionValue }}"
                                                id="{{ $fieldKey }}_{{ $loop->index }}"
                                            >

                                            <label
                                                class="form-check-label"
                                                for="{{ $fieldKey }}_{{ $loop->index }}"
                                            >
                                                {{ $optionLabel }}
                                            </label>

                                        </div>

                                    @endif

                                @endforeach


                            {{-- ==========================
                                CHECKBOX
                            =========================== --}}

                            @elseif($fieldType === 'checkbox')

                                @foreach($field['options'] ?? [] as $option)

                                    @php

                                        if (is_array($option)) {

                                            $optionValue =
                                                $option['value']
                                                ?? $option['label']
                                                ?? '';

                                            $optionLabel =
                                                $option['label']
                                                ?? $option['value']
                                                ?? '';

                                        } else {

                                            $optionValue =
                                                $option;

                                            $optionLabel =
                                                $option;
                                        }

                                    @endphp

                                    @if($optionValue !== '')

                                        <div
                                            class="form-check"
                                            wire:key="checkbox-{{ $fieldKey }}-{{ $loop->index }}"
                                        >

                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                wire:model.live="data.{{ $fieldKey }}"
                                                value="{{ $optionValue }}"
                                                id="{{ $fieldKey }}_{{ $loop->index }}"
                                            >

                                            <label
                                                class="form-check-label"
                                                for="{{ $fieldKey }}_{{ $loop->index }}"
                                            >
                                                {{ $optionLabel }}
                                            </label>

                                        </div>

                                    @endif

                                @endforeach


                            {{-- ==========================
                                FILE
                            =========================== --}}

                            @elseif($fieldType === 'file')

                                <input
                                    type="file"
                                    class="form-control @error('data.' . $fieldKey) is-invalid @enderror"
                                    wire:model="data.{{ $fieldKey }}"
                                >

                                <div class="form-text">
                                    Maximum file size: 10 MB
                                </div>


                            {{-- ==========================
                                RATING
                            =========================== --}}

                            @elseif($fieldType === 'rating')

                                <select
                                    class="form-select @error('data.' . $fieldKey) is-invalid @enderror"
                                    wire:model.live="data.{{ $fieldKey }}"
                                >

                                    <option value="">
                                        Select rating
                                    </option>

                                    @for($rating = 1; $rating <= 5; $rating++)

                                        <option value="{{ $rating }}">
                                            {{ $rating }}
                                        </option>

                                    @endfor

                                </select>

                            @endif


                            {{-- Error --}}

                            @error('data.' . $fieldKey)

                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    @endif

                @endforeach


                {{-- ==============================
                    NAVIGATION
                =============================== --}}

                <div class="d-flex justify-content-between mt-5">

                    {{-- Previous --}}

                    @if($currentStep > 0)

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            wire:click="previousStep"
                        >
                            ← Previous
                        </button>

                    @else

                        <div></div>

                    @endif


                    {{-- Next / Submit --}}

                    @if($currentStep < $totalSteps - 1)

                        <button
                            type="button"
                            class="btn btn-primary px-4"
                            wire:click="nextStep"
                            wire:loading.attr="disabled"
                        >

                            <span wire:loading.remove>
                                Next →
                            </span>

                            <span wire:loading>
                                Please wait...
                            </span>

                        </button>

                    @else

                        <button
                            type="button"
                            class="btn btn-success px-4"
                            wire:click="submit"
                            wire:loading.attr="disabled"
                        >

                            <span wire:loading.remove>
                                Submit
                            </span>

                            <span wire:loading>
                                Submitting...
                            </span>

                        </button>

                    @endif

                </div>

            </div>

        </div>

    @else

        <div class="alert alert-warning">
            This form does not contain any sections or fields.
        </div>

    @endif

</div>