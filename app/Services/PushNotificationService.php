<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\Order;
use App\Models\ServiceBooking;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function __construct(private FcmService $fcm) {}

    public function sendToUser(
        User $user,
        string $title,
        string $message,
        NotificationType $type,
        array $data = [],
        ?Model $notifiable = null,
    ): ?UserNotification {
        $notification = AppNotification::create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'notifiable_type' => $notifiable ? $notifiable->getMorphClass() : null,
            'notifiable_id' => $notifiable?->getKey(),
            'data' => $data,
            'sent_at' => now(),
        ]);

        $userNotification = UserNotification::create([
            'notification_id' => $notification->id,
            'user_id' => $user->id,
        ]);

        $this->pushFcm($user, $title, $message, $data);

        return $userNotification;
    }

    public function sendToUsers(iterable $users, string $title, string $message, NotificationType $type, array $data = [], ?Model $notifiable = null): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                $this->sendToUser($user, $title, $message, $type, $data, $notifiable);
            }
        }
    }

    public function orderPlaced(Order $order): void
    {
        $order->loadMissing(['user', 'vendor.user']);

        if ($order->user) {
            $this->sendToUser(
                $order->user,
                'Order Placed',
                "Your order {$order->order_number} has been placed successfully.",
                NotificationType::Order,
                $this->orderData($order, 'order_placed'),
                $order,
            );
        }

        if ($order->vendor?->user) {
            $this->sendToUser(
                $order->vendor->user,
                'New Order Received',
                "You have a new order {$order->order_number}.",
                NotificationType::Order,
                $this->orderData($order, 'new_order'),
                $order,
            );
        }
    }

    public function orderStatusUpdated(Order $order, string $statusLabel, ?string $notes = null): void
    {
        $order->loadMissing(['user', 'vendor.user']);
        $message = "Order {$order->order_number} is now {$statusLabel}.";
        if ($notes) {
            $message .= ' '.$notes;
        }

        $data = $this->orderData($order, 'order_status_updated');

        if ($order->user) {
            $this->sendToUser(
                $order->user,
                'Order Status Updated',
                $message,
                NotificationType::Order,
                $data,
                $order,
            );
        }

        if ($order->vendor?->user && in_array($order->status->value ?? $order->status, ['cancelled'], true)) {
            $this->sendToUser(
                $order->vendor->user,
                'Order Cancelled',
                $message,
                NotificationType::Order,
                $data,
                $order,
            );
        }
    }

    public function bookingCreated(ServiceBooking $booking): void
    {
        $booking->loadMissing(['user', 'serviceProvider.user']);

        if ($booking->user) {
            $this->sendToUser(
                $booking->user,
                'Booking Confirmed',
                "Your booking {$booking->booking_number} has been scheduled.",
                NotificationType::Booking,
                $this->bookingData($booking, 'booking_created'),
                $booking,
            );
        }

        if ($booking->serviceProvider?->user) {
            $this->sendToUser(
                $booking->serviceProvider->user,
                'New Booking Assigned',
                "You have a new booking {$booking->booking_number} for {$booking->service_name}.",
                NotificationType::Booking,
                $this->bookingData($booking, 'new_booking'),
                $booking,
            );
        }
    }

    public function bookingStatusUpdated(ServiceBooking $booking, string $title, string $message, string $action): void
    {
        $booking->loadMissing(['user', 'serviceProvider.user']);
        $data = $this->bookingData($booking, $action);

        if ($booking->user) {
            $this->sendToUser($booking->user, $title, $message, NotificationType::Booking, $data, $booking);
        }

        $providerActions = ['booking_cancelled', 'booking_assigned'];

        if ($booking->serviceProvider?->user && in_array($action, $providerActions, true)) {
            $this->sendToUser(
                $booking->serviceProvider->user,
                $title,
                $message,
                NotificationType::Booking,
                $data,
                $booking,
            );
        }
    }

    private function orderData(Order $order, string $action): array
    {
        return [
            'type' => $action,
            'order_id' => (string) $order->id,
            'type_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status->value ?? (string) $order->status,
            'chat' => '',
        ];
    }

    private function bookingData(ServiceBooking $booking, string $action): array
    {
        return [
            'type' => $action,
            'booking_id' => (string) $booking->id,
            'type_id' => (string) $booking->id,
            'booking_number' => $booking->booking_number,
            'status' => $booking->status->value ?? (string) $booking->status,
            'order_id' => '',
            'chat' => '',
        ];
    }

    private function pushFcm(User $user, string $title, string $body, array $data): void
    {
        if (! (bool) Setting::getValue('notification', 'push_enabled', true)) {
            return;
        }

        if (! $user->fcm_token) {
            return;
        }

        try {
            $this->fcm->sendToToken($user->fcm_token, $title, $body, $data);
        } catch (\Throwable $e) {
            Log::warning('FCM push exception', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
