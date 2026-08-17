<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function export(Form $form): StreamedResponse
    {
        $filename = str($form->slug)->slug('_')
            . '_submissions.csv';

        return response()->streamDownload(

            function () use ($form) {

                $handle = fopen('php://output', 'w');

                /*
                |--------------------------------------------------------------------------
                | UTF-8 BOM
                |--------------------------------------------------------------------------
                |
                | Important for Excel.
                | Without BOM, Hindi / Marathi / other Unicode text may appear
                | incorrectly.
                |
                */

                fwrite($handle, "\xEF\xBB\xBF");


                /*
                |--------------------------------------------------------------------------
                | Get fields from schema
                |--------------------------------------------------------------------------
                */

                $fields = [];

                foreach (
                    $form->schema['sections'] ?? []
                    as $section
                ) {

                    foreach (
                        $section['fields'] ?? []
                        as $field
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | Section headings are not submission fields
                        |--------------------------------------------------------------------------
                        */

                        if (
                            ($field['type'] ?? '') === 'heading'
                        ) {
                            continue;
                        }

                        $fields[] = $field;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | CSV Headers
                |--------------------------------------------------------------------------
                */

                $headers = [
                    'ID',
                ];

                foreach ($fields as $field) {

                    $fieldKey =
                        $field['key']
                        ?? $field['id']
                        ?? null;

                    $headers[] =
                        $field['label']
                        ?? $fieldKey
                        ?? 'Field';
                }

                $headers[] = 'Submitted At';


                /*
                |--------------------------------------------------------------------------
                | Write CSV Header
                |--------------------------------------------------------------------------
                */

                fputcsv(
                    $handle,
                    $headers
                );


                /*
                |--------------------------------------------------------------------------
                | Submissions
                |--------------------------------------------------------------------------
                |
                | chunkById() prevents loading thousands/millions of submissions
                | into memory at once.
                |
                */

                $form
                    ->submissions()
                    ->latest()
                    ->chunkById(
                        500,
                        function ($submissions) use (
                            $handle,
                            $fields
                        ) {

                            foreach ($submissions as $submission) {

                                $row = [
                                    $submission->id,
                                ];


                                /*
                                |--------------------------------------------------------------------------
                                | Field Values
                                |--------------------------------------------------------------------------
                                */

                                $data = $submission->data ?? [];

                                /*
                                | Make sure data is always an array.
                                */

                                if (!is_array($data)) {
                                    $data = [];
                                }


                                foreach ($fields as $field) {

                                    /*
                                    |--------------------------------------------------------------------------
                                    | IMPORTANT:
                                    | Support both:
                                    |
                                    | Existing/manual forms:
                                    |     key
                                    |
                                    | Imported forms:
                                    |     id
                                    |--------------------------------------------------------------------------
                                    */

                                    $key =
                                        $field['key']
                                        ?? $field['id']
                                        ?? null;


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Get submitted value
                                    |--------------------------------------------------------------------------
                                    */

                                    $value = '';

                                    if (
                                        $key !== null &&
                                        array_key_exists($key, $data)
                                    ) {
                                        $value = $data[$key];
                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Checkbox / Multiple Values
                                    |--------------------------------------------------------------------------
                                    */

                                    if (is_array($value)) {

                                        $value = implode(
                                            ', ',
                                            array_map(
                                                function ($item) {

                                                    if (
                                                        is_array($item)
                                                        || is_object($item)
                                                    ) {

                                                        return json_encode(
                                                            $item,
                                                            JSON_UNESCAPED_UNICODE
                                                        );
                                                    }

                                                    return (string) $item;
                                                },
                                                $value
                                            )
                                        );
                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Objects
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        is_object($value)
                                        && !method_exists(
                                            $value,
                                            '__toString'
                                        )
                                    ) {

                                        $value = json_encode(
                                            $value,
                                            JSON_UNESCAPED_UNICODE
                                        );
                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Phone Numbers
                                    |--------------------------------------------------------------------------
                                    |
                                    | Excel may convert:
                                    |
                                    | +919767264893
                                    |
                                    | into:
                                    |
                                    | 9.2E+11
                                    |
                                    | Prefixing with an apostrophe tells Excel
                                    | to treat the value as text.
                                    |
                                    */

                                    if (
                                        ($field['type'] ?? '')
                                        === 'phone'
                                        && $value !== ''
                                    ) {

                                        $value = "'" . $value;
                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Write Value
                                    |--------------------------------------------------------------------------
                                    */

                                    $row[] = $value;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | Submitted At
                                |--------------------------------------------------------------------------
                                */

                                $row[] =
                                    $submission->created_at
                                        ? $submission
                                            ->created_at
                                            ->toDateTimeString()
                                        : '';


                                /*
                                |--------------------------------------------------------------------------
                                | Write CSV Row
                                |--------------------------------------------------------------------------
                                */

                                fputcsv(
                                    $handle,
                                    $row
                                );
                            }
                        }
                    );


                /*
                |--------------------------------------------------------------------------
                | Close CSV Stream
                |--------------------------------------------------------------------------
                */

                fclose($handle);
            },

            $filename,

            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',

                'Content-Disposition' =>
                    'attachment; filename="' . $filename . '"',

                'Cache-Control' =>
                    'no-store, no-cache, must-revalidate',

                'Pragma' =>
                    'no-cache',
            ]
        );
    }
}