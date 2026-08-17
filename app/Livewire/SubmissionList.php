<?php

namespace App\Livewire;

use App\Models\Form;
use Livewire\Component;
use Livewire\WithPagination;

class SubmissionList extends Component
{
    use WithPagination;

    public Form $form;

    public string $search = '';

    protected $queryString = [
        'search' => [
            'except' => '',
        ],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $submissions = $this->form
            ->submissions()
            ->when(
                $this->search !== '',
                function ($query) {

                    $query->where(
                        'data',
                        'like',
                        '%' . $this->search . '%'
                    );

                }
            )
            ->latest()
            ->paginate(10);

        return view(
            'livewire.submission-list',
            [
                'submissions' => $submissions,
            ]
        )->layout('layouts.app', [
            'title' => 'Submissions - ' . $this->form->title,
        ]);
    }
}