<?php

namespace Tests\Feature\Realtime;

use App\Enums\OrderStatus;
use App\Events\OrderStateChanged;
use App\Events\RiderLocationUpdated;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Models\User;
use App\Services\Orders\OrderStateMachine;
use App\Services\Orders\PlaceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BroadcastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_change_dispatches_broadcast_event(): void
    {
        Event::fake([OrderStateChanged::class]);

        $r = Restaurant::factory()->create();
        $c = Category::factory()->create(['restaurant_id' => $r->id]);
        $i = MenuItem::factory()->create(['category_id' => $c->id]);
        $customer = User::factory()->customer()->create();

        $order = app(PlaceOrder::class)->handle($customer, [
            'restaurant_id' => $r->id, 'delivery_address' => 'X',
            'delivery_latitude' => 30, 'delivery_longitude' => 31,
            'items' => [['menu_item_id' => $i->id, 'quantity' => 1]],
        ]);

        // initialize() does NOT fire OrderStateChanged (no "from" state).
        // The first transition does.
        app(OrderStateMachine::class)->transition($order, OrderStatus::Confirmed, 'system');

        Event::assertDispatched(OrderStateChanged::class);
    }

    public function test_rider_location_update_dispatches_broadcast(): void
    {
        Event::fake([RiderLocationUpdated::class]);

        Sanctum::actingAs(User::factory()->rider()->create());
        $this->postJson('/api/rider/location', ['latitude' => 30.0, 'longitude' => 31.0])
            ->assertOk();

        Event::assertDispatched(RiderLocationUpdated::class);
    }
}
