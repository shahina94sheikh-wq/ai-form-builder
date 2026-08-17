<div class="container py-4">

    {{-- =====================================================
        HEADER
    ====================================================== --}}

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="mb-1">
                {{ $form->title }}
            </h1>

            <div class="text-muted">
                Form Submissions
            </div>

        </div>


        <div class="d-flex gap-2">

            {{-- BACK TO FORMS --}}

            <a
                href="{{ route('forms.index') }}"
                class="btn btn-outline-secondary"
            >
                ← Back to Forms
            </a>


            {{-- CSV EXPORT --}}

            <a
                href="{{ route('forms.submissions.csv', [
                    'form' => $form->slug
                ]) }}"
                class="btn btn-success"
            >
                Export CSV
            </a>

        </div>

    </div>


    {{-- =====================================================
        SEARCH
    ====================================================== --}}

    <div class="card mb-4">

        <div class="card-body">

            <div class="row">

                <div class="col-md-6">

                    <label class="form-label">
                        Search submissions
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        wire:model.live.debounce.500ms="search"
                        placeholder="Search submitted data..."
                    >

                </div>

            </div>

        </div>

    </div>


    {{-- =====================================================
        SUBMISSIONS TABLE
    ====================================================== --}}

    <div class="card">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>
                                #
                            </th>


                            @foreach(
                                $form->schema['sections'] ?? []
                                as $section
                            )

                                @foreach(
                                    $section['fields'] ?? []
                                    as $field
                                )

                                    @if(
                                        ($field['type'] ?? '') !== 'heading'
                                    )

                                        @php

                                            /*
                                            |--------------------------------------------------------------------------
                                            | Support both old/manual forms and imported forms
                                            |--------------------------------------------------------------------------
                                            |
                                            | Manual forms may use:
                                            |     key
                                            |
                                            | Imported forms may use:
                                            |     id
                                            |
                                            */

                                            $fieldKey =
                                                $field['key']
                                                ?? $field['id']
                                                ?? null;

                                        @endphp


                                        <th>
                                            {{ $field['label'] ?? $fieldKey ?? 'Field' }}
                                        </th>

                                    @endif

                                @endforeach

                            @endforeach


                            <th>
                                Submitted
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse(
                            $submissions as $submission
                        )

                            <tr>

                                {{-- Submission ID --}}

                                <td>
                                    {{ $submission->id }}
                                </td>


                                @foreach(
                                    $form->schema['sections'] ?? []
                                    as $section
                                )

                                    @foreach(
                                        $section['fields'] ?? []
                                        as $field
                                    )

                                        @if(
                                            ($field['type'] ?? '') !== 'heading'
                                        )

                                            @php

                                                /*
                                                |--------------------------------------------------------------------------
                                                | Resolve field key
                                                |--------------------------------------------------------------------------
                                                |
                                                | Important:
                                                | Existing forms use "key".
                                                | Imported forms use "id".
                                                |
                                                */

                                                $fieldKey =
                                                    $field['key']
                                                    ?? $field['id']
                                                    ?? null;


                                                /*
                                                |--------------------------------------------------------------------------
                                                | Get submitted value
                                                |--------------------------------------------------------------------------
                                                */

                                                $value = '';

                                                if (
                                                    $fieldKey !== null &&
                                                    isset($submission->data) &&
                                                    is_array($submission->data)
                                                ) {

                                                    $value =
                                                        $submission->data[$fieldKey]
                                                        ?? '';

                                                }

                                            @endphp


                                            <td>

                                                @if(
                                                    is_array($value)
                                                )

                                                    {{ implode(', ', $value) }}

                                                @elseif(
                                                    is_object($value)
                                                )

                                                    {{ json_encode($value) }}

                                                @elseif(
                                                    $value !== null &&
                                                    $value !== ''
                                                )

                                                    {{ $value }}

                                                @else

                                                    <span class="text-muted">
                                                        —
                                                    </span>

                                                @endif

                                            </td>

                                        @endif

                                    @endforeach

                                @endforeach


                                {{-- Submitted date --}}

                                <td>

                                    {{ $submission->created_at->format('d M Y H:i') }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="100%"
                                    class="text-center py-5 text-muted"
                                >

                                    No submissions found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- =====================================================
            PAGINATION
        ====================================================== --}}

        @if($submissions->hasPages())

            <div class="card-footer">

                {{ $submissions->links() }}

            </div>

        @endif

    </div>

</div>