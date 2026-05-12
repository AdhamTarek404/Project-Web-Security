<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Rider;
use App\Services\Pricing\SurgeContext;
use App\Services\Pricing\SurgePricingEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Background job that recomputes the current surge multiplier and caches
 * it for fast read-only checks (e.g. the "live surge banner" on the menu
 * page). Description: "Redis queues for ... surge pricing recalculation ..."
 *
 * Typically scheduled every minute via the kernel scheduler, or fired
 * ad-hoc by the OrderStateChanged listener so a sudden flood of orders
 * gets reflected within seconds instead of waiting a full minute.
 */
class RecalculateSurgePricingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(SurgePricingEngine $engine): void
    {
        $ctx = new SurgeContext(
            activeOrdersCount:   Order::whereNotIn('status', ['delivered', 'cancelled'])->count(),
            availableRiderCount: Rider::where('is_available', true)->where('is_on_duty', true)->count(),
        );

        $multiplier = $engine->compute($ctx);

        // Cache for 90 seconds (slightly longer than the 60s recalc cadence
        // so the cache is always populated). Reads are cheap & lock-free.
        Cache::put('surge:current', [
            'multiplier'  => $multiplier,
            'active'      => $ctx->activeOrdersCount,
            'riders'      => $ctx->availableRiderCount,
            'recalculated_at' => now()->toIso8601String(),
        ], 90);

        Log::info('Surge recalculated', [
            'multiplier' => $multiplier,
            'active'     => $ctx->activeOrdersCount,
            'riders'     => $ctx->availableRiderCount,
        ]);
    }
}
