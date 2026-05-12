<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderStateMachine;
use Illuminate\Http\Request;

// Thin browser-side endpoints that just delegate to the same OrderStateMachine
// the API uses. They redirect back to the dashboard with a flash message.
class WebOrderActionController extends Controller
{
    public function __construct(private OrderStateMachine $sm) {}

    public function ownerConfirm(Request $r, Order $order)        { return $this->run($r, $order, OrderStatus::Confirmed, 'restaurant'); }
    public function ownerStartPreparing(Request $r, Order $order) { return $this->run($r, $order, OrderStatus::Preparing, 'restaurant'); }
    public function ownerCancel(Request $r, Order $order)         { return $this->run($r, $order, OrderStatus::Cancelled, 'restaurant', 'Cancelled by restaurant'); }

    public function riderPickedUp(Request $r, Order $order)       { return $this->run($r, $order, OrderStatus::OnTheWay, 'rider'); }
    public function riderDelivered(Request $r, Order $order)      { return $this->run($r, $order, OrderStatus::Delivered, 'rider'); }

    public function customerCancel(Request $r, Order $order)      { return $this->run($r, $order, OrderStatus::Cancelled, 'customer', 'Cancelled by customer'); }

    private function run(Request $r, Order $order, OrderStatus $to, string $actor, ?string $reason = null)
    {
        $this->assertActorOwnsAction($r->user(), $order, $actor);

        try {
            $this->sm->transition($order, $to, $actor, $r->user()->id, $reason);
            return back()->with('status', "Order #{$order->id} → {$to->value}");
        } catch (InvalidOrderTransitionException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }
    }

    private function assertActorOwnsAction($user, Order $order, string $actor): void
    {
        $ok = match ($actor) {
            'restaurant' => $user->isRestaurantOwner() && $order->restaurant->owner_id === $user->id,
            'rider'      => $user->isRider() && $user->rider && $order->rider_id === $user->rider->id,
            'customer'   => $user->isCustomer() && $order->customer_id === $user->id,
            default      => false,
        };

        abort_unless($ok, 403);
    }
}
