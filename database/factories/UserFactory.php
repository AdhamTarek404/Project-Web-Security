<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => \App\Models\User::ROLE_CUSTOMER,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    // Role-specific shortcut states so tests read clearly:
    //   User::factory()->restaurantOwner()->create()
    public function customer(): static
    {
        return $this->state(['role' => \App\Models\User::ROLE_CUSTOMER]);
    }

    public function restaurantOwner(): static
    {
        return $this->state(['role' => \App\Models\User::ROLE_RESTAURANT_OWNER]);
    }

    public function rider(): static
    {
        return $this->state(['role' => \App\Models\User::ROLE_RIDER]);
    }

    public function admin(): static
    {
        return $this->state(['role' => \App\Models\User::ROLE_ADMIN]);
    }
}
