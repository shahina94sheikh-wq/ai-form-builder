<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class FormSchemaValidator
{
    public function validate(array $schema): array
    {
        Validator::make($schema, [
            'version' => ['required', 'string'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],

            'settings' => ['nullable', 'array'],

            'sections' => [
                'required',
                'array',
                'min:1',
            ],

            'sections.*.id' => [
                'required',
                'string',
            ],

            'sections.*.title' => [
                'required',
                'string',
            ],

            'sections.*.fields' => [
                'required',
                'array',
            ],

            'sections.*.fields.*.id' => [
                'required',
                'string',
            ],

            'sections.*.fields.*.key' => [
                'required',
                'string',
                'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/',
            ],

            'sections.*.fields.*.type' => [
                'required',
                'in:text,textarea,number,email,phone,date,select,radio,checkbox,file,heading,rating',
            ],

            'sections.*.fields.*.label' => [
                'required',
                'string',
                'max:200',
            ],

            'sections.*.fields.*.required' => [
                'boolean',
            ],

            'sections.*.fields.*.options' => [
                'nullable',
                'array',
            ],
            'sections.*.fields.*.validation' => [
                'nullable',
                'array',
            ],

            'sections.*.fields.*.validation.min' => [
                'nullable',
                'numeric',
            ],

            'sections.*.fields.*.validation.max' => [
                'nullable',
                'numeric',
            ],

            'sections.*.fields.*.validation.min_length' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'sections.*.fields.*.validation.max_length' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'sections.*.fields.*.validation.url' => [
                'nullable',
                'boolean',
            ],

            'sections.*.fields.*.validation.regex' => [
                'nullable',
                'string',
            ],

            'sections.*.fields.*.validation.file_types' => [
                'nullable',
                'string',
            ],

            'sections.*.fields.*.validation.file_size' => [
                'nullable',
                'integer',
                'min:1',
            ]
        ])->validate();

        $keys = [];

        foreach ($schema['sections'] as $section) {
            foreach ($section['fields'] as $field) {

                if (in_array($field['key'], $keys, true)) {
                    throw new InvalidArgumentException(
                        "Duplicate field key: {$field['key']}"
                    );
                }

                $keys[] = $field['key'];

                if (
                    in_array($field['type'], ['select', 'radio', 'checkbox'])
                    && empty($field['options'])
                ) {
                    throw new InvalidArgumentException(
                        "Field {$field['key']} requires options."
                    );
                }
            }
        }

        return $schema;
    }
}