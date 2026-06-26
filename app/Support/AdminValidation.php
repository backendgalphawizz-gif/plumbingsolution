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

    /** @gmail.com, @outlook.com, or domain ending in .in / .co */
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

    /** Profile update — any valid email domain, ignore current user */
    public static function profileEmailRules(\App\Models\User $user): array
    {
        return [
            'sometimes',
            'nullable',
            'string',
            'email',
            self::maxRule('email'),
            Rule::unique('users', 'email')->ignore($user),
        ];
    }

    /** Profile update — ignore current user's mobile */
    public static function profileMobileRules(\App\Models\User $user): array
    {
        return [
            'sometimes',
            'string',
            self::mobileFormatRule(),
            Rule::unique('users', 'mobile')->ignore($user),
        ];
    }

    public static function addressRules(): array
    {
        return ['nullable', 'string', self::maxRule('address')];
    }

    public static function requiredAddressRules(): array
    {
        return ['required', 'string', self::maxRule('address')];
    }

    public static function bankRules(bool $required = true): array
    {
        $prefix = $required ? 'required' : 'nullable';

        return [
            'account_holder_name' => [$prefix, 'string', 'max:100'],
            'account_number' => [$prefix, 'string', 'regex:/^[0-9]{9,18}$/'],
            'ifsc_code' => [$prefix, 'string', 'max:11', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'bank_name' => [$prefix, 'string', 'max:100'],
            'account_type' => [$prefix, Rule::in(['savings', 'current', 'saving', 'Saving', 'Savings', 'Current'])],
        ];
    }

    public static function locationRules(bool $required = true): array
    {
        $prefix = $required ? 'required' : 'nullable';

        return [
            'country' => [$prefix, 'string', 'max:100'],
            'state' => [$prefix, 'string', 'max:100'],
            'city' => [$prefix, 'string', 'max:100'],
            'pincode' => [$prefix, 'string', 'max:10'],
        ];
    }

    public static function imageMaxRule(): string
    {
        return 'max:'.self::limit('image_kb');
    }

    public static function imageMaxMb(): int
    {
        return (int) round(self::limit('image_kb') / 1024);
    }

    public static function imageRules(bool $required = true): array
    {
        $rules = ['image', 'mimes:jpg,jpeg,png,webp', self::imageMaxRule()];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function imageDocRules(bool $required = true): array
    {
        return self::imageRules($required);
    }

    public static function pdfOrImageDocRules(bool $required = true): array
    {
        $rules = ['file', 'mimes:pdf,jpg,jpeg,png,webp', self::imageMaxRule()];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function skillsStringRules(bool $required = true): array
    {
        $rules = [
            'string',
            self::maxRule('skills'),
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || trim($value) === '') {
                    return;
                }

                $skills = array_values(array_filter(array_map('trim', explode(',', $value))));
                if ($skills === []) {
                    $fail('At least one skill is required.');
                }
            },
        ];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function normalizeAccountType(string $type): string
    {
        return match (strtolower($type)) {
            'current' => 'current',
            default => 'savings',
        };
    }

    public static function faqQuestionRules(bool $required = true): array
    {
        $rules = ['string', self::maxRule('faq_question')];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function faqAnswerRules(bool $required = true): array
    {
        $rules = ['string', self::maxRule('faq_answer')];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function cmsTitleRules(bool $required = true): array
    {
        $rules = ['string', self::maxRule('cms_title')];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function cmsContentRules(bool $required = false): array
    {
        $rules = ['string', self::maxRule('cms_content')];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
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

    public static function launchDate(): string
    {
        return (string) config('admin.launch_date', '2026-06-01');
    }

    public static function dateRangeRules(): array
    {
        $launch = self::launchDate();

        return [
            'date_from' => ['nullable', 'date', 'after_or_equal:'.$launch, 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
        ];
    }

    public static function customDateRangeRules(): array
    {
        $launch = self::launchDate();

        return [
            'start_date' => ['nullable', 'date', 'after_or_equal:'.$launch, 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
        ];
    }

    public static function passwordRules(bool $required = true): array
    {
        $rules = ['string', 'min:8', self::maxRule('password'), Password::defaults()];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    /** Login / current password — no strength rules, only length */
    public static function loginPasswordRules(): array
    {
        return ['required', 'string', self::maxRule('password')];
    }

    public static function emailHint(): string
    {
        return 'Use @gmail.com, @outlook.com, or a .in / .co domain (e.g. name@gmail.com, name@outlook.com)';
    }

    public static function mobileHint(): string
    {
        return 'Exactly 10 digits, starting with 6–9';
    }

    public static function accountNumberHint(): string
    {
        return 'Digits only · 9 to 18 characters';
    }

    public static function ifscHint(): string
    {
        return '11-character IFSC (e.g. SBIN0001234)';
    }

    public static function bankValidationMessages(): array
    {
        return [
            'account_number.regex' => 'Account number must contain 9 to 18 digits only.',
            'ifsc_code.regex' => 'Enter a valid 11-character IFSC code (e.g. SBIN0001234).',
        ];
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

            $allowed = in_array($domain, ['gmail.com', 'outlook.com'], true)
                || str_ends_with($domain, '.in')
                || str_ends_with($domain, '.co');

            if (! $allowed) {
                $fail('Email must be @gmail.com, @outlook.com, or use a .in or .co domain.');
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
