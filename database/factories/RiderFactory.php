<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rider>
 */
class RiderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->rider(),
            'vehicle_type' => fake()->randomElement(['bike', 'scooter', 'car']),
            'license_plate' => fake()->bothify('???-####'),
            'current_latitude' => fake()->randomFloat(7, 30.00, 30.10),
            'current_longitude' => fake()->randomFloat(7, 31.20, 31.30),
            'last_location_at' => now(),
            'is_on_duty' => true,
            'is_available' => true,
        ];
    }
}
