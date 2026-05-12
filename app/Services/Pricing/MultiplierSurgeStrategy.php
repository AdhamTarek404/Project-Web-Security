<?php

namespace App\Services\Pricing;

// Demand/supply-driven surge. When active orders outnumber available riders,
// price goes up. When orders drop, price rolls back automatically.
// Description: "Surge pricing engine based on demand..."
class MultiplierSurgeStrategy implements SurgePricingStrategy
{
    public function calculate(SurgeContext $context): float
    {
        $supply = max(1, $context->availableRiderCount); // avoid /0
        $ratio = $context->activeOrdersCount / $supply;

        // Step ladder:
        //   ≤ 1 order per rider  → 1.00
        //   1–2                  → 1.25
        //   2–3                  → 1.50
        //   3–5                  → 2.00
        //   > 5                  → 2.50
        return match (true) {
            $ratio <= 1.0 => 1.00,
            $ratio <= 2.0 => 1.25,
            $ratio <= 3.0 => 1.50,
            $ratio <= 5.0 => 2.00,
            default       => 2.50,
        };
    }
}
