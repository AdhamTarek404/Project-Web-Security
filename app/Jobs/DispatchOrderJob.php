<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Dispatch\RiderDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

// Queued job that runs the dispatch algorithm. We push this onto the
// queue (Redis/DB) when an order moves to "preparing", so the HTTP
// request that confirmed the order returns instantly to the restaurant
// dashboard while dispatch runs in the background.
//
// Description: "Redis queues for order dispatch, surge pricing
// recalculation, and notifications."
class DispatchOrderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public readonly int $orderId) {}

    public function handle(RiderDispatcher $dispatcher): void
    {
        $order = Order::find($this->orderId);
        if (! $order || $order->rider_id !== null) {
            return; // already dispatched or deleted
        }

        $dispatcher->dispatch($order);
    }
}
