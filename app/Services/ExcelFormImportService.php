<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelFormImportService
{
    /**
     * Parse an Excel file into a normalized import structure.
     *
     * Supported layouts:
     *
     * 1. Structured layout:
     *
     *    Section | Label | Type | Required | Options
     *
     * 2. Plain header-row layout:
     *
     *    Name | Email | Phone | Age | Date of Birth
     *
     *    John | test@example.com | 9999999999 | 30 | 1996-01-01
     */
    public function parse(string $path): array
    {
        /*
        |--------------------------------------------------------------------------
        | Load spreadsheet
        |--------------------------------------------------------------------------
        |
        | readDataOnly() prevents PhpSpreadsheet from loading formula
        | calculations/styles unnecessarily.
        |
        */

        $reader = IOFactory::createReaderForFile($path);

        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);

        $allSections = [];

        $errors = [];

        /*
        |--------------------------------------------------------------------------
        | Process every worksheet
        |--------------------------------------------------------------------------
        */

        foreach (
            $spreadsheet->getWorksheetIterator()
            as $worksheet
        ) {

            try {

                $sheetName = $worksheet->getTitle();

                $rows = $worksheet->toArray(
                    null,
                    true,
                    true,
                    false
                );

                /*
                |--------------------------------------------------------------------------
                | Skip empty sheets
                |--------------------------------------------------------------------------
                */

                if (empty($rows)) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | First try structured layout
                |--------------------------------------------------------------------------
                */

                $header = $this->findHeaderRow($rows);

                if ($header) {

                    $sections = $this->parseRows(
                        $rows,
                        $header['row'],
                        $header['columns']
                    );

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Try plain header-row layout
                    |--------------------------------------------------------------------------
                    */

                    $plainHeader = $this->findPlainHeaderRow(
                        $rows
                    );

                    if ($plainHeader) {

                        $sections =
                            $this->parsePlainHeaderRows(
                                $rows,
                                $plainHeader['row'],
                                $plainHeader['columns']
                            );

                    } else {

                        $errors[] = [
                            'sheet' =>
                                $sheetName,

                            'message' =>
                                'Could not detect a supported Excel layout. '
                                . 'Use either "Section | Label | Type | Required | Options" '
                                . 'or a plain header row containing field names.',
                        ];

                        continue;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Check parsed result
                |--------------------------------------------------------------------------
                */

                if (empty($sections)) {

                    $errors[] = [
                        'sheet' =>
                            $sheetName,

                        'message' =>
                            'The worksheet was readable, but no form fields '
                            . 'could be detected.',
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Add parsed sections
                |--------------------------------------------------------------------------
                */

                foreach ($sections as $section) {

                    $allSections[] = $section;
                }

            } catch (\Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | Don't allow one bad worksheet to break the whole workbook
                |--------------------------------------------------------------------------
                */

                $errors[] = [
                    'sheet' =>
                        $worksheet->getTitle(),

                    'message' =>
                        'Could not parse this worksheet: '
                        . $e->getMessage(),
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Free spreadsheet memory
        |--------------------------------------------------------------------------
        */

        $spreadsheet->disconnectWorksheets();

        unset($spreadsheet);

        /*
        |--------------------------------------------------------------------------
        | Return normalized structure
        |--------------------------------------------------------------------------
        */

        return [
            'format' =>
                'xlsx',

            'sections' =>
                $allSections,

            'errors' =>
                $errors,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | STRUCTURED EXCEL LAYOUT
    |--------------------------------------------------------------------------
    */

    /**
     * Detect structured header row.
     *
     * Example:
     *
     * Section | Label | Type | Required | Options
     */
    protected function findHeaderRow(
        array $rows
    ): ?array {

        foreach (
            $rows as $rowIndex => $row
        ) {

            $columns = [];

            foreach (
                $row as $columnIndex => $value
            ) {

                if ($value === null) {
                    continue;
                }

                $header = strtolower(
                    trim((string) $value)
                );

                if ($header === '') {
                    continue;
                }

                $normalized = match ($header) {

                    'section',
                    'sections',
                    'group',
                    'category' =>
                        'section',

                    'label',
                    'field label',
                    'field',
                    'question',
                    'question label',
                    'name',
                    'field name' =>
                        'label',

                    'type',
                    'field type',
                    'input type' =>
                        'type',

                    'required',
                    'is required',
                    'mandatory',
                    'is mandatory' =>
                        'required',

                    'options',
                    'choices',
                    'values',
                    'option values',
                    'choice values' =>
                        'options',

                    default =>
                        null,
                };

                if ($normalized) {

                    /*
                    |--------------------------------------------------------------------------
                    | Don't overwrite the first occurrence
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !isset(
                            $columns[$normalized]
                        )
                    ) {
                        $columns[$normalized] =
                            $columnIndex;
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Label column is mandatory for structured layout
            |--------------------------------------------------------------------------
            */

            if (
                isset(
                    $columns['label']
                )
            ) {

                return [
                    'row' =>
                        $rowIndex,

                    'columns' =>
                        $columns,
                ];
            }
        }

        return null;
    }


    /**
     * Parse structured rows.
     */
    protected function parseRows(
        array $rows,
        int $headerRow,
        array $columns
    ): array {

        $sections = [];

        $sectionIndexes = [];

        for (
            $rowIndex = $headerRow + 1;
            $rowIndex < count($rows);
            $rowIndex++
        ) {

            $row = $rows[$rowIndex];

            /*
            |--------------------------------------------------------------------------
            | Read label
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
            | Section
            |--------------------------------------------------------------------------
            */

            $sectionTitle =
                $this->getCell(
                    $row,
                    $columns['section'] ?? null
                );

            $sectionTitle =
                trim(
                    (string) $sectionTitle
                );

            if ($sectionTitle === '') {
                $sectionTitle =
                    'Imported Fields';
            }

            /*
            |--------------------------------------------------------------------------
            | Create section
            |--------------------------------------------------------------------------
            */

            if (
                !isset(
                    $sectionIndexes[$sectionTitle]
                )
            ) {

                $sectionIndexes[$sectionTitle] =
                    count($sections);

                $sections[] = [
                    'id' =>
                        'section_'
                        . substr(
                            md5($sectionTitle),
                            0,
                            10
                        ),

                    'title' =>
                        $sectionTitle,

                    'fields' =>
                        [],
                ];
            }

            $sectionIndex =
                $sectionIndexes[$sectionTitle];

            /*
            |--------------------------------------------------------------------------
            | Type
            |--------------------------------------------------------------------------
            */

            $type =
                $this->getCell(
                    $row,
                    $columns['type'] ?? null
                );

            $type =
                $this->normalizeType(
                    (string) $type,
                    $label
                );

            /*
            |--------------------------------------------------------------------------
            | Required
            |--------------------------------------------------------------------------
            */

            $required =
                $this->getCell(
                    $row,
                    $columns['required'] ?? null
                );

            $required =
                $this->normalizeBoolean(
                    $required
                );

            /*
            |--------------------------------------------------------------------------
            | Options
            |--------------------------------------------------------------------------
            */

            $options =
                $this->getCell(
                    $row,
                    $columns['options'] ?? null
                );

            $options =
                $this->normalizeOptions(
                    $options
                );

            /*
            |--------------------------------------------------------------------------
            | Field
            |--------------------------------------------------------------------------
            */

            $field = [
                'id' =>
                    'field_'
                    . substr(
                        md5(
                            $sectionTitle
                            . '|'
                            . $label
                            . '|'
                            . $rowIndex
                        ),
                        0,
                        12
                    ),

                'type' =>
                    $type,

                'label' =>
                    $label,

                'required' =>
                    $required,

                'options' =>
                    $options,
            ];

            /*
            |--------------------------------------------------------------------------
            | Add field
            |--------------------------------------------------------------------------
            */

            $sections[$sectionIndex]['fields'][] =
                $field;
        }

        return $sections;
    }


    /*
    |--------------------------------------------------------------------------
    | PLAIN HEADER-ROW LAYOUT
    |--------------------------------------------------------------------------
    */

    /**
     * Detect a simple header-row spreadsheet.
     *
     * Example:
     *
     * Name | Email | Phone | Age
     */
    protected function findPlainHeaderRow(
        array $rows
    ): ?array {

        foreach (
            $rows as $rowIndex => $row
        ) {

            $columns = [];

            foreach (
                $row as $columnIndex => $value
            ) {

                if ($value === null) {
                    continue;
                }

                $header =
                    trim(
                        (string) $value
                    );

                if ($header === '') {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Ignore obvious metadata/header words
                |--------------------------------------------------------------------------
                */

                $normalized =
                    strtolower(
                        preg_replace(
                            '/[^a-z0-9]+/',
                            '',
                            $header
                        )
                    );

                if (
                    in_array(
                        $normalized,
                        [
                            'section',
                            'sections',
                            'label',
                            'field',
                            'fieldlabel',
                            'type',
                            'fieldtype',
                            'required',
                            'mandatory',
                            'options',
                            'choices',
                            'values',
                        ],
                        true
                    )
                ) {
                    /*
                    |------------------------------------------------------------------
                    | This is probably the structured layout, which should have
                    | already been detected by findHeaderRow().
                    |------------------------------------------------------------------
                    */

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Treat non-empty cell as a field header
                |--------------------------------------------------------------------------
                */

                $columns[$columnIndex] =
                    $header;
            }

            /*
            |--------------------------------------------------------------------------
            | A plain header row should contain at least 2 fields
            |--------------------------------------------------------------------------
            */

            if (
                count($columns) >= 2
            ) {

                return [
                    'row' =>
                        $rowIndex,

                    'columns' =>
                        $columns,
                ];
            }
        }

        return null;
    }


    /**
     * Parse plain header-row spreadsheet.
     *
     * Each header becomes a form field.
     *
     * Example:
     *
     * Name | Email | Phone
     *
     * becomes:
     *
     * Imported Fields
     *   - Name
     *   - Email
     *   - Phone
     */
    protected function parsePlainHeaderRows(
        array $rows,
        int $headerRow,
        array $columns
    ): array {

        $fields = [];

        foreach (
            $columns as $columnIndex => $label
        ) {

            $label =
                trim(
                    (string) $label
                );

            if ($label === '') {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Infer type from field label
            |--------------------------------------------------------------------------
            */

            $type =
                $this->inferTypeFromLabel(
                    $label
                );

            /*
            |--------------------------------------------------------------------------
            | Inspect sample values
            |--------------------------------------------------------------------------
            |
            | This is deliberately conservative.
            | We don't automatically convert arbitrary values to dropdowns,
            | because doing so could incorrectly change a normal text field.
            |
            */

            $sampleValues =
                $this->getColumnSampleValues(
                    $rows,
                    $headerRow,
                    $columnIndex
                );

            /*
            |--------------------------------------------------------------------------
            | Better numeric detection
            |--------------------------------------------------------------------------
            */

            if (
                $type === 'text'
                && !empty($sampleValues)
                && $this->looksNumeric(
                    $sampleValues
                )
            ) {

                $type = 'number';
            }

            /*
            |--------------------------------------------------------------------------
            | Better date detection
            |--------------------------------------------------------------------------
            */

            if (
                $type === 'text'
                && !empty($sampleValues)
                && $this->looksLikeDates(
                    $sampleValues
                )
            ) {

                $type = 'date';
            }

            /*
            |--------------------------------------------------------------------------
            | Create field
            |--------------------------------------------------------------------------
            */

            $fields[] = [

                'id' =>
                    'field_'
                    . substr(
                        md5(
                            $label
                            . '|'
                            . $columnIndex
                        ),
                        0,
                        12
                    ),

                'type' =>
                    $type,

                'label' =>
                    $label,

                /*
                |--------------------------------------------------------------------------
                | Plain header-row imports are optional by default.
                |--------------------------------------------------------------------------
                */

                'required' =>
                    false,

                'options' =>
                    [],
            ];
        }

        if (empty($fields)) {
            return [];
        }

        return [
            [
                'id' =>
                    'section_'
                    . substr(
                        md5(
                            'Imported Fields'
                        ),
                        0,
                        10
                    ),

                'title' =>
                    'Imported Fields',

                'fields' =>
                    $fields,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | PLAIN HEADER HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Get sample values from a column.
     */
    protected function getColumnSampleValues(
        array $rows,
        int $headerRow,
        int $columnIndex
    ): array {

        $values = [];

        /*
        |--------------------------------------------------------------------------
        | Only inspect a reasonable number of rows.
        |--------------------------------------------------------------------------
        */

        $maxRows =
            min(
                count($rows),
                $headerRow + 21
            );

        for (
            $rowIndex = $headerRow + 1;
            $rowIndex < $maxRows;
            $rowIndex++
        ) {

            $value =
                $rows[$rowIndex][$columnIndex]
                ?? null;

            if (
                $value === null
                || trim((string) $value) === ''
            ) {
                continue;
            }

            $values[] =
                trim(
                    (string) $value
                );
        }

        return $values;
    }


    /**
     * Determine whether sample values are numeric.
     */
    protected function looksNumeric(
        array $values
    ): bool {

        if (empty($values)) {
            return false;
        }

        $numericCount = 0;

        foreach ($values as $value) {

            $clean =
                str_replace(
                    [',', ' '],
                    '',
                    $value
                );

            if (
                is_numeric($clean)
            ) {
                $numericCount++;
            }
        }

        return
            $numericCount >=
            max(
                1,
                (int) ceil(
                    count($values) * 0.8
                )
            );
    }


    /**
     * Determine whether sample values look like dates.
     */
    protected function looksLikeDates(
        array $values
    ): bool {

        if (empty($values)) {
            return false;
        }

        $dateCount = 0;

        foreach ($values as $value) {

            if (
                is_numeric($value)
            ) {
                /*
                |--------------------------------------------------------------------------
                | Excel serial dates are numeric, but don't automatically
                | classify all numeric values as dates.
                |--------------------------------------------------------------------------
                */

                continue;
            }

            $timestamp =
                strtotime($value);

            if (
                $timestamp !== false
            ) {
                $dateCount++;
            }
        }

        return
            $dateCount >=
            max(
                1,
                (int) ceil(
                    count($values) * 0.8
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | COMMON HELPERS
    |--------------------------------------------------------------------------
    */

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
     * Normalize field type.
     */
    protected function normalizeType(
        string $type,
        string $label
    ): string {

        $type =
            strtolower(
                trim($type)
            );

        $type =
            str_replace(
                ['-', '_', ' '],
                '',
                $type
            );

        return match ($type) {

            'text',
            'textbox',
            'input',
            'textinput' =>
                'text',

            'textarea',
            'multiline',
            'longtext' =>
                'textarea',

            'email',
            'emailaddress' =>
                'email',

            'phone',
            'tel',
            'telephone',
            'mobilenumber' =>
                'phone',

            'number',
            'numeric',
            'integer',
            'decimal',
            'float',
            'amount' =>
                'number',

            'date',
            'datepicker' =>
                'date',

            'dropdown',
            'select',
            'selectbox' =>
                'dropdown',

            'radio',
            'choice',
            'choices',
            'singlechoice' =>
                'radio',

            'checkbox',
            'checkboxes',
            'multichoice',
            'multiplechoice' =>
                'checkbox',

            'file',
            'upload',
            'fileupload',
            'attachment' =>
                'file',

            'rating',
            'stars',
            'star' =>
                'rating',

            default =>
                $this->inferTypeFromLabel(
                    $label
                ),
        };
    }


    /**
     * Infer field type from label.
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
            ||
            str_contains(
                $label,
                'contact number'
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
            ||
            str_contains(
                $label,
                'dob'
            )
            ||
            str_contains(
                $label,
                'birth date'
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
                'cv'
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
            ||
            str_contains(
                $label,
                'quantity'
            )
            ||
            str_contains(
                $label,
                'experience'
            )
            ||
            str_contains(
                $label,
                'percentage'
            )
            ||
            str_contains(
                $label,
                'price'
            )
        ) {
            return 'number';
        }

        /*
        |--------------------------------------------------------------------------
        | Long text
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $label,
                'description'
            )
            ||
            str_contains(
                $label,
                'comments'
            )
            ||
            str_contains(
                $label,
                'address'
            )
            ||
            str_contains(
                $label,
                'message'
            )
            ||
            str_contains(
                $label,
                'details'
            )
            ||
            str_contains(
                $label,
                'summary'
            )
        ) {
            return 'textarea';
        }

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

        if (
            is_bool($value)
        ) {
            return $value;
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
     * Supports:
     *
     * Male | Female | Other
     *
     * Male, Female, Other
     *
     * Male
     * Female
     * Other
     */
    protected function normalizeOptions(
        mixed $value
    ): array {

        if (
            $value === null
        ) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | If an array is supplied
        |--------------------------------------------------------------------------
        */

        if (
            is_array($value)
        ) {

            $options = [];

            foreach ($value as $option) {

                $option =
                    trim(
                        (string) $option
                    );

                if ($option === '') {
                    continue;
                }

                $options[] = $option;
            }

            return array_values(
                array_unique(
                    $options
                )
            );
        }

        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Pipe-separated
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $value,
                '|'
            )
        ) {

            $options =
                preg_split(
                    '/\s*\|\s*/',
                    $value
                );

        /*
        |--------------------------------------------------------------------------
        | Comma-separated
        |--------------------------------------------------------------------------
        */

        } elseif (
            str_contains(
                $value,
                ','
            )
        ) {

            $options =
                preg_split(
                    '/\s*,\s*/',
                    $value
                );

        /*
        |--------------------------------------------------------------------------
        | Single option
        |--------------------------------------------------------------------------
        */

        } else {

            $options = [
                $value,
            ];
        }

        if (!$options) {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        fn ($option) =>
                            trim(
                                (string) $option
                            ),
                        $options
                    ),
                    fn ($option) =>
                        $option !== ''
                )
            )
        );
    }
}