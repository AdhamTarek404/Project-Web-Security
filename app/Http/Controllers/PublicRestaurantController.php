<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Read-only endpoints for the customer mobile app. No auth required —
// browsing the menu doesn't need a login. Order placement (Phase 5)
// is what becomes auth-protected.
class PublicRestaurantController extends Controller
{
    /**
     * GET /api/restaurants
     *
     * Lists open restaurants. Phase 6 will add lat/lon-aware sorting
     * via the DistanceCalculator service.
     */
    public function index(Request $request): JsonResponse
    {
        $restaurants = Restaurant::query()
            ->where('is_open', true)
            ->select(['id', 'slug', 'name', 'address', 'latitude', 'longitude'])
            ->get();

        return response()->json(['data' => $restaurants]);
    }

    /**
     * GET /api/restaurants/{slug}/menu
     *
     * Returns the FULL menu tree for one restaurant in a single round-trip:
     *   restaurant → categories → menu_items (available only) → variants
     *
     * Customer mobile app uses this to render the storefront with no
     * follow-up requests, keeping it fast on poor connections.
     */
    public function menu(string $slug): JsonResponse
    {
        $restaurant = Restaurant::where('slug', $slug)
            ->where('is_open', true)
            ->firstOrFail();

        $restaurant->load([
            'categories' => fn ($q) => $q->orderBy('sort_order'),
            'categories.menuItems' => fn ($q) => $q->where('is_available', true),
            'categories.menuItems.variants',
        ]);

        return response()->json(['data' => $restaurant]);
    }
}
