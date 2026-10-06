<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OpenAiFormService
{
    public function __construct(
        protected FormSchemaValidator $schemaValidator
    ) {
    }

    /**
     * Generate a form schema from a natural-language prompt.
     */
    public function generate(
        string $prompt,
        int $maxAttempts = 3
    ): array {
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

            try {

                $result = $this->callOpenAi(
                    prompt: $prompt,
                    previousError: $lastError
                );

                /*
                |--------------------------------------------------------------------------
                | Validate generated schema
                |--------------------------------------------------------------------------
                */

                $schema = $this->schemaValidator->validate(
                    $result['schema']
                );

                return [
                    'schema' => $schema,

                    'model' =>
                        $result['model'] ?? null,

                    'input_tokens' =>
                        $result['input_tokens'] ?? null,

                    'output_tokens' =>
                        $result['output_tokens'] ?? null,

                    'latency_ms' =>
                        $result['latency_ms'] ?? null,

                    'attempts' => $attempt,
                ];

            } catch (Throwable $e) {

                $lastError = $e->getMessage();

                /*
                |--------------------------------------------------------------------------
                | Retry if schema validation or JSON parsing failed
                |--------------------------------------------------------------------------
                */

                if ($attempt === $maxAttempts) {

                    throw new RuntimeException(
                        'AI form generation failed after '
                        . $maxAttempts
                        . ' attempts. '
                        . $lastError,
                        0,
                        $e
                    );
                }
            }
        }

        throw new RuntimeException(
            'AI form generation failed.'
        );
    }


    /**
     * Edit an existing form schema using AI.
     *
     * The existing schema is passed to the AI together
     * with the user's requested changes.
     */
    public function edit(
        array $schema,
        string $prompt,
        int $maxAttempts = 3
    ): array {
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

            try {

                $result = $this->callOpenAiEdit(
                    schema: $schema,
                    prompt: $prompt,
                    previousError: $lastError
                );

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT
                |
                | Never return an AI schema without validating it.
                |--------------------------------------------------------------------------
                */

                $validatedSchema =
                    $this->schemaValidator->validate(
                        $result['schema']
                    );

                return [
                    'schema' => $validatedSchema,

                    'model' =>
                        $result['model'] ?? null,

                    'input_tokens' =>
                        $result['input_tokens'] ?? null,

                    'output_tokens' =>
                        $result['output_tokens'] ?? null,

                    'latency_ms' =>
                        $result['latency_ms'] ?? null,

                    'attempts' => $attempt,
                ];

            } catch (Throwable $e) {

                $lastError = $e->getMessage();

                /*
                |--------------------------------------------------------------------------
                | Retry
                |--------------------------------------------------------------------------
                */

                if ($attempt === $maxAttempts) {

                    throw new RuntimeException(
                        'AI form editing failed after '
                        . $maxAttempts
                        . ' attempts. '
                        . $lastError,
                        0,
                        $e
                    );
                }
            }
        }

        throw new RuntimeException(
            'AI form editing failed.'
        );
    }


    /**
     * Make the actual OpenAI API request for NEW form generation.
     */
    protected function callOpenAi(
        string $prompt,
        ?string $previousError = null
    ): array {

        $startedAt = microtime(true);

        /*
        |--------------------------------------------------------------------------
        | Retry instruction
        |--------------------------------------------------------------------------
        */

        $retryInstruction = '';

        if ($previousError) {

            $retryInstruction = <<<TEXT

The previous generated schema was rejected.

Validation error:

{$previousError}

Generate a corrected schema.
Do not repeat the same problem.
TEXT;
        }

        /*
        |--------------------------------------------------------------------------
        | System instructions
        |--------------------------------------------------------------------------
        */

        $instructions = <<<TEXT
You are an expert AI form-builder assistant.

Your job is to convert a natural-language form request
into a complete JSON form schema.

The generated schema will be consumed directly by a
Laravel form builder.

IMPORTANT RULES:

1. Return ONLY the requested JSON structure.

2. Never invent field types.

3. Only use these field types:

- text
- textarea
- number
- email
- phone
- date
- select
- radio
- checkbox
- file
- heading
- rating

4. Every field must have:

- id
- type
- label
- key
- placeholder
- help
- default
- required
- options
- validation

5. Field keys must be unique.

6. Field keys must use snake_case.

7. Select, radio and checkbox fields MUST have options.

8. Text, textarea, email, phone and number fields
   should use sensible validation rules when appropriate.

9. File fields should include sensible file validation.

10. Use sensible labels and placeholders.

11. Organize related fields into sections.

12. Do not create unnecessary fields.

13. The output must be suitable for immediate use
    by the existing Laravel form builder.

14. The schema version must be "1.0".

{$retryInstruction}
TEXT;

        /*
        |--------------------------------------------------------------------------
        | OpenAI API request
        |--------------------------------------------------------------------------
        */

        $response = Http::timeout(120)
            ->withToken(
                config('services.openai.api_key')
            )
            ->acceptJson()
            ->post(
                config('services.openai.base_url')
                . '/responses',
                [
                    'model' => config(
                        'services.openai.model'
                    ),

                    'store' => false,

                    'instructions' => $instructions,

                    'input' => $prompt,

                    'text' => [
                        'format' => [
                            'type' => 'json_schema',

                            'name' => 'form_schema',

                            'description' =>
                                'A complete Laravel form schema.',

                            'strict' => true,

                            'schema' =>
                                $this->outputSchema(),
                        ],
                    ],
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Latency
        |--------------------------------------------------------------------------
        */

        $latencyMs = (int) round(
            (microtime(true) - $startedAt) * 1000
        );

        /*
        |--------------------------------------------------------------------------
        | API error
        |--------------------------------------------------------------------------
        */

        if ($response->failed()) {

            throw new RuntimeException(
                'OpenAI API error: '
                . $response->body()
            );
        }

        $json = $response->json();

        /*
        |--------------------------------------------------------------------------
        | Extract response text
        |--------------------------------------------------------------------------
        */

        $outputText = $this->extractOutputText(
            $json
        );

        if (!$outputText) {

            throw new RuntimeException(
                'OpenAI returned an empty response.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Decode JSON
        |--------------------------------------------------------------------------
        */

        $schema = json_decode(
            $outputText,
            true
        );

        if (
            !is_array($schema) ||
            json_last_error() !== JSON_ERROR_NONE
        ) {

            throw new RuntimeException(
                'OpenAI returned invalid JSON: '
                . json_last_error_msg()
            );
        }

        return [
            'schema' => $schema,

            'model' =>
                $json['model']
                ?? config('services.openai.model'),

            'input_tokens' =>
                $json['usage']['input_tokens']
                ?? null,

            'output_tokens' =>
                $json['usage']['output_tokens']
                ?? null,

            'latency_ms' =>
                $latencyMs,
        ];
    }


    /**
     * Make the actual OpenAI API request for
     * editing an EXISTING form.
     */
    protected function callOpenAiEdit(
        array $schema,
        string $prompt,
        ?string $previousError = null
    ): array {

        $startedAt = microtime(true);

        /*
        |--------------------------------------------------------------------------
        | Retry instruction
        |--------------------------------------------------------------------------
        */

        $retryInstruction = '';

        if ($previousError) {

            $retryInstruction = <<<TEXT

The previous modified schema was rejected.

Validation error:

{$previousError}

Generate a corrected complete schema.
Do not repeat the same validation problem.
TEXT;
        }

        /*
        |--------------------------------------------------------------------------
        | Convert existing schema to JSON
        |--------------------------------------------------------------------------
        */

        $existingSchema = json_encode(
            $schema,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        if ($existingSchema === false) {

            throw new RuntimeException(
                'Unable to encode existing form schema.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | AI editing instructions
        |--------------------------------------------------------------------------
        */

        $instructions = <<<TEXT
You are an expert AI form-builder assistant.

You are editing an EXISTING Laravel form schema.

The existing schema is provided below.

Your job is to apply ONLY the changes requested by the user.

IMPORTANT RULES:

1. Return ONLY the complete JSON form schema.

2. Do not return markdown.

3. Do not return explanations.

4. Do not return code fences.

5. Preserve the existing form structure.

6. Preserve existing sections unless the user asks
   to add, remove or modify them.

7. Preserve existing fields unless the user asks
   to add, remove or modify them.

8. Preserve existing field keys whenever possible.

9. Do not rename existing field keys unless explicitly requested.

10. Do not remove existing validation unless explicitly requested.

11. Preserve the existing top-level conditional logic exactly as-is unless
    the user explicitly asks to add, remove, or modify conditional logic.

12. If the user is not asking about conditional logic, do not change the
    existing "logic" array, its rules, source fields, operators, values,
    actions, or target fields.

13. New fields must use sensible:

- id
- type
- label
- key
- placeholder
- help
- default
- required
- options
- validation

12. Never invent field types.

13. Only use these field types:

- text
- textarea
- number
- email
- phone
- date
- select
- radio
- checkbox
- file
- heading
- rating

14. Field keys must be unique.

15. Field keys must use snake_case.

16. Select, radio and checkbox fields MUST contain options.

17. Use sensible validation when adding or modifying fields.

18. If the user requests a translation, translate:

- labels
- placeholders
- help text

where appropriate.

19. Preserve field keys when translating.

20. Do not create unnecessary fields.

21. The final result MUST be a complete schema.

22. The final schema must remain compatible with
    the existing Laravel form builder.

23. The schema version must remain "1.0".

EXISTING FORM SCHEMA:

{$existingSchema}

USER REQUEST:

{$prompt}

{$retryInstruction}
TEXT;

        /*
        |--------------------------------------------------------------------------
        | OpenAI API request
        |--------------------------------------------------------------------------
        */

        $response = Http::timeout(120)
            ->withToken(
                config('services.openai.api_key')
            )
            ->acceptJson()
            ->post(
                config('services.openai.base_url')
                . '/responses',
                [
                    'model' => config(
                        'services.openai.model'
                    ),

                    'store' => false,

                    'instructions' => $instructions,

                    'input' => $prompt,

                    'text' => [
                        'format' => [
                            'type' => 'json_schema',

                            'name' => 'form_schema',

                            'description' =>
                                'A complete Laravel form schema.',

                            'strict' => true,

                            /*
                            |--------------------------------------------------------------------------
                            | Reuse the same schema definition
                            |--------------------------------------------------------------------------
                            */

                            'schema' =>
                                $this->outputSchema(),
                        ],
                    ],
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Latency
        |--------------------------------------------------------------------------
        */

        $latencyMs = (int) round(
            (microtime(true) - $startedAt) * 1000
        );

        /*
        |--------------------------------------------------------------------------
        | API error
        |--------------------------------------------------------------------------
        */

        if ($response->failed()) {

            throw new RuntimeException(
                'OpenAI API error: '
                . $response->body()
            );
        }

        $json = $response->json();

        /*
        |--------------------------------------------------------------------------
        | Extract response text
        |--------------------------------------------------------------------------
        */

        $outputText = $this->extractOutputText(
            $json
        );

        if (!$outputText) {

            throw new RuntimeException(
                'OpenAI returned an empty response.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Decode JSON
        |--------------------------------------------------------------------------
        */

        $resultSchema = json_decode(
            $outputText,
            true
        );

        if (
            !is_array($resultSchema) ||
            json_last_error() !== JSON_ERROR_NONE
        ) {

            throw new RuntimeException(
                'OpenAI returned invalid JSON: '
                . json_last_error_msg()
            );
        }

        return [
            'schema' => $resultSchema,

            'model' =>
                $json['model']
                ?? config('services.openai.model'),

            'input_tokens' =>
                $json['usage']['input_tokens']
                ?? null,

            'output_tokens' =>
                $json['usage']['output_tokens']
                ?? null,

            'latency_ms' =>
                $latencyMs,
        ];
    }


    /**
     * Extract text from the Responses API response.
     */
    protected function extractOutputText(
        array $response
    ): ?string {

        foreach (
            $response['output'] ?? []
            as $output
        ) {

            foreach (
                $output['content'] ?? []
                as $content
            ) {

                if (
                    ($content['type'] ?? null)
                    === 'output_text'
                ) {

                    return $content['text'] ?? null;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        */

        return $response['output_text'] ?? null;
    }


    /**
     * JSON Schema supplied to OpenAI Structured Outputs.
     *
     * This is shared by both:
     *
     * 1. AI form creation
     * 2. AI form editing
     */
    protected function outputSchema(): array
    {
        return [

            'type' => 'object',

            'additionalProperties' => false,

            'required' => [
                'version',
                'title',
                'description',
                'sections',
                'settings',
                'logic',
            ],

            'properties' => [

                /*
                |--------------------------------------------------------------------------
                | Version
                |--------------------------------------------------------------------------
                */

                'version' => [
                    'type' => 'string',
                ],

                /*
                |--------------------------------------------------------------------------
                | Title
                |--------------------------------------------------------------------------
                */

                'title' => [
                    'type' => 'string',
                ],

                /*
                |--------------------------------------------------------------------------
                | Description
                |--------------------------------------------------------------------------
                */

                'description' => [
                    'type' => 'string',
                ],

                /*
                |--------------------------------------------------------------------------
                | Sections
                |--------------------------------------------------------------------------
                */

                'sections' => [

                    'type' => 'array',

                    'items' => [

                        'type' => 'object',

                        'additionalProperties' => false,

                        'required' => [
                            'id',
                            'title',
                            'fields',
                        ],

                        'properties' => [

                            'id' => [
                                'type' => 'string',
                            ],

                            'title' => [
                                'type' => 'string',
                            ],

                            /*
                            |--------------------------------------------------------------------------
                            | Fields
                            |--------------------------------------------------------------------------
                            */

                            'fields' => [

                                'type' => 'array',

                                'items' => [

                                    'type' => 'object',

                                    'additionalProperties' => false,

                                    'required' => [
                                        'id',
                                        'type',
                                        'label',
                                        'key',
                                        'placeholder',
                                        'help',
                                        'default',
                                        'required',
                                        'options',
                                        'validation',
                                    ],

                                    'properties' => [

                                        'id' => [
                                            'type' => 'string',
                                        ],

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Field type
                                        |--------------------------------------------------------------------------
                                        */

                                        'type' => [

                                            'type' => 'string',

                                            'enum' => [
                                                'text',
                                                'textarea',
                                                'number',
                                                'email',
                                                'phone',
                                                'date',
                                                'select',
                                                'radio',
                                                'checkbox',
                                                'file',
                                                'heading',
                                                'rating',
                                            ],
                                        ],

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Label
                                        |--------------------------------------------------------------------------
                                        */

                                        'label' => [
                                            'type' => 'string',
                                        ],

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Key
                                        |--------------------------------------------------------------------------
                                        */

                                        'key' => [
                                            'type' => 'string',
                                        ],

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Placeholder
                                        |--------------------------------------------------------------------------
                                        */

                                        'placeholder' => [
                                            'type' => 'string',
                                        ],

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Help
                                        |--------------------------------------------------------------------------
                                        */

                                        'help' => [
                                            'type' => 'string',
                                        ],

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Default
                                        |--------------------------------------------------------------------------
                                        */

                                        'default' => [
                                            'type' => 'string',
                                        ],

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Required
                                        |--------------------------------------------------------------------------
                                        */

                                        'required' => [
                                            'type' => 'boolean',
                                        ],

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Options
                                        |--------------------------------------------------------------------------
                                        */

                                        'options' => [

                                            'type' => 'array',

                                            'items' => [

                                                'type' => 'object',

                                                'additionalProperties'
                                                    => false,

                                                'required' => [
                                                    'label',
                                                    'value',
                                                ],

                                                'properties' => [

                                                    'label' => [
                                                        'type' => 'string',
                                                    ],

                                                    'value' => [
                                                        'type' => 'string',
                                                    ],
                                                ],
                                            ],
                                        ],

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Validation
                                        |--------------------------------------------------------------------------
                                        */

                                        'validation' => [

                                            'type' => 'object',

                                            'additionalProperties'
                                                => false,

                                            'required' => [
                                                'min',
                                                'max',
                                                'min_length',
                                                'max_length',
                                                'url',
                                                'regex',
                                                'file_types',
                                                'file_size',
                                            ],

                                            'properties' => [

                                                /*
                                                | Numeric min
                                                */

                                                'min' => [

                                                    'type' => [
                                                        'number',
                                                        'null',
                                                    ],
                                                ],

                                                /*
                                                | Numeric max
                                                */

                                                'max' => [

                                                    'type' => [
                                                        'number',
                                                        'null',
                                                    ],
                                                ],

                                                /*
                                                | Minimum string length
                                                */

                                                'min_length' => [

                                                    'type' => [
                                                        'integer',
                                                        'null',
                                                    ],
                                                ],

                                                /*
                                                | Maximum string length
                                                */

                                                'max_length' => [

                                                    'type' => [
                                                        'integer',
                                                        'null',
                                                    ],
                                                ],

                                                /*
                                                | URL validation
                                                */

                                                'url' => [

                                                    'type' => [
                                                        'boolean',
                                                        'null',
                                                    ],
                                                ],

                                                /*
                                                | Regular expression
                                                */

                                                'regex' => [

                                                    'type' => [
                                                        'string',
                                                        'null',
                                                    ],
                                                ],

                                                /*
                                                | File types
                                                */

                                                'file_types' => [

                                                    'type' => [
                                                        'string',
                                                        'null',
                                                    ],
                                                ],

                                                /*
                                                | File size
                                                */

                                                'file_size' => [

                                                    'type' => [
                                                        'integer',
                                                        'null',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                /*
                |--------------------------------------------------------------------------
                | Conditional Logic
                |--------------------------------------------------------------------------
                |
                | Conditional rules live at the root of the form schema.
                | This property is intentionally shared by both AI generation
                | and AI editing so AI edits cannot silently drop existing logic.
                |--------------------------------------------------------------------------
                */

                'logic' => [

                    'type' => 'array',

                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'when',
                            'action',
                            'target',
                        ],
                        'properties' => [

                            'when' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => [
                                    'field',
                                    'operator',
                                    'value',
                                ],
                                'properties' => [
                                    'field' => [
                                        'type' => 'string',
                                    ],
                                    'operator' => [
                                        'type' => 'string',
                                        'enum' => [
                                            'equals',
                                            'not_equals',
                                            'contains',
                                            'not_contains',
                                            'greater_than',
                                            'less_than',
                                            'greater_or_equal',
                                            'less_or_equal',
                                        ],
                                    ],
                                    'value' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],

                            'action' => [
                                'type' => 'string',
                                'enum' => [
                                    'show',
                                    'hide',
                                ],
                            ],

                            'target' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],

                /*
                |--------------------------------------------------------------------------
                | Settings
                |--------------------------------------------------------------------------
                */

                'settings' => [

                    'type' => 'object',

                    'additionalProperties' => false,

                    'required' => [
                        'success_message',
                        'show_progress',
                        'allow_multiple',
                    ],

                    'properties' => [

                        'success_message' => [
                            'type' => 'string',
                        ],

                        'show_progress' => [
                            'type' => 'boolean',
                        ],

                        'allow_multiple' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ],
        ];
    }
}