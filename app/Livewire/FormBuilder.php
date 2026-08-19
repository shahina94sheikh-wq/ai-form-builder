<?php

namespace App\Livewire;

use App\Models\Form;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\On;

class FormBuilder extends Component
{
    public Form $form;

    public array $schema = [];

    public string $schemaJson = '';

    public array $fieldTypes = [
        'text' => 'Text Input',
        'textarea' => 'Textarea',
        'number' => 'Number',
        'email' => 'Email',
        'phone' => 'Phone',
        'date' => 'Date',
        'select' => 'Dropdown',
        'radio' => 'Radio',
        'checkbox' => 'Checkbox',
        'file' => 'File Upload',
        'heading' => 'Section Heading',
        'rating' => 'Rating',
    ];

    public ?string $selectedField = null;

    public array $selectedFieldData = [];

    /*
    |--------------------------------------------------------------------------
    | Conditional Logic Builder State
    |--------------------------------------------------------------------------
    */

    public ?string $logicSourceField = null;

    public string $logicOperator = 'equals';

    public string $logicValue = '';

    public string $logicAction = 'show';

    public ?string $logicTargetField = null;

    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(Form $form): void
    {
        $this->form = $form;

        $this->schema = $form->schema ?? [];

        /*
        |--------------------------------------------------------------------------
        | Make sure schema has required structure
        |--------------------------------------------------------------------------
        */

        $this->schema['version'] =
            $this->schema['version'] ?? '1.0';

        $this->schema['title'] =
            $this->schema['title'] ?? $form->title;

        $this->schema['description'] =
            $this->schema['description'] ?? '';

        $this->schema['sections'] =
            $this->schema['sections'] ?? [];

        $this->schema['settings'] =
            $this->schema['settings'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Conditional Logic
        |--------------------------------------------------------------------------
        |
        | Keep conditional logic at the root of the schema. Existing forms
        | without logic remain fully backward compatible.
        |
        */

        $this->schema['logic'] =
            is_array($this->schema['logic'] ?? null)
                ? $this->schema['logic']
                : [];

        /*
        |--------------------------------------------------------------------------
        | Create first section if none exists
        |--------------------------------------------------------------------------
        */

        if (empty($this->schema['sections'])) {
            $this->schema['sections'][] = [
                'id' => 'section_' . Str::random(8),
                'title' => 'Personal Information',
                'fields' => [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Make every section safe
        |--------------------------------------------------------------------------
        */

        foreach ($this->schema['sections'] as &$section) {

            $section['id'] =
                $section['id']
                ?? 'section_' . Str::random(8);

            $section['title'] =
                $section['title']
                ?? 'Untitled Section';

            $section['fields'] =
                $section['fields']
                ?? [];

            /*
            |--------------------------------------------------------------------------
            | Make existing fields backward compatible
            |--------------------------------------------------------------------------
            */

            foreach ($section['fields'] as &$field) {

                $field['id'] =
                    $field['id']
                    ?? 'field_' . Str::random(10);

                $field['type'] =
                    $field['type']
                    ?? 'text';

                $field['label'] =
                    $field['label']
                    ?? 'Untitled Field';

                $field['key'] =
                    $field['key']
                    ?? Str::snake(
                        $field['label']
                    ) . '_' . Str::lower(
                        Str::random(5)
                    );

                $field['placeholder'] =
                    $field['placeholder']
                    ?? '';

                $field['help'] =
                    $field['help']
                    ?? '';

                $field['default'] =
                    $field['default']
                    ?? '';

                $field['required'] =
                    $field['required']
                    ?? false;

                $field['options'] =
                    $field['options']
                    ?? [];

                /*
                |--------------------------------------------------------------------------
                | Validation
                |--------------------------------------------------------------------------
                | Keep old/imported schemas compatible while ensuring the
                | validation object always has the expected keys.
                */

                $validation =
                    is_array($field['validation'] ?? null)
                        ? $field['validation']
                        : [];

                $field['validation'] = [
                    'min' =>
                        $validation['min'] ?? null,

                    'max' =>
                        $validation['max'] ?? null,

                    'min_length' =>
                        $validation['min_length'] ?? null,

                    'max_length' =>
                        $validation['max_length'] ?? null,

                    'url' =>
                        $validation['url'] ?? null,

                    'regex' =>
                        $validation['regex'] ?? null,

                    'file_types' =>
                        $validation['file_types'] ?? null,

                    'file_size' =>
                        $validation['file_size'] ?? null,
                ];
            }

            unset($field);
        }

        unset($section);

        $this->syncJson();
    }

    /*
    |--------------------------------------------------------------------------
    | Add Field
    |--------------------------------------------------------------------------
    */

    public function addField(string $type): void
    {
        if (!isset($this->fieldTypes[$type])) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Add to first section
        |--------------------------------------------------------------------------
        */

        $sectionIndex = 0;

        $id = 'field_' . Str::random(10);

        $label = $this->fieldTypes[$type];

        $key =
            Str::snake($label)
            . '_'
            . Str::lower(Str::random(5));

        $field = [
            'id' => $id,
            'type' => $type,
            'label' => $label,
            'key' => $key,
            'placeholder' => '',
            'help' => '',
            'default' => '',
            'required' => false,
            'options' => [],
            'validation' => [
                'min' => null,
                'max' => null,
                'min_length' => null,
                'max_length' => null,
                'url' => null,
                'regex' => null,
                'file_types' => null,
                'file_size' => null,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Default options
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $type,
                ['select', 'radio', 'checkbox'],
                true
            )
        ) {
            $field['options'] = [
                [
                    'label' => 'Option 1',
                    'value' => 'option_1',
                ],
                [
                    'label' => 'Option 2',
                    'value' => 'option_2',
                ],
            ];
        }

        $this->schema['sections'][$sectionIndex]['fields'][] =
            $field;

        /*
        |--------------------------------------------------------------------------
        | Select newly added field
        |--------------------------------------------------------------------------
        */

        $this->selectedField = $id;

        $this->selectedFieldData = $field;

        $this->syncJson();
    }

    /*
    |--------------------------------------------------------------------------
    | Select Field
    |--------------------------------------------------------------------------
    */

    public function selectField(string $fieldId): void
    {
        foreach ($this->schema['sections'] as $section) {

            foreach ($section['fields'] as $field) {

                if ($field['id'] === $fieldId) {

                    $this->selectedField = $fieldId;

                    $this->selectedFieldData = $field;

                    return;
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update selected field
    |--------------------------------------------------------------------------
    */

    public function updatedSelectedFieldData(): void
    {
        if (!$this->selectedField) {
            return;
        }

        foreach (
            $this->schema['sections']
            as $sectionIndex => $section
        ) {

            foreach (
                $section['fields']
                as $fieldIndex => $field
            ) {

                if ($field['id'] === $this->selectedField) {

                    /*
                    |--------------------------------------------------------------------------
                    | Keep field ID and type safe
                    |--------------------------------------------------------------------------
                    */

                    $this->selectedFieldData['id'] =
                        $field['id'];

                    $this->selectedFieldData['type'] =
                        $field['type'];

                    $this->selectedFieldData['validation'] =
                        is_array(
                            $this->selectedFieldData['validation'] ?? null
                        )
                            ? $this->selectedFieldData['validation']
                            : [
                                'min' => null,
                                'max' => null,
                                'min_length' => null,
                                'max_length' => null,
                                'url' => null,
                                'regex' => null,
                                'file_types' => null,
                                'file_size' => null,
                            ];

                    $this->schema['sections']
                        [$sectionIndex]
                        ['fields']
                        [$fieldIndex] =
                        $this->selectedFieldData;

                    $this->syncJson();

                    return;
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate
    |--------------------------------------------------------------------------
    */

    public function duplicateField(string $fieldId): void
    {
        foreach (
            $this->schema['sections']
            as $sectionIndex => $section
        ) {

            foreach (
                $section['fields']
                as $fieldIndex => $field
            ) {

                if ($field['id'] !== $fieldId) {
                    continue;
                }

                $copy = $field;

                $copy['id'] =
                    'field_' . Str::random(10);

                $copy['key'] =
                    ($field['key'] ?? 'field')
                    . '_copy';

                array_splice(
                    $this->schema['sections']
                        [$sectionIndex]
                        ['fields'],
                    $fieldIndex + 1,
                    0,
                    [$copy]
                );

                $this->selectedField =
                    $copy['id'];

                $this->selectedFieldData =
                    $copy;

                $this->syncJson();

                return;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function deleteField(string $fieldId): void
    {
        foreach (
            $this->schema['sections']
            as $sectionIndex => $section
        ) {

            foreach (
                $section['fields']
                as $fieldIndex => $field
            ) {

                if ($field['id'] !== $fieldId) {
                    continue;
                }

                unset(
                    $this->schema['sections']
                        [$sectionIndex]
                        ['fields']
                        [$fieldIndex]
                );

                $this->schema['sections']
                    [$sectionIndex]
                    ['fields'] =
                    array_values(
                        $this->schema['sections']
                            [$sectionIndex]
                            ['fields']
                    );

                if ($this->selectedField === $fieldId) {

                    $this->selectedField = null;

                    $this->selectedFieldData = [];
                }

                $this->syncJson();

                return;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reorder
    |--------------------------------------------------------------------------
    */

    public function reorder(
        array $ids,
        string $sectionId = ''
    ): void {

        foreach (
            $this->schema['sections']
            as $sectionIndex => $section
        ) {

            if (
                $sectionId &&
                $section['id'] !== $sectionId
            ) {
                continue;
            }

            $fields = $section['fields'];

            $ordered = [];

            foreach ($ids as $id) {

                foreach ($fields as $field) {

                    if ($field['id'] === $id) {

                        $ordered[] = $field;

                        break;
                    }
                }
            }

            if (count($ordered) === count($fields)) {

                $this->schema['sections']
                    [$sectionIndex]
                    ['fields'] =
                    $ordered;
            }
        }

        $this->syncJson();
    }

    /*
    |--------------------------------------------------------------------------
    | JSON Sync
    |--------------------------------------------------------------------------
    */

    protected function syncJson(): void
    {
        $this->schemaJson = json_encode(
            $this->schema,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Schema
    |--------------------------------------------------------------------------
    |
    | Keeps older/imported schemas compatible without changing
    | their existing functionality.
    |
    */

        protected function normalizeSchema(
            array $schema
        ): array {

            $schema['version'] =
                $schema['version'] ?? '1.0';

            $schema['title'] =
                $schema['title'] ?? $this->form->title;

            $schema['description'] =
                $schema['description'] ?? '';

            $schema['sections'] =
                $schema['sections'] ?? [];

            $schema['settings'] =
                $schema['settings'] ?? [];

            /*
            |--------------------------------------------------------------------------
            | Conditional Logic
            |--------------------------------------------------------------------------
            |
            | Normalize the optional logic array so older/imported schemas
            | continue to work without modification.
            |
            */

            $schema['logic'] =
                is_array($schema['logic'] ?? null)
                    ? $schema['logic']
                    : [];

            foreach (
                $schema['sections']
                as $sectionIndex => &$section
            ) {

                if (!is_array($section)) {
                    continue;
                }

                $section['id'] =
                    $section['id']
                    ?? 'section_' . Str::random(8);

                $section['title'] =
                    $section['title']
                    ?? 'Untitled Section';

                $section['fields'] =
                    $section['fields']
                    ?? [];

                foreach (
                    $section['fields']
                    as $fieldIndex => &$field
                ) {

                    if (!is_array($field)) {
                        continue;
                    }

                    $field['id'] =
                        $field['id']
                        ?? 'field_' . Str::random(10);

                    $field['type'] =
                        $field['type']
                        ?? 'text';

                    $field['label'] =
                        $field['label']
                        ?? 'Untitled Field';

                    $field['key'] =
                        $field['key']
                        ?? Str::snake(
                            $field['label']
                        ) . '_' . Str::lower(
                            Str::random(5)
                        );

                    $field['placeholder'] =
                        $field['placeholder']
                        ?? '';

                    $field['help'] =
                        $field['help']
                        ?? '';

                    $field['default'] =
                        $field['default']
                        ?? '';

                    $field['required'] =
                        $field['required']
                        ?? false;

                    $field['options'] =
                        $field['options']
                        ?? [];

                    /*
                    |--------------------------------------------------------------------------
                    | Validation
                    |--------------------------------------------------------------------------
                    */

                    $validation =
                        is_array($field['validation'] ?? null)
                            ? $field['validation']
                            : [];

                    $field['validation'] = [
                        'min' =>
                            $validation['min'] ?? null,

                        'max' =>
                            $validation['max'] ?? null,

                        'min_length' =>
                            $validation['min_length'] ?? null,

                        'max_length' =>
                            $validation['max_length'] ?? null,

                        'url' =>
                            $validation['url'] ?? null,

                        'regex' =>
                            $validation['regex'] ?? null,

                        'file_types' =>
                            $validation['file_types'] ?? null,

                        'file_size' =>
                            $validation['file_size'] ?? null,
                    ];
                }

                unset($field);
            }

            unset($section);

            return $schema;
        }

    /*
    |--------------------------------------------------------------------------
    | Validate JSON Schema
    |--------------------------------------------------------------------------
    */

    protected function validateSchema(
        array $schema
    ): array {

        $errors = [];

        /*
        |--------------------------------------------------------------------------
        | Root structure
        |--------------------------------------------------------------------------
        */

        if (
            !isset($schema['version']) ||
            !is_string($schema['version'])
        ) {
            $errors[] =
                'Schema version is required.';
        }

        if (
            !isset($schema['title']) ||
            !is_string($schema['title']) ||
            trim($schema['title']) === ''
        ) {
            $errors[] =
                'Schema title is required.';
        }

        if (
            !isset($schema['sections']) ||
            !is_array($schema['sections'])
        ) {
            $errors[] =
                'Schema must contain a sections array.';

            return $errors;
        }

        /*
        |--------------------------------------------------------------------------
        | Allowed field types
        |--------------------------------------------------------------------------
        */

        $allowedTypes =
            array_keys($this->fieldTypes);

        /*
        |--------------------------------------------------------------------------
        | Sections
        |--------------------------------------------------------------------------
        */

        foreach (
            $schema['sections']
            as $sectionIndex => $section
        ) {

            if (!is_array($section)) {

                $errors[] =
                    "Section {$sectionIndex} must be an object.";

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Section ID
            |--------------------------------------------------------------------------
            */

            if (
                !isset($section['id']) ||
                !is_string($section['id']) ||
                trim($section['id']) === ''
            ) {
                $errors[] =
                    "Section {$sectionIndex} must have a valid id.";
            }

            /*
            |--------------------------------------------------------------------------
            | Section title
            |--------------------------------------------------------------------------
            */

            if (
                !isset($section['title']) ||
                !is_string($section['title']) ||
                trim($section['title']) === ''
            ) {
                $errors[] =
                    "Section {$sectionIndex} must have a title.";
            }

            /*
            |--------------------------------------------------------------------------
            | Fields
            |--------------------------------------------------------------------------
            */

            if (
                !isset($section['fields']) ||
                !is_array($section['fields'])
            ) {

                $errors[] =
                    "Section {$sectionIndex} must contain a fields array.";

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Fields validation
            |--------------------------------------------------------------------------
            */

            foreach (
                $section['fields']
                as $fieldIndex => $field
            ) {

                if (!is_array($field)) {

                    $errors[] =
                        "Section {$sectionIndex}, field {$fieldIndex} must be an object.";

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Field ID
                |--------------------------------------------------------------------------
                */

                if (
                    !isset($field['id']) ||
                    !is_string($field['id']) ||
                    trim($field['id']) === ''
                ) {

                    $errors[] =
                        "Section {$sectionIndex}, field {$fieldIndex} must have a valid id.";
                }

                /*
                |--------------------------------------------------------------------------
                | Field type
                |--------------------------------------------------------------------------
                */

                if (
                    !isset($field['type']) ||
                    !is_string($field['type'])
                ) {

                    $errors[] =
                        "Section {$sectionIndex}, field {$fieldIndex} must have a type.";

                } elseif (
                    !in_array(
                        $field['type'],
                        $allowedTypes,
                        true
                    )
                ) {

                    $errors[] =
                        "Section {$sectionIndex}, field {$fieldIndex} has unsupported field type: "
                        . $field['type'];
                }

                /*
                |--------------------------------------------------------------------------
                | Label
                |--------------------------------------------------------------------------
                */

                if (
                    !isset($field['label']) ||
                    !is_string($field['label']) ||
                    trim($field['label']) === ''
                ) {

                    $errors[] =
                        "Section {$sectionIndex}, field {$fieldIndex} must have a label.";
                }

                /*
                |--------------------------------------------------------------------------
                | Field key
                |--------------------------------------------------------------------------
                */

                if (
                    !isset($field['key']) ||
                    !is_string($field['key']) ||
                    trim($field['key']) === ''
                ) {

                    $errors[] =
                        "Section {$sectionIndex}, field {$fieldIndex} must have a key.";
                }

                /*
                |--------------------------------------------------------------------------
                | Required
                |--------------------------------------------------------------------------
                */

                if (
                    isset($field['required']) &&
                    !is_bool($field['required'])
                ) {

                    /*
                    | Livewire / imported data may contain 0/1.
                    | Accept those as well.
                    */

                    if (
                        !in_array(
                            $field['required'],
                            [0, 1, '0', '1'],
                            true
                        )
                    ) {

                        $errors[] =
                            "Section {$sectionIndex}, field {$fieldIndex} required must be boolean.";
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Options
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $field['type'] ?? '',
                        ['select', 'radio', 'checkbox'],
                        true
                    )
                ) {

                    if (
                        !isset($field['options']) ||
                        !is_array($field['options'])
                    ) {

                        $errors[] =
                            "Section {$sectionIndex}, field {$fieldIndex} must contain options.";

                    } else {

                        foreach (
                            $field['options']
                            as $optionIndex => $option
                        ) {

                            if (!is_array($option)) {

                                $errors[] =
                                    "Section {$sectionIndex}, field {$fieldIndex}, option {$optionIndex} is invalid.";

                                continue;
                            }

                            if (
                                !isset($option['label']) ||
                                !is_string($option['label'])
                            ) {

                                $errors[] =
                                    "Section {$sectionIndex}, field {$fieldIndex}, option {$optionIndex} must have a label.";
                            }

                            if (
                                !isset($option['value']) ||
                                !is_string($option['value'])
                            ) {

                                $errors[] =
                                    "Section {$sectionIndex}, field {$fieldIndex}, option {$optionIndex} must have a value.";
                            }
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Validation object
                |--------------------------------------------------------------------------
                */

                if (
                    isset($field['validation']) &&
                    !is_array($field['validation'])
                ) {

                    $errors[] =
                        "Section {$sectionIndex}, field {$fieldIndex} validation must be an object.";
                }

                /*
                |--------------------------------------------------------------------------
                | Validation values
                |--------------------------------------------------------------------------
                */

                if (
                    isset($field['validation']) &&
                    is_array($field['validation'])
                ) {

                    $validation =
                        $field['validation'];

                    $numericKeys = [
                        'min',
                        'max',
                        'min_length',
                        'max_length',
                        'file_size',
                    ];

                    foreach (
                        $numericKeys as $validationKey
                    ) {

                        if (
                            isset(
                                $validation[$validationKey]
                            ) &&
                            $validation[$validationKey] !== '' &&
                            !is_numeric(
                                $validation[$validationKey]
                            )
                        ) {

                            $errors[] =
                                "Section {$sectionIndex}, field {$fieldIndex} validation {$validationKey} must be numeric.";
                        }
                    }

                    if (
                        isset($validation['regex']) &&
                        !is_string(
                            $validation['regex']
                        )
                    ) {

                        $errors[] =
                            "Section {$sectionIndex}, field {$fieldIndex} regex must be a string.";
                    }

                    if (
                        isset($validation['url']) &&
                        !in_array(
                            $validation['url'],
                            [true, false, 0, 1, '0', '1'],
                            true
                        )
                    ) {

                        $errors[] =
                            "Section {$sectionIndex}, field {$fieldIndex} URL validation must be boolean.";
                    }

                    if (
                        isset($validation['file_types']) &&
                        !is_string(
                            $validation['file_types']
                        )
                    ) {

                        $errors[] =
                            "Section {$sectionIndex}, field {$fieldIndex} file types must be a string.";
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Conditional Logic validation
        |--------------------------------------------------------------------------
        */

        if (
            isset($schema['logic']) &&
            !is_array($schema['logic'])
        ) {
            $errors[] = 'Schema logic must be an array.';
        }

        $fieldKeys = [];

        foreach ($schema['sections'] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if (
                    isset($field['id']) &&
                    is_string($field['id']) &&
                    trim($field['id']) !== ''
                ) {
                    $fieldKeys[$field['id']] = true;
                }

                if (
                    isset($field['key']) &&
                    is_string($field['key']) &&
                    trim($field['key']) !== ''
                ) {
                    $fieldKeys[$field['key']] = true;
                }
            }
        }

        $allowedLogicOperators = [
            'equals',
            'not_equals',
            'contains',
            'not_contains',
            'greater_than',
            'less_than',
            'greater_or_equal',
            'less_or_equal',
        ];

        $allowedLogicActions = [
            'show',
            'hide',
        ];

        foreach ($schema['logic'] ?? [] as $logicIndex => $rule) {

            if (!is_array($rule)) {
                $errors[] =
                    "Logic rule {$logicIndex} must be an object.";

                continue;
            }

            $when = $rule['when'] ?? null;

            if (!is_array($when)) {
                $errors[] =
                    "Logic rule {$logicIndex} must contain a when object.";

                continue;
            }

            $source =
                $when['field']
                ?? null;

            if (
                !is_string($source) ||
                !isset($fieldKeys[$source])
            ) {
                $errors[] =
                    "Logic rule {$logicIndex} references an invalid source field.";
            }

            $operator =
                $when['operator']
                ?? null;

            if (
                !is_string($operator) ||
                !in_array(
                    $operator,
                    $allowedLogicOperators,
                    true
                )
            ) {
                $errors[] =
                    "Logic rule {$logicIndex} has an unsupported operator.";
            }

            if (!array_key_exists('value', $when)) {
                $errors[] =
                    "Logic rule {$logicIndex} must contain a comparison value.";
            }

            $action =
                $rule['action']
                ?? null;

            if (
                !is_string($action) ||
                !in_array(
                    $action,
                    $allowedLogicActions,
                    true
                )
            ) {
                $errors[] =
                    "Logic rule {$logicIndex} has an unsupported action.";
            }

            $target =
                $rule['target']
                ?? null;

            if (
                !is_string($target) ||
                !isset($fieldKeys[$target])
            ) {
                $errors[] =
                    "Logic rule {$logicIndex} references an invalid target field.";
            }

            if (
                is_string($source) &&
                is_string($target) &&
                $source === $target
            ) {
                $errors[] =
                    "Logic rule {$logicIndex} cannot target its source field.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate field keys
        |--------------------------------------------------------------------------
        */

        $keys = [];

        foreach (
            $schema['sections']
            as $section
        ) {

            foreach (
                $section['fields'] ?? []
                as $field
            ) {

                if (
                    !isset($field['key']) ||
                    !is_string($field['key'])
                ) {
                    continue;
                }

                if (
                    in_array(
                        $field['key'],
                        $keys,
                        true
                    )
                ) {

                    $errors[] =
                        "Duplicate field key detected: "
                        . $field['key'];

                } else {

                    $keys[] =
                        $field['key'];
                }
            }
        }

        return $errors;
    }

    /*
    |--------------------------------------------------------------------------
    | Apply JSON
    |--------------------------------------------------------------------------
    */

    public function updateFromJson(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Decode JSON
        |--------------------------------------------------------------------------
        */

        $decoded = json_decode(
            $this->schemaJson,
            true
        );

        if (
            !is_array($decoded) ||
            json_last_error() !== JSON_ERROR_NONE
        ) {

            $this->addError(
                'schemaJson',
                'Invalid JSON schema: ' . json_last_error_msg()
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize backward-compatible properties
        |--------------------------------------------------------------------------
        */

        $decoded =
            $this->normalizeSchema(
                $decoded
            );

        /*
        |--------------------------------------------------------------------------
        | Validate schema
        |--------------------------------------------------------------------------
        */

        $validationErrors =
            $this->validateSchema(
                $decoded
            );

        if (!empty($validationErrors)) {

            $this->addError(
                'schemaJson',
                implode(
                    ' ',
                    $validationErrors
                )
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Apply schema
        |--------------------------------------------------------------------------
        */

        $this->schema = $decoded;

        /*
        |--------------------------------------------------------------------------
        | Reset selected field after JSON update
        |--------------------------------------------------------------------------
        */

        $this->selectedField = null;

        $this->selectedFieldData = [];

        $this->syncJson();

        $this->resetErrorBag(
            'schemaJson'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    public function saveBuilder(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Decode JSON schema
        |--------------------------------------------------------------------------
        */

        $decoded = json_decode(
            $this->schemaJson,
            true
        );

        if (
            !is_array($decoded) ||
            json_last_error() !== JSON_ERROR_NONE
        ) {

            $this->addError(
                'schemaJson',
                'Invalid JSON schema: ' . json_last_error_msg()
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize schema
        |--------------------------------------------------------------------------
        */

        $decoded =
            $this->normalizeSchema(
                $decoded
            );

        /*
        |--------------------------------------------------------------------------
        | Validate complete schema
        |--------------------------------------------------------------------------
        */

        $validationErrors =
            $this->validateSchema(
                $decoded
            );

        if (!empty($validationErrors)) {

            $this->addError(
                'schemaJson',
                implode(
                    ' ',
                    $validationErrors
                )
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Keep title synchronized
        |--------------------------------------------------------------------------
        */

        $decoded['title'] =
            $this->form->title;

        /*
        |--------------------------------------------------------------------------
        | Get current schema
        |--------------------------------------------------------------------------
        */

        $oldSchema =
            $this->form->schema ?? [];

        /*
        |--------------------------------------------------------------------------
        | Check whether schema changed
        |--------------------------------------------------------------------------
        */

        $schemaChanged =
            json_encode(
                $oldSchema,
                JSON_UNESCAPED_SLASHES
            ) !==
            json_encode(
                $decoded,
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
        | Save form
        |--------------------------------------------------------------------------
        */

        $this->form->update([
            'schema' => $decoded,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create version
        |--------------------------------------------------------------------------
        |
        | 1. If there are NO versions -> create Version 1
        | 2. If schema changed -> create next version
        | 3. If nothing changed and versions exist -> don't create duplicate
        |
        */

        if (
            !$hasVersions ||
            $schemaChanged
        ) {

            $version =
                $this->form
                    ->nextVersionNumber();

            $this->form
                ->versions()
                ->create([
                    'version' =>
                        $version,

                    'schema' =>
                        $decoded,

                    'created_by' =>
                        auth()->id(),
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Synchronize Livewire state
        |--------------------------------------------------------------------------
        */

        $this->schema =
            $decoded;

        $this->syncJson();

        /*
        |--------------------------------------------------------------------------
        | Clear errors
        |--------------------------------------------------------------------------
        */

        $this->resetErrorBag(
            'schemaJson'
        );

        /*
        |--------------------------------------------------------------------------
        | Success message
        |--------------------------------------------------------------------------
        */

        if (!$hasVersions) {

            session()->flash(
                'success',
                'Form saved successfully. Version 1 created.'
            );

        } elseif ($schemaChanged) {

            session()->flash(
                'success',
                'Form saved successfully. New version created.'
            );

        } else {

            session()->flash(
                'success',
                'Form saved successfully.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Version Restored
    |--------------------------------------------------------------------------
    */

    #[\Livewire\Attributes\On('form-version-restored')]
    public function refreshAfterVersionRestore(): void
    {
        $this->form->refresh();

        $this->schema =
            $this->form->schema ?? [];

        $this->syncJson();

        $this->selectedField = null;

        $this->selectedFieldData = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Conditional Logic
    |--------------------------------------------------------------------------
    */

    public function addLogicRule(): void
    {
        $this->resetErrorBag('schemaJson');

        if (
            !$this->logicSourceField ||
            !$this->logicTargetField
        ) {
            $this->addError(
                'logic',
                'Please select both the source and target fields.'
            );

            return;
        }

        if (
            $this->logicSourceField ===
            $this->logicTargetField
        ) {
            $this->addError(
                'logic',
                'The source and target fields must be different.'
            );

            return;
        }

        $allowedOperators = [
            'equals',
            'not_equals',
            'contains',
            'not_contains',
            'greater_than',
            'less_than',
            'greater_or_equal',
            'less_or_equal',
        ];

        $allowedActions = [
            'show',
            'hide',
        ];

        if (
            !in_array(
                $this->logicOperator,
                $allowedOperators,
                true
            )
        ) {
            $this->addError(
                'logic',
                'Invalid conditional logic operator.'
            );

            return;
        }

        if (
            !in_array(
                $this->logicAction,
                $allowedActions,
                true
            )
        ) {
            $this->addError(
                'logic',
                'Invalid conditional logic action.'
            );

            return;
        }

        if (
            trim($this->logicValue) === ''
        ) {
            $this->addError(
                'logic',
                'Please provide a comparison value.'
            );

            return;
        }

        $this->schema['logic'] =
            is_array($this->schema['logic'] ?? null)
                ? $this->schema['logic']
                : [];

        $this->schema['logic'][] = [
            'when' => [
                'field' => $this->logicSourceField,
                'operator' => $this->logicOperator,
                'value' => $this->logicValue,
            ],
            'action' => $this->logicAction,
            'target' => $this->logicTargetField,
        ];

        $validationErrors =
            $this->validateSchema($this->schema);

        if (!empty($validationErrors)) {
            array_pop($this->schema['logic']);

            $this->addError(
                'logic',
                implode(' ', $validationErrors)
            );

            return;
        }

        $this->resetLogicBuilder();

        $this->syncJson();
    }

    public function removeLogicRule(int $index): void
    {
        if (
            !isset(
                $this->schema['logic'][$index]
            )
        ) {
            return;
        }

        unset(
            $this->schema['logic'][$index]
        );

        $this->schema['logic'] =
            array_values(
                $this->schema['logic']
            );

        $this->syncJson();
    }

    public function resetLogicBuilder(): void
    {
        $this->logicSourceField = null;
        $this->logicOperator = 'equals';
        $this->logicValue = '';
        $this->logicAction = 'show';
        $this->logicTargetField = null;

        $this->resetErrorBag('logic');
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view(
            'livewire.form-builder'
        )->layout(
            'layouts.app',
            [
                'title' =>
                    $this->form->title
                    . ' - Builder',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Add Option
    |--------------------------------------------------------------------------
    */

    public function addOption(): void
    {
        if (!$this->selectedField) {
            return;
        }

        $this->selectedFieldData['options'] =
            $this->selectedFieldData['options']
            ?? [];

        $number =
            count(
                $this->selectedFieldData['options']
            ) + 1;

        $this->selectedFieldData['options'][] = [
            'label' =>
                'Option ' . $number,

            'value' =>
                'option_' . $number,
        ];

        $this->updatedSelectedFieldData();
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Option
    |--------------------------------------------------------------------------
    */

    public function removeOption(
        int $index
    ): void {

        if (
            !isset(
                $this->selectedFieldData['options']
                    [$index]
            )
        ) {
            return;
        }

        unset(
            $this->selectedFieldData['options']
                [$index]
        );

        $this->selectedFieldData['options'] =
            array_values(
                $this->selectedFieldData['options']
            );

        $this->updatedSelectedFieldData();
    }

    /*
    |--------------------------------------------------------------------------
    | Check Publishable Fields
    |--------------------------------------------------------------------------
    */

    protected function hasPublishableFields(
        array $schema
    ): bool {

        foreach (
            $schema['sections'] ?? []
            as $section
        ) {

            foreach (
                $section['fields'] ?? []
                as $field
            ) {

                if (
                    ($field['type'] ?? '')
                    === 'heading'
                ) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Publish
    |--------------------------------------------------------------------------
    */

    public function publish(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Make sure latest builder state is used
        |--------------------------------------------------------------------------
        */

        $decoded = json_decode(
            $this->schemaJson,
            true
        );

        if (
            !is_array($decoded) ||
            json_last_error() !== JSON_ERROR_NONE
        ) {

            $this->addError(
                'schemaJson',
                'Invalid JSON schema: ' . json_last_error_msg()
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize schema
        |--------------------------------------------------------------------------
        */

        $decoded =
            $this->normalizeSchema(
                $decoded
            );

        /*
        |--------------------------------------------------------------------------
        | Validate complete schema
        |--------------------------------------------------------------------------
        */

        $validationErrors =
            $this->validateSchema(
                $decoded
            );

        if (!empty($validationErrors)) {

            $this->addError(
                'schemaJson',
                implode(
                    ' ',
                    $validationErrors
                )
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Validate at least one actual field
        |--------------------------------------------------------------------------
        */

        if (!$this->hasPublishableFields($decoded)) {

            $this->addError(
                'publish',
                'Please add at least one field before publishing the form.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Keep title synchronized
        |--------------------------------------------------------------------------
        */

        $decoded['title'] =
            $this->form->title;

        /*
        |--------------------------------------------------------------------------
        | Get current schema
        |--------------------------------------------------------------------------
        */

        $oldSchema =
            $this->form->schema ?? [];

        /*
        |--------------------------------------------------------------------------
        | Check whether schema changed
        |--------------------------------------------------------------------------
        */

        $schemaChanged =
            json_encode(
                $oldSchema,
                JSON_UNESCAPED_SLASHES
            ) !==
            json_encode(
                $decoded,
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
        | Update form
        |--------------------------------------------------------------------------
        */

        $this->form->update([
            'schema' =>
                $decoded,

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

        if (
            !$hasVersions ||
            $schemaChanged
        ) {

            $version =
                $this->form
                    ->nextVersionNumber();

            $this->form
                ->versions()
                ->create([
                    'version' =>
                        $version,

                    'schema' =>
                        $decoded,

                    'created_by' =>
                        auth()->id(),
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Synchronize Livewire state
        |--------------------------------------------------------------------------
        */

        $this->schema =
            $decoded;

        $this->syncJson();

        /*
        |--------------------------------------------------------------------------
        | Clear errors
        |--------------------------------------------------------------------------
        */

        $this->resetErrorBag(
            'schemaJson'
        );

        $this->resetErrorBag(
            'publish'
        );

        /*
        |--------------------------------------------------------------------------
        | Success message
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
        | Redirect to public form
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
}