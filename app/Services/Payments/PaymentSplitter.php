<?php

namespace App\Services\Payments;

use App\Models\Order;

// Reads the money columns we computed in PriceCalculator (Phase 5)
// and packages them as a PaymentSplit. By the time we get here every
// number has been validated and committed — this is purely a translation.
class PaymentSplitter
{
    public function splitFor(Order $order): PaymentSplit
    {
        // Platform's cut = commission on subtotal + delivery_fee differential
        // (if the rider's surge-amplified payout is more than the delivery_fee
        // the customer was charged, the platform absorbs the difference;
        // if less, the platform pockets the surplus).
        $deliverySurplus = ($order->total - $order->subtotal) - $order->rider_payout;
        $platform = $order->platform_fee + $deliverySurplus;

        return new PaymentSplit(
            platformAmount: $platform,
            restaurantAmount: $order->restaurant_payout,
            riderAmount: $order->rider_payout,
        );
    }
}
