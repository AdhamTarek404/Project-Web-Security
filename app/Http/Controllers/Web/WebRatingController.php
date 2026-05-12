<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Restaurant;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Browser-side rating submission. Mirrors the API RatingController but
// redirects back to the order page with a flash message.
class WebRatingController extends Controller
{
    public function store(Request $request, Order $order)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        if ($order->status !== OrderStatus::Delivered) {
            return back()->withErrors(['rating' => 'You can only rate after the order is delivered.']);
        }

        $data = $request->validate([
            'target'  => ['required', Rule::in(['restaurant', 'rider'])],
            'stars'   => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['target'] === 'restaurant') {
            $rateable = Restaurant::findOrFail($order->restaurant_id);
        } else {
            abort_unless($order->rider_id, 422);
            $rateable = Rider::findOrFail($order->rider_id);
        }

        Rating::updateOrCreate(
            [
                'order_id' => $order->id,
                'rateable_type' => $rateable::class,
                'rateable_id' => $rateable->id,
            ],
            [
                'customer_id' => $request->user()->id,
                'stars' => $data['stars'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        return back()->with('status', "Thanks — {$data['stars']}★ rating saved.");
    }
}
