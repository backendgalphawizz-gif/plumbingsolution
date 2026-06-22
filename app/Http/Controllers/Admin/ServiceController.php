<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceProvider;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    use ExportsAdminTable;

    public function index(Request $request): View
    {
        $services = $this->filteredServices($request)->paginate(15)->withQueryString();

        return view('admin.services.index', [
            'services' => $services,
            'categories' => ServiceCategory::orderBy('name')->get(),
            'providers' => ServiceProvider::orderBy('name')->get(),
        ]);
    }

    public function show(Service $service): View
    {
        $service->load(['category', 'serviceProvider', 'images', 'providers']);

        return view('admin.services.show', compact('service'));
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('admin.services.index')->with('success', 'Service deleted.');
    }

    public function export(Request $request)
    {
        $services = $this->filteredServices($request)->get();

        return $this->exportResponse(
            $request,
            'services',
            'Service Catalog',
            ['Service', 'Category', 'Provider', 'Price', 'Status', 'Created Date'],
            $services->map(fn (Service $service) => [
                $service->name,
                $service->category?->name ?? '',
                $service->serviceProvider?->name ?? 'Platform',
                number_format((float) $service->starting_price, 2),
                $service->status ? 'Active' : 'Inactive',
                $service->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredServices(Request $request): Builder
    {
        $request->validate(['search' => V::searchRules()]);

        return Service::query()
            ->with(['category', 'serviceProvider'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn ($q, $id) => $q->where('service_category_id', $id))
            ->when($request->provider_id, fn ($q, $id) => $q->where('service_provider_id', $id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->boolean('status')))
            ->when($request->source === 'provider', fn ($q) => $q->whereNotNull('service_provider_id'))
            ->when($request->source === 'platform', fn ($q) => $q->whereNull('service_provider_id'))
            ->latest();
    }
}
