<?php

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItemVariant>
 */
class MenuItemVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_item_id' => MenuItem::factory(),
            'name' => fake()->randomElement(['Small', 'Medium', 'Large']),
            'price_modifier' => fake()->randomElement([0, 1000, 2500, 5000]),
            'is_default' => false,
        ];
    }
}
