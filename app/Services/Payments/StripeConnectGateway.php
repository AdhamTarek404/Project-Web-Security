<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

/**
 * Production payment gateway — uses Stripe Connect for split payments
 * between the platform, the restaurant, and the rider.
 *
 * Description: "Stripe Connect for split payments between platform,
 * restaurant, and rider."
 *
 * Flow:
 *   1. Customer pays the full order total via a PaymentIntent.
 *   2. The platform's cut stays in the platform's Stripe balance.
 *   3. The restaurant payout is transferred to the restaurant's connected
 *      Stripe account (stripe_account_id).
 *   4. The rider payout is transferred to the rider's connected Stripe
 *      account.
 *
 * Two implementation patterns are supported:
 *   (a) Destination-charge with transfer_group: one PaymentIntent +
 *       two Transfers (used here — keeps the code clean and works with
 *       Stripe Connect Express + Standard accounts).
 *   (b) Separate-charge-and-transfer: same idea, just calls split out.
 *
 * If the order's restaurant or rider does not yet have a stripe_account_id
 * the transfer to that party is queued in `pending_transfers` table for
 * payout once the account is onboarded — but for the demo we just log it
 * and skip the transfer (so the test/demo flow still works without
 * onboarding every account through Stripe Connect).
 */
class StripeConnectGateway implements PaymentGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function chargeAndSplit(Order $order, PaymentSplit $split): string
    {
        if ($split->total() !== $order->total) {
            throw new \RuntimeException(
                "Split mismatch: split total {$split->total()} != order total {$order->total}"
            );
        }

        $transferGroup = 'order_'.$order->id;

        // 1) Charge the customer for the full order total.
        //    All Stripe amounts are in the smallest currency unit (cents/piastres),
        //    matching our internal storage convention.
        $intent = $this->stripe->paymentIntents->create([
            'amount'                 => $order->total,
            'currency'               => strtolower(config('services.stripe.currency', 'usd')),
            'transfer_group'         => $transferGroup,
            'description'            => "Order #{$order->id} at {$order->restaurant->name}",
            'metadata' => [
                'order_id'      => (string) $order->id,
                'customer_id'   => (string) $order->customer_id,
                'restaurant_id' => (string) $order->restaurant_id,
                'rider_id'      => (string) ($order->rider_id ?? ''),
                'platform_fee'  => (string) $split->platformAmount,
                'restaurant'    => (string) $split->restaurantAmount,
                'rider'         => (string) $split->riderAmount,
            ],
            // For the demo we auto-confirm with a Stripe test payment method.
            // In a real customer flow this would be replaced with a
            // payment_method_id sent from the client (Stripe.js) and a
            // 3D Secure confirmation step.
            'payment_method'         => config('services.stripe.test_payment_method', 'pm_card_visa'),
            'confirm'                => true,
            'automatic_payment_methods' => [
                'enabled'         => true,
                'allow_redirects' => 'never',
            ],
        ]);

        // 2) Transfer the restaurant's cut to its connected Stripe account.
        $this->transferIfConnected(
            connectedAccountId: $order->restaurant->stripe_account_id ?? null,
            amount: $split->restaurantAmount,
            transferGroup: $transferGroup,
            party: 'restaurant',
            orderId: $order->id,
        );

        // 3) Transfer the rider's cut to its connected Stripe account.
        $riderStripeAccountId = $order->rider?->stripe_account_id;
        $this->transferIfConnected(
            connectedAccountId: $riderStripeAccountId,
            amount: $split->riderAmount,
            transferGroup: $transferGroup,
            party: 'rider',
            orderId: $order->id,
        );

        Log::info('Stripe Connect: charged + split', [
            'order_id'          => $order->id,
            'payment_intent_id' => $intent->id,
            'transfer_group'    => $transferGroup,
            'platform'          => $split->platformAmount,
            'restaurant'        => $split->restaurantAmount,
            'rider'             => $split->riderAmount,
        ]);

        return $intent->id;
    }

    private function transferIfConnected(
        ?string $connectedAccountId,
        int $amount,
        string $transferGroup,
        string $party,
        int $orderId,
    ): void {
        if ($amount <= 0) {
            return;
        }

        if (! $connectedAccountId) {
            // The party hasn't completed Stripe Connect onboarding yet.
            // Log it — in production this would write to a pending_payouts
            // table and a daily job would retry once onboarding finishes.
            Log::warning('Stripe Connect: no connected account, deferring transfer', [
                'order_id' => $orderId,
                'party'    => $party,
                'amount'   => $amount,
            ]);

            return;
        }

        $this->stripe->transfers->create([
            'amount'         => $amount,
            'currency'       => strtolower(config('services.stripe.currency', 'usd')),
            'destination'    => $connectedAccountId,
            'transfer_group' => $transferGroup,
            'description'    => "Order #{$orderId} {$party} payout",
            'metadata'       => [
                'order_id' => (string) $orderId,
                'party'    => $party,
            ],
        ]);
    }
}
