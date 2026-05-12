<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Background job that sends transactional notifications to the customer,
 * restaurant and rider when an order changes state.
 *
 * Description: "Redis queues for order dispatch, surge pricing
 * recalculation, and notifications."
 *
 * The actual sending channel is left pluggable — production would inject
 * an SMS provider (Twilio), an email mailer, or a push-notification
 * service (FCM/APNs). For the demo we log the notification payload so
 * the test suite and dev environment don't need real credentials.
 */
class SendOrderNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 15;

    public function __construct(
        public readonly int $orderId,
        public readonly string $event, // e.g. 'placed', 'confirmed', 'on_the_way', 'delivered'
    ) {}

    public function handle(): void
    {
        $order = Order::with(['customer', 'restaurant', 'rider.user'])->find($this->orderId);

        if (! $order) {
            return; // order was hard-deleted between enqueue and run
        }

        // Compose the recipients + messages, exactly as a real notifier
        // would, then hand them off to the logger (or any configured
        // channel — Twilio/Mail/FCM all implement the same interface).
        $recipients = [
            'customer'   => [
                'name'    => $order->customer?->name,
                'phone'   => $order->customer?->phone,
                'message' => match ($this->event) {
                    'placed'      => "Order #{$order->id} placed at {$order->restaurant->name}.",
                    'confirmed'   => "Restaurant accepted order #{$order->id}.",
                    'preparing'   => "Your food is being prepared.",
                    'on_the_way'  => "Order #{$order->id} is on the way!",
                    'delivered'   => "Order #{$order->id} delivered. Enjoy!",
                    'cancelled'   => "Order #{$order->id} was cancelled.",
                    default       => "Order #{$order->id} update: {$this->event}.",
                },
            ],
            'restaurant' => [
                'name'    => $order->restaurant?->name,
                'message' => match ($this->event) {
                    'placed'    => "New order #{$order->id} — please confirm.",
                    'cancelled' => "Order #{$order->id} cancelled.",
                    default     => null,
                },
            ],
            'rider'      => [
                'name'    => $order->rider?->user?->name,
                'phone'   => $order->rider?->user?->phone,
                'message' => match ($this->event) {
                    'confirmed'  => "Pickup waiting at {$order->restaurant?->name}.",
                    'delivered'  => "Payout for order #{$order->id} queued.",
                    default      => null,
                },
            ],
        ];

        Log::info('Notification job fired', [
            'order_id'    => $order->id,
            'event'       => $this->event,
            'recipients'  => $recipients,
        ]);
    }
}
