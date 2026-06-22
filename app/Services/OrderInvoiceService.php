<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class OrderInvoiceService
{
    public function generate(Order $order): string
    {
        if ($order->invoice_path && Storage::disk('public')->exists($order->invoice_path)) {
            return $order->invoice_path;
        }

        $order->load(['items', 'vendor', 'user', 'payment']);

        $pdf = Pdf::loadView('invoices.order', ['order' => $order]);
        $path = 'invoices/'.$order->order_number.'.pdf';

        Storage::disk('public')->put($path, $pdf->output());

        $order->update(['invoice_path' => $path]);

        return $path;
    }

    public function url(Order $order): string
    {
        if (! $order->invoice_path) {
            $this->generate($order);
            $order->refresh();
        }

        return url('/api/user/orders/'.$order->id.'/invoice');
    }
}
