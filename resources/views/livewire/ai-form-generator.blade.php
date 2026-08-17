<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-9">

            <div class="card shadow-sm">

                <div class="card-header">

                    <h3 class="mb-0">
                        AI Form Generator
                    </h3>

                </div>

                <div class="card-body">

                    <div class="mb-4">

                        <label class="form-label fw-bold">
                            Describe the form you want
                        </label>

                        <textarea
                            class="form-control"
                            rows="6"
                            wire:model.live="prompt"
                            placeholder="Example: Create an internship application form with personal information, education history, skills and resume upload."
                        ></textarea>

                        @error('prompt')
                            <div class="text-danger mt-2">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Generate button --}}

                    <button
                        type="button"
                        class="btn btn-primary"
                        wire:click="generate"
                        wire:loading.attr="disabled"
                        wire:target="generate"
                    >

                        <span wire:loading.remove wire:target="generate">
                            Generate Form
                        </span>

                        <span wire:loading wire:target="generate">
                            Queuing...
                        </span>

                    </button>


                    {{-- Status --}}

                    @if($generationId)

                        <div class="mt-4">

                            <div class="card">

                                <div class="card-body">

                                    <h5>
                                        Generation Status
                                    </h5>


                                    @if($status === 'queued')

                                        <div class="alert alert-info">
                                            ⏳ AI generation is queued.
                                        </div>

                                    @elseif($status === 'processing')

                                        <div class="alert alert-warning">
                                            ⚙️ AI is generating your form...
                                        </div>

                                    @elseif($status === 'completed')

                                        <div class="alert alert-success">
                                            ✅ Form generated successfully.
                                        </div>

                                    @elseif($status === 'failed')

                                        <div class="alert alert-danger">

                                            ❌ AI generation failed.

                                            @if($error)
                                                <hr>

                                                <strong>
                                                    Error:
                                                </strong>

                                                <div>
                                                    {{ $error }}
                                                </div>
                                            @endif

                                        </div>

                                    @endif

                                </div>

                            </div>

                        </div>

                    @endif


                    {{-- Poll status --}}

                    @if(
                        $generationId &&
                        in_array($status, [
                            'queued',
                            'processing'
                        ])
                    )

                        <div
                            wire:poll.2s="refreshStatus"
                            class="mt-3"
                        >
                            Checking generation status...
                        </div>

                    @endif


                    {{-- Completed action --}}

                    @if($status === 'completed')

                        <div class="mt-4">

                            <button
                                type="button"
                                class="btn btn-success"
                                wire:click="openGeneratedForm"
                            >
                                Open Generated Form
                            </button>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>