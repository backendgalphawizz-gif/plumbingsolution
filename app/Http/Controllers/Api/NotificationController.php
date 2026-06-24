<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->userNotifications()
            ->with('notification')
            ->when($request->boolean('unread_only'), fn ($q) => $q->where('is_read', false))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success([
            'unread_count' => $request->user()->userNotifications()->where('is_read', false)->count(),
            'items' => collect($notifications->items())->map(fn ($n) => $this->formatItem($n)),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markRead(Request $request, UserNotification $userNotification): JsonResponse
    {
        if ($userNotification->user_id !== $request->user()->id) {
            return $this->error('Notification not found.', 404);
        }

        if (! $userNotification->is_read) {
            $userNotification->update(['is_read' => true, 'read_at' => now()]);
        }

        $userNotification->load('notification');

        return $this->success([
            'notification' => $this->formatItem($userNotification),
            'unread_count' => $request->user()->userNotifications()->where('is_read', false)->count(),
        ], 'Marked as read.');
    }

    private function formatItem(UserNotification $n): array
    {
        $data = $n->notification?->data ?? [];

        return [
            'id' => $n->id,
            'title' => $n->notification?->title,
            'message' => $n->notification?->message,
            'type' => $n->notification?->type?->value,
            'is_read' => $n->is_read,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at->diffForHumans(),
            'created_at_iso' => $n->created_at->toIso8601String(),
            'data' => $data,
            'order_id' => $data['order_id'] ?? null,
            'booking_id' => $data['booking_id'] ?? null,
            'return_id' => $data['return_id'] ?? null,
            'wallet_transaction_id' => $data['wallet_transaction_id'] ?? null,
            'type_id' => $data['type_id'] ?? null,
            'action' => $data['type'] ?? null,
        ];
    }
}
