<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BulkOrder;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function createQuotation(Request $request, BulkOrder $bulkOrder, QuotationService $quotations): JsonResponse
    {
        if (! $bulkOrder->canReceiveQuotation()) {
            return $this->error('A quotation has already been sent for this bulk order.', 422);
        }

        $data = $request->validate($quotations->storeRules());

        $quotation = $quotations->create($bulkOrder, $data, $request->user()->id, sendImmediately: true);

        return $this->success($quotations->format($quotation), 'Quotation sent to customer.', 201);
    }

    public function sendQuotation(BulkOrder $bulkOrder, Quotation $quotation, QuotationService $quotations): JsonResponse
    {
        abort_if($quotation->bulk_order_id !== $bulkOrder->id, 404);

        try {
            $quotation = $quotations->send($quotation, $bulkOrder);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success($quotations->format($quotation), 'Quotation sent to customer.');
    }
}
