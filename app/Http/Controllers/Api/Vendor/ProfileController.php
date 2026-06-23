<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendor;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\VendorDocument;
use App\Support\AdminValidation as V;
use App\Support\ProfileImageUploader;
use App\Support\VendorApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use ApiResponse, ResolvesVendor;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['vendor.documents']);

        return $this->success(VendorApiFormatter::user($user));
    }

    public function update(Request $request): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        $user = $request->user();

        if ($request->filled('mobile')) {
            $request->merge(['mobile' => trim((string) $request->input('mobile'))]);
        }
        if ($request->filled('business_mobile')) {
            $request->merge(['business_mobile' => trim((string) $request->input('business_mobile'))]);
        }
        if ($request->has('email')) {
            $request->merge(['email' => $request->filled('email') ? trim((string) $request->input('email')) : null]);
        }

        $data = $request->validate([
            'name' => ['sometimes', ...V::nameRules()],
            'mobile' => V::profileMobileRules($user),
            'email' => V::profileEmailRules($user),
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'aadhar_card' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'shop_name' => ['sometimes', 'string', V::maxRule('shop_name')],
            'business_mobile' => array_merge(['sometimes'], V::mobileRules(required: false)),
            'gst_number' => ['nullable', 'string', V::maxRule('gst_number')],
            'address' => ['sometimes', 'string', V::maxRule('address')],
            'country' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'max:100'],
            'city' => ['sometimes', 'string', 'max:100'],
            'pincode' => ['sometimes', 'string', 'max:10'],
            'shop_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'pan_card' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'account_holder_name' => ['sometimes', 'string', 'max:100'],
            'account_number' => ['sometimes', 'string', 'max:30'],
            'ifsc_code' => ['sometimes', 'string', 'max:11', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'bank_name' => ['sometimes', 'string', 'max:100'],
            'account_type' => ['sometimes', 'in:savings,current,saving,Saving,Savings,Current'],
        ]);

        $userUpdates = [];
        foreach (['name', 'mobile', 'address'] as $field) {
            if (array_key_exists($field, $data)) {
                $userUpdates[$field] = $data[$field];
            }
        }
        if (array_key_exists('email', $data)) {
            $userUpdates['email'] = $data['email'];
        }

        $avatarFile = $request->file('avatar') ?? $request->file('profile_image');
        if ($avatarFile) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $userUpdates['avatar'] = $avatarFile->store('avatars', 'public');
        }

        if ($userUpdates !== []) {
            $user->update($userUpdates);
        }

        $vendorUpdates = array_filter([
            'owner_name' => $data['name'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'email' => array_key_exists('email', $data) ? ($data['email'] ?? $vendor->email) : null,
            'shop_name' => $data['shop_name'] ?? null,
            'business_mobile' => $data['business_mobile'] ?? null,
            'gst_number' => array_key_exists('gst_number', $data) ? $data['gst_number'] : null,
            'address' => $data['address'] ?? null,
            'country' => $data['country'] ?? null,
            'state' => $data['state'] ?? null,
            'city' => $data['city'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'account_holder_name' => $data['account_holder_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'ifsc_code' => isset($data['ifsc_code']) ? strtoupper($data['ifsc_code']) : null,
            'bank_name' => $data['bank_name'] ?? null,
            'account_type' => isset($data['account_type'])
                ? (match (strtolower($data['account_type'])) {
                    'current' => 'current',
                    default => 'savings',
                })
                : null,
        ], fn ($value) => $value !== null);

        if ($request->hasFile('shop_logo')) {
            if ($vendor->shop_logo) {
                Storage::disk('public')->delete($vendor->shop_logo);
            }

            $vendorUpdates['shop_logo'] = $request->file('shop_logo')->store('vendors/logos', 'public');
        }

        if ($vendorUpdates !== []) {
            $vendor->update($vendorUpdates);
        }

        if ($request->hasFile('aadhar_card')) {
            $this->upsertDocument($vendor, 'Aadhar Card', $request->file('aadhar_card'), 'documents/vendors/aadhar_card');
        }

        if ($request->hasFile('pan_card')) {
            $this->upsertDocument($vendor, 'PAN Card', $request->file('pan_card'), 'documents/vendors/pan_card');
        }

        return $this->success(
            VendorApiFormatter::user($user->fresh(['vendor.documents'])),
            'Profile updated.'
        );
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
            'mobile' => array_merge(V::mobileRules(required: true), [Rule::unique('users', 'mobile')->ignore($user)]),
            'email' => [
                'nullable',
                'string',
                'email',
                V::maxRule('email'),
                Rule::unique('users', 'email')->ignore($user),
            ],
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
            $this->upsertDocument($vendor, 'Aadhar Card', $request->file('aadhar_card'), 'documents/vendors/aadhar_card');
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
            $this->upsertDocument($vendor, 'PAN Card', $request->file('pan_card'), 'documents/vendors/pan_card');
        }

        $vendor->load('documents');

        return $this->success(VendorApiFormatter::shopDetails($vendor), 'Shop details updated.');
    }

    private function upsertDocument($vendor, string $documentType, UploadedFile $file, string $directory): void
    {
        $path = $file->store($directory, 'public');
        $document = VendorDocument::query()
            ->where('vendor_id', $vendor->id)
            ->where('document_type', $documentType)
            ->first();

        if ($document) {
            Storage::disk('public')->delete($document->file_path);
            $document->update(['file_path' => $path]);
        } else {
            VendorDocument::create([
                'vendor_id' => $vendor->id,
                'document_type' => $documentType,
                'file_path' => $path,
            ]);
        }
    }
}
