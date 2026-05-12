<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\RateRequest;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Restaurant;
use App\Models\Rider;
use Illuminate\Http\JsonResponse;

class RatingController extends Controller
{
    /**
     * POST /api/customer/orders/{order}/rate
     * Body: { target: 'restaurant'|'rider', stars: 1..5, comment?: string }
     */
    public function store(RateRequest $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        // Business rule: you can only rate AFTER delivery.
        if ($order->status !== OrderStatus::Delivered) {
            return response()->json([
                'message' => 'You can only rate after the order is delivered.',
            ], 422);
        }

        $data = $request->validated();

        // Map the simple 'target' string to the actual polymorphic target.
        if ($data['target'] === 'restaurant') {
            $rateable = Restaurant::findOrFail($order->restaurant_id);
        } else {
            // 'rider' target — must have an assigned rider.
            abort_unless($order->rider_id, 422, 'Order has no rider.');
            $rateable = Rider::findOrFail($order->rider_id);
        }

        // updateOrCreate enforces the unique (order, rateable) constraint
        // we set in Phase 1's migration — one rating per pair.
        $rating = Rating::updateOrCreate(
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

        return response()->json(['data' => $rating], 201);
    }
}
