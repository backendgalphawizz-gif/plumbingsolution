<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $customers = User::withCount(['orders', 'serviceBookings'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%");
            }))
            ->when($request->has('is_blocked'), fn ($q) => $q->where('is_blocked', $request->boolean('is_blocked')))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($customers);
    }

    public function show(User $customer): JsonResponse
    {
        return $this->success($customer->loadCount(['orders', 'serviceBookings']));
    }

    public function block(Request $request, User $customer): JsonResponse
    {
        $request->validate(['reason' => ['nullable', 'string']]);

        $customer->update([
            'is_blocked' => true,
            'blocked_at' => now(),
            'block_reason' => $request->reason,
        ]);

        return $this->success($customer->fresh(), 'Customer blocked.');
    }

    public function unblock(User $customer): JsonResponse
    {
        $customer->update([
            'is_blocked' => false,
            'blocked_at' => null,
            'block_reason' => null,
        ]);

        return $this->success($customer->fresh(), 'Customer unblocked.');
    }

    public function orderHistory(User $customer): JsonResponse
    {
        return $this->success($customer->orders()->with('items')->latest()->paginate(15));
    }

    public function bookingHistory(User $customer): JsonResponse
    {
        return $this->success($customer->serviceBookings()->with('serviceProvider')->latest()->paginate(15));
    }
}
