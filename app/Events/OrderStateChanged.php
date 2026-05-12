<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Fired by OrderStateMachine after every successful transition.
// Reverb broadcasts this over a WebSocket so:
//   - the customer app updates the order status screen instantly
//   - the restaurant dashboard sees new orders appear
//   - the admin control tower (Livewire) refreshes its live map
class OrderStateChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $from,
        public readonly OrderStatus $to,
        public readonly string $actorType,
        public readonly ?int $actorId,
    ) {}

    /**
     * Two channels:
     *   - admin.orders        — the live admin map listens here
     *   - orders.{order_id}   — the specific customer/restaurant/rider listens here
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('admin.orders'),
            new Channel('orders.'.$this->order->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'from' => $this->from->value,
            'to' => $this->to->value,
            'restaurant_id' => $this->order->restaurant_id,
            'rider_id' => $this->order->rider_id,
        ];
    }
}
