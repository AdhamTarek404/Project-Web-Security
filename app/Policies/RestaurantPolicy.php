<?php

namespace App\Policies;

use App\Models\Restaurant;
use App\Models\User;

// Centralizes ownership rules. Every category/menu_item/variant edit
// boils down to: "does the current user own the restaurant that this
// thing ultimately belongs to?"
//
// Admins bypass ownership — they can manage any restaurant.
class RestaurantPolicy
{
    // Global override: admins can do anything. Returning `true` short-circuits
    // the per-ability checks below. Returning `null` lets them proceed.
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Restaurant $restaurant): bool
    {
        return $user->id === $restaurant->owner_id;
    }

    public function update(User $user, Restaurant $restaurant): bool
    {
        return $user->id === $restaurant->owner_id;
    }

    public function manage(User $user, Restaurant $restaurant): bool
    {
        // "manage" covers categories, items, variants under this restaurant.
        return $user->id === $restaurant->owner_id;
    }
}
