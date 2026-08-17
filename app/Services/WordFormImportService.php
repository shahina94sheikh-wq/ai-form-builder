<?php

namespace App\Services;

use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\ListItemRun;

class WordFormImportService
{
    /**
     * Parse a DOCX document and convert it
     * into the application's form schema.
     */
    public function parse(string $path): array
    {
        $sections = [];
        $errors = [];

        try {

            if (!is_file($path)) {
                throw new \RuntimeException(
                    'The Word document could not be found.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Load DOCX
            |--------------------------------------------------------------------------
            */

            $phpWord = IOFactory::load($path);


            /*
            |--------------------------------------------------------------------------
            | Current section
            |--------------------------------------------------------------------------
            */

            $currentSection = [
                'id' => 'section_' . Str::lower(
                    Str::random(10)
                ),

                'title' => 'General Information',

                'fields' => [],
            ];


            /*
            |--------------------------------------------------------------------------
            | Temporary option list
            |--------------------------------------------------------------------------
            */

            $pendingOptions = [];


            /*
            |--------------------------------------------------------------------------
            | Process document sections
            |--------------------------------------------------------------------------
            */

            foreach ($phpWord->getSections() as $wordSection) {

                foreach (
                    $wordSection->getElements()
                    as $element
                ) {

                    $text = $this->extractText($element);

                    if ($text === '') {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Heading detection
                    |--------------------------------------------------------------------------
                    */

                    $style = $this->getStyleName($element);

                    if ($this->isHeading($style, $element)) {

                        /*
                        | Save current section if it contains fields.
                        */

                        if (
                            !empty($currentSection['fields'])
                        ) {

                            $sections[] =
                                $currentSection;
                        }


                        $currentSection = [
                            'id' =>
                                'section_' .
                                Str::lower(
                                    Str::random(10)
                                ),

                            'title' => $text,

                            'fields' => [],
                        ];

                        $pendingOptions = [];

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Detect option/list items
                    |--------------------------------------------------------------------------
                    */

                    if ($this->isListItem($element)) {

                        $option = $this->cleanOption($text);

                        if ($option !== '') {
                            $pendingOptions[] = $option;
                        }

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Checkbox / choice notation
                    |--------------------------------------------------------------------------
                    |
                    | Examples:
                    |
                    | ☐ PHP
                    | ☐ Laravel
                    | □ React
                    | ☑ WordPress
                    |
                    */

                    if ($this->looksLikeOption($text)) {

                        $option = $this->cleanOption($text);

                        if ($option !== '') {
                            $pendingOptions[] = $option;
                        }

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Detect question / field
                    |--------------------------------------------------------------------------
                    */

                    if ($this->looksLikeQuestion($text)) {

                        /*
                        | If previous options exist, attach them to
                        | the newly detected question.
                        */

                        $field = $this->makeField(
                            $text,
                            $pendingOptions
                        );

                        $currentSection['fields'][] =
                            $field;

                        $pendingOptions = [];

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Plain text
                    |--------------------------------------------------------------------------
                    |
                    | A non-heading paragraph is treated as a
                    | possible question/field.
                    |
                    */

                    $field = $this->makeField(
                        $text,
                        $pendingOptions
                    );

                    $currentSection['fields'][] =
                        $field;

                    $pendingOptions = [];
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Add final section
            |--------------------------------------------------------------------------
            */

            if (
                !empty($currentSection['fields'])
            ) {

                $sections[] =
                    $currentSection;
            }


            /*
            |--------------------------------------------------------------------------
            | Defensive cleanup
            |--------------------------------------------------------------------------
            */

            $sections = array_values(
                array_filter(
                    $sections,
                    function ($section) {

                        return !empty(
                            $section['fields']
                        );
                    }
                )
            );


            /*
            |--------------------------------------------------------------------------
            | Return schema
            |--------------------------------------------------------------------------
            */

            return [
                'format' => 'docx',

                'sections' => $sections,

                'errors' => $errors,
            ];

        } catch (\Throwable $e) {

            return [
                'format' => 'docx',

                'sections' => [],

                'errors' => [
                    [
                        'message' =>
                            $e->getMessage(),
                    ],
                ],
            ];
        }
    }


    /**
     * Extract readable text from a PHPWord element.
     */
    protected function extractText($element): string
    {
        /*
        |--------------------------------------------------------------------------
        | Direct Text element
        |--------------------------------------------------------------------------
        */

        if ($element instanceof Text) {

            return $this->cleanText(
                $element->getText()
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TextRun
        |--------------------------------------------------------------------------
        */

        if ($element instanceof TextRun) {

            $text = '';

            foreach (
                $element->getElements()
                as $child
            ) {

                if (
                    $child instanceof Text
                ) {

                    $text .= ' ' .
                        $child->getText();
                }
            }

            return $this->cleanText($text);
        }


        /*
        |--------------------------------------------------------------------------
        | List item
        |--------------------------------------------------------------------------
        */

        if (
            $element instanceof ListItem
            || $element instanceof ListItemRun
        ) {

            $text = '';

            if (method_exists(
                $element,
                'getElements'
            )) {

                foreach (
                    $element->getElements()
                    as $child
                ) {

                    if (
                        $child instanceof Text
                    ) {

                        $text .= ' ' .
                            $child->getText();
                    }
                }
            }

            return $this->cleanText($text);
        }


        /*
        |--------------------------------------------------------------------------
        | Generic element
        |--------------------------------------------------------------------------
        */

        if (
            method_exists(
                $element,
                'getText'
            )
        ) {

            try {

                return $this->cleanText(
                    $element->getText()
                );

            } catch (\Throwable) {
                // Ignore unsupported element.
            }
        }


        return '';
    }


    /**
     * Get style name safely.
     */
    protected function getStyleName($element): string
    {
        try {

            if (
                method_exists(
                    $element,
                    'getStyleName'
                )
            ) {

                return strtolower(
                    (string)
                    $element->getStyleName()
                );
            }

        } catch (\Throwable) {
            //
        }

        return '';
    }


    /**
     * Detect headings.
     */
    protected function isHeading(
        string $style,
        $element
    ): bool {

        if (
            str_contains(
                $style,
                'heading'
            )
        ) {

            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | Common Word styles
        |--------------------------------------------------------------------------
        */

        return in_array(
            $style,
            [
                'title',
                'subtitle',
                'heading 1',
                'heading 2',
                'heading 3',
                'heading1',
                'heading2',
                'heading3',
            ],
            true
        );
    }


    /**
     * Detect Word list items.
     */
    protected function isListItem($element): bool
    {
        return
            $element instanceof ListItem
            ||
            $element instanceof ListItemRun;
    }


    /**
     * Detect checkbox / choice list notation.
     */
    protected function looksLikeOption(
        string $text
    ): bool {

        if ($text === '') {
            return false;
        }


        $prefixes = [
            '☐',
            '☑',
            '□',
            '■',
            '◻',
            '◼',
            '[ ]',
            '[x]',
            '[X]',
            '-',
            '•',
        ];


        foreach ($prefixes as $prefix) {

            if (
                str_starts_with(
                    trim($text),
                    $prefix
                )
            ) {

                return true;
            }
        }


        return false;
    }


    /**
     * Remove option markers.
     */
    protected function cleanOption(
        string $text
    ): string {

        $text = trim($text);


        $text = preg_replace(
            '/^(☐|☑|□|■|◻|◼|\[\s?\]|\[\s?[xX]\s?\]|[-•])\s*/u',
            '',
            $text
        );


        return trim($text ?? '');
    }


    /**
     * Determine whether text looks like a question.
     */
    protected function looksLikeQuestion(
        string $text
    ): bool {

        if ($text === '') {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Question mark
        |--------------------------------------------------------------------------
        */

        if (
            str_ends_with(
                $text,
                '?'
            )
        ) {

            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | Common form labels
        |--------------------------------------------------------------------------
        */

        $patterns = [
            '/\bname\b/i',
            '/\bemail\b/i',
            '/\bphone\b/i',
            '/\bmobile\b/i',
            '/\bage\b/i',
            '/\baddress\b/i',
            '/\bgender\b/i',
            '/\bdob\b/i',
            '/\bdate of birth\b/i',
            '/\bdate\b/i',
            '/\bnumber\b/i',
            '/\bexperience\b/i',
            '/\bcomments?\b/i',
            '/\bmessage\b/i',
            '/\bresume\b/i',
            '/\bskills?\b/i',
            '/\boccupation\b/i',
            '/\bcompany\b/i',
        ];


        foreach ($patterns as $pattern) {

            if (
                preg_match(
                    $pattern,
                    $text
                )
            ) {

                return true;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Colon usually indicates a form label.
        |--------------------------------------------------------------------------
        */

        if (
            str_ends_with(
                $text,
                ':'
            )
        ) {

            return true;
        }


        return true;
    }


    /**
     * Create a form field.
     */
    protected function makeField(
        string $label,
        array $options = []
    ): array {

        $label = trim(
            rtrim(
                $label,
                ':'
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Remove question marker
        |--------------------------------------------------------------------------
        */

        $label = preg_replace(
            '/\?$/',
            '',
            $label
        );


        $label = trim(
            $label ?? ''
        );


        /*
        |--------------------------------------------------------------------------
        | Detect field type
        |--------------------------------------------------------------------------
        */

        $type = $this->detectType(
            $label,
            $options
        );


        /*
        |--------------------------------------------------------------------------
        | Build field
        |--------------------------------------------------------------------------
        */

        $field = [
            'id' =>
                'field_' .
                Str::lower(
                    Str::random(10)
                ),

            'type' => $type,

            'label' => $label,

            'required' => false,

            'options' => [],

            'placeholder' =>
                $this->placeholderFor(
                    $type,
                    $label
                ),
        ];


        /*
        |--------------------------------------------------------------------------
        | Add options
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
        ) {

            foreach (
                $options as $option
            ) {

                $field['options'][] = [
                    'label' => $option,
                    'value' => Str::slug(
                        $option,
                        '_'
                    ),
                ];
            }
        }


        return $field;
    }


    /**
     * Detect field type from label/options.
     */
    protected function detectType(
        string $label,
        array $options
    ): string {

        $lower = strtolower(
            $label
        );


        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $lower,
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
            preg_match(
                '/phone|mobile|telephone|contact number/',
                $lower
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
            preg_match(
                '/date of birth|\bdob\b|\bdate\b/',
                $lower
            )
        ) {

            return 'date';
        }


        /*
        |--------------------------------------------------------------------------
        | Number
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/age|number|quantity|amount|experience|years/',
                $lower
            )
        ) {

            return 'number';
        }


        /*
        |--------------------------------------------------------------------------
        | Choice fields
        |--------------------------------------------------------------------------
        */

        if (!empty($options)) {

            if (
                preg_match(
                    '/select|choose|category|type|gender|country/',
                    $lower
                )
            ) {

                return 'select';
            }


            return 'checkbox';
        }


        /*
        |--------------------------------------------------------------------------
        | Textarea
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/comment|message|description|address|feedback|remark/',
                $lower
            )
        ) {

            return 'textarea';
        }


        /*
        |--------------------------------------------------------------------------
        | Default
        |--------------------------------------------------------------------------
        */

        return 'text';
    }


    /**
     * Generate placeholder.
     */
    protected function placeholderFor(
        string $type,
        string $label
    ): string {

        return match ($type) {

            'email' =>
                'Enter your email address',

            'phone' =>
                'Enter your phone number',

            'number' =>
                'Enter ' . strtolower($label),

            'date' =>
                'Select ' . strtolower($label),

            'textarea' =>
                'Enter ' . strtolower($label),

            default =>
                'Enter ' . strtolower($label),
        };
    }


    /**
     * Clean extracted text.
     */
    protected function cleanText(
        ?string $text
    ): string {

        if ($text === null) {
            return '';
        }


        $text = strip_tags(
            $text
        );


        $text = preg_replace(
            '/\s+/u',
            ' ',
            $text
        );


        return trim(
            $text ?? ''
        );
    }
}