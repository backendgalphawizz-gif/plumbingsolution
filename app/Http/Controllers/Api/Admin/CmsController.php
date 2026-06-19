<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\CmsPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(CmsPage::all());
    }

    public function show(string $slug): JsonResponse
    {
        $page = CmsPage::where('slug', $slug)->firstOrFail();

        return $this->success($page);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'unique:cms_pages,slug'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $page = CmsPage::create($data);

        return $this->success($page, 'CMS page created.', 201);
    }

    public function update(Request $request, CmsPage $cmsPage): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $cmsPage->update($data);

        return $this->success($cmsPage->fresh(), 'CMS page updated.');
    }
}
