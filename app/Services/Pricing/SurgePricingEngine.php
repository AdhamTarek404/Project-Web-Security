<?php

namespace App\Services\Pricing;

// Composes the active strategies, sums their bumps above 1.00,
// and caps the final result. This is the single object the rest of
// the app talks to. The strategies plug in via the constructor — making
// the "Strategy pattern" requirement of the description literal.
class SurgePricingEngine
{
    // Defensive ceiling — never charge more than 3x base.
    public const MAX_MULTIPLIER = 3.00;

    /**
     * @var array<int, SurgePricingStrategy>
     */
    private array $strategies;

    public function __construct(?array $strategies = null)
    {
        $this->strategies = $strategies ?? [
            new MultiplierSurgeStrategy(),
            new TimeBasedSurgeStrategy(),
        ];
    }

    public function compute(SurgeContext $context): float
    {
        // Each strategy returns >= 1.00. We sum the *bumps* (each strategy's
        // contribution above 1.00), then add to 1.00 and cap. This makes
        // strategies additive instead of multiplicative — picking three
        // strategies that each return 1.5 wouldn't explode to 3.375.
        $bumps = 0.0;
        foreach ($this->strategies as $s) {
            $bumps += max(0.0, $s->calculate($context) - 1.0);
        }

        $multiplier = round(1.0 + $bumps, 2);

        // Cap: "Test surge multiplier caps and rollback when demand drops"
        // from the description.
        return min($multiplier, self::MAX_MULTIPLIER);
    }
}
