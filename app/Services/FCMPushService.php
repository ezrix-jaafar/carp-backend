<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Device;
use Illuminate\Support\Facades\Http;

class FCMPushService
{
    /**
     * Send a push notification to the assigned agent when a new order is assigned.
     */
    public static function sendOrderAssigned(Order $order): void
    {
        if (!$order->agent || !$order->agent->user) {
            return;
        }

        $devices = Device::where('user_id', $order->agent->user->id)->pluck('token');
        if ($devices->isEmpty()) {
            return;
        }

        $serverKey = config('services.fcm.server_key');
        if (!$serverKey) {
            return; // FCM not configured
        }

        $payload = [
            'registration_ids' => $devices->values()->all(),
            'notification' => [
                'title' => 'New Order Assigned',
                'body' => 'Order #' . $order->reference_number . ' has been assigned to you.',
                'sound' => 'default',
            ],
            'data' => [
                'order_id' => $order->id,
                'type' => 'order_assigned',
            ],
        ];

        Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $payload)->throw();
    }
}
