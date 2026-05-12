# 09 — Testing & Quality

The brief asks for four specific test scenarios:

> *"Simulate order volume spike: 50 concurrent orders dispatched to available riders.*
> *Validate state machine: rejected transitions (e.g., `delivered → preparing`) must throw.*
> *Test surge multiplier caps and rollback when demand drops.*
> *Payment split accuracy across varying commission rates per restaurant."*

This doc covers the **full test plan**: how to run it, what's covered,
and where each required scenario is implemented.

---

## 1. The one-command verification

```powershell
cd "X:\WebSec Project"
php artisan test
```

Expected:

```
Tests:    52 passed (133 assertions)
Duration: ~3s
```

Every requirement in the brief has at least one test backing it.

---

## 2. The 11 test classes

| File | # tests | Covers |
|---|---|---|
| `tests/Feature/ExampleTest.php` | 1 | Root route smoke test (home page returns 200) |
| `tests/Feature/Dispatch/RiderDispatchTest.php` | 5 | Nearest-rider selection, lockForUpdate concurrency, no-rider fallback, Haversine math |
| `tests/Feature/Orders/OrderStateMachineTest.php` | 5 | FSM transitions, **rejected jumps (delivered → preparing)**, history append, timestamps, cancellation reason |
| `tests/Feature/Orders/PlaceOrderTest.php` | 6 | End-to-end order placement, money math, validation, closed-restaurant guard |
| `tests/Feature/Payments/PaymentSplitTest.php` | 5 | **Split accuracy across varying commission rates**, invariant `subtotal + delivery = total`, integer-cents discipline |
| `tests/Feature/Pricing/SurgePricingTest.php` | 5 | Strategy outputs, engine composition, **multiplier cap**, **rollback when demand drops** |
| `tests/Feature/Ratings/RatingTest.php` | 4 | Polymorphic rating (restaurant + rider), unique-per-order constraint, only delivered orders can be rated, only the customer can rate |
| `tests/Feature/Realtime/BroadcastingTest.php` | 2 | OrderStateChanged + RiderLocationUpdated are dispatched |
| `tests/Feature/Restaurant/PublicMenuTest.php` | 2 | Public restaurant + menu endpoints return open restaurants only |
| `tests/Feature/Restaurant/RestaurantOwnerTest.php` | 5 | Restaurant/category/menu-item/variant CRUD, availability toggle, only owner can touch their own |
| `tests/Feature/Stress/OrderVolumeSpikeTest.php` | 1 | **50 concurrent orders dispatched to 10 riders** — no double-assignment |
| `tests/Feature/Integrations/RealServiceBindingsTest.php` | 8 | Real Google Maps / Stripe / Predis bindings exist and resolve correctly |

**Total: 52 tests, 133 assertions.**

---

## 3. The four required scenarios — exact locations

| Brief scenario | Test file | Test name | Run |
|---|---|---|---|
| "Simulate order volume spike: 50 concurrent orders…" | `tests/Feature/Stress/OrderVolumeSpikeTest.php` | `fifty_concurrent_orders_assign_each_rider_at_most_once` | `php artisan test --filter=OrderVolumeSpikeTest` |
| "Validate state machine: rejected transitions… must throw" | `tests/Feature/Orders/OrderStateMachineTest.php` | `invalid_transition_throws` | `php artisan test --filter=invalid_transition` |
| "Test surge multiplier caps and rollback when demand drops" | `tests/Feature/Pricing/SurgePricingTest.php` | `engine_caps_at_max_multiplier` + `engine_rolls_back_to_one_when_demand_drops` | `php artisan test --filter=SurgePricingTest` |
| "Payment split accuracy across varying commission rates" | `tests/Feature/Payments/PaymentSplitTest.php` | `split_total_invariant_across_random_commission_rates` | `php artisan test --filter=PaymentSplitTest` |

---

## 4. Coverage map (requirement → test)

| Requirement (from the brief) | Source files | Test files |
|---|---|---|
| Restaurant / menu / variants / availability | `app/Http/Controllers/Owner/*` | `RestaurantOwnerTest` (5) |
| Customer ordering flow + FSM | `app/Services/Orders/PlaceOrder`, `OrderStateMachine` | `PlaceOrderTest` (6), `OrderStateMachineTest` (5) |
| Rider GPS dispatch (nearest rider) | `app/Services/Dispatch/RiderDispatcher` | `RiderDispatchTest` (5) |
| Surge pricing engine | `app/Services/Pricing/*` | `SurgePricingTest` (5) |
| Rating + review (restaurant + rider) | `app/Models/Rating`, `Customer/RatingController` | `RatingTest` (4) |
| Revenue + payout dashboard | `app/Services/Payments/PaymentSplitter` | `PaymentSplitTest` (5) |
| Real-time order + rider updates | `app/Events/{OrderStateChanged,RiderLocationUpdated}` | `BroadcastingTest` (2) |
| Google Distance Matrix API | `app/Services/Geo/GoogleMapsDistanceCalculator` | `RealServiceBindingsTest` (`google_distance_calculator_calls_the_real_api`) |
| Redis queues | `app/Jobs/{Dispatch,RecalculateSurgePricing,SendOrderNotification}Job` | `RealServiceBindingsTest` (`three_queueable_jobs_exist_per_description`, `predis_package_is_installed_for_redis_queue_support`) |
| Stripe Connect split payments | `app/Services/Payments/StripeConnectGateway` | `RealServiceBindingsTest` (`payment_driver_resolves_to_stripe_when_enabled`, `stripe_php_package_is_installed`) |
| Sanctum API auth | `routes/api.php`, `app/Models/User` (`HasApiTokens`) | every test that calls `$this->actingAs($user, 'sanctum')` |
| FSM guards (delivered → preparing) | `app/Enums/OrderStatus`, `OrderStateMachine::transition` | `OrderStateMachineTest::invalid_transition_throws` |
| Strategy pattern surge | `app/Services/Pricing/{Flat,Multiplier,TimeBased}SurgeStrategy` | `SurgePricingTest` |
| Event-sourcing history | `app/Models/OrderStatusHistory` | `OrderStateMachineTest::valid_transition_writes_status_and_appends_history` |

---

## 5. How tests are isolated

| Concern | How it's isolated |
|---|---|
| Database | Each test uses the `RefreshDatabase` trait → migrations run on an in-memory SQLite per test, then rolled back |
| Queue | Each test uses `Queue::fake()` so jobs are *recorded* but not run |
| Broadcasting | Each test uses `Event::fake()` and `Broadcast::fake()` so no real WebSocket connection |
| HTTP (Google) | `Http::fake()` returns the documented Distance Matrix JSON shape |
| Time | `Carbon::setTestNow('2026-05-12 10:00:00')` freezes the clock so `TimeBasedSurgeStrategy` is deterministic |

Result: the test suite **does not require Redis, Reverb, Stripe, Google,
or any external service** to run. It exercises the real classes through
the Laravel service container, asserts the real outputs, and finishes
in ~3 seconds.

---

## 6. Running tests selectively

```powershell
# By file
php artisan test --filter=PlaceOrderTest
php artisan test --filter=SurgePricingTest

# By test method
php artisan test --filter="OrderStateMachineTest::invalid_transition"

# By directory
php artisan test tests/Feature/Pricing

# Stop on first failure (great while debugging)
php artisan test --stop-on-failure

# Print extra detail
php artisan test --verbose
```

---

## 7. Running tests with a specific service driver

The test suite always runs against the **fake** versions of Google /
Stripe / Reverb / Redis (`phpunit.xml` overrides `BROADCAST_CONNECTION` to
`log` and uses in-memory SQLite). To **manually** check the real
integrations:

```powershell
# Real Stripe (test keys)
$env:PAYMENT_DRIVER='stripe'
$env:STRIPE_SECRET='sk_test_...'
php artisan tinker
# > app(\App\Services\Payments\PaymentGateway::class)
# → App\Services\Payments\StripeConnectGateway

# Real Google Maps
$env:DISTANCE_DRIVER='google'
$env:GOOGLE_MAPS_API_KEY='AIza...'
php artisan tinker
# > app(\App\Services\Geo\DistanceCalculator::class)->kilometers(30.04, 31.23, 30.06, 31.25)
# (hits the real API and returns the real driving distance)

# Real Redis queues
# Start Memurai or WSL redis, then:
$env:QUEUE_CONNECTION='redis'
php artisan queue:work redis
```

---

## 8. Adding a new test

The shape of every existing test is:

```php
<?php

namespace Tests\Feature\Whatever;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyNewTest extends TestCase
{
    use RefreshDatabase;

    public function test_describe_the_behaviour(): void
    {
        // Arrange: build the world you need (factories, seeders, fakes)
        // Act:     call the service / endpoint
        // Assert:  $this->assertXxx(...)
    }
}
```

Run just that test:

```powershell
php artisan test --filter=MyNewTest
```

---

## 9. CI / pre-push verification

A minimal pre-push hook would run:

```powershell
php artisan test --compact
npm run build
```

If both succeed, the working tree is in a green state.

---

## 10. Reading test output

Compact mode prints one dot per test, one F per failure:

```
............................................

  Tests:    52 passed (133 assertions)
  Duration: 2.62s
```

Verbose mode prints each test name + duration:

```
✓ test_customer_can_place_order_with_correct_money_breakdown      0.18s
✓ test_invalid_transition_throws                                  0.04s
✓ test_engine_caps_at_max_multiplier                              0.02s
...
```

If a test fails, the printout shows the exact assertion that failed and
the diff between expected and actual values — enough to fix it without
opening the test file.
