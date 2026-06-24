<?php

namespace App\Services;

use App\Enums\ProviderStatus;
use App\Enums\UserRole;
use App\Models\ProviderDocument;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderRegistrationService
{
    public function rules(): array
    {
        return array_merge($this->locationRules(), [
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['required', 'string', 'max:50'],
            'experience' => ['required', 'integer', 'min:0', 'max:50'],
            'aadhar_front' => ['required', 'image', 'max:5120'],
            'aadhar_back' => ['required', 'image', 'max:5120'],
            'pan_card' => ['required', 'image', 'max:5120'],
            'account_number' => ['required', 'string', 'max:30'],
            'account_holder_name' => ['required', 'string', 'max:100'],
            'ifsc_code' => ['required', 'string', 'max:11', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', 'in:savings,current'],
        ]);
    }

    public function locationRules(bool $required = true): array
    {
        $rule = $required ? 'required' : 'sometimes';

        return [
            'latitude' => [$rule, 'numeric', 'between:-90,90'],
            'longitude' => [$rule, 'numeric', 'between:-180,180'],
        ];
    }

    public function normalizeSkills(Request $request): void
    {
        $skills = $request->input('skills');

        if (is_string($skills)) {
            $decoded = json_decode($skills, true);
            if (is_array($decoded)) {
                $request->merge(['skills' => array_values(array_filter($decoded))]);
            }
        }
    }

    public function createForUser(User $user, array $data, Request $request): ServiceProvider
    {
        return DB::transaction(function () use ($user, $data, $request) {
            $provider = ServiceProvider::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'skills' => array_values(array_map('trim', $data['skills'])),
                'experience_years' => $data['experience'],
                'service_area' => $user->address,
                'latitude' => round((float) $data['latitude'], 7),
                'longitude' => round((float) $data['longitude'], 7),
                'status' => ProviderStatus::Pending,
                'account_number' => $data['account_number'],
                'account_holder_name' => $data['account_holder_name'],
                'ifsc_code' => strtoupper($data['ifsc_code']),
                'bank_name' => $data['bank_name'],
                'account_type' => $data['account_type'],
            ]);

            $this->storeDocument($provider, 'aadhar_front', $request->file('aadhar_front'));
            $this->storeDocument($provider, 'aadhar_back', $request->file('aadhar_back'));
            $this->storeDocument($provider, 'pan_card', $request->file('pan_card'));

            return $provider->load('documents');
        });
    }

    public function isProviderRole(mixed $role): bool
    {
        $value = $role instanceof UserRole ? $role->value : $role;

        return $value === UserRole::Provider->value;
    }

    private function storeDocument(ServiceProvider $provider, string $type, $file): void
    {
        ProviderDocument::create([
            'service_provider_id' => $provider->id,
            'document_type' => $type,
            'file_path' => $file->store('documents/providers/'.$type, 'public'),
        ]);
    }
}
