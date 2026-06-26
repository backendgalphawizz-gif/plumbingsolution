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
use Illuminate\Validation\Rule;
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
            ...$this->vendorAttributes($data),
            'shop_logo' => $request->file('shop_logo')->store('vendors/logos', 'public'),
            'approved_at' => $data['status'] === VendorStatus::Approved->value ? now() : null,
        ]);

        $this->storeDocuments($request, $vendor, creating: true);

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
            ...$this->vendorAttributes($data),
            'approved_at' => $data['status'] === VendorStatus::Approved->value
                ? ($vendor->approved_at ?? now())
                : null,
        ]);

        if ($request->hasFile('shop_logo')) {
            $vendor->update(['shop_logo' => $request->file('shop_logo')->store('vendors/logos', 'public')]);
        }

        $this->storeDocuments($request, $vendor, creating: false);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function show(Vendor $vendor): View
    {
        $vendor->load(['documents', 'user']);
        $vendor->loadCount(['products', 'orders']);
        $recentOrders = $vendor->orders()->with('user')->latest()->limit(8)->get();

        return view('admin.vendors.show', compact('vendor', 'recentOrders'));
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
        $creating = ! $vendor;

        return $request->validate(array_merge([
            'shop_name' => ['required', 'string', V::maxRule('shop_name')],
            'owner_name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), [
                Rule::unique('vendors', 'mobile')->ignore($vendor?->id),
            ]),
            'business_mobile' => V::mobileRules(required: false),
            'email' => V::emailRules(required: false),
            'address' => V::requiredAddressRules(),
            'gst_number' => ['nullable', 'string', V::maxRule('gst_number')],
            'status' => ['required', 'in:pending,approved,rejected,suspended'],
            'shop_logo' => V::imageDocRules($creating),
            'aadhar_card' => V::imageDocRules($creating),
            'pan_card' => V::imageDocRules($creating),
        ], V::locationRules($creating), V::bankRules($creating)), V::bankValidationMessages());
    }

    private function vendorAttributes(array $data): array
    {
        return [
            'shop_name' => $data['shop_name'],
            'owner_name' => $data['owner_name'],
            'mobile' => $data['mobile'],
            'business_mobile' => $data['business_mobile'] ?? $data['mobile'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'],
            'country' => $data['country'],
            'state' => $data['state'],
            'city' => $data['city'],
            'pincode' => $data['pincode'],
            'gst_number' => $data['gst_number'] ?? null,
            'status' => $data['status'],
            'account_holder_name' => $data['account_holder_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'ifsc_code' => isset($data['ifsc_code']) ? strtoupper($data['ifsc_code']) : null,
            'bank_name' => $data['bank_name'] ?? null,
            'account_type' => isset($data['account_type']) ? V::normalizeAccountType($data['account_type']) : null,
        ];
    }

    private function storeDocuments(Request $request, Vendor $vendor, bool $creating): void
    {
        if ($request->hasFile('aadhar_card')) {
            VendorDocument::updateOrCreate(
                ['vendor_id' => $vendor->id, 'document_type' => 'Aadhar Card'],
                ['file_path' => $request->file('aadhar_card')->store('documents/vendors/aadhar_card', 'public')]
            );
        }

        if ($request->hasFile('pan_card')) {
            VendorDocument::updateOrCreate(
                ['vendor_id' => $vendor->id, 'document_type' => 'PAN Card'],
                ['file_path' => $request->file('pan_card')->store('documents/vendors/pan_card', 'public')]
            );
        }
    }
}
