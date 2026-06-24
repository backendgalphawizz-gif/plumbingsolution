<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BulkOrder;
use App\Models\Order;
use App\Services\OrderInvoiceService;
use App\Services\PushNotificationService;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['all', 'processing', 'out_for_delivery', 'delivered', 'cancelled', 'quotation'])],
        ]);

        $filter = $request->get('status', 'all');
        $perPage = 15;
        $page = max(1, (int) $request->get('page', 1));

        if ($filter === 'quotation') {
            $bulkOrders = $request->user()->bulkOrders()
                ->with(['files', 'quotations'])
                ->where('status', 'quotation_sent')
                ->whereHas('quotations', fn ($q) => $q
                    ->where('status', 'sent')
                    ->whereDate('valid_until', '>=', now()->toDateString()))
                ->latest()
                ->paginate($perPage);

            return $this->success([
                'items' => collect($bulkOrders->items())->map(fn ($o) => UserApiFormatter::bulkOrder($o, detailed: true)),
                'pagination' => [
                    'current_page' => $bulkOrders->currentPage(),
                    'last_page' => $bulkOrders->lastPage(),
                    'total' => $bulkOrders->total(),
                ],
            ]);
        }

        if ($filter === 'all') {
            $productOrders = $request->user()->orders()
                ->with(['items.returns', 'vendor', 'payment'])
                ->latest()
                ->get()
                ->map(fn (Order $order) => array_merge(
                    UserApiFormatter::order($order, detailed: true),
                    ['type' => 'order', '_sort' => $order->created_at->timestamp]
                ));

            $bulkOrders = $request->user()->bulkOrders()
                ->with(['files', 'quotations', 'payment'])
                ->latest()
                ->get()
                ->map(fn (BulkOrder $bulkOrder) => array_merge(
                    UserApiFormatter::bulkOrder($bulkOrder, detailed: true),
                    ['_sort' => $bulkOrder->created_at->timestamp]
                ));

            $merged = $productOrders->concat($bulkOrders)
                ->sortByDesc('_sort')
                ->values()
                ->map(function (array $item) {
                    unset($item['_sort']);

                    return $item;
                });

            $paginator = new LengthAwarePaginator(
                $merged->forPage($page, $perPage)->values(),
                $merged->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return $this->success([
                'items' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        }

        $orders = $request->user()->orders()
            ->with(['items.returns', 'vendor', 'payment'])
            ->when($filter !== 'all', function ($q) use ($filter) {
                $map = [
                    'processing' => [OrderStatus::Pending, OrderStatus::Accepted, OrderStatus::Packed],
                    'out_for_delivery' => [OrderStatus::Shipped],
                    'delivered' => [OrderStatus::Delivered],
                    'cancelled' => [OrderStatus::Cancelled],
                ];
                if (isset($map[$filter])) {
                    $q->whereIn('status', array_map(fn ($s) => $s->value, $map[$filter]));
                }
            })
            ->latest()
            ->paginate($perPage);

        return $this->success([
            'items' => collect($orders->items())->map(fn ($o) => array_merge(
                UserApiFormatter::order($o, detailed: true),
                ['type' => 'order']
            )),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['items.returns', 'vendor', 'payment', 'statusLogs']);

        return $this->success(array_merge(
            UserApiFormatter::order($order, detailed: true),
            ['type' => 'order']
        ));
    }

    public function invoice(Request $request, Order $order, OrderInvoiceService $invoices): BinaryFileResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        $path = $invoices->generate($order);

        return response()->download(
            storage_path('app/public/'.$path),
            $order->order_number.'-invoice.pdf'
        );
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Accepted, OrderStatus::Packed])) {
            return $this->error('This order cannot be cancelled.', 422);
        }

        $data = $request->validate(['reason' => V::reasonRules()]);

        $order->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $data['reason'],
        ]);

        $order->load(['items', 'vendor.user', 'payment']);

        app(PushNotificationService::class)->orderStatusUpdated(
            $order,
            'cancelled',
            $data['reason'],
        );

        return $this->success(
            array_merge(UserApiFormatter::order($order, detailed: true), ['type' => 'order']),
            'Order cancelled.'
        );
    }
}
