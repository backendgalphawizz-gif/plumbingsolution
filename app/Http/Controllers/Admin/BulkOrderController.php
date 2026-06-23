<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\BulkOrder;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\AdminValidation as V;

class BulkOrderController extends Controller
{
    use ExportsAdminTable;

    public function index(Request $request): View
    {
        $bulkOrders = $this->filteredBulkOrders($request)->paginate(15)->withQueryString();

        return view('admin.bulk-orders.index', compact('bulkOrders'));
    }

    public function export(Request $request)
    {
        $bulkOrders = $this->filteredBulkOrders($request)->withCount('files')->get();

        return $this->exportResponse(
            $request,
            'bulk-orders',
            'Bulk Order List',
            ['Reference', 'Customer', 'Status', 'Files', 'Created Date'],
            $bulkOrders->map(fn (BulkOrder $b) => [
                $b->reference_number,
                $b->user?->name ?? '',
                $b->status,
                $b->files_count,
                $b->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredBulkOrders(Request $request): Builder
    {
        return $this->applyDateRange(
            BulkOrder::with(['user', 'files'])
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest(),
            $request
        );
    }

    public function show(BulkOrder $bulkOrder): View
    {
        $bulkOrder->load(['user', 'files', 'quotations.creator']);

        return view('admin.bulk-orders.show', compact('bulkOrder'));
    }

    public function review(Request $request, BulkOrder $bulkOrder): RedirectResponse
    {
        $request->validate(['admin_notes' => V::notesRules()]);

        $bulkOrder->update([
            'status' => 'admin_review',
            'admin_notes' => $request->input('admin_notes'),
        ]);

        return back()->with('success', 'Marked under review.');
    }

    public function createQuotation(Request $request, BulkOrder $bulkOrder, QuotationService $quotations): RedirectResponse
    {
        if (! $bulkOrder->canReceiveQuotation()) {
            return back()->with('error', 'A quotation has already been sent for this bulk order.');
        }

        $data = $request->validate($quotations->storeRules());

        $quotations->create($bulkOrder, $data, auth('admin')->id(), sendImmediately: true);

        return back()->with('success', 'Quotation sent to customer.');
    }

    public function sendQuotation(BulkOrder $bulkOrder, Quotation $quotation, QuotationService $quotations): RedirectResponse
    {
        abort_if($quotation->bulk_order_id !== $bulkOrder->id, 404);

        try {
            $quotations->send($quotation, $bulkOrder);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Quotation sent to customer.');
    }
}
