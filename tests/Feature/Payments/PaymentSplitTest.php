<?php

namespace Tests\Feature\Payments;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Orders\PlaceOrder;
use App\Services\Payments\PaymentSplitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSplitTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(float $commissionRate, int $itemPrice, int $qty, float $surge = 1.00)
    {
        $r = Restaurant::factory()->create(['commission_rate' => $commissionRate]);
        $c = Category::factory()->create(['restaurant_id' => $r->id]);
        $i = MenuItem::factory()->create(['category_id' => $c->id, 'base_price' => $itemPrice]);

        return app(PlaceOrder::class)->handle(
            User::factory()->customer()->create(),
            [
                'restaurant_id' => $r->id,
                'delivery_address' => 'X',
                'delivery_latitude' => 30, 'delivery_longitude' => 31,
                'items' => [['menu_item_id' => $i->id, 'quantity' => $qty]],
            ],
            $surge
        );
    }

    public function test_split_total_equals_order_total_at_10_percent(): void
    {
        $order = $this->placeOrder(10.00, 10000, 1);
        $split = app(PaymentSplitter::class)->splitFor($order);

        $this->assertSame($order->total, $split->total());
        $this->assertSame(1000, $split->platformAmount);   // 10% of 10000
        $this->assertSame(9000, $split->restaurantAmount); // 10000 - 1000
        $this->assertSame(5000, $split->riderAmount);      // delivery fee unmodified
    }

    public function test_split_total_equals_order_total_at_15_percent(): void
    {
        $order = $this->placeOrder(15.00, 20000, 2);
        $split = app(PaymentSplitter::class)->splitFor($order);

        $this->assertSame($order->total, $split->total());
        $this->assertSame(6000, $split->platformAmount);    // 15% of 40000
        $this->assertSame(34000, $split->restaurantAmount); // 40000 - 6000
        $this->assertSame(5000, $split->riderAmount);
    }

    public function test_split_total_equals_order_total_with_surge(): void
    {
        // Surge 2.0 → rider gets 2 × 5000 = 10000. Customer's total goes up too.
        $order = $this->placeOrder(15.00, 10000, 1, surge: 2.00);
        $split = app(PaymentSplitter::class)->splitFor($order);

        $this->assertSame($order->total, $split->total());
        $this->assertSame(10000, $split->riderAmount);
    }

    public function test_split_total_invariant_across_random_commission_rates(): void
    {
        // The description: "Payment split accuracy across varying commission rates per restaurant."
        foreach ([5.00, 7.50, 12.00, 15.00, 20.00, 25.00, 33.33] as $rate) {
            $order = $this->placeOrder($rate, 12345, 3);
            $split = app(PaymentSplitter::class)->splitFor($order);
            $this->assertSame(
                $order->total,
                $split->total(),
                "Split does not equal total at commission_rate={$rate}"
            );
        }
    }

    public function test_payment_intent_id_is_stored_on_order(): void
    {
        $order = $this->placeOrder(15.00, 10000, 1);
        $this->assertStringStartsWith('pi_fake_', $order->payment_intent_id);
    }
}
