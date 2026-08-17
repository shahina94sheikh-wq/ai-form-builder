<?php

namespace App\Livewire;

use App\Models\Form;
use Livewire\Component;

class FormVersionHistory extends Component
{
    public Form $form;

    public ?int $selectedVersion = null;

    public function mount(Form $form): void
    {
        $this->form = $form;

        $this->form->load([
            'versions' => function ($query) {
                $query->latest('version');
            },
        ]);
    }

    public function selectVersion(int $versionId): void
    {
        if ($this->selectedVersion === $versionId) {
            $this->selectedVersion = null;

            return;
        }

        $this->selectedVersion = $versionId;
    }

      public function restoreVersion(int $versionId): void
{
    $version = $this->form
        ->versions()
        ->whereKey($versionId)
        ->first();

    if (!$version) {
        session()->flash(
            'error',
            'Version not found.'
        );

        return;
    }

    $currentSchema = $this->form->schema ?? [];

    /*
    |--------------------------------------------------------------------------
    | Don't restore the same schema
    |--------------------------------------------------------------------------
    */

    if (
        json_encode(
            $currentSchema,
            JSON_UNESCAPED_SLASHES
        ) ===
        json_encode(
            $version->schema,
            JSON_UNESCAPED_SLASHES
        )
    ) {
        session()->flash(
            'error',
            'This version is already the current form.'
        );

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Create a new version containing the restored schema
    |--------------------------------------------------------------------------
    |
    | Historical versions are never modified or deleted.
    |
    | Example:
    |
    | V1 → V2 → V3
    |
    | Restore V1
    |
    | V1 → V2 → V3 → V4
    |                  ↑
    |             V1 schema
    |
    */

    $newVersion = $this->form->nextVersionNumber();

    $this->form->versions()->create([
        'version' => $newVersion,
        'schema' => $version->schema,
        'created_by' => auth()->id(),
    ]);

    /*
    |--------------------------------------------------------------------------
    | Restore selected schema as the current form
    |--------------------------------------------------------------------------
    */

    $this->form->update([
        'schema' => $version->schema,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Refresh form and versions
    |--------------------------------------------------------------------------
    */

    $this->form->refresh();

    $this->form->load([
        'versions' => function ($query) {
            $query->latest('version');
        },
    ]);

    $this->selectedVersion = null;

    session()->flash(
        'success',
        "Version {$version->version} restored successfully as Version {$newVersion}."
    );
}

    public function render()
    {
        return view(
            'livewire.form-version-history'
        )->layout('layouts.app', [
            'title' => 'Version History - ' . $this->form->title,
        ]);
    }
}