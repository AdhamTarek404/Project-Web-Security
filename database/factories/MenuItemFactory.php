<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->randomElement([
                'Margherita Pizza', 'Cheeseburger', 'Caesar Salad',
                'Pad Thai', 'Chicken Shawarma', 'Spaghetti Bolognese',
            ]),
            'description' => fake()->sentence(),
            // 25.00 to 200.00 EGP — stored as cents (2500 to 20000).
            'base_price' => fake()->numberBetween(2500, 20000),
            'is_available' => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(['is_available' => false]);
    }
}
