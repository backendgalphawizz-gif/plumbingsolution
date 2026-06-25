<?php

namespace App\Services;

use App\Models\Setting;

class TaxService
{
    public function percent(): float
    {
        return (float) Setting::getValue('tax', 'gst_rate', 18);
    }

    /**
     * @return array{subtotal: float, discount: float, tax: float, tax_percent: float, total: float}
     */
    public function calculate(float $subtotal, float $discount = 0): array
    {
        $subtotal = round($subtotal, 2);
        $discount = round($discount, 2);
        $taxable = max(0, $subtotal - $discount);
        $tax = round($taxable * $this->percent() / 100, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'tax_percent' => $this->percent(),
            'total' => round($taxable + $tax, 2),
        ];
    }
}
