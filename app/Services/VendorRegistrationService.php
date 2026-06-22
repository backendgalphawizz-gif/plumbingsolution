<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Support\AdminValidation as V;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorRegistrationService
{
    public function rules(): array
    {
        return [
            'name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), ['unique:users,mobile']),
            'email' => V::emailRules(required: false, uniqueTable: 'users'),
            'aadhar_card' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'shop_name' => ['required', 'string', V::maxRule('shop_name')],
            'business_mobile' => V::mobileRules(required: false),
            'shop_email' => V::emailRules(required: false),
            'gst_number' => ['nullable', 'string', V::maxRule('gst_number')],
            'address' => ['required', 'string', V::maxRule('address')],
            'country' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'max:10'],
            'shop_logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'pan_card' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'account_holder_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:30'],
            'ifsc_code' => ['required', 'string', 'max:11', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', 'in:savings,current,saving,Saving,Savings,Current'],
        ];
    }

    public function createForUser(User $user, array $data, Request $request): Vendor
    {
        return DB::transaction(function () use ($user, $data, $request) {
            $email = $data['shop_email'] ?? $data['email'] ?? null;

            $vendor = Vendor::create([
                'user_id' => $user->id,
                'shop_name' => $data['shop_name'],
                'owner_name' => $data['name'],
                'mobile' => $data['mobile'],
                'email' => $email,
                'business_mobile' => $data['business_mobile'] ?? $data['mobile'],
                'address' => $data['address'],
                'country' => $data['country'],
                'state' => $data['state'],
                'city' => $data['city'],
                'pincode' => $data['pincode'],
                'gst_number' => $data['gst_number'] ?? null,
                'shop_logo' => $request->file('shop_logo')->store('vendors/logos', 'public'),
                'status' => VendorStatus::Pending,
                'account_number' => $data['account_number'],
                'account_holder_name' => $data['account_holder_name'],
                'ifsc_code' => strtoupper($data['ifsc_code']),
                'bank_name' => $data['bank_name'],
                'account_type' => $this->normalizeAccountType($data['account_type']),
            ]);

            $this->storeDocument($vendor, 'Aadhar Card', $request->file('aadhar_card'));
            $this->storeDocument($vendor, 'PAN Card', $request->file('pan_card'));

            return $vendor->load('documents');
        });
    }

    private function storeDocument(Vendor $vendor, string $type, $file): void
    {
        VendorDocument::create([
            'vendor_id' => $vendor->id,
            'document_type' => $type,
            'file_path' => $file->store('documents/vendors/'.strtolower(str_replace(' ', '_', $type)), 'public'),
        ]);
    }

    private function normalizeAccountType(string $type): string
    {
        return match (strtolower($type)) {
            'current' => 'current',
            default => 'savings',
        };
    }
}
