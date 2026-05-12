<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Models\User;
use App\Services\Pricing\MultiplierSurgeStrategy;
use App\Services\Pricing\SurgeContext;
use App\Services\Pricing\SurgePricingEngine;
use App\Services\Pricing\TimeBasedSurgeStrategy;
use Illuminate\Http\Request;

// Admin-only pages: a system-wide view of orders, users, restaurants, riders.
// The route group enforces admin-only via middleware.
class WebAdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'counts' => [
                'orders'        => Order::count(),
                'active_orders' => Order::whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value])->count(),
                'users'         => User::count(),
                'customers'     => User::where('role', User::ROLE_CUSTOMER)->count(),
                'owners'        => User::where('role', User::ROLE_RESTAURANT_OWNER)->count(),
                'riders'        => User::where('role', User::ROLE_RIDER)->count(),
                'on_duty'       => Rider::where('is_on_duty', true)->count(),
                'restaurants'   => Restaurant::count(),
                'open'          => Restaurant::where('is_open', true)->count(),
                'gmv_cents'     => (int) Order::where('status', OrderStatus::Delivered)->sum('total'),
                'platform_fee'  => (int) Order::where('status', OrderStatus::Delivered)->sum('platform_fee'),
            ],
            'recentOrders' => Order::with(['customer', 'restaurant', 'rider.user'])
                ->latest()->take(10)->get(),
        ]);
    }

    public function orders(Request $request)
    {
        $filter = $request->query('status');

        $orders = Order::query()
            ->with(['customer', 'restaurant', 'rider.user'])
            ->when($filter, fn ($q) => $q->where('status', $filter))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders', [
            'orders' => $orders,
            'filter' => $filter,
            'statuses' => array_map(fn ($s) => $s->value, OrderStatus::cases()),
        ]);
    }

    public function users(Request $request)
    {
        $filter = $request->query('role');

        $users = User::query()
            ->withCount(['orders', 'restaurants'])
            ->when($filter, fn ($q) => $q->where('role', $filter))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'filter' => $filter,
            'roles' => [
                User::ROLE_CUSTOMER, User::ROLE_RIDER, User::ROLE_RESTAURANT_OWNER, User::ROLE_ADMIN,
            ],
        ]);
    }

    public function restaurants()
    {
        $restaurants = Restaurant::with('owner')
            ->withCount(['categories', 'orders'])
            ->orderBy('id')
            ->paginate(25);

        return view('admin.restaurants', compact('restaurants'));
    }

    public function riders()
    {
        $riders = Rider::with('user')
            ->withCount('orders')
            ->orderBy('id')
            ->paginate(25);

        return view('admin.riders', compact('riders'));
    }

    /** Force open/close a restaurant from the admin view. */
    public function toggleRestaurantOpen(Restaurant $restaurant)
    {
        $restaurant->is_open = ! $restaurant->is_open;
        $restaurant->save();

        return back()->with('status', "{$restaurant->name} is now ".($restaurant->is_open ? 'OPEN' : 'CLOSED').'.');
    }

    /**
     * Surge pricing playground: lets the admin punch in demand/supply/weather/time
     * and SEE the multiplier the engine would return — broken down by strategy.
     */
    public function surge(Request $request)
    {
        // Live counts from the DB (so the page shows "what the engine sees right now").
        $liveOrders = Order::whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value])->count();
        $liveRiders = Rider::where('is_available', true)->where('is_on_duty', true)->count();

        // Form inputs (default to the live values + reasonable time defaults).
        $orders  = (int) $request->query('orders',  $liveOrders);
        $riders  = (int) $request->query('riders',  max(1, $liveRiders));
        $weather = $request->query('weather', 'clear');
        $hour    = (int) $request->query('hour', (int) now()->format('G'));

        // Build a SurgeContext exactly like PlaceOrder does, but with the form inputs.
        $now = now()->setTime($hour, 0, 0);
        $ctx = new SurgeContext(
            activeOrdersCount:   max(0, $orders),
            availableRiderCount: max(1, $riders),
            weather:             $weather,
            now:                 $now,
        );

        // Run each strategy individually so we can show the breakdown.
        $strategies = [
            'Demand / supply (MultiplierSurgeStrategy)' => new MultiplierSurgeStrategy(),
            'Time + weather (TimeBasedSurgeStrategy)'    => new TimeBasedSurgeStrategy(),
        ];

        $breakdown = [];
        foreach ($strategies as $label => $s) {
            $breakdown[$label] = round($s->calculate($ctx), 2);
        }

        // The engine's own composition (sum of bumps, capped).
        $engine = new SurgePricingEngine(array_values($strategies));
        $finalMultiplier = $engine->compute($ctx);

        return view('admin.surge', [
            'liveOrders' => $liveOrders,
            'liveRiders' => $liveRiders,
            'orders'     => $orders,
            'riders'     => $riders,
            'weather'    => $weather,
            'hour'       => $hour,
            'breakdown'  => $breakdown,
            'final'      => $finalMultiplier,
            'cap'        => SurgePricingEngine::MAX_MULTIPLIER,
            'ratio'      => max(1, $riders) > 0 ? round(max(0, $orders) / max(1, $riders), 2) : 0,
            'inRush'     => ($hour >= 12 && $hour < 14) || ($hour >= 19 && $hour < 22),
        ]);
    }
}
