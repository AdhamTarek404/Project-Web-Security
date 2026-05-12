<?php

namespace Tests\Feature\Pricing;

use App\Services\Pricing\FlatSurgeStrategy;
use App\Services\Pricing\MultiplierSurgeStrategy;
use App\Services\Pricing\SurgeContext;
use App\Services\Pricing\SurgePricingEngine;
use App\Services\Pricing\TimeBasedSurgeStrategy;
use Tests\TestCase;

class SurgePricingTest extends TestCase
{
    public function test_flat_strategy_returns_its_fixed_value(): void
    {
        $s = new FlatSurgeStrategy(1.50);
        $this->assertSame(1.50, $s->calculate(new SurgeContext(0, 0)));
    }

    public function test_multiplier_strategy_scales_with_demand_supply_ratio(): void
    {
        $s = new MultiplierSurgeStrategy();
        $this->assertSame(1.00, $s->calculate(new SurgeContext(activeOrdersCount: 1,  availableRiderCount: 5)));
        $this->assertSame(1.25, $s->calculate(new SurgeContext(activeOrdersCount: 10, availableRiderCount: 5)));
        $this->assertSame(1.50, $s->calculate(new SurgeContext(activeOrdersCount: 15, availableRiderCount: 5)));
        $this->assertSame(2.00, $s->calculate(new SurgeContext(activeOrdersCount: 25, availableRiderCount: 5)));
        $this->assertSame(2.50, $s->calculate(new SurgeContext(activeOrdersCount: 60, availableRiderCount: 5)));
    }

    public function test_time_based_strategy_bumps_during_rush_and_weather(): void
    {
        $s = new TimeBasedSurgeStrategy();
        $lunch = new \DateTimeImmutable('2026-05-12 13:00:00');
        $night = new \DateTimeImmutable('2026-05-12 03:00:00');

        $this->assertSame(1.25, $s->calculate(new SurgeContext(0, 1, now: $lunch)));
        $this->assertSame(1.00, $s->calculate(new SurgeContext(0, 1, now: $night)));
        $this->assertSame(1.50, $s->calculate(new SurgeContext(0, 1, 'rain', $lunch))); // rush+rain
        $this->assertSame(1.50, $s->calculate(new SurgeContext(0, 1, 'storm', $night))); // storm only
    }

    public function test_engine_caps_at_max_multiplier(): void
    {
        // Stack high-bump strategies to try to exceed the cap.
        $engine = new SurgePricingEngine([
            new FlatSurgeStrategy(2.00),
            new FlatSurgeStrategy(2.00),
            new FlatSurgeStrategy(2.00),
        ]);
        // Each adds +1.0 bump, total 4.0 → capped to 3.00.
        $this->assertSame(SurgePricingEngine::MAX_MULTIPLIER, $engine->compute(new SurgeContext(0, 0)));
    }

    public function test_engine_rolls_back_to_one_when_demand_drops(): void
    {
        $engine = new SurgePricingEngine([new MultiplierSurgeStrategy()]);

        // High demand → surge.
        $hot = $engine->compute(new SurgeContext(activeOrdersCount: 25, availableRiderCount: 5, now: new \DateTimeImmutable('2026-05-12 03:00:00')));
        $this->assertGreaterThan(1.00, $hot);

        // Demand drops → rolls back to 1.00.
        $cold = $engine->compute(new SurgeContext(activeOrdersCount: 1, availableRiderCount: 10, now: new \DateTimeImmutable('2026-05-12 03:00:00')));
        $this->assertSame(1.00, $cold);
    }
}
