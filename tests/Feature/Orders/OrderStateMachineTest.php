<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Orders\OrderStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private OrderStateMachine $sm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sm = new OrderStateMachine();
    }

    /**
     * Make a freshly-placed order with the minimum required fields.
     * The Order model has many `unsignedInteger` columns we have to satisfy.
     */
    private function makeOrder(): Order
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $owner = User::factory()->create(['role' => User::ROLE_RESTAURANT_OWNER]);

        $restaurant = Restaurant::create([
            'owner_id' => $owner->id,
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant-'.uniqid(),
            'address' => '1 Test St',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'commission_rate' => 15.00,
            'is_open' => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => OrderStatus::Placed,
            'subtotal' => 10000,
            'delivery_fee' => 1500,
            'surge_multiplier' => 1.00,
            'platform_fee' => 1500,
            'restaurant_payout' => 8500,
            'rider_payout' => 1500,
            'total' => 11500,
            'delivery_address' => '5 Customer St',
            'delivery_latitude' => 30.0500,
            'delivery_longitude' => 31.2400,
        ]);

        $this->sm->initialize($order, actorType: 'customer', actorId: $customer->id);

        return $order->fresh();
    }

    public function test_happy_path_placed_to_delivered_succeeds(): void
    {
        $order = $this->makeOrder();

        $this->sm->transition($order, OrderStatus::Confirmed, 'restaurant', 1);
        $this->sm->transition($order, OrderStatus::Preparing, 'restaurant', 1);
        $this->sm->transition($order, OrderStatus::OnTheWay, 'rider', 1);
        $this->sm->transition($order, OrderStatus::Delivered, 'rider', 1);

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);

        // Every state transition must have stamped its `*_at` column.
        $fresh = $order->fresh();
        $this->assertNotNull($fresh->placed_at);
        $this->assertNotNull($fresh->confirmed_at);
        $this->assertNotNull($fresh->preparing_at);
        $this->assertNotNull($fresh->on_the_way_at);
        $this->assertNotNull($fresh->delivered_at);

        // 5 history rows: 1 for initialize + 4 transitions.
        $this->assertSame(5, $fresh->statusHistory()->count());
    }

    public function test_invalid_transition_throws(): void
    {
        $order = $this->makeOrder();

        // Description: "rejected transitions (e.g., delivered → preparing) must throw"
        $this->sm->transition($order, OrderStatus::Confirmed, 'restaurant', 1);
        $this->sm->transition($order, OrderStatus::Preparing, 'restaurant', 1);
        $this->sm->transition($order, OrderStatus::OnTheWay, 'rider', 1);
        $this->sm->transition($order, OrderStatus::Delivered, 'rider', 1);

        $this->expectException(InvalidOrderTransitionException::class);
        $this->sm->transition($order, OrderStatus::Preparing, 'admin', 1);
    }

    public function test_cancel_is_allowed_from_any_non_terminal_state(): void
    {
        foreach ([OrderStatus::Placed, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::OnTheWay] as $atState) {
            $order = $this->makeOrder();

            // Advance the order to the state under test.
            $path = match ($atState) {
                OrderStatus::Placed => [],
                OrderStatus::Confirmed => [OrderStatus::Confirmed],
                OrderStatus::Preparing => [OrderStatus::Confirmed, OrderStatus::Preparing],
                OrderStatus::OnTheWay => [OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::OnTheWay],
            };
            foreach ($path as $step) {
                $this->sm->transition($order, $step, 'system');
            }

            // Cancel — must succeed.
            $this->sm->transition($order, OrderStatus::Cancelled, 'admin', 1, 'Testing');

            $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        }
    }

    public function test_cannot_transition_out_of_terminal_state(): void
    {
        $order = $this->makeOrder();
        $this->sm->transition($order, OrderStatus::Cancelled, 'admin', 1, 'closed');

        $this->expectException(InvalidOrderTransitionException::class);
        $this->sm->transition($order, OrderStatus::Confirmed, 'admin', 1);
    }

    public function test_history_records_actor_and_from_to(): void
    {
        $order = $this->makeOrder();
        $this->sm->transition($order, OrderStatus::Confirmed, 'restaurant', 42, 'auto-accepted');

        $latest = $order->fresh()->statusHistory()->latest('id')->first();

        $this->assertSame('placed', $latest->from_status);
        $this->assertSame('confirmed', $latest->to_status);
        $this->assertSame('restaurant', $latest->actor_type);
        $this->assertSame(42, $latest->actor_id);
        $this->assertSame('auto-accepted', $latest->reason);
    }
}
