<?php

namespace App\FormBuilder;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class FormSchema
{
    public static function validate(array $schema): array
    {
        $validator = Validator::make($schema, [
            'version' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'settings' => ['nullable', 'array'],

            'settings.submit_button_text' => [
                'nullable',
                'string',
                'max:100',
            ],

            'settings.success_message' => [
                'nullable',
                'string',
            ],

            'fields' => [
                'required',
                'array',
                'min:1',
            ],

            'fields.*.id' => [
                'required',
                'string',
                'max:100',
            ],

            'fields.*.type' => [
                'required',
                'string',
                'in:' . implode(',', FieldTypes::all()),
            ],

            'fields.*.label' => [
                'required',
                'string',
                'max:255',
            ],

            'fields.*.placeholder' => [
                'nullable',
                'string',
                'max:255',
            ],

            'fields.*.required' => [
                'nullable',
                'boolean',
            ],

            'fields.*.validation' => [
                'nullable',
                'array',
            ],

            'fields.*.options' => [
                'nullable',
                'array',
            ],

            'fields.*.options.*.label' => [
                'required_with:fields.*.options',
                'string',
            ],

            'fields.*.options.*.value' => [
                'required_with:fields.*.options',
                'string',
            ],
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(
                $validator->errors()->toJson()
            );
        }

        return $schema;
    }
}