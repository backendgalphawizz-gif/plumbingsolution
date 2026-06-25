<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponAppliesTo;
use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    use ExportsAdminTable;

    public function orderIndex(Request $request): View
    {
        return $this->renderIndex($request, CouponAppliesTo::Order);
    }

    public function bookingIndex(Request $request): View
    {
        return $this->renderIndex($request, CouponAppliesTo::Booking);
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        return $this->store($request, CouponAppliesTo::Order);
    }

    public function storeBooking(Request $request): RedirectResponse
    {
        return $this->store($request, CouponAppliesTo::Booking);
    }

    public function updateOrder(Request $request, Coupon $coupon): RedirectResponse
    {
        return $this->update($request, CouponAppliesTo::Order, $coupon);
    }

    public function updateBooking(Request $request, Coupon $coupon): RedirectResponse
    {
        return $this->update($request, CouponAppliesTo::Booking, $coupon);
    }

    public function destroyOrder(Coupon $coupon): RedirectResponse
    {
        return $this->destroy(CouponAppliesTo::Order, $coupon);
    }

    public function destroyBooking(Coupon $coupon): RedirectResponse
    {
        return $this->destroy(CouponAppliesTo::Booking, $coupon);
    }

    private function renderIndex(Request $request, CouponAppliesTo $type): View
    {
        $coupons = $this->filteredCoupons($request, $type)->paginate(15)->withQueryString();

        return view('admin.coupons.index', [
            'coupons' => $coupons,
            'type' => $type,
            'counts' => [
                'order' => Coupon::forOrders()->count(),
                'booking' => Coupon::forBookings()->count(),
            ],
        ]);
    }

    private function filteredCoupons(Request $request, CouponAppliesTo $type): Builder
    {
        $request->validate(['search' => V::searchRules()]);

        return $this->applyDateRange(
            Coupon::where('applies_to', $type)
                ->when($request->search, fn ($q, $s) => $q->where('code', 'like', '%'.strtoupper($s).'%'))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->boolean('status')))
                ->when($request->discount_type, fn ($q, $discountType) => $q->where('discount_type', $discountType))
                ->orderByDesc('created_at'),
            $request
        );
    }

    private function store(Request $request, CouponAppliesTo $type): RedirectResponse
    {
        $data = $this->validated($request, $type);
        $code = trim($data['code'] ?? '');
        $data['code'] = $code !== '' ? strtoupper($code) : $this->generateUniqueCode($type);
        $data['applies_to'] = $type->value;
        $data['status'] = $request->boolean('status');
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;

        Coupon::create($data);

        return redirect()
            ->route($this->indexRoute($type))
            ->with('success', ucfirst($type->value).' coupon created.');
    }

    private function update(Request $request, CouponAppliesTo $type, Coupon $coupon): RedirectResponse
    {
        $this->ensureType($coupon, $type);

        $data = $this->validated($request, $type, $coupon);
        if (isset($data['code']) && $data['code'] !== '') {
            $data['code'] = strtoupper($data['code']);
        } else {
            unset($data['code']);
        }
        $data['status'] = $request->boolean('status');
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;

        $coupon->update($data);

        return redirect()
            ->route($this->indexRoute($type))
            ->with('success', ucfirst($type->value).' coupon updated.');
    }

    private function destroy(CouponAppliesTo $type, Coupon $coupon): RedirectResponse
    {
        $this->ensureType($coupon, $type);
        $coupon->delete();

        return redirect()
            ->route($this->indexRoute($type))
            ->with('success', ucfirst($type->value).' coupon deleted.');
    }

    private function validated(Request $request, CouponAppliesTo $type, ?Coupon $coupon = null): array
    {
        $codeRule = Rule::unique('coupons', 'code')
            ->where('applies_to', $type->value);

        if ($coupon) {
            $codeRule->ignore($coupon->id);
        }

        return $request->validate([
            'code' => [$coupon ? 'required' : 'nullable', 'string', 'max:30', $codeRule],
            'discount_type' => ['required', 'in:fixed,percent'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'expires_at.after_or_equal' => 'Expiry date cannot be in the past.',
        ]);
    }

    private function generateUniqueCode(CouponAppliesTo $type): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Coupon::where('code', $code)->where('applies_to', $type->value)->exists());

        return $code;
    }

    private function ensureType(Coupon $coupon, CouponAppliesTo $type): void
    {
        $couponType = $coupon->applies_to instanceof CouponAppliesTo
            ? $coupon->applies_to
            : CouponAppliesTo::from($coupon->applies_to);

        abort_if($couponType !== $type, 404);
    }

    private function indexRoute(CouponAppliesTo $type): string
    {
        return $type === CouponAppliesTo::Order
            ? 'admin.coupons.order.index'
            : 'admin.coupons.booking.index';
    }
}
