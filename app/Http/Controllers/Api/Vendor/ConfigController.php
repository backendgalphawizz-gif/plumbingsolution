<?php

namespace App\Http\Controllers\Api\Vendor;

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
