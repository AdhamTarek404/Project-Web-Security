<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Dev / test implementation. Behaves like Stripe Connect — accepts a split,
// validates that everything adds up to the order total, and returns a fake
// PaymentIntent id. Swap with StripeConnectGateway in production.
class FakePaymentGateway implements PaymentGateway
{
    public function chargeAndSplit(Order $order, PaymentSplit $split): string
    {
        // Critical invariant: the three slices must add up to the order total.
        // If they don't, we'd be either short-paying someone or eating the loss.
        if ($split->total() !== $order->total) {
            throw new \RuntimeException(
                "Split mismatch: split total {$split->total()} != order total {$order->total}"
            );
        }

        $id = 'pi_fake_'.Str::random(20);

        Log::info('Fake Stripe Connect charge', [
            'order_id' => $order->id,
            'payment_intent_id' => $id,
            'platform' => $split->platformAmount,
            'restaurant' => $split->restaurantAmount,
            'rider' => $split->riderAmount,
        ]);

        return $id;
    }
}
