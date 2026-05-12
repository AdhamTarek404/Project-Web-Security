<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;

// Public landing page: shows all open restaurants. Clicking one opens its menu.
class HomeController extends Controller
{
    public function index()
    {
        $restaurants = Restaurant::where('is_open', true)
            ->withCount('ratings')
            ->orderBy('name')
            ->get();

        return view('home', compact('restaurants'));
    }

    public function show(Restaurant $restaurant)
    {
        $restaurant->load([
            'categories' => fn ($q) => $q->orderBy('sort_order'),
            'categories.menuItems' => fn ($q) => $q->orderBy('id'),
            'categories.menuItems.variants',
        ]);

        return view('restaurants.show', compact('restaurant'));
    }
}
