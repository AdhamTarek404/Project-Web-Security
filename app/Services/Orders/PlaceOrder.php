<?php

namespace App\Services\Orders;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Models\User;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentSplitter;
use App\Services\Pricing\SurgeContext;
use App\Services\Pricing\SurgePricingEngine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

// Single-action service that turns a validated cart into a saved Order.
// All money math goes through PriceCalculator. All status writes go
// through OrderStateMachine. Wrapped in a transaction so we never end up
// with a half-placed order.
class PlaceOrder
{
    public function __construct(
        private readonly PriceCalculator $prices,
        private readonly OrderStateMachine $stateMachine,
        private readonly SurgePricingEngine $surge,
        private readonly PaymentSplitter $splitter,
        private readonly PaymentGateway $gateway,
    ) {}

    /**
     * @param  array  $payload  Validated PlaceOrderRequest data.
     * @param  float|null  $surgeMultiplier  Force a specific multiplier
     *         (useful in tests). When null we ask the SurgePricingEngine
     *         to compute it from live demand/weather/time signals.
     */
    public function handle(User $customer, array $payload, ?float $surgeMultiplier = null): Order
    {
        if ($surgeMultiplier === null) {
            $surgeMultiplier = $this->surge->compute(new SurgeContext(
                activeOrdersCount: Order::whereNotIn('status', ['delivered', 'cancelled'])->count(),
                availableRiderCount: Rider::where('is_available', true)->where('is_on_duty', true)->count(),
            ));
        }

        $restaurant = Restaurant::findOrFail($payload['restaurant_id']);

        if (! $restaurant->is_open) {
            throw new InvalidArgumentException('Restaurant is closed.');
        }

        // Re-fetch the menu items + variants from the DB. NEVER trust prices
        // sent from the client — they'd happily pay 1 cent for a 100 EGP pizza.
        $menuItemIds = collect($payload['items'])->pluck('menu_item_id')->unique();
        $menuItems = MenuItem::with(['variants', 'category'])
            ->whereIn('id', $menuItemIds)
            ->get()
            ->keyBy('id');

        // Every item must (a) exist, (b) be available, (c) belong to this restaurant.
        foreach ($menuItemIds as $id) {
            $item = $menuItems->get($id);
            if (! $item || ! $item->is_available || $item->category->restaurant_id !== $restaurant->id) {
                throw new InvalidArgumentException("Menu item #{$id} is not available at this restaurant.");
            }
        }

        return DB::transaction(function () use ($customer, $payload, $restaurant, $menuItems, $surgeMultiplier) {
            $subtotal = 0;
            $lines = [];

            foreach ($payload['items'] as $line) {
                /** @var MenuItem $item */
                $item = $menuItems[$line['menu_item_id']];
                $unitPrice = $item->priceForVariant($line['variant_id'] ?? null);
                $lineTotal = $unitPrice * $line['quantity'];
                $subtotal += $lineTotal;

                $lines[] = [
                    'menu_item_id' => $item->id,
                    'variant_id' => $line['variant_id'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $unitPrice,      // snapshot — see Phase 1 design rule
                    'line_total' => $lineTotal,
                    'special_instructions' => $line['special_instructions'] ?? null,
                ];
            }

            $totals = $this->prices->compute($subtotal, $surgeMultiplier, $restaurant);

            $order = Order::create(array_merge($totals, [
                'customer_id' => $customer->id,
                'restaurant_id' => $restaurant->id,
                'delivery_address' => $payload['delivery_address'],
                'delivery_latitude' => $payload['delivery_latitude'],
                'delivery_longitude' => $payload['delivery_longitude'],
            ]));

            // Bulk insert all order items.
            $order->items()->createMany($lines);

            // === Phase 8: Stripe Connect split payment ===
            // The customer is charged once; the proceeds split between the
            // three Stripe Connect parties. The gateway returns a payment
            // intent id we store on the order for refunds / reconciliation.
            $split = $this->splitter->splitFor($order);
            $order->payment_intent_id = $this->gateway->chargeAndSplit($order, $split);
            $order->save();

            // First state-machine event — appends the "birth" row to history.
            $this->stateMachine->initialize($order, actorType: 'customer', actorId: $customer->id);

            return $order->fresh(['items']);
        });
    }
}
