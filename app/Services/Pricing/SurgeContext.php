<?php

namespace App\Services\Pricing;

// The inputs every surge strategy might want to look at.
// Description: "Surge pricing engine based on demand, weather, and time of day."
final class SurgeContext
{
    public function __construct(
        public readonly int $activeOrdersCount,    // demand signal
        public readonly int $availableRiderCount,  // supply signal
        public readonly ?string $weather = null,   // 'clear' | 'rain' | 'storm'
        public readonly ?\DateTimeInterface $now = null,
    ) {}
}
