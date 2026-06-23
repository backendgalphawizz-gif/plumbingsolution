<?php

namespace App\Support;

use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\Setting;

class AppConfigBuilder
{
    public static function combined(): array
    {
        return [
            'user' => self::userConfig(),
            'provider' => self::providerConfig(),
        ];
    }

    public static function userConfig(): array
    {
        return self::build('user', [
            'tax_percent' => (float) Setting::getValue('tax', 'gst_rate', 8),
            'payment' => [
                'razorpay_enabled' => (bool) Setting::getValue('payment', 'razorpay_enabled', true),
                'cod_enabled' => (bool) Setting::getValue('payment', 'cod_enabled', true),
            ],
        ]);
    }

    public static function providerConfig(): array
    {
        return self::build('provider', [
            'commission_percent' => (float) Setting::getValue('commission', 'provider_commission', 15),
        ]);
    }

    public static function build(string $audience, array $appExtras = []): array
    {
        $privacy = CmsPage::legalPage('privacy-policy', $audience);
        $terms = CmsPage::legalPage('terms-and-conditions', $audience);

        return [
            'app' => array_merge([
                'name' => Setting::getValue('app', 'app_name', 'PlumbManager'),
                'support_email' => Setting::getValue('app', 'support_email', 'support@plumbmanager.com'),
            ], $appExtras),
            'faqs' => Faq::where('status', true)
                ->forAudience($audience)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'question', 'answer', 'sort_order']),
            'privacy_policy' => self::legalPage($privacy),
            'terms_and_conditions' => self::legalPage($terms),
        ];
    }

    private static function legalPage(?CmsPage $page): ?array
    {
        if (! $page) {
            return null;
        }

        return [
            'slug' => $page->slug,
            'title' => $page->title,
            'content' => $page->content,
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }
}
