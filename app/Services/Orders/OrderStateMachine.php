<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderStateChanged;
use App\Exceptions\InvalidOrderTransitionException;
use App\Jobs\DispatchOrderJob;
use App\Jobs\RecalculateSurgePricingJob;
use App\Jobs\SendOrderNotificationJob;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;

// The finite state machine described in the project brief:
//   "Finite state machine for order transitions with guards preventing
//    invalid jumps. Event-sourcing-inspired order history: every state
//    change logged with timestamp and actor."
//
// This is the ONLY place orders.status is allowed to change. Every
// controller, job, or Livewire component that needs to advance an order
// calls $stateMachine->transition(...).
class OrderStateMachine
{
    /**
     * Stamp the initial state on a freshly placed order.
     *
     * This is called by the place-order flow in Phase 5 right after the
     * Order row is created. It's a separate method (not transition()) because
     * there's no "from" state — and a true append-only audit log records the
     * birth of the order as its first event.
     */
    public function initialize(Order $order, string $actorType, ?int $actorId, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $actorType, $actorId, $reason) {
            $now = now();

            $order->status = OrderStatus::Placed;
            $order->placed_at = $now;
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,           // birth event — no previous state
                'to_status' => OrderStatus::Placed->value,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'reason' => $reason,
                'occurred_at' => $now,
                'created_at' => $now,
            ]);

            // Push the description's two other queue jobs:
            //   "Redis queues for order dispatch, surge pricing
            //    recalculation, and notifications."
            SendOrderNotificationJob::dispatch($order->id, 'placed');
            RecalculateSurgePricingJob::dispatch();
        });
    }

    /**
     * Attempt to transition an order to a new state.
     *
     * @throws InvalidOrderTransitionException when the move is not allowed by the FSM.
     */
    public function transition(
        Order $order,
        OrderStatus $to,
        string $actorType,
        ?int $actorId = null,
        ?string $reason = null,
    ): Order {
        // $order->status is already an OrderStatus enum thanks to the
        // Eloquent cast on the Order model.
        $from = $order->status;

        // === GUARD ===
        // Reject the move if the FSM rules don't permit it. This is the
        // "rejected transitions (e.g., delivered → preparing) must throw"
        // requirement from the description.
        if (! $from->canTransitionTo($to)) {
            throw new InvalidOrderTransitionException($from, $to);
        }

        // Everything below runs in ONE DB transaction so we never get
        // a half-applied transition (status updated but history not logged,
        // or vice versa).
        return DB::transaction(function () use ($order, $from, $to, $actorType, $actorId, $reason) {
            $now = now();

            $order->status = $to;

            // Stamp the matching `*_at` column on the orders table —
            // these are the fast-access duplicates for reporting queries.
            $tsColumn = $to->timestampColumn();
            if ($tsColumn !== null) {
                $order->{$tsColumn} = $now;
            }

            if ($to === OrderStatus::Cancelled && $reason !== null) {
                $order->cancellation_reason = $reason;
            }

            $order->save();

            // Append (NEVER update) the audit row.
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'reason' => $reason,
                'occurred_at' => $now,
                'created_at' => $now,
            ]);

            // Fire the event. In Phase 10 this becomes a broadcasted
            // event so the customer / restaurant / admin UIs all
            // refresh in real time via Reverb.
            OrderStateChanged::dispatch($order, $from, $to, $actorType, $actorId);

            // Phase 6: when an order enters "preparing" we push the
            // dispatch job onto the queue (description: "Redis queues
            // for order dispatch"). The HTTP request returns instantly;
            // the dispatcher picks the nearest rider in the background.
            if ($to === OrderStatus::Preparing && $order->rider_id === null) {
                DispatchOrderJob::dispatch($order->id);
            }

            // Description: "Redis queues for ... notifications."
            // Every state transition fans out a notification (customer,
            // restaurant, rider — whoever is relevant for the new state).
            SendOrderNotificationJob::dispatch($order->id, $to->value);

            // When an order leaves "active" status, the supply/demand ratio
            // shifts → recompute the surge multiplier in the background so
            // the next customer sees an up-to-date price. Description:
            // "Redis queues for ... surge pricing recalculation ..."
            if (in_array($to, [OrderStatus::Delivered, OrderStatus::Cancelled], true)) {
                RecalculateSurgePricingJob::dispatch();
            }

            return $order;
        });
    }
}
