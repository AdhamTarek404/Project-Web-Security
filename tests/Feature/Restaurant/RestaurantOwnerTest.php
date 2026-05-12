<?php

namespace Tests\Feature\Restaurant;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RestaurantOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_restaurant(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        Sanctum::actingAs($owner);

        $res = $this->postJson('/api/owner/restaurants', [
            'name' => "Ahmed's Diner",
            'address' => '12 Tahrir St',
            'latitude' => 30.04,
            'longitude' => 31.23,
        ]);

        $res->assertCreated();
        $this->assertDatabaseHas('restaurants', [
            'owner_id' => $owner->id,
            'name' => "Ahmed's Diner",
        ]);
    }

    public function test_owner_cannot_update_another_owners_restaurant(): void
    {
        $owner1 = User::factory()->restaurantOwner()->create();
        $owner2 = User::factory()->restaurantOwner()->create();
        $r = Restaurant::factory()->create(['owner_id' => $owner1->id]);

        Sanctum::actingAs($owner2);

        $this->patchJson("/api/owner/restaurants/{$r->id}", ['name' => 'Hijacked'])
            ->assertForbidden();
    }

    public function test_customer_cannot_reach_owner_endpoints(): void
    {
        $customer = User::factory()->customer()->create();
        Sanctum::actingAs($customer);

        $this->getJson('/api/owner/restaurants')->assertForbidden();
    }

    public function test_owner_can_create_category_and_item_then_toggle_availability(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $r = Restaurant::factory()->create(['owner_id' => $owner->id]);

        Sanctum::actingAs($owner);

        $cat = $this->postJson('/api/owner/categories', [
            'restaurant_id' => $r->id,
            'name' => 'Mains',
        ])->assertCreated()->json('data');

        $item = $this->postJson('/api/owner/menu-items', [
            'category_id' => $cat['id'],
            'name' => 'Margherita',
            'base_price' => 12500,
        ])->assertCreated()->json('data');

        $this->assertTrue($item['is_available']);

        // Toggle availability — the description's "availability toggles" feature.
        $toggled = $this->patchJson("/api/owner/menu-items/{$item['id']}/availability")
            ->assertOk()
            ->json();

        $this->assertFalse($toggled['is_available']);
        $this->assertDatabaseHas('menu_items', ['id' => $item['id'], 'is_available' => 0]);
    }

    public function test_owner_cannot_create_category_for_other_owner_restaurant(): void
    {
        $owner1 = User::factory()->restaurantOwner()->create();
        $owner2 = User::factory()->restaurantOwner()->create();
        $r = Restaurant::factory()->create(['owner_id' => $owner1->id]);

        Sanctum::actingAs($owner2);

        $this->postJson('/api/owner/categories', [
            'restaurant_id' => $r->id,
            'name' => 'Sneaky',
        ])->assertForbidden();
    }
}
