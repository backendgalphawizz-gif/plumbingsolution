<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    use ApiResponse;

    public function categories(): JsonResponse
    {
        $categories = ServiceCategory::query()
            ->where('status', true)
            ->with(['services' => fn ($q) => $q->where('status', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        return $this->success([
            'categories' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'services' => $category->services->map(fn (Service $service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'slug' => $service->slug,
                    'starting_price' => (float) $service->starting_price,
                    'image' => $service->image ? asset('storage/'.$service->image) : null,
                ])->values(),
            ])->values(),
        ]);
    }
}
