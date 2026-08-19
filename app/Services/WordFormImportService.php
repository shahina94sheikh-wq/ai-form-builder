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
     * Parse a DOCX document and convert it into the application's form schema.
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

            $phpWord = IOFactory::load($path);

            $currentSection = [
                'id' => 'section_' . Str::lower(Str::random(10)),
                'title' => 'General Information',
                'fields' => [],
            ];

            /*
             * Keep track of the most recently created field.
             *
             * This is important because Word form options normally
             * appear AFTER their field label:
             *
             * Gender:
             * ☐ Male
             * ☐ Female
             * ☐ Other
             */
            $lastFieldIndex = null;

            foreach ($phpWord->getSections() as $wordSection) {
                foreach ($wordSection->getElements() as $element) {

                    $text = $this->extractText($element);

                    if ($text === '') {
                        continue;
                    }

                    $style = $this->getStyleName($element);

                    /*
                     * ----------------------------------------------------------
                     * Heading detection
                     * ----------------------------------------------------------
                     */
                    if ($this->isHeading($style, $element)) {

                        if (!empty($currentSection['fields'])) {
                            $sections[] = $currentSection;
                        }

                        $currentSection = [
                            'id' => 'section_' . Str::lower(Str::random(10)),
                            'title' => trim($text),
                            'fields' => [],
                        ];

                        $lastFieldIndex = null;

                        continue;
                    }

                    /*
                     * ----------------------------------------------------------
                     * Option detection
                     * ----------------------------------------------------------
                     *
                     * Options occur AFTER the field label in the Word file.
                     *
                     * Example:
                     *
                     * Gender:
                     * ☐ Male
                     * ☐ Female
                     * ☐ Other
                     *
                     * Therefore attach them to the LAST field instead of
                     * waiting for the next field.
                     */
                    if ($this->looksLikeOption($text)) {

                        $option = $this->cleanOption($text);

                        if (
                            $option !== '' &&
                            $lastFieldIndex !== null &&
                            isset(
                                $currentSection['fields'][$lastFieldIndex]
                            )
                        ) {

                            $fieldType = $this->optionTypeFromMarker($text);

                            /*
                             * If Word explicitly used a circle:
                             *     ○ Full-time
                             *
                             * treat it as radio.
                             *
                             * If Word used a checkbox:
                             *     ☐ PHP
                             *
                             * treat it as checkbox.
                             */
                            if ($fieldType !== null) {
                                $currentSection['fields'][$lastFieldIndex]['type']
                                    = $fieldType;
                            }

                            $currentSection['fields'][$lastFieldIndex]['options'][] = [
                                'label' => $option,
                                'value' => Str::slug($option, '_'),
                            ];
                        }

                        continue;
                    }

                    /*
                     * ----------------------------------------------------------
                     * List items without explicit checkbox/radio symbols
                     * ----------------------------------------------------------
                     */
                    if ($this->isListItem($element)) {

                        $option = $this->cleanOption($text);

                        if (
                            $option !== '' &&
                            $lastFieldIndex !== null &&
                            isset(
                                $currentSection['fields'][$lastFieldIndex]
                            )
                        ) {

                            /*
                             * Preserve existing behavior for normal list
                             * options. If the field is already a choice field,
                             * attach the option to it.
                             */
                            $existingType =
                                $currentSection['fields'][$lastFieldIndex]['type']
                                ?? 'text';

                            if (
                                in_array(
                                    $existingType,
                                    ['select', 'radio', 'checkbox'],
                                    true
                                )
                            ) {
                                $currentSection['fields'][$lastFieldIndex]['options'][] = [
                                    'label' => $option,
                                    'value' => Str::slug($option, '_'),
                                ];
                            }
                        }

                        continue;
                    }

                    /*
                     * ----------------------------------------------------------
                     * Ignore common instruction paragraphs
                     * ----------------------------------------------------------
                     */
                    if ($this->isInstruction($text)) {
                        continue;
                    }

                    /*
                     * ----------------------------------------------------------
                     * Field detection
                     * ----------------------------------------------------------
                     */
                    if ($this->looksLikeQuestion($text)) {

                        $field = $this->makeField($text);

                        $currentSection['fields'][] = $field;

                        $lastFieldIndex =
                            count($currentSection['fields']) - 1;

                        continue;
                    }

                    /*
                     * ----------------------------------------------------------
                     * Plain text
                     * ----------------------------------------------------------
                     */
                    $field = $this->makeField($text);

                    $currentSection['fields'][] = $field;

                    $lastFieldIndex =
                        count($currentSection['fields']) - 1;
                }
            }

            /*
             * Add final section.
             */
            if (!empty($currentSection['fields'])) {
                $sections[] = $currentSection;
            }

            /*
             * Defensive cleanup.
             */
            $sections = array_values(
                array_filter(
                    $sections,
                    function ($section) {
                        return !empty($section['fields']);
                    }
                )
            );

            /*
             * Clean option values and remove duplicates.
             */
            foreach ($sections as &$section) {
                foreach ($section['fields'] as &$field) {

                    if (
                        !empty($field['options']) &&
                        is_array($field['options'])
                    ) {
                        $unique = [];

                        foreach ($field['options'] as $option) {

                            $key = $option['value']
                                ?? Str::slug(
                                    $option['label'] ?? '',
                                    '_'
                                );

                            if ($key === '') {
                                continue;
                            }

                            $unique[$key] = [
                                'label' =>
                                    $option['label'] ?? $key,

                                'value' => $key,
                            ];
                        }

                        $field['options'] =
                            array_values($unique);
                    }
                }
            }

            unset($section, $field);

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
                        'message' => $e->getMessage(),
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
        if ($element instanceof Text) {
            return $this->cleanText(
                $element->getText()
            );
        }

        if ($element instanceof TextRun) {

            $text = '';

            foreach ($element->getElements() as $child) {

                if ($child instanceof Text) {
                    $text .= ' ' . $child->getText();
                }
            }

            return $this->cleanText($text);
        }

        if (
            $element instanceof ListItem ||
            $element instanceof ListItemRun
        ) {

            $text = '';

            if (method_exists($element, 'getElements')) {

                foreach ($element->getElements() as $child) {

                    if ($child instanceof Text) {
                        $text .= ' ' . $child->getText();
                    }
                }
            }

            return $this->cleanText($text);
        }

        if (method_exists($element, 'getText')) {

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

            if (method_exists($element, 'getStyleName')) {

                return strtolower(
                    (string) $element->getStyleName()
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

        if (str_contains($style, 'heading')) {
            return true;
        }

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
     * Determine whether a text line is an option.
     */
    protected function looksLikeOption(
        string $text
    ): bool {

        $text = trim($text);

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
            '○',
            '◯',
            '●',
            '◉',
            '[ ]',
            '[x]',
            '[X]',
        ];

        foreach ($prefixes as $prefix) {

            if (
                str_starts_with(
                    $text,
                    $prefix
                )
            ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Determine the field type from the original option marker.
     *
     * Square marker = checkbox
     * Circle marker = radio
     */
    protected function optionTypeFromMarker(
        string $text
    ): ?string {

        $text = trim($text);

        if (
            preg_match(
                '/^(○|◯|●|◉)/u',
                $text
            )
        ) {
            return 'radio';
        }

        if (
            preg_match(
                '/^(☐|☑|□|■|◻|◼|\[\s?\]|\[\s?[xX]\s?\])/u',
                $text
            )
        ) {
            return 'checkbox';
        }

        return null;
    }


    /**
     * Remove option markers.
     */
    protected function cleanOption(
        string $text
    ): string {

        $text = trim($text);

        $text = preg_replace(
            '/^(☐|☑|□|■|◻|◼|○|◯|●|◉|\[\s?\]|\[\s?[xX]\s?\]|[-•])\s*/u',
            '',
            $text
        );

        return trim($text ?? '');
    }


    /**
     * Determine whether text is an instruction rather than a field.
     */
    protected function isInstruction(
        string $text
    ): bool {

        $lower = strtolower(trim($text));

        if ($lower === '') {
            return true;
        }

        $instructions = [
            'please complete the following information.',
            'please complete the following information',
            'fields marked as required must be completed.',
            'fields marked as required must be completed',
            'please upload your resume.',
            'please upload your resume',
        ];

        if (in_array($lower, $instructions, true)) {

            /*
             * "Please upload your Resume." is actually useful
             * information, so do NOT discard it completely.
             *
             * It will be handled as a file field below.
             */
            if (
                str_contains($lower, 'upload') &&
                str_contains($lower, 'resume')
            ) {
                return false;
            }

            return true;
        }

        return false;
    }


    /**
     * Determine whether text looks like a question/field.
     */
    protected function looksLikeQuestion(
        string $text
    ): bool {

        if ($text === '') {
            return false;
        }

        if (str_ends_with($text, '?')) {
            return true;
        }

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
            '/\bemployment\b/i',
            '/\btype\b/i',
        ];

        foreach ($patterns as $pattern) {

            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        if (str_ends_with($text, ':')) {
            return true;
        }

        /*
         * Keep the original behavior:
         * non-empty plain paragraphs can become fields.
         */
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

        $label = preg_replace(
            '/\?$/',
            '',
            $label
        );

        $label = trim($label ?? '');

        $type = $this->detectType(
            $label,
            $options
        );

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
         * Existing option support.
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

            foreach ($options as $option) {

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
     * Detect field type from label.
     */
    protected function detectType(
        string $label,
        array $options = []
    ): string {

        $lower = strtolower($label);

        /*
         * File upload fields.
         */
        if (
            preg_match(
                '/resume|cv|upload|attachment|document|file/',
                $lower
            )
        ) {
            return 'file';
        }

        /*
         * Email.
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
         * Phone.
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
         * Date.
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
         * Number.
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
         * Choice fields when called directly with options.
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
         * Textarea.
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
         * Default.
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

            'file' =>
                'Upload ' . strtolower($label),

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

        $text = strip_tags($text);

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