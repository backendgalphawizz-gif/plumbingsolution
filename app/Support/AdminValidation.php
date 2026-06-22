<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminValidation
{
    public static function limit(string $key): int
    {
        return (int) config("admin.limits.{$key}");
    }

    public static function maxRule(string $key): string
    {
        return 'max:'.self::limit($key);
    }

    /** Person / display name — max chars + word count */
    public static function nameRules(bool $required = true): array
    {
        $rules = [
            'string',
            self::maxRule('name'),
            'regex:/^[\pL\s\-\'.]+$/u',
            self::maxWordsRule('max_name_words'),
        ];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    /** Exactly 10-digit Indian mobile (starts with 6–9) */
    public static function mobileRules(bool $required = false): array
    {
        $rules = [
            'string',
            self::mobileFormatRule(),
        ];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    /** @gmail.com or domain ending in .in / .co */
    public static function emailRules(bool $required = true, ?string $uniqueTable = null, ?int $ignoreId = null): array
    {
        $rules = ['string', 'email', self::maxRule('email'), self::allowedEmailRule()];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        if ($uniqueTable) {
            $unique = Rule::unique($uniqueTable, 'email');
            if ($ignoreId) {
                $unique->ignore($ignoreId);
            }
            $rules[] = $unique;
        }

        return $rules;
    }

    public static function addressRules(): array
    {
        return ['nullable', 'string', self::maxRule('address')];
    }

    public static function reasonRules(bool $required = true): array
    {
        $rules = ['string', self::maxRule('reason')];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function notesRules(bool $required = false): array
    {
        $rules = ['string', self::maxRule('notes')];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function searchRules(): array
    {
        return ['nullable', 'string', self::maxRule('search')];
    }

    public static function dateRangeRules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public static function customDateRangeRules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public static function passwordRules(bool $required = true): array
    {
        $rules = ['string', 'min:8', self::maxRule('password'), Password::defaults()];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function emailHint(): string
    {
        return 'Use @gmail.com or a .in / .co domain (e.g. name@gmail.com, name@company.in)';
    }

    public static function mobileHint(): string
    {
        return 'Exactly 10 digits, starting with 6–9';
    }

    private static function mobileFormatRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            if (! preg_match('/^[6-9]\d{9}$/', $value)) {
                $fail('Mobile number must be exactly 10 digits and start with 6, 7, 8, or 9.');
            }
        };
    }

    private static function allowedEmailRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $domain = strtolower((string) substr(strrchr($value, '@'), 1));

            if ($domain === '') {
                $fail('Please enter a valid email address.');

                return;
            }

            $allowed = $domain === 'gmail.com'
                || str_ends_with($domain, '.in')
                || str_ends_with($domain, '.co');

            if (! $allowed) {
                $fail('Email must be @gmail.com or use a .in or .co domain.');
            }
        };
    }

    private static function maxWordsRule(string $configKey): \Closure
    {
        $maxWords = self::limit($configKey);

        return function (string $attribute, mixed $value, \Closure $fail) use ($maxWords): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
            if (count($words) > $maxWords) {
                $fail("The {$attribute} must not exceed {$maxWords} words.");
            }
        };
    }
}
