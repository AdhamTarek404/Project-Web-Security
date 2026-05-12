<?php

namespace App\Services\Orders;

use App\Models\Restaurant;

// Centralizes the money math so it's identical at order-placement time,
// at refund time, and in payment-split tests. All amounts are integer cents.
class PriceCalculator
{
    // Base delivery fee in cents (50 EGP). In a real product this would
    // come from a `delivery_fee` config or per-restaurant override.
    public const BASE_DELIVERY_FEE_CENTS = 5000;

    /**
     * @param  int  $subtotal      sum of order item line totals (cents)
     * @param  float  $surge       multiplier from the Surge engine (Phase 7), e.g. 1.50
     * @param  Restaurant  $restaurant   gives us commission_rate
     * @return array{subtotal:int, delivery_fee:int, surge_multiplier:float, platform_fee:int, restaurant_payout:int, rider_payout:int, total:int}
     */
    public function compute(int $subtotal, float $surge, Restaurant $restaurant): array
    {
        $deliveryFee = self::BASE_DELIVERY_FEE_CENTS;

        // intdiv rounds DOWN — favoring the customer/restaurant over the
        // platform. Use this for predictable accounting.
        $platformFee = intdiv($subtotal * (int) round($restaurant->commission_rate * 100), 10000);

        $restaurantPayout = $subtotal - $platformFee;

        // Rider gets the surge-amplified delivery fee.
        $riderPayout = (int) round($deliveryFee * $surge);

        $total = $subtotal + $riderPayout;

        return [
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'surge_multiplier' => $surge,
            'platform_fee' => $platformFee,
            'restaurant_payout' => $restaurantPayout,
            'rider_payout' => $riderPayout,
            'total' => $total,
        ];
    }
}
