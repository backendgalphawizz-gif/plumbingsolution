<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\Provider\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\ProviderRegistrationService;
use App\Support\AdminValidation as V;
use App\Support\ProviderApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use ApiResponse, ResolvesProvider;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['serviceProvider.documents']);

        return $this->success(ProviderApiFormatter::user($user));
    }

    public function update(Request $request): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $user = $request->user();

        if ($request->filled('mobile')) {
            $request->merge(['mobile' => trim((string) $request->input('mobile'))]);
        }
        if ($request->has('email')) {
            $request->merge(['email' => $request->filled('email') ? trim((string) $request->input('email')) : null]);
        }

        if ($request->has('skills') && is_string($request->input('skills'))) {
            $decoded = json_decode($request->input('skills'), true);
            if (is_array($decoded)) {
                $request->merge(['skills' => array_values(array_filter($decoded))]);
            }
        }

        $locationRules = app(ProviderRegistrationService::class)->locationRules(required: false);

        $data = $request->validate(array_merge([
            'name' => ['sometimes', ...V::nameRules()],
            'mobile' => V::profileMobileRules($user),
            'email' => V::profileEmailRules($user),
            'address' => ['sometimes', 'string', V::maxRule('address')],
            'service_area' => ['nullable', 'string', V::maxRule('address')],
            'avatar' => ['nullable', 'image', 'max:5120'],
            'profile_image' => ['nullable', 'image', 'max:5120'],
            'account_holder_name' => ['sometimes', 'string', 'max:100'],
            'account_number' => ['sometimes', 'string', 'max:30'],
            'ifsc_code' => ['sometimes', 'string', 'max:11', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'bank_name' => ['sometimes', 'string', 'max:100'],
            'account_type' => ['sometimes', 'in:savings,current,saving,Saving,Savings,Current'],
            'skills' => ['sometimes', 'array', 'min:1'],
            'skills.*' => ['required', 'string', 'max:50'],
            'experience' => ['sometimes', 'integer', 'min:0', 'max:50'],
        ], $locationRules));

        $userUpdates = [];
        foreach (['name', 'mobile', 'address'] as $field) {
            if (array_key_exists($field, $data)) {
                $userUpdates[$field] = $data[$field];
            }
        }
        if (array_key_exists('email', $data)) {
            $userUpdates['email'] = $data['email'];
        }

        if ($userUpdates !== []) {
            $user->update($userUpdates);
        }

        $avatarFile = $request->file('avatar') ?? $request->file('profile_image');
        if ($avatarFile) {
            if ($provider->avatar) {
                Storage::disk('public')->delete($provider->avatar);
            }
            if ($user->avatar && $user->avatar !== $provider->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $avatarPath = $avatarFile->store('providers/avatars', 'public');
            $provider->update(['avatar' => $avatarPath]);
            $user->update(['avatar' => $avatarPath]);
        }

        $providerUpdates = array_filter([
            'name' => $data['name'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'service_area' => array_key_exists('service_area', $data)
                ? ($data['service_area'] ?? $data['address'] ?? $user->address)
                : (isset($data['address']) ? $data['address'] : null),
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
            'skills' => isset($data['skills']) ? array_values(array_map('trim', $data['skills'])) : null,
            'experience_years' => $data['experience'] ?? null,
            'latitude' => array_key_exists('latitude', $data) ? round((float) $data['latitude'], 7) : null,
            'longitude' => array_key_exists('longitude', $data) ? round((float) $data['longitude'], 7) : null,
        ], fn ($value) => $value !== null);

        if ($providerUpdates !== []) {
            $provider->update($providerUpdates);
        }

        return $this->success(
            ProviderApiFormatter::user($user->fresh(['serviceProvider.documents'])),
            'Profile updated.'
        );
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

        $locationRules = app(ProviderRegistrationService::class)->locationRules(required: false);

        $data = $request->validate(array_merge([
            'name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), [Rule::unique('users', 'mobile')->ignore($user)]),
            'email' => [
                'nullable',
                'string',
                'email',
                V::maxRule('email'),
                Rule::unique('users', 'email')->ignore($user),
            ],
            'address' => ['required', 'string', V::maxRule('address')],
            'service_area' => ['nullable', 'string', V::maxRule('address')],
            'avatar' => ['nullable', 'image', 'max:5120'],
        ], $locationRules));

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
            'latitude' => array_key_exists('latitude', $data) ? round((float) $data['latitude'], 7) : $provider->latitude,
            'longitude' => array_key_exists('longitude', $data) ? round((float) $data['longitude'], 7) : $provider->longitude,
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
