<?php

namespace App\Livewire;

use App\Models\Form;
use App\Models\FormImport;
use Illuminate\Support\Str;
use Livewire\Component;

class FormImportPreview extends Component
{
    public FormImport $import;

    public array $sections = [];

    public array $parserErrors = [];

    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(FormImport $import): void
    {
        $this->import = $import;

        /*
        |--------------------------------------------------------------------------
        | Make sure parsed data exists
        |--------------------------------------------------------------------------
        */

        if (!$this->import->parsed_data) {
            abort(404, 'No parsed import data found.');
        }

        /*
        |--------------------------------------------------------------------------
        | Load parsed sections
        |--------------------------------------------------------------------------
        */

        $data = $this->import->parsed_data;

        $this->sections = $data['sections'] ?? [];

        $this->parserErrors = $data['errors'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Normalize imported fields
        |--------------------------------------------------------------------------
        */

        $this->normalizeSections();

        /*
        |--------------------------------------------------------------------------
        | Nothing detected
        |--------------------------------------------------------------------------
        */

        if (empty($this->sections)) {
            session()->flash(
                'error',
                'No form fields were detected in this file.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Sections
    |--------------------------------------------------------------------------
    */

    protected function normalizeSections(): void
    {
        foreach ($this->sections as $sectionIndex => &$section) {

            /*
            |--------------------------------------------------------------------------
            | Section defaults
            |--------------------------------------------------------------------------
            */

            $section['id'] =
                $section['id']
                ?? 'section_' . Str::random(10);

            $section['title'] =
                trim(
                    $section['title']
                    ?? 'Untitled Section'
                );

            if ($section['title'] === '') {
                $section['title'] = 'Untitled Section';
            }

            $section['fields'] =
                $section['fields']
                ?? [];

            /*
            |--------------------------------------------------------------------------
            | Normalize fields
            |--------------------------------------------------------------------------
            */

            foreach (
                $section['fields']
                as $fieldIndex => &$field
            ) {

                $field['id'] =
                    $field['id']
                    ?? 'field_' . Str::random(10);

                /*
                |--------------------------------------------------------------------------
                | Generate field key
                |--------------------------------------------------------------------------
                */

                $label =
                    trim(
                        $field['label']
                        ?? 'Untitled Field'
                    );

                $field['label'] =
                    $label !== ''
                        ? $label
                        : 'Untitled Field';

                $field['key'] =
                    $field['key']
                    ?? Str::slug($field['label'], '_');

                if (!$field['key']) {
                    $field['key'] =
                        'field_' . Str::lower(
                            Str::random(8)
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Field type
                |--------------------------------------------------------------------------
                */

                $allowedTypes = [
                    'text',
                    'textarea',
                    'number',
                    'email',
                    'phone',
                    'date',
                    'dropdown',
                    'radio',
                    'checkbox',
                    'file',
                    'rating',
                ];

                $type =
                    $field['type']
                    ?? 'text';

                /*
                |--------------------------------------------------------------------------
                | Normalize common parser type names
                |--------------------------------------------------------------------------
                */

                $typeMap = [
                    'select' => 'dropdown',
                    'choice' => 'dropdown',
                    'multiselect' => 'checkbox',
                    'string' => 'text',
                    'integer' => 'number',
                    'int' => 'number',
                    'tel' => 'phone',
                ];

                $type =
                    $typeMap[$type]
                    ?? $type;

                if (
                    !in_array(
                        $type,
                        $allowedTypes,
                        true
                    )
                ) {
                    $type = 'text';
                }

                $field['type'] = $type;

                /*
                |--------------------------------------------------------------------------
                | Required
                |--------------------------------------------------------------------------
                */

                $field['required'] =
                    (bool) (
                        $field['required']
                        ?? false
                    );

                /*
                |--------------------------------------------------------------------------
                | Options
                |--------------------------------------------------------------------------
                */

                $field['options'] =
                    $this->normalizeOptions(
                        $field['options'] ?? []
                    );

                /*
                |--------------------------------------------------------------------------
                | Validation
                |--------------------------------------------------------------------------
                */

                $field['validation'] =
                    is_array(
                        $field['validation'] ?? null
                    )
                        ? $field['validation']
                        : [];

            }

            unset($field);
        }

        unset($section);

        /*
        |--------------------------------------------------------------------------
        | Make field keys unique
        |--------------------------------------------------------------------------
        */

        $this->makeFieldKeysUnique();
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Options
    |--------------------------------------------------------------------------
    */

    protected function normalizeOptions(
        mixed $options
    ): array {

        if (!is_array($options)) {
            return [];
        }

        $normalized = [];

        foreach ($options as $option) {

            /*
            |--------------------------------------------------------------------------
            | Existing option array
            |--------------------------------------------------------------------------
            */

            if (is_array($option)) {

                $label =
                    trim(
                        (string) (
                            $option['label']
                            ?? $option['value']
                            ?? ''
                        )
                    );

                $value =
                    trim(
                        (string) (
                            $option['value']
                            ?? $label
                        )
                    );

            } else {

                /*
                |--------------------------------------------------------------------------
                | Simple string option
                |--------------------------------------------------------------------------
                */

                $label =
                    trim(
                        (string) $option
                    );

                $value = $label;
            }

            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $normalized;
    }

    /*
    |--------------------------------------------------------------------------
    | Make Field Keys Unique
    |--------------------------------------------------------------------------
    */

    protected function makeFieldKeysUnique(): void
    {
        $usedKeys = [];

        foreach ($this->sections as $sectionIndex => &$section) {

            foreach (
                $section['fields']
                as $fieldIndex => &$field
            ) {

                $baseKey =
                    Str::slug(
                        $field['key']
                        ?? $field['label']
                        ?? 'field',
                        '_'
                    );

                if ($baseKey === '') {
                    $baseKey = 'field';
                }

                $key = $baseKey;

                $counter = 1;

                while (in_array($key, $usedKeys, true)) {

                    $key =
                        $baseKey
                        . '_'
                        . $counter;

                    $counter++;
                }

                $field['key'] = $key;

                $usedKeys[] = $key;
            }

            unset($field);
        }

        unset($section);
    }

    /*
    |--------------------------------------------------------------------------
    | Change Field Type
    |--------------------------------------------------------------------------
    */

    public function updateFieldType(
        int $sectionIndex,
        int $fieldIndex,
        string $type
    ): void {

        $allowedTypes = [
            'text',
            'textarea',
            'number',
            'email',
            'phone',
            'date',
            'dropdown',
            'radio',
            'checkbox',
            'file',
            'rating',
        ];

        if (
            !in_array(
                $type,
                $allowedTypes,
                true
            )
        ) {
            return;
        }

        if (
            !isset(
                $this->sections[$sectionIndex]['fields'][$fieldIndex]
            )
        ) {
            return;
        }

        $this->sections[$sectionIndex]['fields'][$fieldIndex]['type'] =
            $type;

        /*
        |--------------------------------------------------------------------------
        | Choice fields require options
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $type,
                [
                    'dropdown',
                    'radio',
                    'checkbox',
                ],
                true
            )
        ) {

            if (
                empty(
                    $this->sections[$sectionIndex]['fields'][$fieldIndex]['options']
                )
            ) {

                $this->sections[$sectionIndex]['fields'][$fieldIndex]['options'] = [
                    [
                        'label' => 'Option 1',
                        'value' => 'Option 1',
                    ],
                    [
                        'label' => 'Option 2',
                        'value' => 'Option 2',
                    ],
                ];
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | Non-choice fields don't need options
            |--------------------------------------------------------------------------
            */

            $this->sections[$sectionIndex]['fields'][$fieldIndex]['options'] = [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Required
    |--------------------------------------------------------------------------
    */

    public function updateRequired(
        int $sectionIndex,
        int $fieldIndex,
        bool $required
    ): void {

        if (
            !isset(
                $this->sections[$sectionIndex]['fields'][$fieldIndex]
            )
        ) {
            return;
        }

        $this->sections[$sectionIndex]['fields'][$fieldIndex]['required'] =
            $required;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Options
    |--------------------------------------------------------------------------
    */

    public function updateOptions(
        int $sectionIndex,
        int $fieldIndex,
        string $value
    ): void {

        if (
            !isset(
                $this->sections[$sectionIndex]['fields'][$fieldIndex]
            )
        ) {
            return;
        }

        $parts = preg_split(
            '/\s*\|\s*/',
            $value
        );

        $options = [];

        foreach ($parts ?: [] as $option) {

            $option = trim($option);

            if ($option === '') {
                continue;
            }

            $options[] = [
                'label' => $option,
                'value' => $option,
            ];
        }

        $this->sections[$sectionIndex]['fields'][$fieldIndex]['options'] =
            $options;
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Field
    |--------------------------------------------------------------------------
    */

    public function removeField(
        int $sectionIndex,
        int $fieldIndex
    ): void {

        if (
            !isset(
                $this->sections[$sectionIndex]['fields'][$fieldIndex]
            )
        ) {
            return;
        }

        unset(
            $this->sections[$sectionIndex]['fields'][$fieldIndex]
        );

        $this->sections[$sectionIndex]['fields'] =
            array_values(
                $this->sections[$sectionIndex]['fields']
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Form
    |--------------------------------------------------------------------------
    */

    public function createForm()
    {
        /*
        |--------------------------------------------------------------------------
        | Validate fields
        |--------------------------------------------------------------------------
        */

        $hasFields = false;

        foreach ($this->sections as $section) {

            if (
                !empty(
                    $section['fields']
                )
            ) {
                $hasFields = true;
                break;
            }
        }

        if (!$hasFields) {

            $this->addError(
                'form',
                'Please keep at least one form field before creating the form.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Clean empty sections
        |--------------------------------------------------------------------------
        */

        $sections = [];

        foreach ($this->sections as $section) {

            if (
                empty(
                    $section['fields']
                )
            ) {
                continue;
            }

            $section['fields'] =
                array_values(
                    $section['fields']
                );

            $sections[] = $section;
        }

        if (empty($sections)) {

            $this->addError(
                'form',
                'Please keep at least one form field before creating the form.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Generate title
        |--------------------------------------------------------------------------
        */

        $title = pathinfo(
            $this->import->filename,
            PATHINFO_FILENAME
        );

        $title = str_replace(
            ['_', '-'],
            ' ',
            $title
        );

        $title = Str::title(
            trim($title)
        );

        if ($title === '') {
            $title = 'Imported Form';
        }

        /*
        |--------------------------------------------------------------------------
        | Generate unique slug
        |--------------------------------------------------------------------------
        */

        $baseSlug = Str::slug($title);

        if ($baseSlug === '') {
            $baseSlug = 'imported-form';
        }

        $slug = $baseSlug;

        $counter = 1;

        while (
            Form::where(
                'slug',
                $slug
            )->exists()
        ) {

            $slug =
                $baseSlug
                . '-'
                . $counter;

            $counter++;
        }

        /*
        |--------------------------------------------------------------------------
        | Build final schema
        |--------------------------------------------------------------------------
        */

        $schema = [

            'version' => '1.0',

            'title' => $title,

            'description' =>
                'Imported from '
                . $this->import->filename,

            'sections' => $sections,

            'settings' => [

                'success_message' =>
                    'Thank you for your submission.',

                'show_progress' =>
                    true,

                'allow_multiple' =>
                    false,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create Form
        |--------------------------------------------------------------------------
        */

        $form = Form::create([

            'title' =>
                $title,

            'slug' =>
                $slug,

            /*
            |----------------------------------------------------------------------
            | Imported forms start as drafts
            |----------------------------------------------------------------------
            */

            'status' =>
                'draft',

            'schema' =>
                $schema,

            'settings' =>
                $schema['settings'],

            'ai_generated' =>
                false,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update import record
        |--------------------------------------------------------------------------
        */

        $this->import->update([

            'status' =>
                'completed',

            'parsed_data' =>
                array_merge(
                    $this->import->parsed_data ?? [],
                    [
                        'sections' => $sections,
                    ]
                ),

            'schema' =>
                $schema,

            'errors' =>
                !empty($this->parserErrors)
                    ? $this->parserErrors
                    : null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        session()->flash(
            'success',
            'Form imported successfully. You can now edit it.'
        );

        /*
        |--------------------------------------------------------------------------
        | Redirect to Builder
        |--------------------------------------------------------------------------
        */

        return redirect()->route(
            'forms.builder',
            [
                'form' =>
                    $form->slug,
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
            'livewire.form-import-preview'
        )->layout('layouts.app', [
            'title' =>
                'Import Preview',
        ]);
    }
}