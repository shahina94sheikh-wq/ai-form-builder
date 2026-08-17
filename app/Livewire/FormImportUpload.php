<?php

namespace App\Livewire;

use App\Jobs\ProcessFormImport;
use App\Models\FormImport;
use App\Services\ExcelFormImportService;
use App\Services\WordFormImportService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class FormImportUpload extends Component
{
    use WithFileUploads;

    public $file = null;

    public ?string $uploadError = null;

    public ?FormImport $import = null;

    /**
     * Files larger than this will be processed by the queue.
     *
     * 5 MB
     */
    protected int $queueThreshold = 5242880;


    /**
     * Create the import record and store the uploaded file.
     */
    public function createImport(): void
    {
        $this->resetErrorBag();

        $this->uploadError = null;

        /*
        |--------------------------------------------------------------------------
        | Validate file
        |--------------------------------------------------------------------------
        */

        $this->validate([
            'file' => [
                'required',
                'file',
                'mimes:docx,xlsx',
                'max:10240',
            ],
        ], [
            'file.required' =>
                'Please select a Word or Excel file.',

            'file.mimes' =>
                'Only .docx and .xlsx files are supported.',

            'file.max' =>
                'The file must not be larger than 10 MB.',
        ]);


        try {

            /*
            |--------------------------------------------------------------------------
            | Detect extension
            |--------------------------------------------------------------------------
            */

            $extension = strtolower(
                $this->file->getClientOriginalExtension()
            );


            /*
            |--------------------------------------------------------------------------
            | Get original file size
            |--------------------------------------------------------------------------
            */

            $fileSize =
                $this->file->getSize();


            /*
            |--------------------------------------------------------------------------
            | Store file
            |--------------------------------------------------------------------------
            */

            $path = $this->file->store(
                'form-imports',
                'local'
            );


            /*
            |--------------------------------------------------------------------------
            | Create import record
            |--------------------------------------------------------------------------
            */

            $this->import = FormImport::create([
                'user_id' =>
                    auth()->id(),

                'filename' =>
                    $this->file->getClientOriginalName(),

                'disk' =>
                    'local',

                'type' =>
                    $extension,

                'status' =>
                    'uploaded',

                /*
                |--------------------------------------------------------------------------
                | Keep file information
                |--------------------------------------------------------------------------
                */

                'parsed_data' => [
                    '_file' => [
                        'path' =>
                            $path,

                        'disk' =>
                            'local',

                        'size' =>
                            $fileSize,
                    ],
                ],

                'schema' =>
                    null,

                'errors' =>
                    null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Clear selected upload
            |--------------------------------------------------------------------------
            */

            $this->file = null;


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            session()->flash(
                'success',
                'File uploaded successfully.'
            );

        } catch (\Throwable $e) {

            report($e);

            $this->uploadError =
                'Unable to upload the file. Please try again.';
        }
    }


    /**
     * Parse the uploaded Excel or Word document.
     */
    public function parseImport(): void
    {
        if (!$this->import) {

            $this->uploadError =
                'No uploaded file is available to parse.';

            return;
        }


        $this->resetErrorBag();

        $this->uploadError = null;

        $this->import->refresh();


        /*
        |--------------------------------------------------------------------------
        | Already processing
        |--------------------------------------------------------------------------
        */

        if (
            $this->import->status === 'processing'
        ) {

            return;
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | Get file information
            |--------------------------------------------------------------------------
            */

            $fileInfo =
                $this->import->parsed_data['_file']
                ?? null;


            if (
                !$fileInfo ||
                empty($fileInfo['path'])
            ) {

                throw new \RuntimeException(
                    'Uploaded file path could not be found.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Determine file size
            |--------------------------------------------------------------------------
            */

            $fileSize =
                (int) (
                    $fileInfo['size']
                    ?? 0
                );


            /*
            |--------------------------------------------------------------------------
            | Large file → queue
            |--------------------------------------------------------------------------
            */

            if (
                $fileSize >
                $this->queueThreshold
            ) {

                /*
                |--------------------------------------------------------------------------
                | Mark as processing before dispatching
                |--------------------------------------------------------------------------
                */

                $this->import->update([
                    'status' =>
                        'processing',

                    'errors' =>
                        null,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Dispatch queue job
                |--------------------------------------------------------------------------
                */

                ProcessFormImport::dispatch(
                    $this->import->id
                );


                session()->flash(
                    'success',
                    'Your file is large and has been queued for processing.'
                );


                /*
                |--------------------------------------------------------------------------
                | Stay on this page.
                |
                | Frontend polling will detect when processing finishes.
                |--------------------------------------------------------------------------
                */

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Small file → process immediately
            |--------------------------------------------------------------------------
            */

            $this->processImmediately();

        } catch (\Throwable $e) {

            report($e);


            if ($this->import) {

                $this->import->update([
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


            $this->uploadError =
                'Unable to process the file: '
                . $e->getMessage();
        }
    }


    /**
     * Process small files immediately.
     */
    protected function processImmediately(): void
    {
        if (!$this->import) {
            return;
        }


        $this->import->refresh();


        /*
        |--------------------------------------------------------------------------
        | Mark processing
        |--------------------------------------------------------------------------
        */

        $this->import->update([
            'status' =>
                'processing',

            'errors' =>
                null,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Get file information
        |--------------------------------------------------------------------------
        */

        $fileInfo =
            $this->import->parsed_data['_file']
            ?? null;


        if (
            !$fileInfo ||
            empty($fileInfo['path'])
        ) {

            throw new \RuntimeException(
                'Uploaded file path could not be found.'
            );
        }


        $disk =
            $fileInfo['disk']
            ?? 'local';

        $path =
            $fileInfo['path'];


        /*
        |--------------------------------------------------------------------------
        | Resolve filesystem path
        |--------------------------------------------------------------------------
        */

        $fullPath =
            Storage::disk($disk)->path($path);


        if (!is_file($fullPath)) {

            throw new \RuntimeException(
                'The uploaded file could not be found on disk.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Select parser
        |--------------------------------------------------------------------------
        */

        if (
            $this->import->type === 'xlsx'
        ) {

            $service =
                app(
                    ExcelFormImportService::class
                );

        } elseif (
            $this->import->type === 'docx'
        ) {

            $service =
                app(
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

        $parsed =
            $service->parse(
                $fullPath
            );


        /*
        |--------------------------------------------------------------------------
        | Preserve file information
        |--------------------------------------------------------------------------
        */

        $parsed['_file'] = [
            'path' =>
                $path,

            'disk' =>
                $disk,

            'size' =>
                $fileInfo['size'] ?? 0,
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
        | No fields
        |--------------------------------------------------------------------------
        */

        if (!$hasFields) {

            $message =
                'No form fields could be detected in this '
                . strtoupper(
                    $this->import->type
                )
                . ' file.';


            $this->import->update([
                'status' =>
                    'failed',

                'parsed_data' =>
                    $parsed,

                'schema' =>
                    null,

                'errors' => [
                    [
                        'message' =>
                            $message,
                    ],
                ],
            ]);


            $this->uploadError =
                $message;

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Build schema
        |--------------------------------------------------------------------------
        */

        $schema = [

            'version' =>
                '1.0',

            'title' =>
                pathinfo(
                    $this->import->filename,
                    PATHINFO_FILENAME
                ),

            'description' =>
                'Imported from '
                . $this->import->filename,

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
        | Save preview
        |--------------------------------------------------------------------------
        */

        $this->import->update([

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


        /*
        |--------------------------------------------------------------------------
        | Redirect to preview
        |--------------------------------------------------------------------------
        */

        redirect()->route(
            'forms.import.preview',
            [
                'import' =>
                    $this->import->id,
            ]
        );
    }


    /**
     * Check status of a queued import.
     *
     * Called automatically by Livewire polling.
     */
    public function refreshImportStatus(): void
    {
        if (!$this->import) {
            return;
        }


        $this->import->refresh();


        /*
        |--------------------------------------------------------------------------
        | Processing
        |--------------------------------------------------------------------------
        */

        if (
            $this->import->status === 'processing'
        ) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Completed preview
        |--------------------------------------------------------------------------
        */

        if (
            $this->import->status === 'preview'
        ) {

            redirect()->route(
                'forms.import.preview',
                [
                    'import' =>
                        $this->import->id,
                ]
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Failed
        |--------------------------------------------------------------------------
        */

        if (
            $this->import->status === 'failed'
        ) {

            $errors =
                $this->import->errors
                ?? [];

            $message =
                $errors[0]['message']
                ?? 'The import could not be processed.';


            $this->uploadError =
                $message;
        }
    }


    /**
     * Render component.
     */
    public function render()
    {
        return view(
            'livewire.form-import-upload'
        )->layout('layouts.app', [
            'title' =>
                'Import Form',
        ]);
    }
}