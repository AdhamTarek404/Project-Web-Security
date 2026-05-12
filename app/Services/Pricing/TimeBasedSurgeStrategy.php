<?php

namespace App\Services\Pricing;

// Rush-hour and weather-aware surge. Description: "...based on demand,
// weather, and time of day."
class TimeBasedSurgeStrategy implements SurgePricingStrategy
{
    public function calculate(SurgeContext $context): float
    {
        $multiplier = 1.00;

        $now = $context->now ?? now();
        $hour = (int) $now->format('G');

        // Lunch rush 12:00–14:00 and dinner rush 19:00–22:00.
        if (($hour >= 12 && $hour < 14) || ($hour >= 19 && $hour < 22)) {
            $multiplier += 0.25;
        }

        // Bad weather: nobody wants to ride; price up so the few brave
        // riders that DO show up earn more.
        $multiplier += match ($context->weather) {
            'rain' => 0.25,
            'storm' => 0.50,
            default => 0.00,
        };

        return $multiplier;
    }
}
