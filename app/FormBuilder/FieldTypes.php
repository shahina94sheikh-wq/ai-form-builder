<?php

namespace App\FormBuilder;

class FieldTypes
{
    public const TEXT = 'text';
    public const TEXTAREA = 'textarea';
    public const EMAIL = 'email';
    public const NUMBER = 'number';
    public const DATE = 'date';
    public const SELECT = 'select';
    public const RADIO = 'radio';
    public const CHECKBOX = 'checkbox';
    public const FILE = 'file';

    public static function all(): array
    {
        return [
            self::TEXT,
            self::TEXTAREA,
            self::EMAIL,
            self::NUMBER,
            self::DATE,
            self::SELECT,
            self::RADIO,
            self::CHECKBOX,
            self::FILE,
        ];
    }
}