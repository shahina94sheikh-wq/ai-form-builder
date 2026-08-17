<?php

namespace App\Livewire;

use App\Jobs\GenerateAiForm;
use App\Models\AiGeneration;
use App\Models\Form;
use Livewire\Component;
use Illuminate\Support\Str;

class AiFormGenerator extends Component
{
    public string $prompt = '';

    public ?int $generationId = null;

    public string $status = '';

    public ?string $error = null;

    public function generate(): void
    {
        $this->validate([
            'prompt' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
        ]);

        $this->error = null;

        /*
         * Create the generation record first.
         */
        $generation = AiGeneration::create([
            'prompt' => $this->prompt,
            'mode' => 'create',
            'status' => 'queued',
        ]);

        $this->generationId = $generation->id;

        $this->status = 'queued';

        /*
         * Send the AI request to the queue.
         */
        GenerateAiForm::dispatch($generation);
    }

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

        $this->status = $generation->status;

        if ($generation->status === 'failed') {
            $this->error = $generation->error;
        }
    }

    public function openGeneratedForm()
    {
        $generation = AiGeneration::findOrFail(
            $this->generationId
        );

        if (
            $generation->status !== 'completed' ||
            empty($generation->result_schema)
        ) {
            return;
        }

   
    $schema = $generation->result_schema;

    $title = $schema['title']
        ?? 'AI Generated Form';

        $baseSlug = Str::slug($title);

        if (!$baseSlug) {
            $baseSlug = 'ai-form';
        }

        $slug = $baseSlug;

        $counter = 1;

        while (Form::where('slug', $slug)->exists()) {

            $slug = $baseSlug . '-' . $counter;

            $counter++;
        }

   

        $form = Form::create([

            'title' => $title,

            'slug' => $slug,

            'schema' => $schema,

            'settings' => $schema['settings'] ?? [],

            'status' => 'draft',
            'ai_generated' => true,

        ]);

                
        $form->versions()->create([
            'version' => 1,
            'schema' => $schema,
            'created_by' => auth()->id(),
        ]);


        $generation->update([
            'form_id' => $form->id,
        ]);


        return redirect()->route(
            'forms.builder',
            $form
        );
    }

    public function render()
    {
        return view(
            'livewire.ai-form-generator'
        )->layout('layouts.app', [
            'title' => 'AI Form Generator',
        ]);
    }
}