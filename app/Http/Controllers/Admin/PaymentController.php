<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Support\AdminValidation as V;

class PaymentController extends Controller
{
    use ExportsAdminTable;

    public function index(Request $request): View
    {
        $payments = $this->filteredPayments($request)->paginate(15)->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function export(Request $request)
    {
        $payments = $this->filteredPayments($request)->get();

        return $this->exportResponse(
            $request,
            'payments',
            'Payment Transactions',
            ['Payment ID', 'Customer', 'Method', 'Amount', 'Status', 'Created Date'],
            $payments->map(fn (Payment $p) => [
                $p->payment_id,
                $p->user?->name ?? '',
                $p->method->value ?? $p->method,
                number_format((float) $p->amount, 2),
                $p->status->value ?? $p->status,
                $p->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredPayments(Request $request): Builder
    {
        return $this->applyDateRange(
            Payment::with('user')
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->method, fn ($q, $m) => $q->where('method', $m))
                ->latest(),
            $request
        );
    }

    public function show(Payment $payment): View
    {
        $payment->load(['user', 'refunds', 'transactions']);

        return view('admin.payments.show', compact('payment'));
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:'.$payment->amount],
            'reason' => V::reasonRules(required: false),
        ]);

        Refund::create([
            'payment_id' => $payment->id,
            'refund_id' => 'REF-'.Str::upper(Str::random(10)),
            'amount' => $request->amount,
            'status' => 'processed',
            'reason' => $request->reason,
            'processed_by' => auth('admin')->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Refund processed.');
    }
}
