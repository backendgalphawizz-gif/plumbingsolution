<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .meta { color: #64748b; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f8fafc; }
        .totals td { border: none; padding: 4px 8px; }
        .totals tr td:last-child { text-align: right; }
    </style>
</head>
<body>
    <h1>Invoice</h1>
    <div class="meta">
        Order: {{ $order->order_number }}<br>
        Date: {{ $order->created_at->format('M d, Y') }}<br>
        Customer: {{ $order->user->name }}<br>
        Vendor: {{ $order->vendor?->shop_name ?? 'N/A' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>₹{{ number_format($item->unit_price, 2) }}</td>
                    <td>₹{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="width: 280px; margin-left: auto;">
        <tr><td>Subtotal</td><td>₹{{ number_format($order->subtotal, 2) }}</td></tr>
        <tr><td>Discount</td><td>₹{{ number_format($order->discount_amount, 2) }}</td></tr>
        <tr><td>Tax</td><td>₹{{ number_format($order->tax_amount, 2) }}</td></tr>
        <tr><td>Shipping</td><td>₹{{ number_format($order->shipping_amount, 2) }}</td></tr>
        <tr><td><strong>Total</strong></td><td><strong>₹{{ number_format($order->total_amount, 2) }}</strong></td></tr>
    </table>
</body>
</html>
