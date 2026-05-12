<?php

namespace App\Services\Geo;

// Interface that abstracts the "how do we compute distance" decision.
// The Phase 6 implementation is Haversine (offline). The description's
// "Google Maps Distance Matrix API" plug-in becomes a 20-line adapter
// implementing this same interface — we swap one binding in the
// service container and nothing else changes.
interface DistanceCalculator
{
    /**
     * Returns straight-line distance in KILOMETERS between two points.
     */
    public function kilometers(float $lat1, float $lon1, float $lat2, float $lon2): float;
}
