<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company().' Kitchen';

        return [
            'owner_id' => User::factory()->restaurantOwner(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'address' => fake()->streetAddress().', Cairo',
            // Random point around central Cairo so the Haversine logic
            // in Phase 6 has realistic distances to chew on.
            'latitude' => fake()->randomFloat(7, 30.00, 30.10),
            'longitude' => fake()->randomFloat(7, 31.20, 31.30),
            'commission_rate' => 15.00,
            'is_open' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state(['is_open' => false]);
    }
}
