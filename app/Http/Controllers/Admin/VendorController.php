<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesUploads;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    use ExportsAdminTable, HandlesUploads;

    public function index(Request $request): View
    {
        $vendors = $this->filteredVendors($request)->paginate(15)->withQueryString();

        $stats = [
            'total' => Vendor::count(),
            'pending' => Vendor::where('status', VendorStatus::Pending)->count(),
            'approved' => Vendor::where('status', VendorStatus::Approved)->count(),
            'suspended' => Vendor::where('status', VendorStatus::Suspended)->count(),
        ];

        return view('admin.vendors.index', compact('vendors', 'stats'));
    }

    public function export(Request $request)
    {
        $vendors = $this->filteredVendors($request)->get();

        return $this->exportResponse(
            $request,
            'vendors',
            'Vendor List',
            ['Shop', 'Owner', 'Mobile', 'GST', 'Status', 'Products', 'Created Date'],
            $vendors->map(fn (Vendor $v) => [
                $v->shop_name,
                $v->owner_name,
                $v->mobile,
                $v->gst_number ?? '',
                $v->status->value ?? $v->status,
                $v->products_count,
                $v->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredVendors(Request $request): Builder
    {
        $request->validate(['search' => V::searchRules()]);

        return $this->applyDateRange(
            Vendor::withCount('products')
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                    $q->where('shop_name', 'like', "%{$s}%")
                        ->orWhere('owner_name', 'like', "%{$s}%")
                        ->orWhere('mobile', 'like', "%{$s}%")
                        ->orWhere('gst_number', 'like', "%{$s}%");
                }))
                ->latest(),
            $request
        );
    }

    public function create(): View
    {
        return view('admin.vendors.form', ['vendor' => new Vendor]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $vendor = Vendor::create([
            ...collect($data)->except(['gst_document', 'license_document'])->toArray(),
            'approved_at' => $data['status'] === VendorStatus::Approved->value ? now() : null,
        ]);

        $this->storeDocuments($request, $vendor);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function edit(Vendor $vendor): View
    {
        $vendor->load('documents');

        return view('admin.vendors.form', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $data = $this->validated($request, $vendor);

        $vendor->update([
            ...collect($data)->except(['gst_document', 'license_document'])->toArray(),
            'approved_at' => $data['status'] === VendorStatus::Approved->value
                ? ($vendor->approved_at ?? now())
                : null,
        ]);

        $this->storeDocuments($request, $vendor);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function show(Vendor $vendor): View
    {
        $vendor->load(['documents', 'products']);

        return view('admin.vendors.show', compact('vendor'));
    }

    public function approve(Vendor $vendor): RedirectResponse
    {
        $vendor->update(['status' => VendorStatus::Approved, 'approved_at' => now(), 'rejection_reason' => null]);

        return back()->with('success', 'Vendor approved.');
    }

    public function reject(Request $request, Vendor $vendor): RedirectResponse
    {
        $request->validate(['reason' => V::reasonRules()]);
        $vendor->update(['status' => VendorStatus::Rejected, 'rejection_reason' => $request->reason]);

        return back()->with('success', 'Vendor rejected.');
    }

    public function suspend(Vendor $vendor): RedirectResponse
    {
        $vendor->update(['status' => VendorStatus::Suspended]);

        return back()->with('success', 'Vendor suspended.');
    }

    private function validated(Request $request, ?Vendor $vendor = null): array
    {
        return $request->validate([
            'shop_name' => ['required', 'string', V::maxRule('shop_name')],
            'owner_name' => V::nameRules(),
            'mobile' => V::mobileRules(required: true),
            'address' => V::addressRules(),
            'gst_number' => ['nullable', 'string', V::maxRule('gst_number')],
            'status' => ['required', 'in:pending,approved,rejected,suspended'],
            'gst_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'license_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
    }

    private function storeDocuments(Request $request, Vendor $vendor): void
    {
        if ($request->hasFile('gst_document')) {
            VendorDocument::updateOrCreate(
                ['vendor_id' => $vendor->id, 'document_type' => 'GST Certificate'],
                ['file_path' => $request->file('gst_document')->store('documents/vendors', 'public')]
            );
        }

        if ($request->hasFile('license_document')) {
            VendorDocument::updateOrCreate(
                ['vendor_id' => $vendor->id, 'document_type' => 'Shop License'],
                ['file_path' => $request->file('license_document')->store('documents/vendors', 'public')]
            );
        }
    }
}
