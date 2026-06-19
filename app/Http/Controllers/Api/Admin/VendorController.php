<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $vendors = Vendor::withCount('products')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('shop_name', 'like', "%{$s}%")
                    ->orWhere('owner_name', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($vendors);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        return $this->success($vendor->load(['documents', 'products']));
    }

    public function approve(Vendor $vendor): JsonResponse
    {
        $vendor->update([
            'status' => VendorStatus::Approved,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return $this->success($vendor->fresh(), 'Vendor approved.');
    }

    public function reject(Request $request, Vendor $vendor): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string']]);

        $vendor->update([
            'status' => VendorStatus::Rejected,
            'rejection_reason' => $request->reason,
        ]);

        return $this->success($vendor->fresh(), 'Vendor rejected.');
    }

    public function suspend(Request $request, Vendor $vendor): JsonResponse
    {
        $vendor->update(['status' => VendorStatus::Suspended]);

        return $this->success($vendor->fresh(), 'Vendor suspended.');
    }

    public function verifyDocument(Request $request, VendorDocument $document): JsonResponse
    {
        $document->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        return $this->success($document->fresh(), 'Document verified.');
    }
}
