<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BulkOrder;
use App\Models\BulkOrderFile;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Transaction;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BulkOrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['all', 'submitted', 'quotation', 'approved', 'rejected'])],
        ]);

        $filter = $request->get('status', 'all');

        $orders = $request->user()->bulkOrders()
            ->with(['files', 'quotations'])
            ->when($filter === 'quotation', fn ($q) => $q->where('status', 'quotation_sent'))
            ->when($filter === 'submitted', fn ($q) => $q->whereIn('status', ['requirement_submitted', 'admin_review']))
            ->when($filter === 'approved', fn ($q) => $q->whereIn('status', ['customer_approved', 'order_created']))
            ->when($filter === 'rejected', fn ($q) => $q->where('status', 'customer_rejected'))
            ->latest()
            ->paginate(15);

        return $this->success([
            'items' => collect($orders->items())->map(fn ($o) => UserApiFormatter::bulkOrder($o, detailed: true)),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => V::nameRules(),
            'mobile' => V::mobileRules(required: true),
            'note' => ['nullable', 'string', V::maxRule('notes')],
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,xls,xlsx,csv',
            ],
        ]);

        $bulkOrder = BulkOrder::create([
            'reference_number' => 'BULK-'.strtoupper(Str::random(8)),
            'user_id' => $request->user()->id,
            'full_name' => $data['full_name'],
            'mobile' => $data['mobile'],
            'requirement_description' => $data['note'] ?? null,
            'status' => 'requirement_submitted',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        BulkOrderFile::create([
            'bulk_order_id' => $bulkOrder->id,
            'file_path' => $file->store('bulk-orders', 'public'),
            'file_type' => $extension,
            'original_name' => $file->getClientOriginalName(),
        ]);

        $bulkOrder->load(['files', 'quotations']);

        return $this->success(UserApiFormatter::bulkOrder($bulkOrder, detailed: true), 'Bulk order submitted.', 201);
    }

    public function show(Request $request, BulkOrder $bulkOrder): JsonResponse
    {
        abort_if($bulkOrder->user_id !== $request->user()->id, 403);

        $bulkOrder->load(['files', 'quotations', 'payment']);

        return $this->success(UserApiFormatter::bulkOrder($bulkOrder, detailed: true));
    }

    public function acceptQuotation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'quotation_id' => ['required', 'exists:quotations,id'],
            'payment_method' => ['required', 'in:razorpay,cod'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['payment_method'] === 'razorpay' && empty($data['transaction_id'])) {
            return $this->error('Transaction ID is required for online payment.', 422);
        }

        $quotation = Quotation::with('bulkOrder')->findOrFail($data['quotation_id']);
        $bulkOrder = $quotation->bulkOrder;

        abort_if($bulkOrder->user_id !== $request->user()->id, 403);

        if ($bulkOrder->status !== 'quotation_sent' || $quotation->status !== 'sent') {
            return $this->error('This quotation is not available for acceptance.', 422);
        }

        $quotation->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);

        $bulkOrder->update(['status' => 'customer_approved']);

        $paymentStatus = $data['payment_method'] === 'cod' ? PaymentStatus::Pending : PaymentStatus::Completed;

        $payment = Payment::create([
            'payment_id' => 'PAY-'.strtoupper(Str::random(10)),
            'user_id' => $request->user()->id,
            'payable_type' => BulkOrder::class,
            'payable_id' => $bulkOrder->id,
            'method' => PaymentMethod::from($data['payment_method']),
            'status' => $paymentStatus,
            'amount' => $quotation->amount,
            'currency' => 'INR',
            'gateway_payment_id' => $data['transaction_id'] ?? null,
        ]);

        if (! empty($data['transaction_id'])) {
            Transaction::create([
                'payment_id' => $payment->id,
                'transaction_id' => $data['transaction_id'],
                'type' => 'payment',
                'amount' => $quotation->amount,
                'status' => 'completed',
                'description' => 'Bulk order quotation payment',
            ]);
        }

        $bulkOrder->update(['status' => 'order_created']);
        $bulkOrder->load(['files', 'quotations', 'payment']);

        return $this->success([
            'bulk_order' => UserApiFormatter::bulkOrder($bulkOrder, detailed: true),
            'payment' => [
                'payment_id' => $payment->payment_id,
                'method' => $payment->method->value,
                'status' => $payment->status->value,
                'amount' => (float) $payment->amount,
                'transaction_id' => $payment->gateway_payment_id,
            ],
        ], 'Quotation accepted.');
    }

    public function rejectQuotation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'quotation_id' => ['required', 'exists:quotations,id'],
            'reason' => V::reasonRules(),
        ]);

        $quotation = Quotation::with('bulkOrder')->findOrFail($data['quotation_id']);
        $bulkOrder = $quotation->bulkOrder;

        abort_if($bulkOrder->user_id !== $request->user()->id, 403);

        if ($bulkOrder->status !== 'quotation_sent' || $quotation->status !== 'sent') {
            return $this->error('This quotation is not available for rejection.', 422);
        }

        $quotation->update([
            'status' => 'rejected',
            'rejection_reason' => $data['reason'],
            'responded_at' => now(),
        ]);

        $bulkOrder->update(['status' => 'customer_rejected']);
        $bulkOrder->load(['files', 'quotations']);

        return $this->success(UserApiFormatter::bulkOrder($bulkOrder, detailed: true), 'Quotation rejected.');
    }
}
