# Demo video — which code files to show

Use this while recording: **pause on each file for a few seconds** and say one short line (the “say this” column). You do not need to read the code aloud.

**Paths** are relative to the project root: `x:\WebSec Project\`.

---

## Quick index (open these tabs before you hit Record)

| Topic | Primary files |
|--------|----------------|
| State machine | `app/Enums/OrderStatus.php`, `app/Services/Orders/OrderStateMachine.php` |
| Audit trail | `app/Models/OrderStatusHistory.php` |
| Ordering + pricing math | `app/Services/Orders/PlaceOrder.php`, `app/Services/Orders/PriceCalculator.php` |
| Surge engine + strategies | `app/Services/Pricing/SurgePricingEngine.php`, `SurgeContext.php`, `FlatSurgeStrategy.php`, `MultiplierSurgeStrategy.php`, `TimeBasedSurgeStrategy.php` |
| Rider dispatch | `app/Services/Dispatch/RiderDispatcher.php`, `app/Jobs/DispatchOrderJob.php` |
| Distance / maps | `app/Services/Geo/DistanceCalculator.php`, `HaversineDistanceCalculator.php`, `GoogleMapsDistanceCalculator.php` |
| Real-time | `app/Events/OrderStateChanged.php`, `app/Events/RiderLocationUpdated.php`, `app/Livewire/AdminControlTower.php` |
| Queued jobs | `app/Jobs/DispatchOrderJob.php`, `RecalculateSurgePricingJob.php`, `SendOrderNotificationJob.php` |
| Payments / split | `app/Services/Payments/PaymentSplitter.php`, `PaymentSplit.php`, `StripeConnectGateway.php`, `FakePaymentGateway.php` |
| Ratings | `app/Models/Rating.php`, `app/Http/Controllers/Customer/RatingController.php` |
| Sanctum API surface | `routes/api.php`, `app/Http/Controllers/Auth/AuthController.php` |
| Admin map UI | `resources/views/admin/control-tower.blade.php`, `resources/views/livewire/admin-control-tower.blade.php` |

---

## Requirement → file → what to say

### Functionality (project sheet)

| Requirement | Show in IDE | Say this (one line) |
|-------------|-------------|----------------------|
| Restaurant menus: categories, items, variants, availability | `app/Models/{Restaurant,Category,MenuItem,MenuItemVariant}.php` · `app/Http/Controllers/Owner/{Restaurant,Category,MenuItem,MenuItemVariant}Controller.php` | “Menu data is normalized in these models; owners mutate menu through the Owner controllers and API.” |
| Customer order flow + FSM statuses | `app/Enums/OrderStatus.php` · `app/Services/Orders/OrderStateMachine.php` · `PlaceOrder.php` | “Statuses and legal transitions are defined in the enum; the state machine is the only place status changes.” |
| Rider GPS dispatch, nearest rider | `app/Services/Dispatch/RiderDispatcher.php` · `app/Jobs/DispatchOrderJob.php` | “A queued job runs the dispatcher; it picks the closest available rider and uses row locks to avoid double booking.” |
| Surge pricing (demand, weather, time) | `SurgePricingEngine.php` · `SurgeContext.php` · the three `*SurgeStrategy.php` files | “The engine builds context, runs strategies, and applies a cap on the multiplier.” |
| Ratings for restaurant + rider | `app/Models/Rating.php` · `app/Http/Controllers/Customer/RatingController.php` | “Ratings are polymorphic so one model attaches stars to either a restaurant or a rider.” |
| Payout / revenue views | `app/Services/Payments/PaymentSplitter.php` · `PriceCalculator.php` | “Commission and per-party amounts are computed here and stored on the order for dashboards.” |

### Implementation (tech stack)

| Requirement | Show in IDE | Say this (one line) |
|-------------|-------------|----------------------|
| Real-time order + rider location | `OrderStateChanged.php` · `RiderLocationUpdated.php` · `AdminControlTower.php` | “These events broadcast; Livewire + Echo subscribe on the control tower.” |
| Google Maps distance (optional live API) | `DistanceCalculator.php` · `GoogleMapsDistanceCalculator.php` · `HaversineDistanceCalculator.php` | “Distance is behind an interface; we swap Haversine vs Google via config.” |
| Redis / database queues for jobs | `DispatchOrderJob.php` · `RecalculateSurgePricingJob.php` · `SendOrderNotificationJob.php` | “These jobs implement `ShouldQueue`; the worker processes them asynchronously.” |
| Stripe Connect–style split | `StripeConnectGateway.php` · `PaymentGateway.php` · `FakePaymentGateway.php` | “Payment gateway is swappable; Stripe path does real Connect-style charges when configured.” |
| Livewire admin control tower | `AdminControlTower.php` · `admin/control-tower.blade.php` · `livewire/admin-control-tower.blade.php` | “The admin map is a Livewire component backed by the broadcast events.” |
| Sanctum for customer/rider API | `routes/api.php` · `app/Models/User.php` (HasApiTokens) · `app/Http/Controllers/Auth/AuthController.php` | “REST routes are in `api.php`; clients use Sanctum bearer tokens from login.” |

### Code quality (patterns + audit)

| Requirement | Show in IDE | Say this (one line) |
|-------------|-------------|----------------------|
| FSM guards (no illegal jumps) | `OrderStatus.php` (`allowedNextStates`) · `OrderStateMachine.php` | “Invalid transitions throw; the enum defines the allowed graph.” |
| Strategy pattern for surge | `SurgePricingStrategy.php` (interface) · `Flat*`, `Multiplier*`, `TimeBased*` strategies · `SurgePricingEngine.php` | “Each pricing rule is a strategy; the engine composes them.” |
| Order history / event-style log | `OrderStatusHistory.php` · `OrderStateMachine.php` (where history rows are written) | “Every transition appends a row with actor and timestamp.” |
| Feature tests | `tests/Feature/Dispatch/RiderDispatchTest.php` · `Pricing/SurgePricingTest.php` · `Payments/PaymentSplitTest.php` · `Orders/OrderStateMachineTest.php` | “These tests lock in dispatch, surge, splits, and illegal transitions.” |

---

## Terminal snippets to record (proof, not code browsing)

```powershell
cd "x:\WebSec Project"
php artisan test
```

| Sheet scenario | Filter |
|----------------|--------|
| 50 concurrent orders | `php artisan test --filter=OrderVolumeSpikeTest` |
| Illegal state transition | `php artisan test --filter="invalid_transition"` |
| Surge cap + rollback | `php artisan test --filter=SurgePricingTest` |
| Payment split / commission rates | `php artisan test --filter=PaymentSplitTest` |

---

## Single source of truth

The authoritative “requirement → file → test” matrix with exact class names lives in **`PROJECT_GUIDE.md`**, section **“Requirements — where it's coded — how to test.”**
