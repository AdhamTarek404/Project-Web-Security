<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\MenuItemRequest;
use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function store(MenuItemRequest $request): JsonResponse
    {
        // Resolve the owning restaurant via the parent category so we
        // can authorize based on restaurant ownership.
        $category = Category::findOrFail($request->validated('category_id'));
        $this->authorize('manage', $category->restaurant);

        // fresh() reloads from DB so any column with a DB default
        // (e.g. is_available=true) is present in the response.
        $item = MenuItem::create($request->validated())->fresh();

        return response()->json(['data' => $item], 201);
    }

    public function update(MenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        $this->authorize('manage', $menuItem->category->restaurant);

        $menuItem->update($request->validated());

        return response()->json(['data' => $menuItem]);
    }

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $this->authorize('manage', $menuItem->category->restaurant);

        $menuItem->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * PATCH /api/owner/menu-items/{menuItem}/availability
     *
     * Quick toggle endpoint — the description's "availability toggles"
     * feature. The owner hits this from their dashboard when something
     * runs out of stock.
     */
    public function toggleAvailability(Request $request, MenuItem $menuItem): JsonResponse
    {
        $this->authorize('manage', $menuItem->category->restaurant);

        $menuItem->is_available = ! $menuItem->is_available;
        $menuItem->save();

        return response()->json([
            'data' => $menuItem,
            'is_available' => $menuItem->is_available,
        ]);
    }
}
