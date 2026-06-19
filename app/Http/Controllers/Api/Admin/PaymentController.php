<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::with('user:id,name,email')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->method, fn ($q, $m) => $q->where('method', $m))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($payments);
    }

    public function show(Payment $payment): JsonResponse
    {
        return $this->success($payment->load(['user', 'refunds', 'transactions']));
    }

    public function processRefund(Request $request, Payment $payment): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:'.$payment->amount],
            'reason' => ['nullable', 'string'],
        ]);

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'refund_id' => 'REF-'.Str::upper(Str::random(10)),
            'amount' => $request->amount,
            'status' => 'processed',
            'reason' => $request->reason,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return $this->success($refund, 'Refund processed.');
    }
}
