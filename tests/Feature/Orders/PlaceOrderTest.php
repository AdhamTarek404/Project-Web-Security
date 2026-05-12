<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Freeze time to 10:00 AM so the time-based surge strategy stays at 1.00x.
        // The lunch/dinner rush logic lives in TimeBasedSurgeStrategy.
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 10, 0, 0));
    }

    private function makeRestaurantWithItem(int $price = 12500): array
    {
        $r = Restaurant::factory()->create(['commission_rate' => 15.00]);
        $c = Category::factory()->create(['restaurant_id' => $r->id]);
        $i = MenuItem::factory()->create(['category_id' => $c->id, 'base_price' => $price]);
        return [$r, $i];
    }

    public function test_customer_can_place_order_with_correct_money_breakdown(): void
    {
        [$r, $item] = $this->makeRestaurantWithItem(12500); // 125 EGP

        $customer = User::factory()->customer()->create();
        Sanctum::actingAs($customer);

        $res = $this->postJson('/api/customer/orders', [
            'restaurant_id' => $r->id,
            'delivery_address' => '5 Tahrir',
            'delivery_latitude' => 30.045,
            'delivery_longitude' => 31.235,
            'items' => [
                ['menu_item_id' => $item->id, 'quantity' => 2],
            ],
        ])->assertCreated();

        $order = $res->json('data');

        $this->assertSame(25000, $order['subtotal']);                        // 2 × 12500
        $this->assertSame(5000, $order['delivery_fee']);                     // base
        $this->assertSame(3750, $order['platform_fee']);                     // 15% of 25000
        $this->assertSame(21250, $order['restaurant_payout']);               // 25000 - 3750
        $this->assertSame(5000, $order['rider_payout']);                     // surge=1.00
        $this->assertSame(30000, $order['total']);                           // 25000 + 5000
        $this->assertSame(OrderStatus::Placed->value, $order['status']);
    }

    public function test_prices_are_snapshotted_even_if_menu_changes(): void
    {
        [$r, $item] = $this->makeRestaurantWithItem(10000);
        $customer = User::factory()->customer()->create();
        Sanctum::actingAs($customer);

        $orderId = $this->postJson('/api/customer/orders', [
            'restaurant_id' => $r->id, 'delivery_address' => 'X',
            'delivery_latitude' => 30, 'delivery_longitude' => 31,
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->json('data.id');

        // Owner bumps the price — should NOT affect the placed order.
        $item->update(['base_price' => 99999]);

        $line = \App\Models\OrderItem::where('order_id', $orderId)->first();
        $this->assertSame(10000, $line->unit_price);
    }

    public function test_cannot_order_from_closed_restaurant(): void
    {
        $r = Restaurant::factory()->closed()->create();
        $c = Category::factory()->create(['restaurant_id' => $r->id]);
        $i = MenuItem::factory()->create(['category_id' => $c->id]);

        Sanctum::actingAs(User::factory()->customer()->create());

        $this->postJson('/api/customer/orders', [
            'restaurant_id' => $r->id, 'delivery_address' => 'X',
            'delivery_latitude' => 30, 'delivery_longitude' => 31,
            'items' => [['menu_item_id' => $i->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_cannot_order_unavailable_item(): void
    {
        $r = Restaurant::factory()->create();
        $c = Category::factory()->create(['restaurant_id' => $r->id]);
        $i = MenuItem::factory()->unavailable()->create(['category_id' => $c->id]);

        Sanctum::actingAs(User::factory()->customer()->create());

        $this->postJson('/api/customer/orders', [
            'restaurant_id' => $r->id, 'delivery_address' => 'X',
            'delivery_latitude' => 30, 'delivery_longitude' => 31,
            'items' => [['menu_item_id' => $i->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_customer_can_cancel_their_placed_order(): void
    {
        [$r, $item] = $this->makeRestaurantWithItem();
        $customer = User::factory()->customer()->create();
        Sanctum::actingAs($customer);

        $orderId = $this->postJson('/api/customer/orders', [
            'restaurant_id' => $r->id, 'delivery_address' => 'X',
            'delivery_latitude' => 30, 'delivery_longitude' => 31,
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->json('data.id');

        $this->postJson("/api/customer/orders/{$orderId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_owner_can_confirm_then_start_preparing(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $r = Restaurant::factory()->create(['owner_id' => $owner->id]);
        $c = Category::factory()->create(['restaurant_id' => $r->id]);
        $i = MenuItem::factory()->create(['category_id' => $c->id]);

        Sanctum::actingAs(User::factory()->customer()->create());
        $orderId = $this->postJson('/api/customer/orders', [
            'restaurant_id' => $r->id, 'delivery_address' => 'X',
            'delivery_latitude' => 30, 'delivery_longitude' => 31,
            'items' => [['menu_item_id' => $i->id, 'quantity' => 1]],
        ])->json('data.id');

        Sanctum::actingAs($owner);
        $this->postJson("/api/owner/orders/{$orderId}/confirm")
            ->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->postJson("/api/owner/orders/{$orderId}/start-preparing")
            ->assertOk()->assertJsonPath('data.status', 'preparing');
    }
}
