<?php

namespace App\Livewire;

use App\Jobs\EditAiForm;
use App\Models\AiGeneration;
use App\Models\Form;
use Livewire\Component;

class AiFormEdit extends Component
{
    public Form $form;

    public string $prompt = '';

    /*
    |--------------------------------------------------------------------------
    | Current AI generation
    |--------------------------------------------------------------------------
    */

    public ?int $generationId = null;

    public ?string $generationStatus = null;

    public ?string $generationError = null;


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(Form $form): void
    {
        /*
        |--------------------------------------------------------------------------
        | AI editing is available for every existing form.
        | The current form schema is supplied to the queued edit job,
        | which validates the returned schema before applying it.
        |--------------------------------------------------------------------------
        */

        $this->form = $form;
    }


    /*
    |--------------------------------------------------------------------------
    | Generate AI Changes
    |--------------------------------------------------------------------------
    */

    public function generate(): void
    {
        $this->validate([
            'prompt' => [
                'required',
                'string',
                'min:5',
                'max:5000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Reset previous status
        |--------------------------------------------------------------------------
        */

        $this->generationError = null;

        /*
        |--------------------------------------------------------------------------
        | Create AI generation record
        |--------------------------------------------------------------------------
        */

        $generation = AiGeneration::create([
            'form_id' => $this->form->id,
            'prompt' => $this->prompt,
            'mode' => 'edit',
            'status' => 'queued',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Store generation information
        |--------------------------------------------------------------------------
        */

        $this->generationId = $generation->id;

        $this->generationStatus = 'queued';

        /*
        |--------------------------------------------------------------------------
        | Dispatch queued job
        |--------------------------------------------------------------------------
        */

        EditAiForm::dispatch($generation);

        /*
        |--------------------------------------------------------------------------
        | Clear prompt
        |--------------------------------------------------------------------------
        */

        $this->prompt = '';
    }


    /*
    |--------------------------------------------------------------------------
    | Refresh AI generation status
    |--------------------------------------------------------------------------
    */

    public function refreshStatus(): void
    {
        if (!$this->generationId) {
            return;
        }

        $generation = AiGeneration::find(
            $this->generationId
        );

        if (!$generation) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Update status
        |--------------------------------------------------------------------------
        */

        $this->generationStatus =
            $generation->status;

        /*
        |--------------------------------------------------------------------------
        | Update error
        |--------------------------------------------------------------------------
        */

        $this->generationError =
            $generation->error;

        /*
        |--------------------------------------------------------------------------
        | Refresh form after successful AI edit
        |--------------------------------------------------------------------------
        */

        if (
            $generation->status === 'completed'
        ) {
            $this->form->refresh();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Try Again
    |--------------------------------------------------------------------------
    */

    public function tryAgain(): void
    {
        $this->generationId = null;

        $this->generationStatus = null;

        $this->generationError = null;

        $this->prompt = '';
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view(
            'livewire.ai-form-edit'
        )->layout('layouts.app', [
            'title' =>
                'AI Edit - ' . $this->form->title,
        ]);
    }
}