<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\Provider\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\ProviderServiceManagement;
use App\Support\AdminValidation as V;
use App\Support\ProviderApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ApiResponse, ResolvesProvider;

    public function index(Request $request): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $request->validate([
            'search' => V::searchRules(),
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $services = $provider->services()
            ->with(['category', 'images'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderByDesc('service_provider_service.is_available')
            ->paginate($request->integer('per_page', 15));

        $provider->loadAvg('reviews', 'rating');
        $provider->loadCount('reviews');

        return $this->success([
            'items' => collect($services->items())->map(fn ($service) => ProviderApiFormatter::service($service, $provider))->values(),
            'pagination' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    public function store(Request $request, ProviderServiceManagement $services): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($response = $this->ensureApproved($provider)) {
            return $response;
        }

        $services->prepareRequest($request);
        $data = $request->validate($services->storeRules());
        $service = $services->create($provider, $data, $request);

        $provider->loadAvg('reviews', 'rating');
        $provider->loadCount('reviews');

        return $this->success(
            ProviderApiFormatter::service($service, $provider),
            'Service added.',
            201
        );
    }

    public function show(Request $request, int $service): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $serviceModel = app(ProviderServiceManagement::class)->ownedBy($provider, $service);

        if (! $serviceModel) {
            return $this->error('Service not found.', 404);
        }

        $provider->load(['reviews' => fn ($q) => $q->with('user')->latest()->limit(20)]);
        $provider->loadAvg('reviews', 'rating');
        $provider->loadCount('reviews');

        return $this->success(ProviderApiFormatter::service($serviceModel, $provider, detailed: true));
    }

    public function update(Request $request, int $service, ProviderServiceManagement $services): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($response = $this->ensureApproved($provider)) {
            return $response;
        }

        $serviceModel = $services->ownedBy($provider, $service);

        if (! $serviceModel) {
            return $this->error('Service not found.', 404);
        }

        $services->prepareRequest($request);
        $data = $request->validate($services->updateRules());
        $serviceModel = $services->update($provider, $serviceModel, $data, $request);

        $provider->loadAvg('reviews', 'rating');
        $provider->loadCount('reviews');

        return $this->success(ProviderApiFormatter::service($serviceModel, $provider, detailed: true), 'Service updated.');
    }

    public function destroy(Request $request, int $service, ProviderServiceManagement $services): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($response = $this->ensureApproved($provider)) {
            return $response;
        }

        $serviceModel = $services->ownedBy($provider, $service);

        if (! $serviceModel) {
            return $this->error('Service not found.', 404);
        }

        $services->delete($provider, $serviceModel);

        return $this->success(null, 'Service deleted.');
    }
}
