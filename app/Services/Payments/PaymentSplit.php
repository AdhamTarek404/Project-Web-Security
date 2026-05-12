<?php

namespace App\Services\Payments;

// Value object representing how an order's total is split across the
// three Stripe Connect parties. Cents (integers) so amounts add up exactly.
final class PaymentSplit
{
    public function __construct(
        public readonly int $platformAmount,    // our cut
        public readonly int $restaurantAmount,  // their cut
        public readonly int $riderAmount,       // their cut
    ) {}

    public function total(): int
    {
        return $this->platformAmount + $this->restaurantAmount + $this->riderAmount;
    }
}
