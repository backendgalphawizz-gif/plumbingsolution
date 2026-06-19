<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $notifications = AppNotification::with('sender:id,name')
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($notifications);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'type' => ['required', 'in:order,booking,promotion,system'],
            'data' => ['nullable', 'array'],
        ]);

        $notification = AppNotification::create([
            ...$data,
            'sent_at' => now(),
            'sent_by' => $request->user()->id,
        ]);

        return $this->success($notification, 'Notification sent.', 201);
    }

    public function destroy(AppNotification $notification): JsonResponse
    {
        $notification->delete();

        return $this->success(null, 'Notification deleted.');
    }
}
