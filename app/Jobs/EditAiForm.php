<?php

namespace App\Jobs;

use App\Models\AiGeneration;
use App\Services\OpenAiFormService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class EditAiForm implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 180;

    public AiGeneration $generation;

    public function __construct(
        AiGeneration $generation
    ) {
        $this->generation = $generation;
    }

       public function handle(
    OpenAiFormService $service
): void {

    /*
    |--------------------------------------------------------------------------
    | Mark generation as processing
    |--------------------------------------------------------------------------
    */

    $this->generation->update([
        'status' => 'processing',
        'error' => null,
    ]);


    /*
    |--------------------------------------------------------------------------
    | Get form
    |--------------------------------------------------------------------------
    */

    $form = $this->generation->form;

    if (!$form) {
        throw new \RuntimeException(
            'Form not found for AI edit.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Get current schema
    |--------------------------------------------------------------------------
    */

    $currentSchema = $form->schema;

    if (
        !is_array($currentSchema) ||
        empty($currentSchema)
    ) {
        throw new \RuntimeException(
            'Existing form schema is empty or invalid.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Generate modified schema
    |--------------------------------------------------------------------------
    */

    $result = $service->edit(
        schema: $currentSchema,
        prompt: $this->generation->prompt
    );


    /*
    |--------------------------------------------------------------------------
    | Get new schema
    |--------------------------------------------------------------------------
    */

    $newSchema = $result['schema'] ?? null;

    if (
        !is_array($newSchema) ||
        empty($newSchema)
    ) {
        throw new \RuntimeException(
            'AI returned an invalid form schema.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Preserve existing conditional logic
    |--------------------------------------------------------------------------
    |
    | Conditional logic is part of the form schema. If an AI response ever
    | omits the top-level logic property, keep the existing rules instead of
    | allowing an unrelated AI edit to delete them.
    |
    | If the user explicitly edits conditional logic, the AI response already
    | contains the requested logic and this fallback does not overwrite it.
    |--------------------------------------------------------------------------
    */

    if (
        !array_key_exists('logic', $newSchema) &&
        array_key_exists('logic', $currentSchema)
    ) {
        $newSchema['logic'] = $currentSchema['logic'];
    }


    /*
    |--------------------------------------------------------------------------
    | Check whether AI actually changed the schema
    |--------------------------------------------------------------------------
    */

    $schemaChanged =
        json_encode(
            $currentSchema,
            JSON_UNESCAPED_SLASHES
        ) !==
        json_encode(
            $newSchema,
            JSON_UNESCAPED_SLASHES
        );


    /*
    |--------------------------------------------------------------------------
    | Update form
    |--------------------------------------------------------------------------
    */

    $form->update([
        'schema' => $newSchema,
    ]);


    /*
    |--------------------------------------------------------------------------
    | Create version
    |--------------------------------------------------------------------------
    |
    | Store the NEW schema as the version snapshot.
    |
    | This keeps Version History consistent with the actual
    | form after AI editing.
    |
    */

    if ($schemaChanged) {

        $version = $form->nextVersionNumber();

        $form->versions()->create([
            'version' => $version,
            'schema' => $newSchema,
            'created_by' => $form->user_id,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Store AI generation information
    |--------------------------------------------------------------------------
    */

    $this->generation->update([
        'status' => 'completed',

        'model' =>
            $result['model'] ?? null,

        'input_tokens' =>
            $result['input_tokens'] ?? null,

        'output_tokens' =>
            $result['output_tokens'] ?? null,

        'latency_ms' =>
            $result['latency_ms'] ?? null,

        'result_schema' =>
            $newSchema,

        'error' => null,
    ]);
}

    /**
     * Handle job failure.
     */
    public function failed(
        Throwable $exception
    ): void {

        $this->generation->update([
            'status' => 'failed',

            'error' =>
                $exception->getMessage(),
        ]);
    }
}