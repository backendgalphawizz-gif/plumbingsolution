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
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
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

    public function create(BulkOrder $bulkOrder, array $data, ?int $createdBy = null, bool $sendImmediately = false): Quotation
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
            'valid_until' => $data['valid_until'],
            'status' => $sendImmediately ? 'sent' : 'draft',
            'sent_at' => $sendImmediately ? now() : null,
            'created_by' => $createdBy,
        ]);

        $bulkOrder->update([
            'status' => $sendImmediately ? 'quotation_sent' : 'quotation_generated',
        ]);

        return $quotation;
    }

    public function send(Quotation $quotation, BulkOrder $bulkOrder): Quotation
    {
        if ($quotation->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft quotations can be sent.');
        }

        if (! $quotation->valid_until) {
            throw new \InvalidArgumentException('Quotation validity date is required before sending.');
        }

        if ($quotation->valid_until->startOfDay()->lt(now()->startOfDay())) {
            throw new \InvalidArgumentException('Quotation validity date has already passed. Update the date before sending.');
        }

        $quotation->update(['status' => 'sent', 'sent_at' => now()]);
        $bulkOrder->update(['status' => 'quotation_sent']);

        return $quotation->fresh();
    }

    public function expireIfNeeded(Quotation $quotation): bool
    {
        if (! $quotation->isExpired()) {
            return false;
        }

        $quotation->update(['status' => 'expired']);

        return true;
    }

    public function expireStaleForBulkOrder(BulkOrder $bulkOrder): void
    {
        $bulkOrder->quotations()
            ->where('status', 'sent')
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->each(fn (Quotation $quotation) => $this->expireIfNeeded($quotation));
    }

    public function canRespond(Quotation $quotation, BulkOrder $bulkOrder): bool
    {
        $this->expireIfNeeded($quotation->fresh());

        return $bulkOrder->status === 'quotation_sent'
            && $quotation->fresh()->status === 'sent';
    }

    public function format(Quotation $quotation): array
    {
        $this->expireIfNeeded($quotation);
        $quotation->refresh();

        $details = is_array($quotation->details)
            ? $quotation->details
            : (json_decode($quotation->details ?? '{}', true) ?: []);
        $items = $details['items'] ?? [];
        $isExpired = $quotation->status === 'expired';

        return [
            'id' => $quotation->id,
            'quotation_number' => $quotation->quotation_number,
            'amount' => (float) $quotation->amount,
            'items' => $items,
            'notes' => $details['notes'] ?? null,
            'valid_until' => $quotation->valid_until?->format('Y-m-d'),
            'valid_until_label' => $quotation->valid_until?->format('M d, Y'),
            'is_expired' => $isExpired,
            'status' => $quotation->status,
            'sent_at' => $quotation->sent_at?->format('M d, Y • g:i A'),
            'responded_at' => $quotation->responded_at?->format('M d, Y • g:i A'),
            'rejection_reason' => $quotation->rejection_reason,
        ];
    }
}
