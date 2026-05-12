<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

// One entry point /dashboard that picks the right view based on the user's role.
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return match (true) {
            $user->isAdmin()           => redirect()->route('admin.dashboard'),
            $user->isCustomer()        => $this->customer($user),
            $user->isRestaurantOwner() => $this->owner($user),
            $user->isRider()           => $this->rider($user),
            default => abort(403),
        };
    }

    private function customer($user)
    {
        $orders = Order::where('customer_id', $user->id)
            ->with(['restaurant', 'items.menuItem', 'rider.user'])
            ->latest()
            ->take(20)
            ->get();

        return view('dashboards.customer', compact('user', 'orders'));
    }

    private function owner($user)
    {
        $restaurantIds = $user->restaurants()->pluck('id');

        $orders = Order::whereIn('restaurant_id', $restaurantIds)
            ->with(['customer', 'items.menuItem', 'rider.user'])
            ->latest()
            ->take(20)
            ->get();

        $restaurants = $user->restaurants()
            ->withCount(['categories', 'orders'])
            ->get();

        return view('dashboards.owner', compact('user', 'orders', 'restaurants'));
    }

    private function rider($user)
    {
        $rider = $user->rider;

        $orders = $rider
            ? Order::where('rider_id', $rider->id)
                ->whereIn('status', [
                    OrderStatus::Preparing,
                    OrderStatus::OnTheWay,
                    OrderStatus::Delivered,
                ])
                ->with(['customer', 'restaurant', 'items.menuItem'])
                ->latest()
                ->take(20)
                ->get()
            : collect();

        return view('dashboards.rider', compact('user', 'rider', 'orders'));
    }
}
