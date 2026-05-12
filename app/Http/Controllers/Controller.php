<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

// Re-enable Laravel's `$this->authorize($ability, $model)` helper inside
// controllers — it was stripped from the new Laravel 11/12 skeleton.
// Phase 4: needed so policies (e.g. RestaurantPolicy) can be invoked.
abstract class Controller
{
    use AuthorizesRequests;
}
