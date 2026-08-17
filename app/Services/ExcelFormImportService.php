<?php

namespace App\Services;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelFormImportService
{
    /**
     * Parse an Excel file into a normalized form structure.
     *
     * Supported Excel structure:
     *
     * Section | Label | Type | Required | Options
     *
     * Example:
     *
     * Personal Information | Full Name | text | yes |
     * Personal Information | Email | email | yes |
     * Personal Information | Gender | dropdown | no | Male|Female|Other
     */
    public function parse(string $path): array
    {
        $spreadsheet = IOFactory::load($path);

        $allSections = [];
        $errors = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {

            $rows = $worksheet->toArray(
                null,
                true,
                true,
                false
            );

            /*
            |--------------------------------------------------------------------------
            | Skip empty worksheet
            |--------------------------------------------------------------------------
            */
            if (empty($rows)) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Find header row
            |--------------------------------------------------------------------------
            */
            $header = $this->findHeaderRow($rows);

            if (!$header) {

                $errors[] = [
                    'sheet' => $worksheet->getTitle(),
                    'message' =>
                        'Could not detect a supported header row.',
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Parse rows
            |--------------------------------------------------------------------------
            */
            $sections = $this->parseRows(
                $rows,
                $header['row'],
                $header['columns']
            );

            /*
            |--------------------------------------------------------------------------
            | Add parsed sections
            |--------------------------------------------------------------------------
            */
            foreach ($sections as $section) {
                $allSections[] = $section;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Return normalized import structure
        |--------------------------------------------------------------------------
        */
        return [
            'format' => 'xlsx',

            'sections' => $allSections,

            'errors' => $errors,
        ];
    }


    /**
     * Detect the header row.
     *
     * We only require the Label column.
     * Other columns are optional.
     */
    protected function findHeaderRow(array $rows): ?array
    {
        foreach ($rows as $rowIndex => $row) {

            $columns = [];

            foreach ($row as $columnIndex => $value) {

                if ($value === null) {
                    continue;
                }

                $header = strtolower(
                    trim((string) $value)
                );

                if ($header === '') {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Normalize Excel header names
                |--------------------------------------------------------------------------
                */
                $normalized = match ($header) {

                    'section',
                    'sections',
                    'section name' =>
                        'section',

                    'label',
                    'field label',
                    'field',
                    'question',
                    'question label',
                    'field name' =>
                        'label',

                    'type',
                    'field type',
                    'input type' =>
                        'type',

                    'required',
                    'is required',
                    'mandatory',
                    'required field' =>
                        'required',

                    'options',
                    'choices',
                    'values',
                    'option values',
                    'dropdown options',
                    'select options' =>
                        'options',

                    default => null,
                };

                if ($normalized) {
                    $columns[$normalized] = $columnIndex;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Label column is mandatory
            |--------------------------------------------------------------------------
            */
            if (isset($columns['label'])) {

                return [
                    'row' => $rowIndex,

                    'columns' => $columns,
                ];
            }
        }

        return null;
    }


    /**
     * Convert spreadsheet rows into form sections.
     */
    protected function parseRows(
        array $rows,
        int $headerRow,
        array $columns
    ): array {

        $sections = [];

        $sectionIndexes = [];

        /*
        |--------------------------------------------------------------------------
        | Process every row after header
        |--------------------------------------------------------------------------
        */
        for (
            $rowIndex = $headerRow + 1;
            $rowIndex < count($rows);
            $rowIndex++
        ) {

            $row = $rows[$rowIndex];

            /*
            |--------------------------------------------------------------------------
            | Read field label
            |--------------------------------------------------------------------------
            */
            $label = $this->getCell(
                $row,
                $columns['label'] ?? null
            );

            $label = trim(
                (string) $label
            );

            /*
            |--------------------------------------------------------------------------
            | Skip empty rows
            |--------------------------------------------------------------------------
            */
            if ($label === '') {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Read section
            |--------------------------------------------------------------------------
            */
            $sectionTitle = $this->getCell(
                $row,
                $columns['section'] ?? null
            );

            $sectionTitle = trim(
                (string) $sectionTitle
            );

            /*
            |--------------------------------------------------------------------------
            | Default section
            |--------------------------------------------------------------------------
            */
            if ($sectionTitle === '') {
                $sectionTitle = 'Imported Fields';
            }

            /*
            |--------------------------------------------------------------------------
            | Create section if it doesn't exist
            |--------------------------------------------------------------------------
            */
            if (!isset($sectionIndexes[$sectionTitle])) {

                $sectionIndexes[$sectionTitle] =
                    count($sections);

                $sections[] = [
                    'id' =>
                        'section_' .
                        substr(
                            md5($sectionTitle),
                            0,
                            10
                        ),

                    'title' =>
                        $sectionTitle,

                    'fields' => [],
                ];
            }

            $sectionIndex =
                $sectionIndexes[$sectionTitle];


            /*
            |--------------------------------------------------------------------------
            | Read field type
            |--------------------------------------------------------------------------
            */
            $type = $this->getCell(
                $row,
                $columns['type'] ?? null
            );

            $type = $this->normalizeType(
                (string) $type,
                $label
            );


            /*
            |--------------------------------------------------------------------------
            | Read required
            |--------------------------------------------------------------------------
            */
            $required = $this->getCell(
                $row,
                $columns['required'] ?? null
            );

            $required =
                $this->normalizeBoolean(
                    $required
                );


            /*
            |--------------------------------------------------------------------------
            | Read options
            |--------------------------------------------------------------------------
            */
            $options = $this->getCell(
                $row,
                $columns['options'] ?? null
            );

            $options =
                $this->normalizeOptions(
                    $options
                );


            /*
            |--------------------------------------------------------------------------
            | Make sure option based fields have options
            |--------------------------------------------------------------------------
            */
            if (
                in_array(
                    $type,
                    [
                        'select',
                        'radio',
                        'checkbox',
                    ],
                    true
                )
                &&
                empty($options)
            ) {
                $options = [];
            }


            /*
            |--------------------------------------------------------------------------
            | Generate stable field ID
            |--------------------------------------------------------------------------
            */
            $fieldId =
                'field_' .
                substr(
                    md5(
                        $sectionTitle .
                        '|' .
                        $label .
                        '|' .
                        $rowIndex
                    ),
                    0,
                    12
                );


            /*
            |--------------------------------------------------------------------------
            | Generate unique field key
            |--------------------------------------------------------------------------
            */
            $fieldKey =
                Str::snake($label);

            if ($fieldKey === '') {
                $fieldKey = 'field';
            }

            $fieldKey .=
                '_' .
                substr(
                    md5(
                        $sectionTitle .
                        '|' .
                        $label .
                        '|' .
                        $rowIndex
                    ),
                    0,
                    5
                );


            /*
            |--------------------------------------------------------------------------
            | Create normalized field
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | This structure matches the structure used by
            | manually-created builder fields.
            |
            */
            $field = [

                'id' =>
                    $fieldId,

                'type' =>
                    $type,

                'label' =>
                    $label,

                'key' =>
                    $fieldKey,

                'placeholder' =>
                    '',

                'help' =>
                    '',

                'default' =>
                    '',

                'required' =>
                    $required,

                'options' =>
                    $options,

                'validation' => [

                    'min' =>
                        null,

                    'max' =>
                        null,

                    'min_length' =>
                        null,

                    'max_length' =>
                        null,

                    'url' =>
                        null,

                    'regex' =>
                        null,

                    'file_types' =>
                        null,

                    'file_size' =>
                        null,
                ],
            ];


            /*
            |--------------------------------------------------------------------------
            | Add field to section
            |--------------------------------------------------------------------------
            */
            $sections[
                $sectionIndex
            ]['fields'][] = $field;
        }


        return $sections;
    }


    /**
     * Safely get a cell.
     */
    protected function getCell(
        array $row,
        ?int $index
    ): mixed {

        if ($index === null) {
            return null;
        }

        return $row[$index] ?? null;
    }


    /**
     * Normalize field types.
     *
     * IMPORTANT:
     *
     * The internal schema uses:
     *
     * select
     * radio
     * checkbox
     *
     * "dropdown" is accepted as an Excel alias,
     * but it MUST become "select".
     */
    protected function normalizeType(
        string $type,
        string $label
    ): string {

        $type = strtolower(
            trim($type)
        );

        /*
        |--------------------------------------------------------------------------
        | Remove spaces, hyphens and underscores
        |--------------------------------------------------------------------------
        */
        $type = str_replace(
            [
                '-',
                '_',
                ' ',
            ],
            '',
            $type
        );


        /*
        |--------------------------------------------------------------------------
        | Normalize supported types
        |--------------------------------------------------------------------------
        */
        return match ($type) {

            /*
            |--------------------------------------------------------------------------
            | Text
            |--------------------------------------------------------------------------
            */
            'text',
            'textbox',
            'input',
            'textinput' =>
                'text',


            /*
            |--------------------------------------------------------------------------
            | Textarea
            |--------------------------------------------------------------------------
            */
            'textarea',
            'multiline',
            'longtext' =>
                'textarea',


            /*
            |--------------------------------------------------------------------------
            | Email
            |--------------------------------------------------------------------------
            */
            'email',
            'emailaddress',
            'mail' =>
                'email',


            /*
            |--------------------------------------------------------------------------
            | Phone
            |--------------------------------------------------------------------------
            */
            'phone',
            'tel',
            'telephone',
            'mobile' =>
                'phone',


            /*
            |--------------------------------------------------------------------------
            | Number
            |--------------------------------------------------------------------------
            */
            'number',
            'numeric',
            'integer',
            'decimal' =>
                'number',


            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */
            'date',
            'datepicker' =>
                'date',


            /*
            |--------------------------------------------------------------------------
            | Dropdown / Select
            |--------------------------------------------------------------------------
            |
            | Excel may contain:
            |
            | dropdown
            | drop-down
            | select
            | choice
            | choices
            |
            | ALL become:
            |
            | select
            |
            |--------------------------------------------------------------------------
            */
            'dropdown',
            'select',
            'choice',
            'choices' =>
                'select',


            /*
            |--------------------------------------------------------------------------
            | Radio
            |--------------------------------------------------------------------------
            */
            'radio',
            'radiobutton',
            'singlechoice' =>
                'radio',


            /*
            |--------------------------------------------------------------------------
            | Checkbox
            |--------------------------------------------------------------------------
            */
            'checkbox',
            'checkboxes',
            'multichoice',
            'multiplechoice' =>
                'checkbox',


            /*
            |--------------------------------------------------------------------------
            | File
            |--------------------------------------------------------------------------
            */
            'file',
            'upload',
            'fileupload',
            'attachment' =>
                'file',


            /*
            |--------------------------------------------------------------------------
            | Unknown / empty type
            |--------------------------------------------------------------------------
            */
            default =>
                $this->inferTypeFromLabel(
                    $label
                ),
        };
    }


    /**
     * Infer field type from label
     * when Excel Type is empty or unknown.
     */
    protected function inferTypeFromLabel(
        string $label
    ): string {

        $label =
            strtolower(
                trim($label)
            );


        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */
        if (
            str_contains(
                $label,
                'email'
            )
        ) {
            return 'email';
        }


        /*
        |--------------------------------------------------------------------------
        | Phone
        |--------------------------------------------------------------------------
        */
        if (
            str_contains(
                $label,
                'phone'
            )
            ||
            str_contains(
                $label,
                'mobile'
            )
            ||
            str_contains(
                $label,
                'telephone'
            )
        ) {
            return 'phone';
        }


        /*
        |--------------------------------------------------------------------------
        | Date
        |--------------------------------------------------------------------------
        */
        if (
            str_contains(
                $label,
                'date'
            )
        ) {
            return 'date';
        }


        /*
        |--------------------------------------------------------------------------
        | File
        |--------------------------------------------------------------------------
        */
        if (
            str_contains(
                $label,
                'resume'
            )
            ||
            str_contains(
                $label,
                'attachment'
            )
            ||
            str_contains(
                $label,
                'upload'
            )
            ||
            str_contains(
                $label,
                'document'
            )
        ) {
            return 'file';
        }


        /*
        |--------------------------------------------------------------------------
        | Number
        |--------------------------------------------------------------------------
        */
        if (
            str_contains(
                $label,
                'experience'
            )
            ||
            str_contains(
                $label,
                'age'
            )
            ||
            str_contains(
                $label,
                'amount'
            )
            ||
            str_contains(
                $label,
                'number'
            )
        ) {
            return 'number';
        }


        /*
        |--------------------------------------------------------------------------
        | Default
        |--------------------------------------------------------------------------
        */
        return 'text';
    }


    /**
     * Normalize required value.
     */
    protected function normalizeBoolean(
        mixed $value
    ): bool {

        if ($value === null) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Boolean values
        |--------------------------------------------------------------------------
        */
        if (is_bool($value)) {
            return $value;
        }


        /*
        |--------------------------------------------------------------------------
        | Numeric values
        |--------------------------------------------------------------------------
        */
        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }


        $value =
            strtolower(
                trim(
                    (string) $value
                )
            );


        return in_array(
            $value,
            [
                'yes',
                'y',
                'true',
                '1',
                'required',
                'mandatory',
            ],
            true
        );
    }


    /**
     * Normalize options.
     *
     * Excel example:
     *
     * Male|Female|Other
     *
     * becomes:
     *
     * [
     *     [
     *         'label' => 'Male',
     *         'value' => 'male'
     *     ],
     *     ...
     * ]
     */
    protected function normalizeOptions(
        mixed $value
    ): array {

        if ($value === null) {
            return [];
        }


        /*
        |--------------------------------------------------------------------------
        | If Excel already gives us an array
        |--------------------------------------------------------------------------
        */
        if (is_array($value)) {

            $result = [];

            foreach ($value as $option) {

                if (is_array($option)) {

                    $label =
                        trim(
                            (string) (
                                $option['label']
                                ??
                                $option['value']
                                ??
                                ''
                            )
                        );

                    $optionValue =
                        trim(
                            (string) (
                                $option['value']
                                ??
                                Str::snake($label)
                            )
                        );

                } else {

                    $label =
                        trim(
                            (string) $option
                        );

                    $optionValue =
                        Str::snake(
                            $label
                        );
                }


                if ($label === '') {
                    continue;
                }


                $result[] = [

                    'label' =>
                        $label,

                    'value' =>
                        $optionValue,
                ];
            }


            return array_values(
                $result
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Convert value to string
        |--------------------------------------------------------------------------
        */
        $value =
            trim(
                (string) $value
            );


        if ($value === '') {
            return [];
        }


        /*
        |--------------------------------------------------------------------------
        | Support pipe-separated options
        |--------------------------------------------------------------------------
        |
        | Male|Female|Other
        |
        |--------------------------------------------------------------------------
        */
        $options =
            preg_split(
                '/\s*\|\s*/',
                $value
            );


        if (!$options) {
            return [];
        }


        $result = [];


        foreach ($options as $option) {

            $label =
                trim(
                    (string) $option
                );


            if ($label === '') {
                continue;
            }


            $result[] = [

                'label' =>
                    $label,

                'value' =>
                    Str::snake(
                        $label
                    ),
            ];
        }


        return array_values(
            $result
        );
    }
}