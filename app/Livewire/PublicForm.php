<?php

namespace App\Livewire;

use App\Models\Form;
use Livewire\Component;
use Livewire\WithFileUploads;

class PublicForm extends Component
{
    use WithFileUploads;

    public Form $form;

    public array $data = [];

    public int $currentStep = 0;


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(Form $form): void
    {
        abort_if(
            $form->status !== 'published',
            404
        );

        $this->form = $form;

        /*
        |--------------------------------------------------------------------------
        | Initialize checkbox fields
        |--------------------------------------------------------------------------
        */

        foreach (
            $this->form->schema['sections'] ?? []
            as $section
        ) {

            foreach (
                $section['fields'] ?? []
                as $field
            ) {

                $fieldKey = $this->getFieldKey($field);

                if (!$fieldKey) {
                    continue;
                }

                if (
                    ($field['type'] ?? '') === 'checkbox'
                ) {

                    $this->data[$fieldKey] = [];
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Get field key
    |--------------------------------------------------------------------------
    |
    | Manual forms normally use "key".
    | Imported forms may use "id".
    |
    */

    protected function getFieldKey(array $field): ?string
    {
        return $field['key']
            ?? $field['id']
            ?? null;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize field type
    |--------------------------------------------------------------------------
    |
    | Imported Excel files can contain:
    |
    | dropdown
    | choice
    | select
    |
    | We treat them as select fields.
    |
    */

    protected function normalizeFieldType(?string $type): string
    {
        $type = strtolower(
            trim($type ?? '')
        );

        return match ($type) {

            'dropdown',
            'drop-down',
            'choice',
            'choices',
            'select' => 'select',

            'radio',
            'radio button',
            'single choice' => 'radio',

            'checkbox',
            'checkboxes',
            'multiple choice' => 'checkbox',

            'text',
            'textbox',
            'text box',
            'text input',
            'input' => 'text',

            'textarea',
            'text area',
            'long text' => 'textarea',

            'email',
            'e-mail' => 'email',

            'number',
            'numeric',
            'integer' => 'number',

            'phone',
            'telephone',
            'mobile' => 'phone',

            'date' => 'date',

            'file',
            'upload',
            'file upload' => 'file',

            default => $type ?: 'text',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Get option values
    |--------------------------------------------------------------------------
    */

    protected function getOptionValues(array $field): array
    {
        return collect(
            $field['options'] ?? []
        )
            ->map(function ($option) {

                if (is_array($option)) {

                    return $option['value']
                        ?? $option['label']
                        ?? null;
                }

                return $option;
            })
            ->filter(function ($value) {
                return $value !== null
                    && $value !== '';
            })
            ->values()
            ->all();
    }


    /*
    |--------------------------------------------------------------------------
    | Conditional Logic
    |--------------------------------------------------------------------------
    |
    | Logic rules are stored in the form schema. A rule may reference a
    | field by either its id or its key so manual and imported forms work
    | consistently.
    |
    */

    protected function allFields(): array
    {
        $fields = [];

        foreach ($this->form->schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if (is_array($field)) {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

    protected function findFieldByReference(string $reference): ?array
    {
        foreach ($this->allFields() as $field) {
            if (
                ($field['id'] ?? null) === $reference ||
                ($field['key'] ?? null) === $reference
            ) {
                return $field;
            }
        }

        return null;
    }

    protected function getLogicValue(array $field): mixed
    {
        $key = $this->getFieldKey($field);

        if (!$key) {
            return null;
        }

        return $this->data[$key] ?? null;
    }

    protected function compareLogicValues(
        mixed $actual,
        string $operator,
        mixed $expected
    ): bool {
        if (is_array($actual)) {
            $actualValues = array_map(
                static fn ($value) => (string) $value,
                $actual
            );

            $expectedValue = (string) $expected;

            return match ($operator) {
                'equals' =>
                    count($actualValues) === 1 &&
                    $actualValues[0] === $expectedValue,

                'not_equals' =>
                    count($actualValues) !== 1 ||
                    $actualValues[0] !== $expectedValue,

                'contains' =>
                    in_array($expectedValue, $actualValues, true),

                'not_contains' =>
                    !in_array($expectedValue, $actualValues, true),

                default => false,
            };
        }

        $actualString = is_scalar($actual)
            ? (string) $actual
            : '';

        $expectedString = is_scalar($expected)
            ? (string) $expected
            : '';

        return match ($operator) {
            'equals' =>
                $actualString === $expectedString,

            'not_equals' =>
                $actualString !== $expectedString,

            'contains' =>
                $expectedString !== '' &&
                str_contains(
                    strtolower($actualString),
                    strtolower($expectedString)
                ),

            'not_contains' =>
                $expectedString === '' ||
                !str_contains(
                    strtolower($actualString),
                    strtolower($expectedString)
                ),

            'greater_than' =>
                is_numeric($actual) &&
                is_numeric($expected) &&
                (float) $actual > (float) $expected,

            'less_than' =>
                is_numeric($actual) &&
                is_numeric($expected) &&
                (float) $actual < (float) $expected,

            'greater_or_equal' =>
                is_numeric($actual) &&
                is_numeric($expected) &&
                (float) $actual >= (float) $expected,

            'less_or_equal' =>
                is_numeric($actual) &&
                is_numeric($expected) &&
                (float) $actual <= (float) $expected,

            default => false,
        };
    }

    protected function logicRuleMatches(array $rule): bool
    {
        $sourceReference =
            $rule['when']['field'] ?? null;

        $operator =
            $rule['when']['operator'] ?? null;

        if (
            !is_string($sourceReference) ||
            !is_string($operator)
        ) {
            return false;
        }

        $sourceField =
            $this->findFieldByReference($sourceReference);

        if (!$sourceField) {
            return false;
        }

        return $this->compareLogicValues(
            $this->getLogicValue($sourceField),
            $operator,
            $rule['when']['value'] ?? ''
        );
    }

    public function isFieldVisible(array $field): bool
    {
        $fieldReference =
            $field['id']
            ?? $field['key']
            ?? null;

        if (!$fieldReference) {
            return true;
        }

        $rules = $this->form->schema['logic'] ?? [];

        if (!is_array($rules) || empty($rules)) {
            return true;
        }

        $hasShowRule = false;
        $showRuleMatched = false;

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $target =
                $rule['target'] ?? null;

            if (
                !is_string($target) ||
                !in_array(
                    $target,
                    [
                        $fieldReference,
                        $field['id'] ?? null,
                        $field['key'] ?? null,
                    ],
                    true
                )
            ) {
                continue;
            }

            $action =
                $rule['action'] ?? 'show';

            if ($action === 'show') {
                $hasShowRule = true;

                if ($this->logicRuleMatches($rule)) {
                    $showRuleMatched = true;
                }
            }

            if (
                $action === 'hide' &&
                $this->logicRuleMatches($rule)
            ) {
                return false;
            }
        }

        if ($hasShowRule) {
            return $showRuleMatched;
        }

        return true;
    }

    protected function isFieldActive(array $field): bool
    {
        return $this->isFieldVisible($field);
    }


    /*
    |--------------------------------------------------------------------------
    | Validation rules
    |--------------------------------------------------------------------------
    */

    protected function buildFieldRules(array $field): array
    {
        $fieldRules = [];

        if ($field['required'] ?? false) {
            $fieldRules[] = 'required';
        } else {
            $fieldRules[] = 'nullable';
        }

        $type = $this->normalizeFieldType($field['type'] ?? '');

        switch ($type) {
            case 'email':
                $fieldRules[] = 'email';
                break;

            case 'number':
                $fieldRules[] = 'numeric';
                break;

            case 'phone':
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:30';
                break;

            case 'date':
                $fieldRules[] = 'date';
                break;

            case 'file':
                $fieldRules[] = 'file';

                $validation = is_array($field['validation'] ?? null)
                    ? $field['validation']
                    : [];

                $fileSize = $validation['file_size'] ?? null;

                if ($fileSize !== '' && is_numeric($fileSize) && (float) $fileSize > 0) {
                    $fieldRules[] = 'max:' . (int) $fileSize;
                } else {
                    // Preserve the previous 10 MB default for old schemas.
                    $fieldRules[] = 'max:10240';
                }

                $fileTypes = $this->normalizeFileTypes(
                    $validation['file_types'] ?? null
                );

                if ($fileTypes !== []) {
                    $fieldRules[] = 'mimes:' . implode(',', $fileTypes);
                }
                break;

            case 'select':
            case 'radio':
                $options = $this->getOptionValues($field);

                if ($options) {
                    $fieldRules[] = 'in:' . implode(',', $options);
                }
                break;

            case 'checkbox':
                /*
                 * Checkbox values are submitted as an array.
                 * Allowed values are validated on data.<key>.* below.
                 */
                $fieldRules[] = 'array';
                break;
        }

        $validation = is_array($field['validation'] ?? null)
            ? $field['validation']
            : [];

        if (isset($validation['min']) && $validation['min'] !== '' && is_numeric($validation['min'])) {
            $fieldRules[] = 'min:' . $validation['min'];
        }

        if (isset($validation['max']) && $validation['max'] !== '' && is_numeric($validation['max'])) {
            $fieldRules[] = 'max:' . $validation['max'];
        }

        if (isset($validation['min_length']) && $validation['min_length'] !== '' && is_numeric($validation['min_length'])) {
            $fieldRules[] = 'min:' . (int) $validation['min_length'];
        }

        if (isset($validation['max_length']) && $validation['max_length'] !== '' && is_numeric($validation['max_length'])) {
            $fieldRules[] = 'max:' . (int) $validation['max_length'];
        }

        if (($validation['url'] ?? false) === true || in_array($validation['url'] ?? null, [1, '1', 'true', 'on'], true)) {
            $fieldRules[] = 'url';
        }

        if (!empty($validation['regex'])) {
            $regex = trim((string) $validation['regex']);

            if (str_starts_with($regex, '/') && str_ends_with($regex, '/')) {
                $regex = substr($regex, 1, -1);
            }

            $regex = '~' . $regex . '~';

            if (@preg_match($regex, '') !== false) {
                $fieldRules[] = 'regex:' . $regex;
            }
        }

        return $fieldRules;
    }

    protected function normalizeFileTypes(mixed $fileTypes): array
    {
        if (!is_string($fileTypes) || trim($fileTypes) === '') {
            return [];
        }

        return collect(preg_split('/[,|\s]+/', $fileTypes) ?: [])
            ->map(fn ($type) => strtolower(trim((string) $type)))
            ->map(fn ($type) => ltrim($type, '.'))
            ->filter(fn ($type) => $type !== '' && preg_match('/^[a-z0-9]+$/', $type))
            ->unique()
            ->values()
            ->all();
    }

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->form->schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $fieldKey = $this->getFieldKey($field);

                if (!$fieldKey || !$this->isFieldActive($field)) {
                    continue;
                }

                $rules['data.' . $fieldKey] = $this->buildFieldRules($field);

                /*
                 * Checkbox values are arrays. Validate every selected
                 * option against the field's configured options.
                 */
                if ($this->normalizeFieldType($field['type'] ?? '') === 'checkbox') {
                    $options = $this->getOptionValues($field);

                    if ($options !== []) {
                        $rules['data.' . $fieldKey . '.*'] = [
                            'in:' . implode(',', $options),
                        ];
                    }
                }
            }
        }

        return $rules;
    }

    /*
    |--------------------------------------------------------------------------
    | Submit
    |--------------------------------------------------------------------------
    */

      public function submit()
{
    /*
    |--------------------------------------------------------------------------
    | Validate
    |--------------------------------------------------------------------------
    |
    | If validation fails, Livewire automatically stays on the current
    | public form and displays the validation errors.
    |
    */

    $this->validate($this->rules());

    try {

        /*
        |--------------------------------------------------------------------------
        | Prepare submission data
        |--------------------------------------------------------------------------
        */

        $stored = $this->data;

        /*
        |--------------------------------------------------------------------------
        | Remove data for conditionally hidden fields
        |--------------------------------------------------------------------------
        |
        | A hidden field must not be persisted, even if it was previously
        | filled before its condition changed.
        |
        */

        foreach ($this->form->schema['sections'] ?? [] as $section) {

            foreach ($section['fields'] ?? [] as $field) {

                $key = $this->getFieldKey($field);

                if (
                    $key &&
                    !$this->isFieldActive($field)
                ) {
                    unset($stored[$key]);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Store uploaded files
        |--------------------------------------------------------------------------
        */

        foreach ($this->form->schema['sections'] ?? [] as $section) {

            foreach ($section['fields'] ?? [] as $field) {

                if (($field['type'] ?? '') !== 'file') {
                    continue;
                }

                if (!$this->isFieldActive($field)) {
                    continue;
                }

                $key = $this->getFieldKey($field);

                if (
                    !$key ||
                    !isset($this->data[$key]) ||
                    !$this->data[$key]
                ) {
                    continue;
                }

                $stored[$key] = $this->data[$key]
                    ->store('form-submissions');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Create submission
        |--------------------------------------------------------------------------
        */

        $this->form->submissions()->create([
            'data' => $stored,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Success message
        |--------------------------------------------------------------------------
        */

        session()->flash(
            'success',
            $this->form->schema['settings']['success_message']
                ?? 'Thank you for your submission.'
        );

        /*
        |--------------------------------------------------------------------------
        | Redirect to Submission List
        |--------------------------------------------------------------------------
        */

        return redirect()->route(
            'forms.submissions',
            [
                'form' => $this->form->slug,
            ]
        );

    } catch (\Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | Log unexpected error
        |--------------------------------------------------------------------------
        */

        report($e);

        /*
        |--------------------------------------------------------------------------
        | Stay on current form
        |--------------------------------------------------------------------------
        */

        $this->addError(
            'form',
            'We could not submit your form right now. Please try again.'
        );

        return;
    }
}


    /*
    |--------------------------------------------------------------------------
    | Next step
    |--------------------------------------------------------------------------
    */

    public function nextStep(): void
    {
        $this->validate(
            $this->rulesForCurrentStep()
        );

        $totalSteps =
            count(
                $this->form
                    ->schema['sections']
                ?? []
            );

        if (
            $this->currentStep
            <
            $totalSteps - 1
        ) {

            $this->currentStep++;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Current step validation
    |--------------------------------------------------------------------------
    */

    protected function rulesForCurrentStep(): array
    {
        $rules = [];
        $sections = $this->form->schema['sections'] ?? [];
        $section = $sections[$this->currentStep] ?? null;

        if (!$section) {
            return [];
        }

        foreach ($section['fields'] ?? [] as $field) {
            $fieldKey = $this->getFieldKey($field);

            if (!$fieldKey || !$this->isFieldActive($field)) {
                continue;
            }

            $rules['data.' . $fieldKey] = $this->buildFieldRules($field);

            /*
             * Checkbox values are arrays. Validate every selected
             * option against the field's configured options.
             */
            if ($this->normalizeFieldType($field['type'] ?? '') === 'checkbox') {
                $options = $this->getOptionValues($field);

                if ($options !== []) {
                    $rules['data.' . $fieldKey . '.*'] = [
                        'in:' . implode(',', $options),
                    ];
                }
            }
        }

        return $rules;
    }

    /*
    |--------------------------------------------------------------------------
    | Previous step
    |--------------------------------------------------------------------------
    */

    public function previousStep(): void
    {
        if (
            $this->currentStep > 0
        ) {

            $this->currentStep--;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view(
            'livewire.public-form'
        )->layout(
            'layouts.app',
            [
                'title' =>
                    $this->form->title,
            ]
        );
    }
}