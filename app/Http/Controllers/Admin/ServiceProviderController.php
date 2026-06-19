<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProviderStatus;
use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\ProviderDocument;
use App\Models\ServiceProvider;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $provider = ServiceProvider::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'skills' => $this->parseSkills($data['skills'] ?? ''),
            'experience_years' => $data['experience_years'],
            'service_area' => $data['service_area'] ?? null,
            'status' => $data['status'],
            'approved_at' => $data['status'] === ProviderStatus::Approved->value ? now() : null,
        ]);

        $this->storeDocument($request, $provider);

        return redirect()->route('admin.service-providers.index')->with('success', 'Service provider created successfully.');
    }

    public function edit(ServiceProvider $serviceProvider): View
    {
        $serviceProvider->load('documents');

        return view('admin.service-providers.form', compact('serviceProvider'));
    }

    public function update(Request $request, ServiceProvider $serviceProvider): RedirectResponse
    {
        $data = $this->validated($request);

        $serviceProvider->update([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'skills' => $this->parseSkills($data['skills'] ?? ''),
            'experience_years' => $data['experience_years'],
            'service_area' => $data['service_area'] ?? null,
            'status' => $data['status'],
            'approved_at' => $data['status'] === ProviderStatus::Approved->value
                ? ($serviceProvider->approved_at ?? now())
                : null,
        ]);

        $this->storeDocument($request, $serviceProvider);

        return redirect()->route('admin.service-providers.index')->with('success', 'Service provider updated successfully.');
    }

    public function show(ServiceProvider $serviceProvider): View
    {
        $serviceProvider->load(['documents', 'bookings.user']);

        return view('admin.service-providers.show', compact('serviceProvider'));
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

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => V::nameRules(),
            'mobile' => V::mobileRules(required: true),
            'skills' => ['nullable', 'string', V::maxRule('skills')],
            'experience_years' => ['required', 'integer', 'min:0', 'max:50'],
            'service_area' => ['nullable', 'string', V::maxRule('service_area')],
            'status' => ['required', 'in:pending,approved,rejected,suspended'],
            'id_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
    }

    private function parseSkills(string $skills): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $skills))));
    }

    private function storeDocument(Request $request, ServiceProvider $provider): void
    {
        if ($request->hasFile('id_document')) {
            ProviderDocument::updateOrCreate(
                ['service_provider_id' => $provider->id, 'document_type' => 'ID Proof'],
                ['file_path' => $request->file('id_document')->store('documents/providers', 'public')]
            );
        }
    }
}
