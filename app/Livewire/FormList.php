<?php

namespace App\Livewire;

use App\Models\Form;
use Livewire\Component;
use Livewire\WithPagination;

class FormList extends Component
{
    use WithPagination;

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
        $forms = Form::query()
            ->when(
                $this->search !== '',
                function ($query) {
                    $query->where(function ($q) {
                        $q->where('title', 'like', '%' . $this->search . '%')
                          ->orWhere('slug', 'like', '%' . $this->search . '%');
                    });
                }
            )
            ->withCount('submissions')
            ->latest()
            ->paginate(10);

        return view(
            'livewire.form-list',
            compact('forms')
        )->layout('layouts.app', [
            'title' => 'My Forms',
        ]);
    }
}