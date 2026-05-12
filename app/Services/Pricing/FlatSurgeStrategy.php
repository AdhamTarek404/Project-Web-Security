<?php

namespace App\Services\Pricing;

// The simplest strategy — always returns the same fixed multiplier.
// Useful for "rain mode" where ops just flips it to 1.5 manually.
class FlatSurgeStrategy implements SurgePricingStrategy
{
    public function __construct(private readonly float $multiplier = 1.00) {}

    public function calculate(SurgeContext $context): float
    {
        return $this->multiplier;
    }
}
