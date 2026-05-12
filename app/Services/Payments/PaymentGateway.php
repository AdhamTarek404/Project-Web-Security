<?php

namespace App\Services\Payments;

use App\Models\Order;

// Interface for charging a customer and splitting the proceeds between
// platform / restaurant / rider. Production uses StripeConnectGateway.
// Tests use FakePaymentGateway. Both implement this interface.
//
// Description: "Stripe Connect for split payments between platform,
// restaurant, and rider."
interface PaymentGateway
{
    /**
     * Charge the customer and split the payment. Returns the gateway's
     * payment id (the value stored on `orders.payment_intent_id`).
     */
    public function chargeAndSplit(Order $order, PaymentSplit $split): string;
}
