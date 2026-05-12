<?php

namespace Tests\Feature\Restaurant;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_open_restaurants(): void
    {
        Restaurant::factory()->count(3)->create();
        Restaurant::factory()->closed()->count(2)->create();

        $res = $this->getJson('/api/restaurants')->assertOk();

        $this->assertCount(3, $res->json('data'));
    }

    public function test_menu_returns_categories_items_and_variants_for_open_restaurant(): void
    {
        $r = Restaurant::factory()->create();
        $cat = Category::factory()->create(['restaurant_id' => $r->id]);
        $item = MenuItem::factory()->create(['category_id' => $cat->id]);
        MenuItemVariant::factory()->create(['menu_item_id' => $item->id, 'name' => 'Large', 'price_modifier' => 2000]);

        $res = $this->getJson("/api/restaurants/{$r->slug}/menu")->assertOk();

        $this->assertSame($r->slug, $res->json('data.slug'));
        $this->assertCount(1, $res->json('data.categories'));
        $this->assertCount(1, $res->json('data.categories.0.menu_items'));
        $this->assertCount(1, $res->json('data.categories.0.menu_items.0.variants'));
    }

    public function test_menu_hides_unavailable_items(): void
    {
        $r = Restaurant::factory()->create();
        $cat = Category::factory()->create(['restaurant_id' => $r->id]);
        MenuItem::factory()->create(['category_id' => $cat->id, 'is_available' => true]);
        MenuItem::factory()->unavailable()->create(['category_id' => $cat->id]);

        $res = $this->getJson("/api/restaurants/{$r->slug}/menu")->assertOk();

        $this->assertCount(1, $res->json('data.categories.0.menu_items'));
    }

    public function test_closed_restaurant_menu_returns_404(): void
    {
        $r = Restaurant::factory()->closed()->create();

        $this->getJson("/api/restaurants/{$r->slug}/menu")->assertNotFound();
    }
}
