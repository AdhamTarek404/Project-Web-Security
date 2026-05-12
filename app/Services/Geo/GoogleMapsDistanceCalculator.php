<?php

namespace App\Services\Geo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Production distance calculator — calls Google Maps Distance Matrix API
 * to get the *driving* distance between two points (not straight-line),
 * which is what we actually want for rider dispatch (a river / one-way
 * street can make Haversine wildly wrong).
 *
 * Description: "Google Maps Distance Matrix API for rider-to-restaurant
 * distance calculation."
 *
 * Behaviour:
 *   - Hits https://maps.googleapis.com/maps/api/distancematrix/json
 *   - Reads the API key from config('services.google_maps.key')
 *   - Caches results for 60 seconds keyed by lat/long pair so the same
 *     rider-to-restaurant pair isn't billed twice in a row.
 *   - On any failure (network, quota, invalid key) falls back to the
 *     Haversine calculation so dispatch never completely breaks.
 */
class GoogleMapsDistanceCalculator implements DistanceCalculator
{
    private const ENDPOINT = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    public function __construct(
        private readonly HaversineDistanceCalculator $fallback,
        private readonly ?string $apiKey = null,
    ) {}

    public function kilometers(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $key = $this->apiKey ?: (string) config('services.google_maps.key');

        // No key configured → fall back silently. Means the test/demo
        // environment still works without a Google Cloud account.
        if ($key === '') {
            return $this->fallback->kilometers($lat1, $lon1, $lat2, $lon2);
        }

        $cacheKey = sprintf(
            'gmaps:dm:%.5f,%.5f:%.5f,%.5f',
            $lat1, $lon1, $lat2, $lon2,
        );

        return Cache::remember($cacheKey, 60, function () use ($key, $lat1, $lon1, $lat2, $lon2) {
            try {
                $response = Http::timeout(3)->get(self::ENDPOINT, [
                    'origins'      => "{$lat1},{$lon1}",
                    'destinations' => "{$lat2},{$lon2}",
                    'mode'         => 'driving',
                    'units'        => 'metric',
                    'key'          => $key,
                ]);

                if (! $response->ok()) {
                    throw new \RuntimeException("Google Maps HTTP {$response->status()}");
                }

                $body = $response->json();

                if (($body['status'] ?? null) !== 'OK') {
                    throw new \RuntimeException("Google Maps status {$body['status']}");
                }

                $element = $body['rows'][0]['elements'][0] ?? null;

                if (! $element || ($element['status'] ?? null) !== 'OK') {
                    throw new \RuntimeException('Google Maps: no route found');
                }

                $meters = (int) ($element['distance']['value'] ?? 0);

                return $meters / 1000;
            } catch (\Throwable $e) {
                // Quota exceeded? Network down? Invalid key? — fall back
                // to Haversine so dispatch keeps working.
                Log::warning('Google Maps Distance Matrix failed, falling back to Haversine', [
                    'error' => $e->getMessage(),
                ]);

                return $this->fallback->kilometers($lat1, $lon1, $lat2, $lon2);
            }
        });
    }
}
