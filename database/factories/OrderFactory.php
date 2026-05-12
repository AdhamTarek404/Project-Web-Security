<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => User::factory()->customer(),
            'restaurant_id' => Restaurant::factory(),
            'rider_id' => null,
            'status' => OrderStatus::Placed,
            'subtotal' => 10000,
            'delivery_fee' => 5000,
            'surge_multiplier' => 1.00,
            'platform_fee' => 1500,
            'restaurant_payout' => 8500,
            'rider_payout' => 5000,
            'total' => 15000,
            'delivery_address' => fake()->streetAddress(),
            'delivery_latitude' => fake()->randomFloat(7, 30.00, 30.10),
            'delivery_longitude' => fake()->randomFloat(7, 31.20, 31.30),
            'placed_at' => now(),
        ];
    }
}
