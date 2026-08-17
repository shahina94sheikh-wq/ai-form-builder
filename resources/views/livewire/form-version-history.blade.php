<div class="container py-5">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="fw-bold mb-1">
                Version History
            </h1>

            <div class="text-muted">
                {{ $form->title }}
            </div>
        </div>

        <a
            href="{{ route('forms.index') }}"
            class="btn btn-outline-secondary"
        >
            ← Back to Forms
        </a>

    </div>


    {{-- Success message --}}
    @if(session()->has('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif


    {{-- Error message --}}
    @if(session()->has('error'))

        <div class="alert alert-danger">
            {{ session('error') }}
        </div>

    @endif


    {{-- Versions --}}
    @if($form->versions->count() > 0)

        <div class="card shadow-sm">

            <div class="card-header bg-white">

                <strong>
                    Form Versions
                </strong>

                <span class="text-muted">
                    ({{ $form->versions->count() }})
                </span>

            </div>


            <div class="list-group list-group-flush">

                @foreach($form->versions as $version)

                    <div class="list-group-item p-4">

                        <div class="d-flex justify-content-between align-items-center">

                            {{-- Version information --}}
                            <div>

                                <h5 class="mb-1">
                                    Version {{ $version->version }}
                                </h5>

                                <div class="text-muted small">

                                    Created:
                                    {{ $version->created_at->format('d M Y, h:i A') }}

                                </div>

                            </div>


                            {{-- Actions --}}
                            <div class="d-flex gap-2">

                                <button
                                    type="button"
                                    class="btn btn-outline-primary"
                                    wire:click="selectVersion({{ $version->id }})"
                                >

                                    @if($selectedVersion === $version->id)

                                        Hide

                                    @else

                                        View

                                    @endif

                                </button>


                                <button
                                    type="button"
                                    class="btn btn-outline-warning"
                                    wire:click="restoreVersion({{ $version->id }})"
                                    wire:confirm="Are you sure you want to restore Version {{ $version->version }}?"
                                >
                                    Restore
                                </button>

                            </div>

                        </div>


                        {{-- Schema preview --}}
                        @if($selectedVersion === $version->id)

                            <div class="mt-4">

                                <h6 class="fw-bold">
                                    Schema
                                </h6>

                                <pre
                                    class="bg-light border rounded p-3"
                                    style="
                                        max-height: 500px;
                                        overflow-y: auto;
                                        white-space: pre-wrap;
                                        word-break: break-word;
                                    "
                                >{{ json_encode(
                                    $version->schema,
                                    JSON_PRETTY_PRINT |
                                    JSON_UNESCAPED_UNICODE |
                                    JSON_UNESCAPED_SLASHES
                                ) }}</pre>

                            </div>

                        @endif

                    </div>

                @endforeach

            </div>

        </div>

    @else

        {{-- No versions --}}
        <div class="card shadow-sm">

            <div class="card-body text-center py-5">

                <div style="font-size: 48px;">
                    🕘
                </div>

                <h4 class="mt-3">
                    No versions yet
                </h4>

                <p class="text-muted">
                    Saved form changes will appear here.
                </p>

                <a
                    href="{{ route('forms.builder', ['form' => $form->slug]) }}"
                    class="btn btn-primary"
                >
                    Open Builder
                </a>

            </div>

        </div>

    @endif

</div>