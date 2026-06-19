<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\CmsPage;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;

class CmsController extends Controller
{
    use ApiResponse;

    public function show(string $slug): JsonResponse
    {
        $page = CmsPage::where('slug', $slug)->where('is_active', true)->first();

        if (! $page) {
            return $this->error('Page not found.', 404);
        }

        return $this->success([
            'slug' => $page->slug,
            'title' => $page->title,
            'content' => $page->content,
        ]);
    }

    public function faqs(): JsonResponse
    {
        $faqs = Faq::where('status', true)
            ->orderBy('sort_order')
            ->get(['id', 'question', 'answer']);

        return $this->success($faqs);
    }
}
