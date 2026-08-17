<?php

namespace App\Livewire;

use App\Models\Form;
use Illuminate\Support\Str;
use Livewire\Component;

class FormWizard extends Component
{
    public int $step = 1;

    public ?Form $form = null;

    public string $title = '';

    public string $description = '';

    public string $slug = '';

    public string $status = 'draft';

    public array $schema = [];

    public string $schemaJson = '';

    public array $settings = [
        'success_message' => 'Thank you for submitting the form.',
        'show_progress' => true,
        'allow_multiple' => true,
    ];


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->schema = [
            'version' => '1.0',
            'title' => '',
            'description' => '',
            'sections' => [],
            'settings' => $this->settings,
        ];

        $this->syncSchemaJson();
    }


    /*
    |--------------------------------------------------------------------------
    | Next Step
    |--------------------------------------------------------------------------
    */

    public function nextStep(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Step 1
        |--------------------------------------------------------------------------
        */

        if ($this->step === 1) {

            $this->validate([
                'title' => 'required|string|max:200',
                'description' => 'nullable|string|max:1000',
            ]);

            $this->generateSlug();

            /*
            |--------------------------------------------------------------------------
            | Create draft form only once
            |--------------------------------------------------------------------------
            */

            if (!$this->form) {

                $this->schema = [
                    'version' => '1.0',

                    'title' => $this->title,

                    'description' => $this->description,

                    'sections' => [
                        [
                            'id' => 'section_' . Str::random(10),

                            'title' => 'Personal Information',

                            'fields' => [],
                        ],
                    ],

                    'settings' => [
                        'success_message' =>
                            'Thank you for your submission.',

                        'show_progress' => true,

                        'allow_multiple' => false,
                    ],
                ];

                $this->form = Form::create([
                    'title' => $this->title,

                    'slug' => $this->slug,

                    'status' => 'draft',

                    'schema' => $this->schema,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Create initial Version 1
                |--------------------------------------------------------------------------
                */

                $this->form->versions()->create([
                    'version' => 1,

                    'schema' => $this->schema,

                    'created_by' => auth()->id(),
                ]);
            }

            $this->step = 2;

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Step 2
        |--------------------------------------------------------------------------
        */

        if ($this->step === 2) {

            if ($this->form) {
                $this->form->refresh();
            }

            $this->step = 3;

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Step 3
        |--------------------------------------------------------------------------
        */

        if ($this->step === 3) {

            $this->validate([
                'settings.success_message' =>
                    'required|string|max:500',
            ]);

            $this->step = 4;

            return;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Previous Step
    |--------------------------------------------------------------------------
    */

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Slug
    |--------------------------------------------------------------------------
    */

    protected function generateSlug(): void
    {
        if (!$this->slug) {

            $this->slug =
                Str::slug($this->title)
                . '-'
                . strtoupper(Str::random(5));
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Sync JSON
    |--------------------------------------------------------------------------
    */

    protected function syncSchemaJson(): void
    {
        $this->schema['title'] =
            $this->title;

        $this->schema['description'] =
            $this->description;

        $this->schema['settings'] =
            $this->settings;

        $this->schemaJson = json_encode(
            $this->schema,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Apply JSON Schema
    |--------------------------------------------------------------------------
    */

    public function applyJsonSchema(): void
    {
        $decoded = json_decode(
            $this->schemaJson,
            true
        );

        if (
            !is_array($decoded) ||
            !isset($decoded['sections']) ||
            !is_array($decoded['sections'])
        ) {

            $this->addError(
                'schemaJson',
                'Invalid JSON schema.'
            );

            return;
        }

        $this->schema = $decoded;

        $this->title =
            $decoded['title'] ?? $this->title;

        $this->description =
            $decoded['description'] ?? $this->description;

        $this->settings =
            $decoded['settings'] ?? $this->settings;

        $this->resetErrorBag('schemaJson');
    }


    /*
    |--------------------------------------------------------------------------
    | Check Actual Form Fields
    |--------------------------------------------------------------------------
    |
    | Headings do NOT count as form fields.
    |
    */

    protected function hasFormFields(array $schema): bool
    {
        foreach ($schema['sections'] ?? [] as $section) {

            foreach ($section['fields'] ?? [] as $field) {

                if (($field['type'] ?? '') === 'heading') {
                    continue;
                }

                return true;
            }
        }

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Save Wizard
    |--------------------------------------------------------------------------
    |
    | Important:
    | Do NOT create another Form here.
    |
    | nextStep() already creates the draft form.
    |
    */

    public function save(): void
    {
        $this->validate([
            'title' =>
                'required|string|max:200',

            'schemaJson' =>
                'required|json',
        ]);

        $this->applyJsonSchema();

        if ($this->getErrorBag()->has('schemaJson')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Make sure form exists
        |--------------------------------------------------------------------------
        */

        if (!$this->form) {

            $this->generateSlug();

            $this->form = Form::create([
                'title' => $this->title,

                'slug' => $this->slug,

                'status' => 'draft',

                'schema' => $this->schema,
            ]);

            /*
            |----------------------------------------------------------------------
            | Create Version 1
            |----------------------------------------------------------------------
            */

            $this->form->versions()->create([
                'version' => 1,

                'schema' => $this->schema,

                'created_by' => auth()->id(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Prepare schema
        |--------------------------------------------------------------------------
        */

        $this->schema['title'] =
            $this->title;

        $this->schema['description'] =
            $this->description;

        $this->schema['settings'] =
            $this->settings;


        /*
        |--------------------------------------------------------------------------
        | Compare with current schema
        |--------------------------------------------------------------------------
        */

        $oldSchema =
            $this->form->schema ?? [];

        $schemaChanged =
            json_encode(
                $oldSchema,
                JSON_UNESCAPED_SLASHES
            ) !==
            json_encode(
                $this->schema,
                JSON_UNESCAPED_SLASHES
            );


        /*
        |--------------------------------------------------------------------------
        | Check existing versions
        |--------------------------------------------------------------------------
        */

        $hasVersions =
            $this->form
                ->versions()
                ->exists();


        /*
        |--------------------------------------------------------------------------
        | Update existing form
        |--------------------------------------------------------------------------
        */

        $this->form->update([
            'title' => $this->title,

            'schema' => $this->schema,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Create version when required
        |--------------------------------------------------------------------------
        */

        if (!$hasVersions || $schemaChanged) {

            $version =
                $this->form->nextVersionNumber();

            $this->form->versions()->create([
                'version' => $version,

                'schema' => $this->schema,

                'created_by' => auth()->id(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Sync Livewire
        |--------------------------------------------------------------------------
        */

        $this->schemaJson = json_encode(
            $this->schema,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );


        session()->flash(
            'success',
            $schemaChanged
                ? 'Form saved successfully. New version created.'
                : 'Form saved successfully.'
        );

        /*
        |--------------------------------------------------------------------------
        | Go to Builder
        |--------------------------------------------------------------------------
        */

        redirect()->route(
            'forms.builder',
            [
                'form' => $this->form->slug,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Publish
    |--------------------------------------------------------------------------
    */

    public function publishForm(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Form must exist
        |--------------------------------------------------------------------------
        */

        if (!$this->form) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Refresh latest data
        |--------------------------------------------------------------------------
        */

        $this->form->refresh();

        $schema =
            $this->form->schema ?? [];


        /*
        |--------------------------------------------------------------------------
        | Apply Wizard settings
        |--------------------------------------------------------------------------
        */

        $schema['settings'] =
            $this->settings;

        $schema['title'] =
            $this->title;

        $schema['description'] =
            $this->description;


        /*
        |--------------------------------------------------------------------------
        | Validate fields
        |--------------------------------------------------------------------------
        */

        if (!$this->hasFormFields($schema)) {

            $this->addError(
                'publish',
                'Please add at least one field before publishing the form.'
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Check schema change
        |--------------------------------------------------------------------------
        */

        $oldSchema =
            $this->form->schema ?? [];

        $schemaChanged =
            json_encode(
                $oldSchema,
                JSON_UNESCAPED_SLASHES
            ) !==
            json_encode(
                $schema,
                JSON_UNESCAPED_SLASHES
            );


        /*
        |--------------------------------------------------------------------------
        | Check versions
        |--------------------------------------------------------------------------
        */

        $hasVersions =
            $this->form
                ->versions()
                ->exists();


        /*
        |--------------------------------------------------------------------------
        | Update form
        |--------------------------------------------------------------------------
        */

        $this->form->update([
            'schema' =>
                $schema,

            'status' =>
                'published',

            'published_at' =>
                now(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Create version
        |--------------------------------------------------------------------------
        */

        if (!$hasVersions || $schemaChanged) {

            $version =
                $this->form->nextVersionNumber();

            $this->form->versions()->create([
                'version' =>
                    $version,

                'schema' =>
                    $schema,

                'created_by' =>
                    auth()->id(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Sync Livewire
        |--------------------------------------------------------------------------
        */

        $this->schema =
            $schema;

        $this->syncSchemaJson();


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        session()->flash(
            'success',
            !$hasVersions
                ? 'Form published successfully. Version 1 created.'
                : (
                    $schemaChanged
                        ? 'Form published successfully. New version created.'
                        : 'Form published successfully.'
                )
        );


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        redirect()->route(
            'forms.public',
            [
                'form' =>
                    $this->form->slug,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view(
            'livewire.form-wizard'
        )->layout('layouts.app', [
            'title' => 'Create Form',
        ]);
    }
}   