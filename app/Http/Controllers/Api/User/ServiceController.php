<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ApiResponse;

    public function categories(): JsonResponse
    {
        $categories = ServiceCategory::where('status', true)
            ->withCount(['services' => fn ($q) => $q->where('status', true)])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'image' => $c->image ? asset('storage/'.$c->image) : null,
                'providers_count' => $c->services_count,
            ]);

        return $this->success($categories);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate(['search' => V::searchRules()]);

        $services = Service::where('status', true)
            ->with('category')
            ->when($request->category_id, fn ($q, $id) => $q->where('service_category_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('sort_order')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => collect($services->items())->map(fn ($s) => UserApiFormatter::service($s)),
            'pagination' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    public function show(Service $service): JsonResponse
    {
        if (! $service->status) {
            return $this->error('Service not available.', 404);
        }

        $service->load('category');

        return $this->success(UserApiFormatter::service($service));
    }
}
