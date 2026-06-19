<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BulkOrder;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BulkOrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $orders = BulkOrder::with(['user:id,name', 'files'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($orders);
    }

    public function show(BulkOrder $bulkOrder): JsonResponse
    {
        return $this->success($bulkOrder->load(['user', 'files', 'quotations.creator']));
    }

    public function review(Request $request, BulkOrder $bulkOrder): JsonResponse
    {
        $request->validate(['admin_notes' => ['nullable', 'string']]);

        $bulkOrder->update([
            'status' => 'admin_review',
            'admin_notes' => $request->admin_notes,
        ]);

        return $this->success($bulkOrder->fresh(), 'Bulk order under review.');
    }

    public function createQuotation(Request $request, BulkOrder $bulkOrder): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'details' => ['nullable', 'string'],
        ]);

        $quotation = Quotation::create([
            'bulk_order_id' => $bulkOrder->id,
            'quotation_number' => 'QT-'.Str::upper(Str::random(8)),
            'amount' => $data['amount'],
            'details' => $data['details'] ?? null,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        $bulkOrder->update(['status' => 'quotation_generated']);

        return $this->success($quotation, 'Quotation created.', 201);
    }

    public function sendQuotation(BulkOrder $bulkOrder, Quotation $quotation): JsonResponse
    {
        $quotation->update(['status' => 'sent', 'sent_at' => now()]);
        $bulkOrder->update(['status' => 'quotation_sent']);

        return $this->success($quotation->fresh(), 'Quotation sent to customer.');
    }
}
