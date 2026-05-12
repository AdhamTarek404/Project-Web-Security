<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\VariantRequest;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MenuItemVariantController extends Controller
{
    public function store(VariantRequest $request): JsonResponse
    {
        $menuItem = MenuItem::findOrFail($request->validated('menu_item_id'));
        $this->authorize('manage', $menuItem->category->restaurant);

        $data = $request->validated();

        // If the owner marks this variant as default, the previous default
        // for the same item must be un-defaulted — there can only be one.
        if (! empty($data['is_default'])) {
            DB::transaction(function () use ($menuItem, &$data) {
                MenuItemVariant::where('menu_item_id', $menuItem->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            });
        }

        $variant = MenuItemVariant::create($data);

        return response()->json(['data' => $variant], 201);
    }

    public function update(VariantRequest $request, MenuItemVariant $variant): JsonResponse
    {
        $this->authorize('manage', $variant->menuItem->category->restaurant);

        $data = $request->safe()->except('menu_item_id');

        if (! empty($data['is_default'])) {
            DB::transaction(function () use ($variant) {
                MenuItemVariant::where('menu_item_id', $variant->menu_item_id)
                    ->where('is_default', true)
                    ->where('id', '!=', $variant->id)
                    ->update(['is_default' => false]);
            });
        }

        $variant->update($data);

        return response()->json(['data' => $variant]);
    }

    public function destroy(MenuItemVariant $variant): JsonResponse
    {
        $this->authorize('manage', $variant->menuItem->category->restaurant);

        $variant->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
