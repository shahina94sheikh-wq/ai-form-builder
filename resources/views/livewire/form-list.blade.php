<div class="container py-4">

    {{-- HEADER --}}

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h1 class="mb-1 fw-bold">
                My Forms
            </h1>

            <p class="text-muted mb-0">
                Manage your forms and view submissions.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">

            <a
                href="{{ route('forms.create') }}"
                class="btn btn-primary px-4"
            >
                + Create Form
            </a>

            <a
                href="{{ route('forms.ai') }}"
                class="btn btn-warning px-4"
            >
                ✨ Create AI Form
            </a>
            <a
                href="{{ route('forms.import') }}"
                class="btn btn-outline-primary"
            >
                📄 Import Form
            </a>

        </div>

    </div>


    {{-- SEARCH --}}

    <div class="card mb-4 shadow-sm">

        <div class="card-body">

            <div class="row">

                <div class="col-md-6">

                    <label class="form-label">
                        Search Forms
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search by form title or slug..."
                    >

                </div>

            </div>

        </div>

    </div>


    {{-- FORM LIST --}}

    <div class="card shadow-sm">

        <div class="card-header">

            <strong>
                All Forms
            </strong>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Form
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Submissions
                            </th>

                            <th>
                                Public URL
                            </th>

                            <th>
                                Submission URL
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($forms as $form)

                            <tr>

                                {{-- ID --}}

                                <td>
                                    {{ $form->id }}
                                </td>


                                {{-- FORM --}}

                                <td>

                                    <strong>
                                        {{ $form->title }}
                                    </strong>

                                    <div class="small text-muted">

                                        {{ $form->slug }}

                                    </div>

                                </td>


                                {{-- STATUS --}}

                                <td>

                                    @if($form->status === 'published')

                                        <span class="badge bg-success">
                                            Published
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">
                                            {{ ucfirst($form->status) }}
                                        </span>

                                    @endif

                                </td>


                                {{-- SUBMISSIONS COUNT --}}

                                <td>

                                    <span class="badge bg-primary">
                                        {{ $form->submissions_count }}
                                    </span>

                                </td>


                                {{-- PUBLIC URL --}}

                                <td>

                                    @if($form->status === 'published')

                                        <a
                                            href="{{ route('forms.public', [
                                                'form' => $form->slug
                                            ]) }}"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Open Form
                                        </a>

                                    @else

                                        <span class="text-muted small">
                                            Not published
                                        </span>

                                    @endif

                                </td>


                                {{-- SUBMISSION URL --}}

                                <td>

                                    <a
                                        href="{{ route('forms.submissions', [
                                            'form' => $form->slug
                                        ]) }}"
                                        class="btn btn-sm btn-outline-success"
                                    >
                                        View Submissions
                                    </a>

                                </td>


                                {{-- CREATED --}}

                                <td>

                                    <span class="small">

                                        {{ $form->created_at->format('d M Y') }}

                                    </span>

                                </td>


                                {{-- ACTIONS --}}

                                <td>

                                <div class="d-flex gap-1">

                                    {{-- EDIT --}}

                                    <a
                                        href="{{ route('forms.builder', [
                                            'form' => $form->slug
                                        ]) }}"
                                        class="btn btn-sm btn-primary"
                                    >
                                        Edit
                                    </a>

                                   <a
                                        href="{{ route('forms.ai.edit', $form) }}"
                                        class="btn btn-warning"
                                    >
                                        AI Edit
                                    </a>


                                    {{-- SUBMISSIONS --}}

                                    <a
                                        href="{{ route('forms.submissions', [
                                            'form' => $form->slug
                                        ]) }}"
                                        class="btn btn-sm btn-dark"
                                    >
                                        Submissions
                                    </a>


                                    <a
                                        href="{{ route('forms.versions', $form) }}"
                                        class="btn btn-outline-secondary"
                                    >
                                        Versions
                                    </a>

                                </div>

                            </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center py-5"
                                >

                                    <div class="text-muted">

                                        No forms found.

                                    </div>

                                    <a
                                        href="{{ route('forms.create') }}"
                                        class="btn btn-primary mt-3"
                                    >
                                        Create Your First Form
                                    </a>


                                      <a
                                        href="{{ route('forms.ai') }}"
                                        class="btn btn-warning  mt-3"
                                    >
                                        ✨ Create Your First AI Form
                                    </a>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- PAGINATION --}}

        @if($forms->hasPages())

            <div class="card-footer">

                {{ $forms->links() }}

            </div>

        @endif

    </div>

</div>