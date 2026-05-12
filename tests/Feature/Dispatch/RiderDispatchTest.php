<?php

namespace Tests\Feature\Dispatch;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Models\User;
use App\Services\Dispatch\RiderDispatcher;
use App\Services\Geo\HaversineDistanceCalculator;
use App\Services\Orders\PlaceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiderDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_haversine_distance_is_reasonable(): void
    {
        $h = new HaversineDistanceCalculator();
        // Cairo Tower (30.0459, 31.2243) → Pyramids of Giza (29.9792, 31.1342)
        // Real great-circle distance ≈ 11.5 km.
        $km = $h->kilometers(30.0459, 31.2243, 29.9792, 31.1342);
        $this->assertGreaterThan(10.5, $km);
        $this->assertLessThan(12.5, $km);
    }

    public function test_dispatcher_picks_nearest_available_rider(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $r = Restaurant::factory()->create([
            'owner_id' => $owner->id, 'latitude' => 30.0000, 'longitude' => 31.0000,
        ]);
        $c = Category::factory()->create(['restaurant_id' => $r->id]);
        $item = MenuItem::factory()->create(['category_id' => $c->id]);

        // Three available riders at varying distances from the restaurant.
        $far = Rider::factory()->create([
            'user_id' => User::factory()->rider()->create()->id,
            'is_on_duty' => true, 'is_available' => true,
            'current_latitude' => 30.10, 'current_longitude' => 31.10, // ~14 km
        ]);
        $near = Rider::factory()->create([
            'user_id' => User::factory()->rider()->create()->id,
            'is_on_duty' => true, 'is_available' => true,
            'current_latitude' => 30.001, 'current_longitude' => 31.001, // ~0.15 km
        ]);
        $mid = Rider::factory()->create([
            'user_id' => User::factory()->rider()->create()->id,
            'is_on_duty' => true, 'is_available' => true,
            'current_latitude' => 30.02, 'current_longitude' => 31.02, // ~3 km
        ]);

        $customer = User::factory()->customer()->create();
        $place = app(PlaceOrder::class);
        $order = $place->handle($customer, [
            'restaurant_id' => $r->id, 'delivery_address' => 'X',
            'delivery_latitude' => 30, 'delivery_longitude' => 31,
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ]);

        $picked = app(RiderDispatcher::class)->dispatch($order);

        $this->assertNotNull($picked);
        $this->assertSame($near->id, $picked->id);
        $this->assertFalse($picked->fresh()->is_available);
    }

    public function test_dispatcher_returns_null_when_no_riders(): void
    {
        $r = Restaurant::factory()->create();
        $order = Order::factory()->create(['restaurant_id' => $r->id]);

        $this->assertNull(app(RiderDispatcher::class)->dispatch($order));
    }

    public function test_preparing_transition_queues_dispatch_job(): void
    {
        Queue::fake();

        $owner = User::factory()->restaurantOwner()->create();
        $r = Restaurant::factory()->create(['owner_id' => $owner->id]);
        $c = Category::factory()->create(['restaurant_id' => $r->id]);
        $item = MenuItem::factory()->create(['category_id' => $c->id]);

        Sanctum::actingAs(User::factory()->customer()->create());
        $orderId = $this->postJson('/api/customer/orders', [
            'restaurant_id' => $r->id, 'delivery_address' => 'X',
            'delivery_latitude' => 30, 'delivery_longitude' => 31,
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->json('data.id');

        Sanctum::actingAs($owner);
        $this->postJson("/api/owner/orders/{$orderId}/confirm")->assertOk();
        $this->postJson("/api/owner/orders/{$orderId}/start-preparing")->assertOk();

        Queue::assertPushed(\App\Jobs\DispatchOrderJob::class);
    }

    public function test_rider_can_update_location(): void
    {
        $u = User::factory()->rider()->create();
        Sanctum::actingAs($u);

        $this->postJson('/api/rider/location', ['latitude' => 30.05, 'longitude' => 31.25])
            ->assertOk()
            ->assertJsonPath('data.current_latitude', '30.0500000');
    }
}
