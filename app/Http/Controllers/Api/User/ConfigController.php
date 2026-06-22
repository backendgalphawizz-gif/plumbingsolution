<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $page = fn (string $slug) => CmsPage::where('slug', $slug)->where('is_active', true)->first();

        $privacy = $page('privacy-policy');
        $terms = $page('terms-and-conditions');

        return $this->success([
            'app' => [
                'name' => Setting::getValue('app', 'app_name', 'PlumbManager'),
                'support_email' => Setting::getValue('app', 'support_email', 'support@plumbmanager.com'),
                'tax_percent' => (float) Setting::getValue('tax', 'gst_rate', 8),
                'payment' => [
                    'razorpay_enabled' => (bool) Setting::getValue('payment', 'razorpay_enabled', true),
                    'cod_enabled' => (bool) Setting::getValue('payment', 'cod_enabled', true),
                ],
            ],
            'faqs' => Faq::where('status', true)
                ->orderBy('sort_order')
                ->get(['id', 'question', 'answer']),
            'privacy_policy' => $privacy ? [
                'slug' => $privacy->slug,
                'title' => $privacy->title,
                'content' => $privacy->content,
            ] : null,
            'terms_and_conditions' => $terms ? [
                'slug' => $terms->slug,
                'title' => $terms->title,
                'content' => $terms->content,
            ] : null,
        ]);
    }
}
