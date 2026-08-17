<div class="container py-4">

    {{-- =========================================================
        PAGE TITLE
    ========================================================== --}}

    <h1 class="mb-4">
        Form Builder
    </h1>


    {{-- =========================================================
        WIZARD HEADER
    ========================================================== --}}

    <div class="card mb-4 shadow-sm">

        <div class="card-body">

            <div class="row text-center">

                {{-- STEP 1 --}}

                <div class="col">

                    <div
                        class="{{ $step >= 1 ? 'text-primary fw-bold' : 'text-muted' }}"
                    >

                        <span
                            class="badge rounded-circle
                            {{ $step >= 1 ? 'bg-primary' : 'bg-secondary' }}"
                        >
                            1
                        </span>

                        <div class="mt-1">
                            Details
                        </div>

                    </div>

                </div>


                {{-- STEP 2 --}}

                <div class="col">

                    <div
                        class="{{ $step >= 2 ? 'text-primary fw-bold' : 'text-muted' }}"
                    >

                        <span
                            class="badge rounded-circle
                            {{ $step >= 2 ? 'bg-primary' : 'bg-secondary' }}"
                        >
                            2
                        </span>

                        <div class="mt-1">
                            Builder
                        </div>

                    </div>

                </div>


                {{-- STEP 3 --}}

                <div class="col">

                    <div
                        class="{{ $step >= 3 ? 'text-primary fw-bold' : 'text-muted' }}"
                    >

                        <span
                            class="badge rounded-circle
                            {{ $step >= 3 ? 'bg-primary' : 'bg-secondary' }}"
                        >
                            3
                        </span>

                        <div class="mt-1">
                            Settings
                        </div>

                    </div>

                </div>


                {{-- STEP 4 --}}

                <div class="col">

                    <div
                        class="{{ $step >= 4 ? 'text-primary fw-bold' : 'text-muted' }}"
                    >

                        <span
                            class="badge rounded-circle
                            {{ $step >= 4 ? 'bg-primary' : 'bg-secondary' }}"
                        >
                            4
                        </span>

                        <div class="mt-1">
                            Finish
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
        STEP 1 - DETAILS
    ========================================================== --}}

    @if($step === 1)

        <div class="card shadow-sm">

            <div class="card-header">
                <strong>
                    Form Basics
                </strong>
            </div>

            <div class="card-body">

                {{-- FORM TITLE --}}

                <div class="mb-3">

                    <label class="form-label">
                        Form Title <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        wire:model.live="title"
                        placeholder="e.g. Internship Application"
                    >

                    @error('title')

                        <div class="text-danger mt-1">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- DESCRIPTION --}}

                <div class="mb-3">

                    <label class="form-label">
                        Description
                    </label>

                    <textarea
                        class="form-control"
                        wire:model.live="description"
                        rows="4"
                        placeholder="Describe what this form is used for..."
                    ></textarea>

                    @error('description')

                        <div class="text-danger mt-1">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- SLUG / PUBLIC URL --}}

                @if($slug)

                    <div class="alert alert-light border">

                        <strong>
                            Public URL:
                        </strong>

                        <div class="mt-1">

                            {{ url('/f/' . $slug) }}

                        </div>

                    </div>

                @endif

            </div>

        </div>

    @endif


    {{-- =========================================================
        STEP 2 - BUILDER
    ========================================================== --}}

    @if($step === 2)

        <div>

            @if($form)

                {{-- 
                    Existing FormBuilder component.
                    Do NOT put this inside an alert.
                --}}

                <livewire:form-builder
                    :form="$form"
                    :key="'form-builder-' . $form->id"
                />

            @else

                <div class="alert alert-danger">

                    Form record has not been created yet.

                </div>

            @endif

        </div>

    @endif


    {{-- =========================================================
        STEP 3 - SETTINGS
    ========================================================== --}}

    @if($step === 3)

        <div class="card shadow-sm">

            <div class="card-header">

                <strong>
                    Form Settings
                </strong>

            </div>

            <div class="card-body">

                {{-- SUCCESS MESSAGE --}}

                <div class="mb-4">

                    <label class="form-label">
                        Success Message
                    </label>

                    <textarea
                        class="form-control"
                        wire:model.live="settings.success_message"
                        rows="3"
                        placeholder="Thank you for your submission."
                    ></textarea>

                    @error('settings.success_message')

                        <div class="text-danger mt-1">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- SHOW PROGRESS --}}

                <div class="form-check mb-3">

                    <input
                        type="checkbox"
                        class="form-check-input"
                        wire:model.live="settings.show_progress"
                        id="showProgress"
                    >

                    <label
                        class="form-check-label"
                        for="showProgress"
                    >
                        Show progress bar
                    </label>

                </div>


                {{-- MULTIPLE SUBMISSIONS --}}

                <div class="form-check">

                    <input
                        type="checkbox"
                        class="form-check-input"
                        wire:model.live="settings.allow_multiple"
                        id="allowMultiple"
                    >

                    <label
                        class="form-check-label"
                        for="allowMultiple"
                    >
                        Allow multiple submissions
                    </label>

                </div>

            </div>

        </div>

    @endif


    {{-- =========================================================
        STEP 4 - FINISH
    ========================================================== --}}

    @if($step === 4)

        <div class="card shadow-sm">

            @if ($errors->has('publish'))
                <div class="alert alert-danger">
                    {{ $errors->first('publish') }}
                </div>
            @endif

            <div class="card-header">

                <strong>
                    Finish & Publish
                </strong>

            </div>

            <div class="card-body">

                <h3>
                    {{ $title }}
                </h3>


                @if($description)

                    <p class="text-muted">
                        {{ $description }}
                    </p>

                @endif


                <hr>


                {{-- PUBLIC URL --}}

                <div class="mb-4">

                    <strong>
                        Public Form URL
                    </strong>

                    <div class="mt-2">

                        <div class="input-group">

                            <input
                                type="text"
                                class="form-control"
                                value="{{ url('/f/' . $slug) }}"
                                readonly
                            >

                            <a
                                href="{{ url('/f/' . $slug) }}"
                                target="_blank"
                                class="btn btn-outline-primary"
                            >
                                Preview
                            </a>

                        </div>

                    </div>

                </div>


                {{-- STATUS --}}

                <div class="alert alert-warning">

                    <strong>
                        Status:
                    </strong>

                    Draft

                    <br>

                    Clicking
                    <strong>Finish & Publish</strong>
                    will publish this form and make the public URL available.

                </div>


                {{-- SUCCESS MESSAGE --}}

                @if(session()->has('success'))

                    <div class="alert alert-success">

                        {{ session('success') }}

                    </div>

                @endif

            </div>

        </div>

    @endif


    {{-- =========================================================
        NAVIGATION
    ========================================================== --}}

    <div class="d-flex justify-content-between mt-4">


        {{-- LEFT BUTTON --}}

        <div>

            @if($step > 1)

                <button
                    type="button"
                    class="btn btn-secondary"
                    wire:click="previousStep"
                >
                    ← Back
                </button>

            @else

                <a
                    href="{{ route('forms.create') }}"
                    class="btn btn-outline-danger"
                >
                    Cancel
                </a>

            @endif

        </div>


        {{-- RIGHT BUTTON --}}

        <div>

            {{-- STEP 1 → STEP 2 --}}

            @if($step === 1)

                <button
                    type="button"
                    class="btn btn-primary"
                    wire:click="nextStep"
                    wire:loading.attr="disabled"
                >

                    <span wire:loading.remove wire:target="nextStep">
                        Next: Builder →
                    </span>

                    <span wire:loading wire:target="nextStep">
                        Loading...
                    </span>

                </button>

            @endif


            {{-- STEP 2 → STEP 3 --}}

            @if($step === 2)

                <button
                    type="button"
                    class="btn btn-primary"
                    wire:click="nextStep"
                    wire:loading.attr="disabled"
                >

                    <span wire:loading.remove wire:target="nextStep">
                        Next: Settings →
                    </span>

                    <span wire:loading wire:target="nextStep">
                        Loading...
                    </span>

                </button>

            @endif


            {{-- STEP 3 → STEP 4 --}}

            @if($step === 3)

                <button
                    type="button"
                    class="btn btn-primary"
                    wire:click="nextStep"
                    wire:loading.attr="disabled"
                >

                    <span wire:loading.remove wire:target="nextStep">
                        Next: Finish →
                    </span>

                    <span wire:loading wire:target="nextStep">
                        Loading...
                    </span>

                </button>

            @endif


            {{-- STEP 4 → PUBLISH --}}

            @if($step === 4)

                <button
                    type="button"
                    class="btn btn-success"
                    wire:click="publishForm"
                    wire:loading.attr="disabled"
                >

                    <span
                        wire:loading.remove
                        wire:target="publishForm"
                    >
                        Finish & Publish →
                    </span>

                    <span
                        wire:loading
                        wire:target="publishForm"
                    >
                        Publishing...
                    </span>

                </button>

            @endif

        </div>

    </div>

</div>