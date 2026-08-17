<?php

namespace App\Jobs;

use App\Models\FormImport;
use App\Services\ExcelFormImportService;
use App\Services\WordFormImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessFormImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Do not retry indefinitely.
     */
    public int $tries = 1;

    /**
     * Maximum processing time.
     */
    public int $timeout = 180;

    /**
     * Form import record ID.
     */
    public int $importId;

    public function __construct(int $importId)
    {
        $this->importId = $importId;
    }

    /**
     * Process the uploaded document.
     */
    public function handle(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Increase memory for document processing
        |--------------------------------------------------------------------------
        |
        | PhpSpreadsheet can require considerably more memory when processing
        | XLSX files. The normal CLI PHP memory limit on this machine is 128M.
        |
        | This only applies to this queue process.
        |
        */

        @ini_set('memory_limit', '512M');

        /*
        |--------------------------------------------------------------------------
        | Increase execution time
        |--------------------------------------------------------------------------
        */

        @set_time_limit(180);

        Log::info('ProcessFormImport started', [
            'import_id' => $this->importId,
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage' => memory_get_usage(true),
        ]);

        $import = FormImport::find($this->importId);

        if (!$import) {
            Log::warning('ProcessFormImport: import record not found', [
                'import_id' => $this->importId,
            ]);

            return;
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | Mark as processing
            |--------------------------------------------------------------------------
            */

            $import->update([
                'status' => 'processing',
                'errors' => null,
            ]);

            Log::info('ProcessFormImport: status changed to processing', [
                'import_id' => $this->importId,
                'type' => $import->type,
                'filename' => $import->filename,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Get uploaded file information
            |--------------------------------------------------------------------------
            */

            $fileInfo = $import->parsed_data['_file'] ?? null;

            if (
                !$fileInfo ||
                empty($fileInfo['path'])
            ) {
                throw new \RuntimeException(
                    'Uploaded file path could not be found.'
                );
            }

            $disk = $fileInfo['disk'] ?? 'local';

            $path = $fileInfo['path'];


            /*
            |--------------------------------------------------------------------------
            | Resolve physical path
            |--------------------------------------------------------------------------
            */

            $fullPath = Storage::disk($disk)->path($path);

            if (!is_file($fullPath)) {
                throw new \RuntimeException(
                    'The uploaded file could not be found on disk.'
                );
            }

            Log::info('ProcessFormImport: file located', [
                'import_id' => $this->importId,
                'disk' => $disk,
                'path' => $path,
                'full_path' => $fullPath,
                'file_size' => filesize($fullPath),
                'memory_usage' => memory_get_usage(true),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Select parser
            |--------------------------------------------------------------------------
            */

            if ($import->type === 'xlsx') {

                $service = app(
                    ExcelFormImportService::class
                );

            } elseif ($import->type === 'docx') {

                $service = app(
                    WordFormImportService::class
                );

            } else {

                throw new \RuntimeException(
                    'Unsupported import file type.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Parse document
            |--------------------------------------------------------------------------
            */

            Log::info('ProcessFormImport: starting document parsing', [
                'import_id' => $this->importId,
                'type' => $import->type,
                'memory_limit' => ini_get('memory_limit'),
                'memory_usage' => memory_get_usage(true),
            ]);

            $parsed = $service->parse($fullPath);

            Log::info('ProcessFormImport: document parsing completed', [
                'import_id' => $this->importId,
                'type' => $import->type,
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Preserve uploaded file information
            |--------------------------------------------------------------------------
            */

            $parsed['_file'] = [
                'path' => $path,
                'disk' => $disk,
            ];


            /*
            |--------------------------------------------------------------------------
            | Check detected fields
            |--------------------------------------------------------------------------
            */

            $hasFields = collect(
                $parsed['sections'] ?? []
            )->contains(function ($section) {

                return !empty(
                    $section['fields'] ?? []
                );
            });


            /*
            |--------------------------------------------------------------------------
            | No fields detected
            |--------------------------------------------------------------------------
            */

            if (!$hasFields) {

                $message =
                    'No form fields could be detected in this '
                    . strtoupper($import->type)
                    . ' file.';

                $import->update([
                    'status' => 'failed',
                    'parsed_data' => $parsed,
                    'schema' => null,
                    'errors' => [
                        [
                            'message' => $message,
                        ],
                    ],
                ]);

                Log::warning(
                    'ProcessFormImport: no fields detected',
                    [
                        'import_id' => $this->importId,
                        'type' => $import->type,
                    ]
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Build normalized form schema
            |--------------------------------------------------------------------------
            */

            $schema = [

                'version' => '1.0',

                'title' =>
                    pathinfo(
                        $import->filename,
                        PATHINFO_FILENAME
                    ),

                'description' =>
                    'Imported from '
                    . $import->filename,

                'sections' =>
                    $parsed['sections'],

                'settings' => [

                    'success_message' =>
                        'Thank you for submitting the form.',

                    'show_progress' =>
                        true,

                    'allow_multiple' =>
                        true,
                ],
            ];


            /*
            |--------------------------------------------------------------------------
            | Save successful result
            |--------------------------------------------------------------------------
            */

            $import->update([

                'status' =>
                    'preview',

                'parsed_data' =>
                    $parsed,

                'schema' =>
                    $schema,

                'errors' =>
                    !empty($parsed['errors'])
                        ? $parsed['errors']
                        : null,
            ]);

            Log::info('ProcessFormImport completed successfully', [
                'import_id' => $this->importId,
                'type' => $import->type,
                'filename' => $import->filename,
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true),
            ]);

        } catch (Throwable $e) {

            report($e);

            Log::error('ProcessFormImport failed', [
                'import_id' => $this->importId,
                'type' => $import->type ?? null,
                'filename' => $import->filename ?? null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Save failure
            |--------------------------------------------------------------------------
            */

            $import->update([

                'status' =>
                    'failed',

                'errors' => [
                    [
                        'message' =>
                            $e->getMessage(),
                    ],
                ],
            ]);
        }
    }

    /**
     * Handle permanent job failure.
     */
    public function failed(
        Throwable $exception
    ): void {

        Log::error('ProcessFormImport permanently failed', [
            'import_id' => $this->importId,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        $import =
            FormImport::find(
                $this->importId
            );

        if (!$import) {
            return;
        }

        $import->update([

            'status' =>
                'failed',

            'errors' => [
                [
                    'message' =>
                        $exception->getMessage(),
                ],
            ],
        ]);
    }
}