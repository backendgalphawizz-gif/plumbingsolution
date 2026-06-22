<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\Provider\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Support\AdminValidation as V;
use App\Support\ProviderApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponse, ResolvesProvider;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['serviceProvider.documents']);

        return $this->success(ProviderApiFormatter::user($user));
    }

    public function personalDetails(Request $request): JsonResponse
    {
        $user = $request->user()->load('serviceProvider');

        return $this->success(ProviderApiFormatter::personalDetails($user));
    }

    public function updatePersonalDetails(Request $request): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $user = $request->user();

        $data = $request->validate([
            'name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), ['unique:users,mobile,'.$user->id]),
            'email' => V::emailRules(required: false, uniqueTable: 'users', ignoreId: $user->id),
            'address' => ['required', 'string', V::maxRule('address')],
            'service_area' => ['nullable', 'string', V::maxRule('address')],
            'avatar' => ['nullable', 'image', 'max:5120'],
        ]);

        $user->update([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($provider->avatar) {
                Storage::disk('public')->delete($provider->avatar);
            }

            $avatarPath = $request->file('avatar')->store('providers/avatars', 'public');
            $provider->update(['avatar' => $avatarPath]);
            $user->update(['avatar' => $avatarPath]);
        }

        $provider->update([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'service_area' => $data['service_area'] ?? $data['address'],
        ]);

        $user->load('serviceProvider');

        return $this->success(ProviderApiFormatter::personalDetails($user), 'Personal details updated.');
    }

    public function bankDetails(Request $request): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        return $this->success(ProviderApiFormatter::bankDetails($provider));
    }

    public function updateBankDetails(Request $request): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'account_holder_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:30'],
            'ifsc_code' => ['required', 'string', 'max:11', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', 'in:savings,current,saving,Saving,Savings,Current'],
        ]);

        $provider->update([
            'account_holder_name' => $data['account_holder_name'],
            'account_number' => $data['account_number'],
            'ifsc_code' => strtoupper($data['ifsc_code']),
            'bank_name' => $data['bank_name'],
            'account_type' => match (strtolower($data['account_type'])) {
                'current' => 'current',
                default => 'savings',
            },
        ]);

        return $this->success(ProviderApiFormatter::bankDetails($provider), 'Bank details updated.');
    }

    public function skillsDetails(Request $request): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        return $this->success(ProviderApiFormatter::skillsDetails($provider));
    }

    public function updateSkillsDetails(Request $request): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($request->has('skills') && is_string($request->input('skills'))) {
            $decoded = json_decode($request->input('skills'), true);
            if (is_array($decoded)) {
                $request->merge(['skills' => array_values(array_filter($decoded))]);
            }
        }

        $data = $request->validate([
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['required', 'string', 'max:50'],
            'experience' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        $provider->update([
            'skills' => array_values(array_map('trim', $data['skills'])),
            'experience_years' => $data['experience'],
        ]);

        return $this->success(ProviderApiFormatter::skillsDetails($provider), 'Skills updated.');
    }
}
