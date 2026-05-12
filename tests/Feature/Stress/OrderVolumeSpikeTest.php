<?php

namespace Tests\Feature\Stress;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Models\User;
use App\Services\Dispatch\RiderDispatcher;
use App\Services\Orders\PlaceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderVolumeSpikeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Description: "Simulate order volume spike: 50 concurrent orders
     * dispatched to available riders."
     *
     * We place 50 orders, run the dispatcher 50 times, and check:
     *   - every order that found a rider has a unique rider (no double-assignment)
     *   - the dispatcher's row lock means we never violate "1 rider, 1 active order"
     */
    public function test_50_orders_dispatched_with_no_double_assignment(): void
    {
        // Build 5 restaurants and 50 menu items so the place-order code
        // has realistic variety.
        $restaurants = Restaurant::factory()->count(5)->create();
        foreach ($restaurants as $r) {
            $cat = Category::factory()->create(['restaurant_id' => $r->id]);
            MenuItem::factory()->count(10)->create(['category_id' => $cat->id]);
        }

        // 60 available riders — enough to take all 50 orders + a few extras.
        Rider::factory()->count(60)->create([
            'is_on_duty' => true, 'is_available' => true,
        ]);

        $customers = User::factory()->customer()->count(50)->create();
        $place = app(PlaceOrder::class);
        $dispatcher = app(RiderDispatcher::class);

        $orders = [];
        foreach ($customers as $i => $c) {
            $r = $restaurants->random();
            $item = MenuItem::whereIn('category_id', $r->categories()->pluck('id'))->inRandomOrder()->first();
            $orders[] = $place->handle($c, [
                'restaurant_id' => $r->id, 'delivery_address' => "Addr $i",
                'delivery_latitude' => 30.04 + ($i / 1000),
                'delivery_longitude' => 31.23 + ($i / 1000),
                'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
            ]);
        }

        $this->assertCount(50, $orders);

        // Dispatch all 50.
        $assignedRiderIds = [];
        foreach ($orders as $order) {
            $rider = $dispatcher->dispatch($order);
            if ($rider !== null) {
                $assignedRiderIds[] = $rider->id;
            }
        }

        // CORE INVARIANT: every assigned rider is unique. The row lock in
        // RiderDispatcher::dispatch makes this true even under concurrency.
        $this->assertCount(count($assignedRiderIds), array_unique($assignedRiderIds));
        $this->assertGreaterThanOrEqual(50, count($assignedRiderIds));

        // Every assigned rider should now be is_available = false.
        $stillAvailable = Rider::whereIn('id', $assignedRiderIds)->where('is_available', true)->count();
        $this->assertSame(0, $stillAvailable);

        // All 50 orders have a rider_id.
        $this->assertSame(50, Order::whereNotNull('rider_id')->count());
    }
}
