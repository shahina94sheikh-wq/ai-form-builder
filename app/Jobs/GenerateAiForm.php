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

class GenerateAiForm implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * We already retry schema-related problems
     * inside OpenAiFormService.
     *
     * Therefore don't retry the entire queue job,
     * especially for billing/API errors.
     */
    public int $tries = 1;

    /**
     * Maximum time the job may run.
     */
    public int $timeout = 180;

    /**
     * AI generation record.
     */
    public AiGeneration $generation;

    /**
     * Create a new job instance.
     */
    public function __construct(
        AiGeneration $generation
    ) {
        $this->generation = $generation;
    }

    /**
     * Execute the job.
     */
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
        | Generate the form schema
        |--------------------------------------------------------------------------
        */

        $result = $service->generate(
            $this->generation->prompt
        );

        /*
        |--------------------------------------------------------------------------
        | Store AI result and metadata
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
                $result['schema'],

            'error' => null,
        ]);
    }

    /**
     * Handle a job failure.
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