<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ProviderStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ProviderDocument;
use App\Models\ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $providers = ServiceProvider::withCount('bookings')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('mobile', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($providers);
    }

    public function show(ServiceProvider $serviceProvider): JsonResponse
    {
        return $this->success($serviceProvider->load(['documents', 'bookings']));
    }

    public function approve(ServiceProvider $serviceProvider): JsonResponse
    {
        $serviceProvider->update([
            'status' => ProviderStatus::Approved,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return $this->success($serviceProvider->fresh(), 'Provider approved.');
    }

    public function reject(Request $request, ServiceProvider $serviceProvider): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string']]);

        $serviceProvider->update([
            'status' => ProviderStatus::Rejected,
            'rejection_reason' => $request->reason,
        ]);

        return $this->success($serviceProvider->fresh(), 'Provider rejected.');
    }

    public function suspend(ServiceProvider $serviceProvider): JsonResponse
    {
        $serviceProvider->update(['status' => ProviderStatus::Suspended]);

        return $this->success($serviceProvider->fresh(), 'Provider suspended.');
    }

    public function verifyDocument(Request $request, ProviderDocument $document): JsonResponse
    {
        $document->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        return $this->success($document->fresh(), 'Document verified.');
    }
}
