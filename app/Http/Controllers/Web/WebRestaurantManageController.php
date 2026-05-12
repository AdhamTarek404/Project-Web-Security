<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreRestaurantRequest;
use App\Http\Requests\Owner\UpdateRestaurantRequest;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Browser-side restaurant management for owners.
// All actions reuse the same FormRequest validators as the API controllers.
class WebRestaurantManageController extends Controller
{
    /** GET /owner/restaurants/create */
    public function create()
    {
        return view('restaurants.create');
    }

    /** POST /owner/restaurants */
    public function store(StoreRestaurantRequest $request)
    {
        $data = $request->validated();
        $data['owner_id'] = $request->user()->id;
        $data['slug'] = Restaurant::generateUniqueSlug($data['name']);

        $r = Restaurant::create($data);

        return redirect()->route('owner.restaurants.manage', $r)
            ->with('status', "Restaurant '{$r->name}' created.");
    }

    /** GET /owner/restaurants/{restaurant}/manage */
    public function manage(Request $request, Restaurant $restaurant)
    {
        $this->authorize('view', $restaurant);

        $restaurant->load([
            'categories' => fn ($q) => $q->orderBy('sort_order'),
            'categories.menuItems' => fn ($q) => $q->orderBy('id'),
            'categories.menuItems.variants',
        ]);

        return view('restaurants.manage', compact('restaurant'));
    }

    /** PATCH /owner/restaurants/{restaurant} */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant)
    {
        $this->authorize('update', $restaurant);

        $restaurant->update($request->validated());

        return back()->with('status', 'Restaurant updated.');
    }

    /** POST /owner/restaurants/{restaurant}/toggle-open */
    public function toggleOpen(Request $request, Restaurant $restaurant)
    {
        $this->authorize('update', $restaurant);

        $restaurant->is_open = ! $restaurant->is_open;
        $restaurant->save();

        return back()->with('status', $restaurant->is_open ? 'Restaurant is now OPEN.' : 'Restaurant is now CLOSED.');
    }

    // ============== Categories ==============

    /** POST /owner/restaurants/{restaurant}/categories */
    public function storeCategory(Request $request, Restaurant $restaurant)
    {
        $this->authorize('manage', $restaurant);

        // restaurant_id comes from the URL, not the form body — so we don't reuse
        // the API CategoryRequest (which requires it in the body).
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'Category added.');
    }

    /** PATCH /owner/categories/{category} */
    public function updateCategory(Request $request, Category $category)
    {
        $this->authorize('manage', $category->restaurant);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category->update($data);

        return back()->with('status', 'Category updated.');
    }

    /** DELETE /owner/categories/{category} */
    public function destroyCategory(Category $category)
    {
        $this->authorize('manage', $category->restaurant);

        $category->delete();

        return back()->with('status', 'Category deleted.');
    }

    // ============== Menu items ==============

    /** POST /owner/categories/{category}/menu-items */
    public function storeMenuItem(Request $request, Category $category)
    {
        $this->authorize('manage', $category->restaurant);

        // category_id comes from the URL.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            // Price is in integer cents (project rule: no floats for money).
            'base_price' => ['required', 'integer', 'min:1', 'max:1000000'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        MenuItem::create([
            'category_id' => $category->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'base_price' => $data['base_price'],
            'is_available' => $data['is_available'] ?? true,
        ]);

        return back()->with('status', 'Menu item added.');
    }

    /** PATCH /owner/menu-items/{menuItem} */
    public function updateMenuItem(Request $request, MenuItem $menuItem)
    {
        $this->authorize('manage', $menuItem->category->restaurant);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_price' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $menuItem->update($data);

        return back()->with('status', "'{$menuItem->name}' updated.");
    }

    /** POST /owner/menu-items/{menuItem}/toggle-availability */
    public function toggleAvailability(MenuItem $menuItem)
    {
        $this->authorize('manage', $menuItem->category->restaurant);

        $menuItem->is_available = ! $menuItem->is_available;
        $menuItem->save();

        return back()->with('status', $menuItem->is_available ? "'{$menuItem->name}' is now available." : "'{$menuItem->name}' is now unavailable.");
    }

    /** DELETE /owner/menu-items/{menuItem} */
    public function destroyMenuItem(MenuItem $menuItem)
    {
        $this->authorize('manage', $menuItem->category->restaurant);

        $menuItem->delete();

        return back()->with('status', 'Menu item deleted.');
    }

    // ============== Variants ==============

    /** POST /owner/menu-items/{menuItem}/variants */
    public function storeVariant(Request $request, MenuItem $menuItem)
    {
        $this->authorize('manage', $menuItem->category->restaurant);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            // Price modifier is in cents and can be 0 (a "Small" variant might be free).
            'price_modifier' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        // Only one variant per item can be default — flip the others off in a transaction.
        DB::transaction(function () use ($data, $menuItem) {
            if (! empty($data['is_default'])) {
                $menuItem->variants()->update(['is_default' => false]);
            }

            $menuItem->variants()->create([
                'name' => $data['name'],
                'price_modifier' => $data['price_modifier'],
                'is_default' => ! empty($data['is_default']),
            ]);
        });

        return back()->with('status', "Variant '{$data['name']}' added.");
    }

    /** POST /owner/variants/{variant}/make-default */
    public function makeDefaultVariant(MenuItemVariant $variant)
    {
        $this->authorize('manage', $variant->menuItem->category->restaurant);

        DB::transaction(function () use ($variant) {
            $variant->menuItem->variants()->update(['is_default' => false]);
            $variant->update(['is_default' => true]);
        });

        return back()->with('status', "Variant '{$variant->name}' is now the default.");
    }

    /** DELETE /owner/variants/{variant} */
    public function destroyVariant(MenuItemVariant $variant)
    {
        $this->authorize('manage', $variant->menuItem->category->restaurant);

        $variant->delete();

        return back()->with('status', 'Variant deleted.');
    }
}
