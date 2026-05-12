<?php

namespace Tests\Feature\Ratings;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Models\User;
use App\Services\Orders\OrderStateMachine;
use App\Services\Orders\PlaceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0:User,1:Order,2:Rider} */
    private function placeAndDeliver(): array
    {
        $r = Restaurant::factory()->create();
        $c = Category::factory()->create(['restaurant_id' => $r->id]);
        $item = MenuItem::factory()->create(['category_id' => $c->id]);
        $customer = User::factory()->customer()->create();
        $rider = Rider::factory()->create();

        $order = app(PlaceOrder::class)->handle($customer, [
            'restaurant_id' => $r->id, 'delivery_address' => 'X',
            'delivery_latitude' => 30, 'delivery_longitude' => 31,
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ]);

        $sm = app(OrderStateMachine::class);
        $sm->transition($order, OrderStatus::Confirmed, 'system');
        $sm->transition($order, OrderStatus::Preparing, 'system');
        $order->rider_id = $rider->id;
        $order->save();
        $sm->transition($order, OrderStatus::OnTheWay, 'system');
        $sm->transition($order, OrderStatus::Delivered, 'system');

        return [$customer, $order->fresh(), $rider];
    }

    public function test_customer_can_rate_restaurant_after_delivery(): void
    {
        [$customer, $order] = $this->placeAndDeliver();
        Sanctum::actingAs($customer);

        $this->postJson("/api/customer/orders/{$order->id}/rate", [
            'target' => 'restaurant', 'stars' => 5, 'comment' => 'Excellent',
        ])->assertCreated()->assertJsonPath('data.stars', 5);

        $this->assertSame(5, (int) $order->restaurant->ratings()->avg('stars'));
    }

    public function test_customer_can_rate_rider_after_delivery(): void
    {
        [$customer, $order, $rider] = $this->placeAndDeliver();
        Sanctum::actingAs($customer);

        $this->postJson("/api/customer/orders/{$order->id}/rate", [
            'target' => 'rider', 'stars' => 4,
        ])->assertCreated();

        $this->assertSame(4, (int) $rider->ratings()->avg('stars'));
    }

    public function test_cannot_rate_before_delivery(): void
    {
        $r = Restaurant::factory()->create();
        $c = Category::factory()->create(['restaurant_id' => $r->id]);
        $i = MenuItem::factory()->create(['category_id' => $c->id]);
        $customer = User::factory()->customer()->create();

        $order = app(PlaceOrder::class)->handle($customer, [
            'restaurant_id' => $r->id, 'delivery_address' => 'X',
            'delivery_latitude' => 30, 'delivery_longitude' => 31,
            'items' => [['menu_item_id' => $i->id, 'quantity' => 1]],
        ]);

        Sanctum::actingAs($customer);
        $this->postJson("/api/customer/orders/{$order->id}/rate", [
            'target' => 'restaurant', 'stars' => 5,
        ])->assertStatus(422);
    }

    public function test_rating_is_unique_per_order_and_target(): void
    {
        [$customer, $order] = $this->placeAndDeliver();
        Sanctum::actingAs($customer);

        $this->postJson("/api/customer/orders/{$order->id}/rate", ['target' => 'restaurant', 'stars' => 5])->assertCreated();
        $this->postJson("/api/customer/orders/{$order->id}/rate", ['target' => 'restaurant', 'stars' => 1])->assertCreated();

        // updateOrCreate behavior — there should be exactly ONE row, with the latest stars.
        $this->assertSame(1, $order->restaurant->ratings()->count());
        $this->assertSame(1, $order->restaurant->ratings()->first()->stars);
    }
}
