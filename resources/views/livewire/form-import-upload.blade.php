<div
    class="container py-5"

    @if(
        $import &&
        $import->status === 'processing'
    )
        wire:poll.3s="refreshImportStatus"
    @endif
>

    {{-- =====================================================
        HEADER
    ====================================================== --}}

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="fw-bold mb-1">
                Import Form
            </h1>

            <p class="text-muted mb-0">
                Create an editable form from a Word or Excel document.
            </p>

        </div>


        <a
            href="{{ route('forms.index') }}"
            class="btn btn-outline-secondary"
        >
            ← Back to Forms
        </a>

    </div>


    {{-- =====================================================
        SUCCESS MESSAGE
    ====================================================== --}}

    @if (session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif


    {{-- =====================================================
        ERROR
    ====================================================== --}}

    @if ($uploadError)

        <div class="alert alert-danger">

            <strong>
                Import Error
            </strong>

            <div class="mt-1">
                {{ $uploadError }}
            </div>

        </div>

    @endif


    {{-- =====================================================
        MAIN CARD
    ====================================================== --}}

    <div class="card shadow-sm border-0">

        <div class="card-body p-5">


            {{-- =================================================
                ICON / HEADING
            ================================================== --}}

            <div class="text-center mb-4">

                <div
                    style="
                        width:70px;
                        height:70px;
                        border-radius:16px;
                        background:#eef4ff;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        margin:0 auto 20px;
                        font-size:32px;
                    "
                >
                    📄
                </div>


                <h3 class="fw-bold mb-2">
                    Upload your document
                </h3>


                <p class="text-muted mb-0">
                    Upload a Word document or Excel spreadsheet
                    and we'll convert it into an editable form.
                </p>

            </div>


            {{-- =================================================
                PROCESSING STATE
            ================================================== --}}

            @if(
                $import &&
                $import->status === 'processing'
            )

                <div class="alert alert-info">

                    <div class="d-flex align-items-center">

                        <div
                            class="spinner-border spinner-border-sm me-3"
                            role="status"
                        ></div>

                        <div>

                            <div class="fw-semibold">
                                Processing your document...
                            </div>

                            <div class="small mt-1">
                                Your file is being converted into an editable form.
                                This page will update automatically when processing is complete.
                            </div>

                        </div>

                    </div>

                </div>

            @endif


            {{-- =================================================
                SAMPLE TEMPLATES
            ================================================== --}}

            @if(
                !$import ||
                $import->status !== 'processing'
            )

                <div class="mb-4">

                    <div class="text-center mb-3">

                        <h5 class="fw-bold mb-1">
                            📥 Start with a sample template
                        </h5>

                        <p class="text-muted small mb-0">
                            Download a sample, make your changes,
                            and upload it here.
                        </p>

                    </div>


                    <div class="row g-3">

                        {{-- =====================================
                            WORD SAMPLE
                        ====================================== --}}

                        <div class="col-md-6">

                            <div
                                class="border rounded-3 p-3 h-100"
                                style="background:#fafafa;"
                            >

                                <div class="d-flex align-items-center">

                                    <div
                                        class="me-3"
                                        style="font-size:30px;"
                                    >
                                        📝
                                    </div>


                                    <div class="flex-grow-1">

                                        <div class="fw-semibold">
                                            Word Template
                                        </div>

                                        <div class="text-muted small">
                                            Sections, questions and choice lists
                                        </div>

                                    </div>


                                    <a
                                        href="{{ asset('templates/form-import/sample_form_import_test.docx') }}"
                                        class="btn btn-outline-primary btn-sm"
                                        download
                                    >
                                        Download
                                    </a>

                                </div>

                            </div>

                        </div>


                        {{-- =====================================
                            EXCEL SAMPLE
                        ====================================== --}}

                        <div class="col-md-6">

                            <div
                                class="border rounded-3 p-3 h-100"
                                style="background:#fafafa;"
                            >

                                <div class="d-flex align-items-center">

                                    <div
                                        class="me-3"
                                        style="font-size:30px;"
                                    >
                                        📊
                                    </div>


                                    <div class="flex-grow-1">

                                        <div class="fw-semibold">
                                            Excel Template
                                        </div>

                                        <div class="text-muted small">
                                            Section, Label, Type, Required and Options
                                        </div>

                                    </div>


                                    <a
                                        href="{{ asset('templates/form-import/sample_form_import_test.xlsx') }}"
                                        class="btn btn-outline-success btn-sm"
                                        download
                                    >
                                        Download
                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            @endif


            {{-- =================================================
                FILE INPUT
            ================================================== --}}

            @if(
                !$import ||
                $import->status !== 'processing'
            )

                <div
                    class="border rounded-3 p-4 text-center mb-4"
                    style="background:#fafafa;"
                >

                    <label
                        for="import-file"
                        class="form-label fw-semibold"
                    >
                        Select document
                    </label>


                    <input
                        id="import-file"
                        type="file"
                        wire:model="file"
                        accept=".docx,.xlsx"
                        class="form-control"
                    >


                    @error('file')

                        <div class="text-danger small mt-2">
                            {{ $message }}
                        </div>

                    @enderror


                    <div class="text-muted small mt-2">

                        Supported formats:
                        <strong>.DOCX</strong>,
                        <strong>.XLSX</strong>

                        <br>

                        Maximum size:
                        <strong>10 MB</strong>

                        <br>

                        Files larger than
                        <strong>5 MB</strong>
                        are processed in the background.

                    </div>

                </div>

            @endif


            {{-- =================================================
                UPLOADING
            ================================================== --}}

            <div
                wire:loading
                wire:target="file"
                class="alert alert-info"
            >

                <div class="d-flex align-items-center">

                    <div
                        class="spinner-border spinner-border-sm me-2"
                        role="status"
                    ></div>

                    <div>
                        Uploading selected file...
                    </div>

                </div>

            </div>


            {{-- =================================================
                SELECTED FILE
            ================================================== --}}

            @if ($file)

                <div
                    wire:loading.remove
                    wire:target="file"
                    class="alert alert-success"
                >

                    <div class="d-flex align-items-center">

                        <div
                            class="me-3"
                            style="font-size:24px;"
                        >
                            ✓
                        </div>

                        <div>

                            <div class="fw-semibold">
                                File ready
                            </div>

                            <div class="small mt-1">
                                {{ $file->getClientOriginalName() }}
                            </div>

                        </div>

                    </div>

                </div>

            @endif


            {{-- =================================================
                UPLOAD / PARSE BUTTON
            ================================================== --}}

            @if(
                !$import
            )

                <div class="text-center mt-4">

                    <button
                        type="button"
                        wire:click="createImport"
                        wire:loading.attr="disabled"
                        wire:target="createImport,file"
                        @disabled(!$file)
                        class="btn btn-primary btn-lg px-5"
                    >

                        <span
                            wire:loading.remove
                            wire:target="createImport"
                        >
                            Continue
                        </span>


                        <span
                            wire:loading
                            wire:target="createImport"
                        >
                            Uploading...
                        </span>

                    </button>

                </div>

            @elseif(
                $import->status === 'uploaded'
            )

                <div class="text-center mt-4">

                    <button
                        type="button"
                        wire:click="parseImport"
                        wire:loading.attr="disabled"
                        wire:target="parseImport"
                        class="btn btn-success btn-lg px-5"
                    >

                        <span
                            wire:loading.remove
                            wire:target="parseImport"
                        >

                            {{ $import->type === 'docx'
                                ? 'Parse Word'
                                : 'Parse Excel'
                            }}

                        </span>


                        <span
                            wire:loading
                            wire:target="parseImport"
                        >
                            Processing...
                        </span>

                    </button>

                </div>

            @elseif(
                $import->status === 'processing'
            )

                <div class="text-center mt-4">

                    <button
                        type="button"
                        class="btn btn-primary btn-lg px-5"
                        disabled
                    >

                        <span
                            class="spinner-border spinner-border-sm me-2"
                        ></span>

                        Processing...

                    </button>

                </div>

            @elseif(
                $import->status === 'failed'
            )

                <div class="text-center mt-4">

                    <button
                        type="button"
                        wire:click="parseImport"
                        wire:loading.attr="disabled"
                        wire:target="parseImport"
                        class="btn btn-warning btn-lg px-5"
                    >
                        Try Again
                    </button>

                </div>

            @endif

        </div>

    </div>


    {{-- =====================================================
        SUPPORTED FORMATS
    ====================================================== --}}

    <div class="row mt-4 g-4">

        {{-- WORD --}}

        <div class="col-md-6">

            <div class="card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <h5 class="fw-bold mb-3">
                        📝 Word Documents
                    </h5>

                    <p class="text-muted mb-0">
                        Headings become sections, questions become
                        fields, and checkbox or choice lists become
                        options.
                    </p>

                </div>

            </div>

        </div>


        {{-- EXCEL --}}

        <div class="col-md-6">

            <div class="card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <h5 class="fw-bold mb-3">
                        📊 Excel Spreadsheets
                    </h5>

                    <p class="text-muted mb-0">
                        Use a structured layout with Section, Label,
                        Type, Required and Options columns.
                    </p>

                </div>

            </div>

        </div>

    </div>


</div>