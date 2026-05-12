<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // The four roles in the system.
    // Using class constants instead of "magic strings" everywhere so a typo
    // becomes a fatal error instead of a silent bug.
    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_RESTAURANT_OWNER = 'restaurant_owner';
    public const ROLE_RIDER = 'rider';
    public const ROLE_ADMIN = 'admin';

    public const ROLES = [
        self::ROLE_CUSTOMER,
        self::ROLE_RESTAURANT_OWNER,
        self::ROLE_RIDER,
        self::ROLE_ADMIN,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ---------- Role helpers ----------
    // These read better in code than `$user->role === 'rider'`
    // and they're the only place the string literal appears.

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(self::ROLE_CUSTOMER);
    }

    public function isRestaurantOwner(): bool
    {
        return $this->hasRole(self::ROLE_RESTAURANT_OWNER);
    }

    public function isRider(): bool
    {
        return $this->hasRole(self::ROLE_RIDER);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    // ---------- Relationships ----------

    // A user with role=restaurant_owner can own many restaurants.
    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'owner_id');
    }

    // A user with role=rider has exactly one Rider profile (1:1).
    public function rider(): HasOne
    {
        return $this->hasOne(Rider::class);
    }

    // A user with role=customer can place many orders.
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
}
