<?php

namespace App\Providers;

use App\Services\Geo\DistanceCalculator;
use App\Services\Geo\GoogleMapsDistanceCalculator;
use App\Services\Geo\HaversineDistanceCalculator;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\StripeConnectGateway;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // === DistanceCalculator ===
        // Production: Google Maps Distance Matrix API (driving distance,
        // exactly as the description requires).
        // Development / tests: Haversine — offline, free, deterministic.
        //
        // Switch via SERVICES_DISTANCE_DRIVER=google|haversine in .env.
        $this->app->bind(DistanceCalculator::class, function ($app) {
            $driver = config('services.distance.driver', 'haversine');

            if ($driver === 'google') {
                return new GoogleMapsDistanceCalculator(
                    fallback: $app->make(HaversineDistanceCalculator::class),
                    apiKey:   config('services.google_maps.key'),
                );
            }

            return $app->make(HaversineDistanceCalculator::class);
        });

        // === PaymentGateway ===
        // Production: real Stripe Connect via stripe/stripe-php.
        // Tests / demo without keys: FakePaymentGateway (logs to laravel.log).
        //
        // Switch via SERVICES_PAYMENT_DRIVER=stripe|fake in .env.
        $this->app->bind(PaymentGateway::class, function ($app) {
            $driver = config('services.payment.driver', 'fake');

            if ($driver === 'stripe' && config('services.stripe.secret')) {
                return new StripeConnectGateway(
                    new StripeClient(config('services.stripe.secret'))
                );
            }

            return $app->make(FakePaymentGateway::class);
        });
    }

    public function boot(): void
    {
        //
    }
}
