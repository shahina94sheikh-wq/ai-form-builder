<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiFormGenerator
{
    public function generate(string $prompt): array
    {
        $system = <<<'PROMPT'
You are an expert form schema generator.

Return ONLY valid JSON.

Never return markdown.
Never return explanations.

Allowed field types:

text
textarea
number
email
phone
date
select
radio
checkbox
file
heading
rating

Schema:

{
  "version": "1.0",
  "title": "string",
  "description": "string",
  "settings": {},
  "sections": [
    {
      "id": "string",
      "title": "string",
      "fields": [
        {
          "id": "string",
          "key": "snake_case",
          "type": "text",
          "label": "string",
          "placeholder": "string",
          "help": "string",
          "default": "",
          "required": false,
          "validation": {},
          "options": []
        }
      ]
    }
  ]
}

Rules:

1. Never invent unsupported field types.
2. Use email for email addresses.
3. Use phone for telephone numbers.
4. Use select/radio for finite choices.
5. Use file for resume/document uploads.
6. Use sensible validation.
7. Keys must be unique.
8. Sections should represent logical groups.
PROMPT;

        $response = Http::timeout(60)
            ->withToken(config('services.ai.key'))
            ->post(
                rtrim(config('services.ai.base_url'), '/')
                . '/chat/completions',
                [
                    'model' => config('services.ai.model'),

                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $system,
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],

                    'temperature' => 0.2,

                    'response_format' => [
                        'type' => 'json_object',
                    ],
                ]
            )
            ->throw();

        $content = data_get(
            $response->json(),
            'choices.0.message.content'
        );

        $decoded = json_decode(
            $content,
            true
        );

        if (!is_array($decoded)) {
            throw new \RuntimeException(
                'AI returned invalid JSON.'
            );
        }

        return $decoded;
    }
}