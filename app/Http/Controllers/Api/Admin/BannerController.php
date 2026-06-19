<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(Banner::orderBy('sort_order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image' => ['required', 'string'],
            'redirect_type' => ['required', 'in:none,category,product,url'],
            'redirect_id' => ['nullable', 'integer'],
            'redirect_url' => ['nullable', 'string'],
            'status' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $banner = Banner::create($data);

        return $this->success($banner, 'Banner created.', 201);
    }

    public function update(Request $request, Banner $banner): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'image' => ['sometimes', 'string'],
            'redirect_type' => ['sometimes', 'in:none,category,product,url'],
            'redirect_id' => ['nullable', 'integer'],
            'redirect_url' => ['nullable', 'string'],
            'status' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $banner->update($data);

        return $this->success($banner->fresh(), 'Banner updated.');
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return $this->success(null, 'Banner deleted.');
    }
}
