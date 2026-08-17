<div class="container py-5">

    {{-- =========================================================
        HEADER
    ========================================================== --}}

    <div class="mb-4">

        <h1 class="fw-bold mb-2">
            ✨ AI Edit Form
        </h1>

        <p class="text-muted mb-0">
            Use AI to modify your existing form using natural language.
        </p>

    </div>


    {{-- =========================================================
        FORM INFORMATION
    ========================================================== --}}

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-start">

                <div>

                    <h3 class="fw-bold mb-1">
                        {{ $form->title }}
                    </h3>

                    <div class="text-muted">
                        Existing Form
                    </div>

                </div>

                <span class="badge bg-success px-3 py-2">
                    {{ ucfirst($form->status) }}
                </span>

            </div>

        </div>

    </div>


    {{-- =========================================================
        AI EDIT FORM
    ========================================================== --}}

    @if (
        !$generationId ||
        in_array(
            $generationStatus,
            ['completed', 'failed']
        )
    )

        <div class="card shadow-sm border-0">

            <div class="card-header bg-primary text-white py-3">

                <h4 class="mb-1 fw-bold">
                    🤖 What would you like to change?
                </h4>

                <small>
                    Describe the changes you want AI to make to this form.
                </small>

            </div>


            <div class="card-body p-4">

                {{-- Prompt Error --}}

                @error('prompt')

                    <div class="alert alert-danger">
                        {{ $message }}
                    </div>

                @enderror


                {{-- Prompt --}}

                <div class="mb-4">

                    <label
                        for="prompt"
                        class="form-label fw-semibold"
                    >
                        Describe the changes
                    </label>

                    <textarea
                        id="prompt"
                        wire:model="prompt"
                        rows="7"
                        class="form-control form-control-lg @error('prompt') is-invalid @enderror"
                        placeholder="Example: Add an emergency contact section with name, phone number and relationship."
                    ></textarea>

                    @error('prompt')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- =================================================
                    EXAMPLES
                ================================================== --}}

                <div class="mb-4">

                    <h6 class="fw-bold mb-3">
                        💡 Example instructions
                    </h6>

                    <div class="row g-3">

                        <div class="col-md-6">

                            <button
                                type="button"
                                class="btn btn-outline-secondary w-100 text-start"
                                wire:click="$set('prompt', 'Add an emergency contact section with name, phone number and relationship.')"
                            >

                                <strong>
                                    Add section
                                </strong>

                                <br>

                                <small class="text-muted">
                                    Add an emergency contact section
                                </small>

                            </button>

                        </div>


                        <div class="col-md-6">

                            <button
                                type="button"
                                class="btn btn-outline-secondary w-100 text-start"
                                wire:click="$set('prompt', 'Make the phone number field required.')"
                            >

                                <strong>
                                    Make field required
                                </strong>

                                <br>

                                <small class="text-muted">
                                    Make the phone number required
                                </small>

                            </button>

                        </div>


                        <div class="col-md-6">

                            <button
                                type="button"
                                class="btn btn-outline-secondary w-100 text-start"
                                wire:click="$set('prompt', 'Translate all field labels and help text to Hindi.')"
                            >

                                <strong>
                                    Translate labels
                                </strong>

                                <br>

                                <small class="text-muted">
                                    Translate labels to Hindi
                                </small>

                            </button>

                        </div>


                        <div class="col-md-6">

                            <button
                                type="button"
                                class="btn btn-outline-secondary w-100 text-start"
                                wire:click="$set('prompt', 'Add a resume upload field with PDF and DOCX validation.')"
                            >

                                <strong>
                                    Add resume upload
                                </strong>

                                <br>

                                <small class="text-muted">
                                    Add a resume/file upload field
                                </small>

                            </button>

                        </div>


                        <div class="col-md-6">

                            <button
                                type="button"
                                class="btn btn-outline-secondary w-100 text-start"
                                wire:click="$set('prompt', 'Add a dropdown field for highest qualification with suitable options.')"
                            >

                                <strong>
                                    Add dropdown
                                </strong>

                                <br>

                                <small class="text-muted">
                                    Add a qualification dropdown
                                </small>

                            </button>

                        </div>


                        <div class="col-md-6">

                            <button
                                type="button"
                                class="btn btn-outline-secondary w-100 text-start"
                                wire:click="$set('prompt', 'Add a LinkedIn profile URL field with URL validation.')"
                            >

                                <strong>
                                    Add LinkedIn URL
                                </strong>

                                <br>

                                <small class="text-muted">
                                    Add a validated LinkedIn URL
                                </small>

                            </button>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                    BUTTONS
                ================================================== --}}

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">

                    <a
                        href="{{ route('forms.builder', [
                            'form' => $form->slug
                        ]) }}"
                        class="btn btn-outline-secondary"
                    >
                        ← Back to Builder
                    </a>


                    <button
                        type="button"
                        wire:click="generate"
                        wire:loading.attr="disabled"
                        wire:target="generate"
                        class="btn btn-primary btn-lg px-4"
                    >

                        <span
                            wire:loading.remove
                            wire:target="generate"
                        >
                            ✨ Generate Changes
                        </span>

                        <span
                            wire:loading
                            wire:target="generate"
                        >
                            <span class="spinner-border spinner-border-sm me-2"></span>

                            Queuing...
                        </span>

                    </button>

                </div>

            </div>

        </div>

    @endif


    {{-- =========================================================
        AI GENERATION STATUS
    ========================================================== --}}

    @if ($generationId)

        <div
            class="card shadow-sm border-0 mt-4"
            @if (
                $generationStatus !== 'completed' &&
                $generationStatus !== 'failed'
            )
                wire:poll.2s="refreshStatus"
            @endif
        >

            <div class="card-body p-4">

                <h5 class="fw-bold mb-4">
                    🤖 AI Form Update
                </h5>


                {{-- QUEUED --}}

                @if ($generationStatus === 'queued')

                    <div class="alert alert-info mb-0">

                        <div class="d-flex align-items-center">

                            <div
                                class="spinner-border spinner-border-sm me-3"
                            ></div>

                            <div>

                                <strong>
                                    Queued
                                </strong>

                                <div class="small">
                                    Your AI edit request is waiting to be processed.
                                </div>

                            </div>

                        </div>

                    </div>

                @endif


                {{-- PROCESSING --}}

                @if ($generationStatus === 'processing')

                    <div class="alert alert-primary mb-0">

                        <div class="d-flex align-items-center">

                            <div
                                class="spinner-border spinner-border-sm me-3"
                            ></div>

                            <div>

                                <strong>
                                    AI is updating your form...
                                </strong>

                                <div class="small">
                                    Please wait while your requested changes are generated.
                                </div>

                            </div>

                        </div>

                    </div>

                @endif


                {{-- COMPLETED --}}

                @if ($generationStatus === 'completed')

                    <div class="alert alert-success">

                        <h5 class="alert-heading">
                            ✅ Changes completed successfully
                        </h5>

                        <p class="mb-3">
                            Your form has been updated successfully.
                        </p>

                        <a
                            href="{{ route('forms.builder', [
                                'form' => $form->slug
                            ]) }}"
                            class="btn btn-success"
                        >
                            Open Updated Form Builder
                        </a>

                    </div>

                @endif


                {{-- FAILED --}}

                @if ($generationStatus === 'failed')

                <div class="alert alert-danger">

                    <h5 class="alert-heading">
                        ❌ AI edit failed
                    </h5>

                    <p>
                        We could not update the form.
                    </p>

                    @if ($generationError)

                        <hr>

                        <div>
                            <strong>
                                Error details:
                            </strong>
                        </div>

                        <pre
                            class="mt-2 p-3 bg-light border rounded"
                            style="
                                white-space: pre-wrap;
                                word-break: break-word;
                                color: #842029;
                                max-height: 300px;
                                overflow-y: auto;
                            "
                        >{{ $generationError }}</pre>

                    @endif

                    <button
                        type="button"
                        class="btn btn-outline-danger mt-3"
                        wire:click="tryAgain"
                    >
                        Try Again
                    </button>

                </div>

            @endif

            </div>

        </div>

    @endif


    {{-- =========================================================
        INFORMATION
    ========================================================== --}}

    @if (!$generationId)

        <div class="alert alert-info mt-4">

            <strong>
                How AI editing works:
            </strong>

            <ul class="mb-0 mt-2">

                <li>
                    Your existing form is kept intact.
                </li>

                <li>
                    AI applies only the changes you request.
                </li>

                <li>
                    The generated schema is validated before being saved.
                </li>

                <li>
                    You can continue editing the result in the Form Builder.
                </li>

            </ul>

        </div>

    @endif

</div>