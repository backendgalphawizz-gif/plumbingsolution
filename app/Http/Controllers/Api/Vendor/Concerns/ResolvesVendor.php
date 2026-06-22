<?php

namespace App\Http\Controllers\Api\Vendor\Concerns;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ResolvesVendor
{
    protected function resolveVendor(Request $request): ?Vendor
    {
        return $request->user()->vendor;
    }

    protected function requireVendor(Request $request): Vendor|JsonResponse
    {
        $vendor = $this->resolveVendor($request);

        if (! $vendor) {
            return $this->error('Vendor profile not found.', 404);
        }

        return $vendor;
    }

    protected function ensureApproved(Vendor $vendor): ?JsonResponse
    {
        if ($vendor->status !== VendorStatus::Approved) {
            return $this->error('Your vendor account is pending admin approval.', 403);
        }

        return null;
    }

    protected function vendorOrder(Vendor $vendor, int $orderId)
    {
        return $vendor->orders()
            ->with(['user', 'items.product.images', 'payment'])
            ->whereKey($orderId)
            ->first();
    }
}
