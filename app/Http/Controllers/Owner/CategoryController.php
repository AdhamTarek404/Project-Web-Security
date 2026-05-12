<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CategoryRequest;
use App\Models\Category;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function store(CategoryRequest $request): JsonResponse
    {
        $restaurant = Restaurant::findOrFail($request->validated('restaurant_id'));
        $this->authorize('manage', $restaurant);

        $category = Category::create($request->validated());

        return response()->json(['data' => $category], 201);
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('manage', $category->restaurant);

        $category->update($request->safe()->except('restaurant_id'));

        return response()->json(['data' => $category]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('manage', $category->restaurant);

        $category->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
