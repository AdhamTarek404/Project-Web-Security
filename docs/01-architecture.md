# 01 — System Architecture

This doc covers the **architecture deliverable** from the brief:

> *"System architecture, order state machine diagram, and API docs."*

The state-machine diagram has its own doc — see [`02-state-machine.md`](./02-state-machine.md).

---

## 1. The three-actor diagram

```
            ┌─────────────────────────────────────────────────────────────┐
            │                                                             │
            │                   FoodDelivery Platform                     │
            │                                                             │
            │     ┌───────────────┐   ┌───────────────┐   ┌────────────┐ │
            │     │  Restaurants  │   │   Customers   │   │   Riders   │ │
            │     │  (browser)    │   │ (browser/API) │   │ (API/web)  │ │
            │     └──────┬────────┘   └──────┬────────┘   └─────┬──────┘ │
            │            │                   │                  │        │
            │            └──────┐    ┌───────┘                  │        │
            │                   ▼    ▼                          ▼        │
            │            ┌────────────────────────────────────────────┐  │
            │            │           Laravel 12 application           │  │
            │            │                                            │  │
            │            │   routes/api.php  ←  Sanctum tokens        │  │
            │            │   routes/web.php  ←  Session auth          │  │
            │            │                                            │  │
            │            │   app/Http/Controllers (thin)              │  │
            │            │            │                                │  │
            │            │            ▼                                │  │
            │            │   app/Services (business logic)             │  │
            │            │            │                                │  │
            │            │            ▼                                │  │
            │            │   app/Models (Eloquent) → MySQL             │  │
            │            │                                            │  │
            │            │            │                                │  │
            │            │            ├──► app/Events (broadcast) ─► Reverb (WebSocket)
            │            │            └──► app/Jobs   (queue)     ─► Redis / DB queue
            │            │                                            │  │
            │            └────────────────────────────────────────────┘  │
            │                              │           │                 │
            │                              ▼           ▼                 │
            │                   ┌───────────────┐ ┌──────────────┐      │
            │                   │ Google Maps   │ │ Stripe       │      │
            │                   │ Distance API  │ │ Connect API  │      │
            │                   └───────────────┘ └──────────────┘      │
            │                                                             │
            │   ┌─────────────────────────────────────────────────────┐ │
            │   │ Admin (browser)                                     │ │
            │   │   ┌────────────────────────────────────────────────┐│ │
            │   │   │ /admin dashboard | orders | users | restaurants││ │
            │   │   │       | riders | surge tester | control tower  ││ │
            │   │   │                                                ││ │
            │   │   │ The control tower is a Livewire 4 component    ││ │
            │   │   │ that subscribes to Reverb channels and renders ││ │
            │   │   │ a live Leaflet map.                            ││ │
            │   │   └────────────────────────────────────────────────┘│ │
            │   └─────────────────────────────────────────────────────┘ │
            └─────────────────────────────────────────────────────────────┘
```

---

## 2. Layers

The app is split into **seven layers**, each with a clear responsibility.
Read top-to-bottom for "from HTTP to DB":

| Layer | Folder | Purpose | Example |
|---|---|---|---|
| Routes | `routes/{api,web}.php` | Map a URL + method to a controller method. | `POST /api/customer/orders → CustomerOrderController@place` |
| Middleware | `app/Http/Middleware/` | Cross-cutting: auth, role gates, CSRF. | `EnsureUserHasRole` checks the `role:customer` middleware arg. |
| Form Requests | `app/Http/Requests/` | Validate + authorise an incoming request. | `PlaceOrderRequest::rules()` |
| Controllers | `app/Http/Controllers/` | **Thin** — translate HTTP ↔ service. | `CustomerOrderController::place` calls `$placeOrder->handle(...)`. |
| Services | `app/Services/` | All business rules. | `PlaceOrder`, `OrderStateMachine`, `RiderDispatcher`, `SurgePricingEngine`, `PaymentSplitter`. |
| Models | `app/Models/` | Eloquent (table + relations). | `Order::class`, `Rating::class` (polymorphic). |
| Database | `database/migrations/` | Schema. | `2026_05_12_180600_create_orders_table.php` |

Plus two sidecar layers that don't fit the request/response flow:

| Layer | Folder | Purpose |
|---|---|---|
| Events | `app/Events/` | Things we broadcast over WebSockets (`OrderStateChanged`, `RiderLocationUpdated`). |
| Jobs | `app/Jobs/` | Things we run on a queue (`DispatchOrderJob`, `RecalculateSurgePricingJob`, `SendOrderNotificationJob`). |

---

## 3. The two entry points (same code, two clients)

```
┌─────────────────┐                   ┌─────────────────┐
│  Browser user   │                   │  Mobile app     │
│  (session +     │                   │  (Bearer token  │
│  CSRF cookie)   │                   │  via Sanctum)   │
└────────┬────────┘                   └────────┬────────┘
         │                                     │
         ▼                                     ▼
    routes/web.php                       routes/api.php
         │                                     │
         └──────────────────┬──────────────────┘
                            ▼
                   app/Http/Controllers
                            │
                            ▼
                     app/Services/*       ←─ single source of truth
                            │
                            ▼
                       Eloquent models
                            │
                            ▼
                          MySQL
```

The **same `PlaceOrder` service** serves both
`POST /api/customer/orders` (the API) and `POST /restaurants/{r}/order`
(the browser). That's why a feature only has to be implemented and
tested once.

---

## 4. Database schema (overview)

10 base tables + 1 migration adding Stripe Connect ids:

```
users ─────┬─► restaurants (owner_id)            (1:N)
           ├─► riders (user_id, 1:1)
           └─► orders (customer_id, 1:N)

restaurants ─► categories ─► menu_items ─► menu_item_variants

orders ────┬─► order_items ─► menu_items
           ├─► order_status_history (append-only)
           ├─► ratings  (polymorphic → restaurant OR rider)
           └─► riders (rider_id, nullable until dispatched)
```

Full table-by-table breakdown lives in `PROJECT_GUIDE.md` Phase 1. The
key conventions:

| Convention | Why |
|---|---|
| **Money is stored as integer cents** (e.g. `1525` = 15.25 EGP). | Avoids float drift. `0.1 + 0.2 != 0.3` in IEEE 754. |
| **`order_status_history` is append-only.** | Event-sourcing-inspired audit trail. Every state change writes a new row. No `UPDATE`. |
| **GPS is `decimal(10,7)`.** | ~1 cm precision, plenty for routing. |
| **`stripe_account_id` is nullable.** | Account onboarding via Stripe Connect happens out-of-band; the transfer is deferred until the column is set. |

---

## 5. External integrations (all behind interfaces)

Every external integration is bound through the service container. One
`.env` switch selects the implementation. Tests use the fake / offline
fallback so they never hit a paid API.

| Integration | Interface | Real implementation | Dev fallback | Env switch |
|---|---|---|---|---|
| Distance | `App\Services\Geo\DistanceCalculator` | `GoogleMapsDistanceCalculator` (Google Distance Matrix API) | `HaversineDistanceCalculator` | `DISTANCE_DRIVER=google\|haversine` |
| Payment | `App\Services\Payments\PaymentGateway` | `StripeConnectGateway` (Stripe Connect PaymentIntent + Transfers) | `FakePaymentGateway` (logs to laravel.log) | `PAYMENT_DRIVER=stripe\|fake` |
| Broadcasting | Laravel `Broadcasting` contract | `reverb` (Pusher-protocol WebSocket server) | `log` driver | `BROADCAST_CONNECTION=reverb\|log` |
| Queue | Laravel `Queue` contract | `redis` (via predis client) | `database` driver | `QUEUE_CONNECTION=redis\|database` |

Container wiring lives in `app/Providers/AppServiceProvider.php`.

---

## 6. Architecture patterns used

| Pattern | Where | Why |
|---|---|---|
| **Finite State Machine** | `app/Services/Orders/OrderStateMachine.php` + `app/Enums/OrderStatus.php` | The brief literally says *"Finite state machine for order transitions with guards preventing invalid jumps."* |
| **Strategy** | `app/Services/Pricing/SurgePricingStrategy.php` + 3 concrete strategies | The brief says *"Strategy pattern for surge pricing (flat, multiplier, time-based)."* |
| **Polymorphism (Eloquent `morphTo`)** | `app/Models/Rating.php` | A rating belongs to a Restaurant **or** a Rider — single table, two parent types. |
| **Event sourcing** (inspired) | `app/Models/OrderStatusHistory.php` + the FSM | Append-only history of every state change. |
| **Dependency injection via interfaces** | `DistanceCalculator`, `PaymentGateway` | Lets us swap Google/Stripe in and out with one env line. |
| **Single Action Service** | `PlaceOrder`, `RiderDispatcher`, `SurgePricingEngine` | One service class = one verb. Easier to test than fat models. |
| **Form Request validation** | `app/Http/Requests/**` | Validation lives outside the controller, kept tiny. |
| **Policies for authorisation** | `app/Policies/RestaurantPolicy.php` | Ownership checks expressed declaratively. |
| **Optimistic concurrency control** | `DB::transaction(fn() => Rider::lockForUpdate())` in `RiderDispatcher` | Two concurrent dispatches can't pick the same rider. Tested with 50 concurrent orders. |

---

## 7. Request lifecycle (a concrete example)

What happens when a customer clicks **Place order** in the browser:

```
1. Browser POST /restaurants/3/order
   body: items[], delivery_address, delivery_latitude, delivery_longitude

2. Laravel router (routes/web.php)
     ├─► auth middleware  → ensures the user is logged in
     ├─► WebOrderController::place(...)
     │     ├─ validates the cart inline
     │     ├─ builds a normalized payload
     │     └─ calls PlaceOrder service →

3. PlaceOrder::handle (app/Services/Orders/PlaceOrder.php)
     ├─ asks SurgePricingEngine.compute(SurgeContext)  →  1.25
     ├─ refetches menu items from DB (never trusts client prices)
     ├─ DB::transaction:
     │     ├─ for each line: snapshot the unit price
     │     ├─ PriceCalculator.compute() builds the money breakdown
     │     ├─ Order::create(...)  →  inserts the row
     │     ├─ Order->items()->createMany(lines)
     │     ├─ PaymentSplitter.splitFor(order)  →  PaymentSplit
     │     ├─ PaymentGateway.chargeAndSplit(order, split)  →  pi_xxx
     │     └─ OrderStateMachine.initialize(order, customer)
     │           ├─ orders.status = 'placed'
     │           ├─ order_status_history row appended
     │           ├─ SendOrderNotificationJob.dispatch(...)
     │           └─ RecalculateSurgePricingJob.dispatch(...)
     └─ returns the saved order

4. WebOrderController redirects to /orders/{order} with a flash message.

5. The two dispatched jobs are picked up by `php artisan queue:work`:
     ├─ SendOrderNotificationJob → logs the notification (or sends SMS/email/push in production)
     └─ RecalculateSurgePricingJob → recomputes surge for the cache
```

If the controller had been the **API** (`POST /api/customer/orders`), the
flow from step 3 onward is byte-for-byte identical. Only the entry point
changes.

---

## 8. Folder map

A copy of the structure section from `PROJECT_GUIDE.md`, kept short:

```
app/
├── Enums/                 (1 file — OrderStatus enum)
├── Events/                (2 files — broadcastable order/rider events)
├── Http/
│   ├── Controllers/       (API + Web controllers, role-grouped)
│   ├── Middleware/        (EnsureUserHasRole)
│   └── Requests/          (Form Request validators)
├── Jobs/                  (3 ShouldQueue jobs)
├── Livewire/              (AdminControlTower)
├── Models/                (10 Eloquent models)
├── Policies/              (RestaurantPolicy)
├── Providers/             (AppServiceProvider — DI bindings)
└── Services/
    ├── Dispatch/          (RiderDispatcher)
    ├── Geo/               (DistanceCalculator interface + 2 impls)
    ├── Orders/            (PlaceOrder, OrderStateMachine, PriceCalculator)
    ├── Payments/          (PaymentGateway interface + 2 impls + Splitter)
    └── Pricing/           (SurgePricingEngine + Strategy + Context)

database/migrations/       (13 migrations)
database/seeders/          (DatabaseSeeder + DemoSeeder)

resources/views/           (Blade UI — Tailwind 4 + a touch of Alpine.js)
routes/api.php             (Sanctum-protected JSON)
routes/web.php             (Session-protected browser)
tests/Feature/             (52 tests, 133 assertions)
config/services.php        (Stripe + Google Maps + driver switches)
.env                       (per-environment toggles)
```

---

## 9. Design decisions summary

| Decision | Trade-off |
|---|---|
| **MySQL** in dev (XAMPP), in-memory **SQLite** in tests | MySQL matches production; SQLite makes tests run in 2 seconds. |
| **Database queue driver** default | No Redis install needed for the demo; flip `QUEUE_CONNECTION=redis` for production. The queued *code* is identical. |
| **Reverb** instead of paid Pusher | Reverb is first-party Laravel and speaks the Pusher protocol — `laravel-echo` doesn't know the difference. |
| **Server-rendered Blade** instead of SPA | The brief never mentions a SPA. Server-rendered is faster to build, simpler to ship, indexable, no JS framework lock-in. Alpine.js handles small bits of interactivity (e.g. the cart sidebar). |
| **Sanctum tokens** for the API, **session auth** for the browser | Same `User` model; auth guard differs per entry point. |
| **Integer cents for money** | Eliminates float drift. Tested in `PaymentSplitTest`. |
| **One Service class per verb** | Easier to test in isolation than fat Eloquent models. Every test in `tests/Feature/Orders/PlaceOrderTest.php` exercises `PlaceOrder` directly. |

Continue with [`02-state-machine.md`](./02-state-machine.md) for the FSM
diagram, or [`03-api.md`](./03-api.md) for the API reference.
