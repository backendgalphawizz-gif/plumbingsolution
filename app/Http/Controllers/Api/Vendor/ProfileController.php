<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendor;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\VendorDocument;
use App\Support\AdminValidation as V;
use App\Support\VendorApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponse, ResolvesVendor;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['vendor.documents']);

        return $this->success(VendorApiFormatter::user($user));
    }

    public function ownerDetails(Request $request): JsonResponse
    {
        $user = $request->user()->load(['vendor.documents']);

        return $this->success(VendorApiFormatter::ownerDetails($user));
    }

    public function updateOwnerDetails(Request $request): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        $user = $request->user();

        $data = $request->validate([
            'name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), ['unique:users,mobile,'.$user->id]),
            'email' => V::emailRules(required: false, uniqueTable: 'users', ignoreId: $user->id),
            'aadhar_card' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user->update([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
        ]);

        $vendor->update([
            'owner_name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? $vendor->email,
        ]);

        if ($request->hasFile('aadhar_card')) {
            $path = $request->file('aadhar_card')->store('documents/vendors/aadhar_card', 'public');
            $document = VendorDocument::query()
                ->where('vendor_id', $vendor->id)
                ->where('document_type', 'Aadhar Card')
                ->first();

            if ($document) {
                Storage::disk('public')->delete($document->file_path);
                $document->update(['file_path' => $path]);
            } else {
                VendorDocument::create([
                    'vendor_id' => $vendor->id,
                    'document_type' => 'Aadhar Card',
                    'file_path' => $path,
                ]);
            }
        }

        $user->load(['vendor.documents']);

        return $this->success(VendorApiFormatter::ownerDetails($user), 'Owner details updated.');
    }

    public function shopDetails(Request $request): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        $vendor->load('documents');

        return $this->success(VendorApiFormatter::shopDetails($vendor));
    }

    public function updateShopDetails(Request $request): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        $data = $request->validate([
            'shop_name' => ['required', 'string', V::maxRule('shop_name')],
            'business_mobile' => V::mobileRules(required: false),
            'email' => V::emailRules(required: false),
            'gst_number' => ['nullable', 'string', V::maxRule('gst_number')],
            'address' => ['required', 'string', V::maxRule('address')],
            'country' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'max:10'],
            'shop_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'pan_card' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('shop_logo')) {
            if ($vendor->shop_logo) {
                Storage::disk('public')->delete($vendor->shop_logo);
            }

            $data['shop_logo'] = $request->file('shop_logo')->store('vendors/logos', 'public');
        } else {
            unset($data['shop_logo']);
        }

        $vendor->update(collect($data)->except(['pan_card'])->toArray());

        if ($request->hasFile('pan_card')) {
            $path = $request->file('pan_card')->store('documents/vendors/pan_card', 'public');
            $document = VendorDocument::query()
                ->where('vendor_id', $vendor->id)
                ->where('document_type', 'PAN Card')
                ->first();

            if ($document) {
                Storage::disk('public')->delete($document->file_path);
                $document->update(['file_path' => $path]);
            } else {
                VendorDocument::create([
                    'vendor_id' => $vendor->id,
                    'document_type' => 'PAN Card',
                    'file_path' => $path,
                ]);
            }
        }

        $vendor->load('documents');

        return $this->success(VendorApiFormatter::shopDetails($vendor), 'Shop details updated.');
    }
}
