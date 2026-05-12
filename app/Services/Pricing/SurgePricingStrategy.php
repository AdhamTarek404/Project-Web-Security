<?php

namespace App\Services\Pricing;

// The Strategy pattern interface. Description: "Strategy pattern for
// surge pricing (flat, multiplier, time-based)."
//
// Each strategy returns a multiplier ≥ 1.00. The SurgePricingEngine
// caps the final number to a maximum to protect customers from runaway prices.
interface SurgePricingStrategy
{
    public function calculate(SurgeContext $context): float;
}
