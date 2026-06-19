<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\UserAddress;
use App\Support\AdminValidation as V;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->latest()->get()->map(fn ($a) => $this->format($a));

        return $this->success($addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $user = $request->user();

        if ($data['is_default'] ?? false) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($data);

        return $this->success($this->format($address), 'Address saved.', 201);
    }

    public function update(Request $request, UserAddress $userAddress): JsonResponse
    {
        $this->authorizeAddress($request, $userAddress);

        $data = $this->validated($request);

        if ($data['is_default'] ?? false) {
            $request->user()->addresses()->where('id', '!=', $userAddress->id)->update(['is_default' => false]);
        }

        $userAddress->update($data);

        return $this->success($this->format($userAddress->fresh()), 'Address updated.');
    }

    public function destroy(Request $request, UserAddress $userAddress): JsonResponse
    {
        $this->authorizeAddress($request, $userAddress);
        $userAddress->delete();

        return $this->success(null, 'Address deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:20'],
            'full_name' => V::nameRules(),
            'mobile' => V::mobileRules(required: true),
            'house_no' => ['nullable', 'string', 'max:100'],
            'road_area' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'full_address' => ['nullable', 'string', V::maxRule('address')],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeAddress(Request $request, UserAddress $address): void
    {
        abort_if($address->user_id !== $request->user()->id, 403, 'Unauthorized.');
    }

    private function format(UserAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'full_name' => $address->full_name,
            'mobile' => $address->mobile,
            'house_no' => $address->house_no,
            'road_area' => $address->road_area,
            'city' => $address->city,
            'state' => $address->state,
            'country' => $address->country,
            'pincode' => $address->pincode,
            'full_address' => $address->full_address ?? implode(', ', array_filter([
                $address->house_no, $address->road_area, $address->city, $address->state, $address->pincode,
            ])),
            'is_default' => $address->is_default,
        ];
    }
}
