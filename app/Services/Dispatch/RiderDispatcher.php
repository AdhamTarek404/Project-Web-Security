<?php

namespace App\Services\Dispatch;

use App\Models\Order;
use App\Models\Rider;
use App\Services\Geo\DistanceCalculator;
use Illuminate\Support\Facades\DB;

// Picks the nearest *available* rider to a restaurant and assigns the order.
//
// Description: "Rider GPS dispatch: assign nearest available rider and
// track live on map." + "Google Maps Distance Matrix API for rider-to-
// restaurant distance calculation."
//
// We use the DistanceCalculator interface so swapping Haversine for the
// Google Distance Matrix API is a one-line binding change.
class RiderDispatcher
{
    public function __construct(private readonly DistanceCalculator $distance) {}

    /**
     * Returns the assigned Rider, or null if nobody was available.
     *
     * IMPORTANT: this method runs inside a DB transaction with a SELECT...
     * FOR UPDATE-style row lock so two simultaneous dispatch jobs can't
     * pick the same rider for two different orders (race condition tested
     * in Phase 11 with 50 concurrent orders).
     */
    public function dispatch(Order $order): ?Rider
    {
        $restaurant = $order->restaurant;

        return DB::transaction(function () use ($order, $restaurant) {
            // Pull all currently-available riders. We sort in PHP because
            // SQL doesn't natively support Haversine portably across drivers.
            // For a real city-scale workload, switch to PostGIS or use a
            // bounding-box pre-filter here before the in-memory sort.
            $available = Rider::query()
                ->where('is_available', true)
                ->where('is_on_duty', true)
                ->whereNotNull('current_latitude')
                ->whereNotNull('current_longitude')
                ->lockForUpdate()
                ->get();

            if ($available->isEmpty()) {
                return null;
            }

            $best = $available
                ->map(fn (Rider $r) => [
                    'rider' => $r,
                    'km' => $this->distance->kilometers(
                        (float) $r->current_latitude,
                        (float) $r->current_longitude,
                        (float) $restaurant->latitude,
                        (float) $restaurant->longitude,
                    ),
                ])
                ->sortBy('km')
                ->first()['rider'];

            // Mark this rider unavailable so the next dispatch job won't pick
            // them. They become available again when the order is delivered.
            $best->is_available = false;
            $best->save();

            $order->rider_id = $best->id;
            $order->save();

            return $best;
        });
    }
}
