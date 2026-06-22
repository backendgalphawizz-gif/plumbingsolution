<?php

namespace App\Services;

use App\Models\BulkOrder;
use App\Models\Quotation;
use Illuminate\Support\Str;

class QuotationService
{
    public function storeRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function buildItems(array $items): array
    {
        return collect($items)->map(function (array $item): array {
            $price = round((float) $item['price'], 2);
            $quantity = (int) $item['quantity'];

            return [
                'product_name' => $item['product_name'],
                'price' => $price,
                'quantity' => $quantity,
                'total' => round($price * $quantity, 2),
            ];
        })->values()->all();
    }

    public function calculateAmount(array $items): float
    {
        return round(collect($items)->sum('total'), 2);
    }

    public function create(BulkOrder $bulkOrder, array $data, ?int $createdBy = null): Quotation
    {
        $items = $this->buildItems($data['items']);
        $amount = $this->calculateAmount($items);

        $quotation = Quotation::create([
            'bulk_order_id' => $bulkOrder->id,
            'quotation_number' => 'QT-'.Str::upper(Str::random(8)),
            'amount' => $amount,
            'details' => [
                'items' => $items,
                'notes' => $data['notes'] ?? null,
            ],
            'status' => 'draft',
            'created_by' => $createdBy,
        ]);

        $bulkOrder->update(['status' => 'quotation_generated']);

        return $quotation;
    }

    public function format(Quotation $quotation): array
    {
        $details = is_array($quotation->details)
            ? $quotation->details
            : (json_decode($quotation->details ?? '{}', true) ?: []);
        $items = $details['items'] ?? [];

        return [
            'id' => $quotation->id,
            'quotation_number' => $quotation->quotation_number,
            'amount' => (float) $quotation->amount,
            'items' => $items,
            'notes' => $details['notes'] ?? null,
            'status' => $quotation->status,
            'sent_at' => $quotation->sent_at?->format('M d, Y • g:i A'),
            'responded_at' => $quotation->responded_at?->format('M d, Y • g:i A'),
            'rejection_reason' => $quotation->rejection_reason,
        ];
    }
}
