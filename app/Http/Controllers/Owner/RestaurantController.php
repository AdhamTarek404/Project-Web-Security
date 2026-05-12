<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreRestaurantRequest;
use App\Http\Requests\Owner\UpdateRestaurantRequest;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// CRUD for the restaurant_owner's own restaurant(s).
// All routes here are mounted under the `role:restaurant_owner,admin` group.
class RestaurantController extends Controller
{
    /**
     * GET /api/owner/restaurants
     * Owners see only their own. Admins see all (handled by the policy bypass).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Restaurant::query();
        if (! $user->isAdmin()) {
            $query->where('owner_id', $user->id);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    /**
     * POST /api/owner/restaurants
     * Creates a new restaurant owned by the current user.
     */
    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['owner_id'] = $request->user()->id;
        $data['slug'] = Restaurant::generateUniqueSlug($data['name']);

        $restaurant = Restaurant::create($data);

        return response()->json(['data' => $restaurant], 201);
    }

    /**
     * GET /api/owner/restaurants/{restaurant}
     */
    public function show(Request $request, Restaurant $restaurant): JsonResponse
    {
        // authorize() throws AuthorizationException (403) if the user
        // is not the owner (admins pass via the policy's before()).
        $this->authorize('view', $restaurant);

        return response()->json([
            'data' => $restaurant->loadCount('categories', 'orders'),
        ]);
    }

    /**
     * PATCH /api/owner/restaurants/{restaurant}
     */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): JsonResponse
    {
        $this->authorize('update', $restaurant);

        $restaurant->update($request->validated());

        return response()->json(['data' => $restaurant]);
    }
}
