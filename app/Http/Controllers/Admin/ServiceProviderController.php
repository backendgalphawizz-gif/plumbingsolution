<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProviderStatus;
use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\ProviderDocument;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderImage;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceProviderController extends Controller
{
    use ExportsAdminTable;

    public function index(Request $request): View
    {
        $providers = $this->filteredProviders($request)->paginate(15)->withQueryString();

        $stats = [
            'total' => ServiceProvider::count(),
            'pending' => ServiceProvider::where('status', ProviderStatus::Pending)->count(),
            'approved' => ServiceProvider::where('status', ProviderStatus::Approved)->count(),
            'rejected' => ServiceProvider::where('status', ProviderStatus::Rejected)->count(),
        ];

        return view('admin.service-providers.index', compact('providers', 'stats'));
    }

    public function export(Request $request)
    {
        $providers = $this->filteredProviders($request)->get();

        return $this->exportResponse(
            $request,
            'service-providers',
            'Service Provider List',
            ['Name', 'Mobile', 'Skills', 'Experience', 'Status', 'Bookings', 'Created Date'],
            $providers->map(fn (ServiceProvider $p) => [
                $p->name,
                $p->mobile,
                implode(', ', $p->skills ?? []),
                $p->experience_years.' yrs',
                $p->status->value ?? $p->status,
                $p->bookings_count,
                $p->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredProviders(Request $request): Builder
    {
        $request->validate(['search' => V::searchRules()]);

        return $this->applyDateRange(
            ServiceProvider::withCount('bookings')
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('mobile', 'like', "%{$s}%")
                        ->orWhere('service_area', 'like', "%{$s}%");
                }))
                ->when($request->filled('min_experience'), fn ($q) => $q->where('experience_years', '>=', $request->min_experience))
                ->latest(),
            $request
        );
    }

    public function create(): View
    {
        return view('admin.service-providers.form', ['serviceProvider' => new ServiceProvider]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $provider = ServiceProvider::create($this->providerAttributes($data));

        $this->syncLinkedUserEmail($provider, $data['email'] ?? null);
        $this->storeImages($request, $provider);
        $this->storeDocuments($request, $provider, creating: true);

        return redirect()->route('admin.service-providers.index')->with('success', 'Service provider created successfully.');
    }

    public function edit(ServiceProvider $serviceProvider): View
    {
        $serviceProvider->load(['documents', 'user']);

        return view('admin.service-providers.form', compact('serviceProvider'));
    }

    public function update(Request $request, ServiceProvider $serviceProvider): RedirectResponse
    {
        $data = $this->validated($request, $serviceProvider);

        $serviceProvider->update([
            ...$this->providerAttributes($data),
            'approved_at' => $data['status'] === ProviderStatus::Approved->value
                ? ($serviceProvider->approved_at ?? now())
                : null,
        ]);

        $this->syncLinkedUserEmail($serviceProvider, $data['email'] ?? null);
        $this->storeImages($request, $serviceProvider);
        $this->storeDocuments($request, $serviceProvider, creating: false);

        return redirect()->route('admin.service-providers.index')->with('success', 'Service provider updated successfully.');
    }

    public function show(ServiceProvider $serviceProvider): View
    {
        $serviceProvider->load(['documents', 'user', 'services.category']);
        $serviceProvider->loadCount('bookings');
        $bookings = $serviceProvider->bookings()->with('user')->latest()->limit(10)->get();

        return view('admin.service-providers.show', compact('serviceProvider', 'bookings'));
    }

    public function approve(ServiceProvider $serviceProvider): RedirectResponse
    {
        $serviceProvider->update(['status' => ProviderStatus::Approved, 'approved_at' => now(), 'rejection_reason' => null]);

        return back()->with('success', 'Provider approved.');
    }

    public function reject(Request $request, ServiceProvider $serviceProvider): RedirectResponse
    {
        $request->validate(['reason' => V::reasonRules()]);
        $serviceProvider->update(['status' => ProviderStatus::Rejected, 'rejection_reason' => $request->reason]);

        return back()->with('success', 'Provider rejected.');
    }

    public function suspend(ServiceProvider $serviceProvider): RedirectResponse
    {
        $serviceProvider->update(['status' => ProviderStatus::Suspended]);

        return back()->with('success', 'Provider suspended.');
    }

    private function validated(Request $request, ?ServiceProvider $provider = null): array
    {
        $creating = ! $provider;

        $rules = array_merge([
            'name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), [
                Rule::unique('service_providers', 'mobile')->ignore($provider?->id),
            ]),
            'email' => V::emailRules(required: false),
            'address' => V::requiredAddressRules(),
            'skills' => V::skillsStringRules($creating),
            'experience_years' => ['required', 'integer', 'min:0', 'max:50'],
            'status' => ['required', 'in:pending,approved,rejected,suspended'],
            'avatar' => V::imageRules(required: false),
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => V::imageRules(required: true),
            'aadhar_front' => V::imageDocRules($creating),
            'aadhar_back' => V::imageDocRules($creating),
            'pan_card' => V::imageDocRules($creating),
        ], V::bankRules($creating));

        return $request->validate($rules);
    }

    private function providerAttributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'skills' => $this->parseSkills($data['skills'] ?? ''),
            'experience_years' => $data['experience_years'],
            'service_area' => $data['address'],
            'status' => $data['status'],
            'account_holder_name' => $data['account_holder_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'ifsc_code' => isset($data['ifsc_code']) ? strtoupper($data['ifsc_code']) : null,
            'bank_name' => $data['bank_name'] ?? null,
            'account_type' => isset($data['account_type']) ? V::normalizeAccountType($data['account_type']) : null,
            'approved_at' => $data['status'] === ProviderStatus::Approved->value ? now() : null,
        ];
    }

    private function parseSkills(string $skills): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $skills))));
    }

    private function syncLinkedUserEmail(ServiceProvider $provider, ?string $email): void
    {
        if (! $provider->user_id || ! $provider->relationLoaded('user')) {
            $provider->load('user');
        }

        if ($provider->user && $email !== null) {
            $provider->user->update(['email' => $email !== '' ? $email : null]);
        }
    }

    private function storeDocuments(Request $request, ServiceProvider $provider, bool $creating): void
    {
        foreach (['aadhar_front', 'aadhar_back', 'pan_card'] as $type) {
            if ($request->hasFile($type)) {
                ProviderDocument::updateOrCreate(
                    ['service_provider_id' => $provider->id, 'document_type' => $type],
                    ['file_path' => $request->file($type)->store('documents/providers/'.$type, 'public')]
                );
            }
        }
    }

    private function storeImages(Request $request, ServiceProvider $provider): void
    {
        if ($request->hasFile('avatar')) {
            $provider->update([
                'avatar' => $request->file('avatar')->store('providers/avatars', 'public'),
            ]);
        }

        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $i => $file) {
                ServiceProviderImage::create([
                    'service_provider_id' => $provider->id,
                    'image_path' => $file->store('providers/gallery', 'public'),
                    'is_primary' => $i === 0 && ! $provider->avatar,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
