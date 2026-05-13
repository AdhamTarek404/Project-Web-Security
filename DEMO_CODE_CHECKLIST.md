# Demo video — which code files to show (detailed paths)

**Project root (use this prefix for every path below):**

`x:\WebSec Project\`

Example: the state machine file is **`x:\WebSec Project\app\Services\Orders\OrderStateMachine.php`**.

While recording: **pause on each file** for a few seconds. Use **Ctrl+P** (Quick Open) in VS Code / Cursor, paste the path after the root, or navigate the tree on the left. Say the short line in the **Say this** column; you do not need to read code aloud.

---

## Folder map (where categories live)

| Folder under `x:\WebSec Project\` | What lives there |
|-----------------------------------|------------------|
| `app\Enums\` | Order status enum + transition rules |
| `app\Events\` | Broadcast events (order + rider GPS) |
| `app\Exceptions\` | e.g. invalid order transition |
| `app\Http\Controllers\Auth\` | Sanctum token login |
| `app\Http\Controllers\Customer\` | Customer API + ratings |
| `app\Http\Controllers\Owner\` | Restaurant owner / menu management |
| `app\Http\Controllers\Web\` | Session-based browser pages |
| `app\Jobs\` | Queued work: dispatch, surge recalc, notifications |
| `app\Livewire\` | Admin control tower component |
| `app\Models\` | Eloquent models (Order, Rating, …) |
| `app\Services\Dispatch\` | Nearest-rider assignment |
| `app\Services\Geo\` | Distance: Haversine vs Google Maps API |
| `app\Services\Orders\` | Place order, state machine, price math |
| `app\Services\Payments\` | Split + Stripe Connect gateway |
| `app\Services\Pricing\` | Surge engine + strategy classes |
| `database\migrations\` | Table shapes (including `order_status_history`) |
| `resources\views\admin\` | Admin Blade pages (dashboard, control tower shell) |
| `resources\views\livewire\` | Livewire view for the map component |
| `routes\` | `web.php` (browser), `api.php` (Sanctum JSON) |
| `tests\Feature\` | PHPUnit feature tests |

---

## Quick tab list (open before you hit Record)

Open these in your IDE so you can **click tabs** instead of searching during the take:

1. `x:\WebSec Project\app\Enums\OrderStatus.php`
2. `x:\WebSec Project\app\Services\Orders\OrderStateMachine.php`
3. `x:\WebSec Project\app\Services\Orders\PlaceOrder.php`
4. `x:\WebSec Project\app\Services\Dispatch\RiderDispatcher.php`
5. `x:\WebSec Project\app\Jobs\DispatchOrderJob.php`
6. `x:\WebSec Project\app\Services\Pricing\SurgePricingEngine.php`
7. `x:\WebSec Project\app\Services\Payments\PaymentSplitter.php`
8. `x:\WebSec Project\app\Events\OrderStateChanged.php`
9. `x:\WebSec Project\app\Events\RiderLocationUpdated.php`
10. `x:\WebSec Project\app\Livewire\AdminControlTower.php`
11. `x:\WebSec Project\routes\api.php`
12. `x:\WebSec Project\tests\Feature\Orders\OrderStateMachineTest.php`

---

## Requirement → exact file path → where to point the scroll bar

### Functionality (project sheet)

#### Restaurant menus (categories, items, variants, availability)

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\app\Models\Restaurant.php` | class `Restaurant` — relations to categories/items | “Restaurant is the menu root entity.” |
| `x:\WebSec Project\app\Models\Category.php` | belongs to restaurant | “Categories group menu items.” |
| `x:\WebSec Project\app\Models\MenuItem.php` | item fields + availability | “Items can be toggled off stock.” |
| `x:\WebSec Project\app\Models\MenuItemVariant.php` | size/price variants | “Variants hang off each line item.” |
| `x:\WebSec Project\app\Http\Controllers\Owner\RestaurantController.php` | `Owner\` folder = owner-only HTTP | “Owners manage stores through these controllers.” |
| `x:\WebSec Project\app\Http\Controllers\Owner\CategoryController.php` | CRUD for categories | |
| `x:\WebSec Project\app\Http\Controllers\Owner\MenuItemController.php` | CRUD + availability toggle endpoints | “Availability toggles hit the owner API.” |
| `x:\WebSec Project\app\Http\Controllers\Owner\MenuItemVariantController.php` | variant CRUD | |

#### Customer order flow + finite state machine

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\app\Enums\OrderStatus.php` | enum cases + `allowedNextStates()` (or equivalent) | “Legal next states live in one enum — no random string statuses.” |
| `x:\WebSec Project\app\Services\Orders\OrderStateMachine.php` | class `OrderStateMachine` · methods `initialize`, `transition` | “All status updates go through this service only.” |
| `x:\WebSec Project\app\Services\Orders\PlaceOrder.php` | `handle` (or main entry) | “Placing an order runs in a transaction: prices, surge, payment, then FSM init.” |
| `x:\WebSec Project\app\Exceptions\InvalidOrderTransitionException.php` | exception class | “Illegal jumps throw here.” |

#### Rider dispatch (nearest rider, GPS, map story)

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\app\Services\Dispatch\RiderDispatcher.php` | method `dispatch(Order $order)` — comments about `lockForUpdate` | “Closest rider wins; DB lock prevents double assign.” |
| `x:\WebSec Project\app\Jobs\DispatchOrderJob.php` | class implements `ShouldQueue` | “Preparing an order queues this job.” |
| `x:\WebSec Project\app\Services\Geo\DistanceCalculator.php` | interface | “Distance is pluggable.” |
| `x:\WebSec Project\app\Services\Geo\HaversineDistanceCalculator.php` | concrete class | “Default math without any API calls.” |
| `x:\WebSec Project\app\Services\Geo\GoogleMapsDistanceCalculator.php` | HTTP to Distance Matrix | “Flip config to use Google for prod distances.” |
| `x:\WebSec Project\app\Providers\AppServiceProvider.php` | binding `DistanceCalculator` to implementation | “One binding line swaps Haversine vs Google.” |

#### Surge pricing (demand, weather, time — strategy pattern)

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\app\Services\Pricing\SurgePricingStrategy.php` | interface | “Each rule type implements this contract.” |
| `x:\WebSec Project\app\Services\Pricing\FlatSurgeStrategy.php` | strategy class | |
| `x:\WebSec Project\app\Services\Pricing\MultiplierSurgeStrategy.php` | demand/supply style multiplier | |
| `x:\WebSec Project\app\Services\Pricing\TimeBasedSurgeStrategy.php` | rush hour / weather | |
| `x:\WebSec Project\app\Services\Pricing\SurgeContext.php` | DTO fields | “Context bundles orders, riders, weather, hour.” |
| `x:\WebSec Project\app\Services\Pricing\SurgePricingEngine.php` | `compute` (or similar) + cap | “Engine runs strategies and caps the multiplier.” |

#### Ratings (restaurant + rider)

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\app\Models\Rating.php` | `morphTo` rateable | “One rating model attaches to restaurant or rider.” |
| `x:\WebSec Project\app\Http\Controllers\Customer\RatingController.php` | store / validate | “HTTP entry for submitting stars after delivery.” |

#### Payouts / revenue (math + dashboards read DB columns)

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\app\Services\Orders\PriceCalculator.php` | cents-safe breakdown | “Line items, fees, commission — integer money.” |
| `x:\WebSec Project\app\Services\Payments\PaymentSplit.php` | value object / DTO | “Split amounts for platform, restaurant, rider.” |
| `x:\WebSec Project\app\Services\Payments\PaymentSplitter.php` | `splitFor` (or equivalent) | “Reads order columns and builds the three-way split.” |

---

### Implementation (tech stack)

#### Real-time (broadcast + admin map)

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\app\Events\OrderStateChanged.php` | `implements ShouldBroadcast` · channel names | “Order status pushes over websockets.” |
| `x:\WebSec Project\app\Events\RiderLocationUpdated.php` | `implements ShouldBroadcast` | “Rider GPS pings broadcast to the map.” |
| `x:\WebSec Project\app\Livewire\AdminControlTower.php` | Livewire component + Echo listeners | “Admin tower subscribes here.” |
| `x:\WebSec Project\resources\views\admin\control-tower.blade.php` | `@livewire` + layout | “Browser route wraps the component.” |
| `x:\WebSec Project\resources\views\livewire\admin-control-tower.blade.php` | Leaflet / map markup | “Map UI lives in this Blade partial.” |

#### Queues (Redis or database driver)

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\app\Jobs\DispatchOrderJob.php` | `ShouldQueue` | |
| `x:\WebSec Project\app\Jobs\RecalculateSurgePricingJob.php` | `ShouldQueue` | |
| `x:\WebSec Project\app\Jobs\SendOrderNotificationJob.php` | `ShouldQueue` | “Three background jobs; worker drains the queue.” |

#### Stripe Connect–style payments

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\app\Services\Payments\PaymentGateway.php` | interface | |
| `x:\WebSec Project\app\Services\Payments\FakePaymentGateway.php` | dev default | “Fake driver for local demo without Stripe.” |
| `x:\WebSec Project\app\Services\Payments\StripeConnectGateway.php` | PaymentIntent / transfers | “Real Stripe when `PAYMENT_DRIVER=stripe`.” |

#### Sanctum API (mobile / token clients)

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\routes\api.php` | `auth:sanctum` groups · route list | “All protected JSON routes are here.” |
| `x:\WebSec Project\app\Http\Controllers\Auth\AuthController.php` | token issuance (login) | “Login returns a Sanctum personal access token.” |
| `x:\WebSec Project\app\Models\User.php` | `HasApiTokens` trait | “User model issues API tokens.” |

---

### Code quality (audit log + tests)

| Full path | What to flash on screen | Say this |
|-----------|-------------------------|----------|
| `x:\WebSec Project\app\Models\OrderStatusHistory.php` | append-only style fields | “Every transition gets a history row.” |
| `x:\WebSec Project\app\Services\Orders\OrderStateMachine.php` | where `OrderStatusHistory` is written | “FSM writes status + history in one transaction.” |
| `x:\WebSec Project\tests\Feature\Orders\OrderStateMachineTest.php` | illegal transition test | “Proves you cannot jump delivered → preparing.” |
| `x:\WebSec Project\tests\Feature\Dispatch\RiderDispatchTest.php` | nearest rider / concurrency | |
| `x:\WebSec Project\tests\Feature\Pricing\SurgePricingTest.php` | cap + rollback | |
| `x:\WebSec Project\tests\Feature\Payments\PaymentSplitTest.php` | commission / split math | |
| `x:\WebSec Project\tests\Feature\Stress\OrderVolumeSpikeTest.php` | 50 orders | |
| `x:\WebSec Project\tests\Feature\Realtime\BroadcastingTest.php` | broadcast assertions | |

---

## Browser URLs (tie the story to the address bar)

Recorded with **`php artisan serve`** → base URL **`http://127.0.0.1:8000`**.

| Role | Path | Notes |
|------|------|--------|
| Public home | `/` | Restaurant list |
| Login | `/login` | Use seeded accounts from `PROJECT_GUIDE.md` § 0.4.B |
| Customer dashboard | `/dashboard` or role redirect | After login as customer |
| Place flow | `/restaurants/{slug}` | Menu + cart |
| Order detail | `/orders/{id}` | Status badge + audit trail + ratings |
| Owner dashboard | `/dashboards/owner` | Confirm / prepare |
| Rider dashboard | `/dashboards/rider` | GPS + picked up / delivered |
| Admin KPIs | `/admin/dashboard` | |
| Surge playground | `/admin/surge` | Sliders |
| Control tower map | `/admin/control-tower` | Needs Reverb if you want live moves |

Route definitions: **`x:\WebSec Project\routes\web.php`** (browser) · **`x:\WebSec Project\routes\api.php`** (JSON).

---

## Terminal (from project root)

```powershell
cd "x:\WebSec Project"
php artisan test
```

| Sheet scenario | Command |
|----------------|---------|
| Full suite | `php artisan test` |
| 50 concurrent orders | `php artisan test --filter=OrderVolumeSpikeTest` |
| Illegal state transition | `php artisan test --filter="invalid_transition"` |
| Surge cap + rollback | `php artisan test --filter=SurgePricingTest` |
| Payment split / commission rates | `php artisan test --filter=PaymentSplitTest` |

---

## Single source of truth

Longer matrix with migration names and extra detail: **`x:\WebSec Project\PROJECT_GUIDE.md`** → section **“Requirements — where it's coded — how to test.”**
