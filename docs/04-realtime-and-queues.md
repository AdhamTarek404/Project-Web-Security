# 04 — Real-time Architecture (Pusher Channels + Redis Queues)

The brief asks for:

> *"Real-time architecture explanation (Pusher channels and Redis queues)."*

This doc explains both pieces and how they fit together.

---

## 1. Two completely separate channels of "non-request" work

When a customer places an order, only the bare minimum happens inside
the HTTP request. The slow / fan-out / push work is delegated to two
different infrastructure pieces:

| Concern | Tech | What flows over it |
|---|---|---|
| **Push something to a connected client** (browser, mobile app) | **Pusher channels** — implemented by **Laravel Reverb**, a Pusher-protocol WebSocket server | `OrderStateChanged`, `RiderLocationUpdated` |
| **Run something in the background after the request returns** | **Redis queues** (Predis client) | `DispatchOrderJob`, `RecalculateSurgePricingJob`, `SendOrderNotificationJob` |

The HTTP request itself stays fast (<100 ms typical) because it just
**fires** events and **dispatches** jobs — both are basically writes to
the respective bus.

```
┌────────────────────┐
│  HTTP request      │
│  POST /orders      │
└──────────┬─────────┘
           │  responds immediately
           │
           ├──► event(...)  ─► Reverb  ─► WebSocket clients
           │                    (Pusher protocol)
           │
           └──► dispatch(...) ─► Redis  ─► queue worker (php artisan queue:work)
                                  (Predis)
```

---

## 2. Pusher channels (Laravel Reverb)

### Why Reverb instead of pusher.com?

Reverb is Laravel's first-party WebSocket server. It **speaks the Pusher
protocol**, so the client SDK (`laravel-echo` + `pusher-js`) doesn't
know the difference. We get the exact protocol the brief names without
needing a Pusher account.

### The two broadcastable events

| Event | Channel | Fired when | Payload |
|---|---|---|---|
| `App\Events\OrderStateChanged` | `private-order.{orderId}` and `private-restaurant.{restaurantId}` | Every FSM transition (placed → ... → delivered/cancelled) | `{ order_id, from, to, actor_type, actor_id, at }` |
| `App\Events\RiderLocationUpdated` | `private-rider.{riderId}` and `presence-control-tower` | Every rider GPS ping | `{ rider_id, lat, lng, at }` |

Both implement `Illuminate\Contracts\Broadcasting\ShouldBroadcast`. The
moment they are dispatched in PHP, Reverb gets the payload and pushes it
out to every subscribed client.

### Client wiring

`resources/js/echo.js` configures Laravel Echo to talk to Reverb:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key:    import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws'],
});
```

Then in a Livewire component or a Blade view:

```javascript
window.Echo
    .private(`order.${orderId}`)
    .listen('.OrderStateChanged', payload => {
        updateStatusBadge(payload.to);
        renderTimelineRow(payload);
    });
```

The dot prefix in `.OrderStateChanged` means "match the broadcast-as name"
— see the `broadcastAs()` method on the event class.

### The admin control tower

`app/Livewire/AdminControlTower.php` + `resources/views/admin/control-tower.blade.php`
together form the live map. It subscribes to:

- `presence-control-tower` → joined by every on-duty rider; pushes their GPS pings.
- `private-orders` → broadcasts every order transition.

The Leaflet map markers move without any page reload.

### Auth on private/presence channels

`routes/channels.php` declares who can subscribe to what:

| Channel pattern | Allowed | Why |
|---|---|---|
| `order.{orderId}` | the customer who placed the order, the restaurant owner, the assigned rider, any admin | only stakeholders watch a specific order |
| `restaurant.{restaurantId}` | the restaurant owner, admins | new-order alerts |
| `rider.{riderId}` | the rider, admins | self GPS + admin tracking |
| `control-tower` (presence) | admins only | dashboard map |

Sanctum tokens or session cookies are used to authenticate the WebSocket
handshake — same auth as HTTP.

### To enable live broadcasting locally

```powershell
# 1) .env
BROADCAST_CONNECTION=reverb

# 2) Build the JS bundle once so the Echo client is ready
npm run build

# 3) Start Reverb (terminal A)
php artisan reverb:start
# → "Reverb server started on 0.0.0.0:8080."

# 4) Start the web app (terminal B)
php artisan serve

# 5) Open /admin/control-tower — markers move when a rider POSTs /api/rider/location
```

### Without Reverb (test / demo fallback)

`.env`: `BROADCAST_CONNECTION=log`. Events still fire, but they go to
`storage/logs/laravel.log` instead of a WebSocket. The PHP test suite uses
this driver — see `phpunit.xml`.

---

## 3. Redis queues

### The three queued jobs

The brief literally says:

> *"Redis queues for order dispatch, surge pricing recalculation, and notifications."*

We have **exactly those three** jobs, each implementing
`Illuminate\Contracts\Queue\ShouldQueue`:

| Class | Triggered by | What it does |
|---|---|---|
| `App\Jobs\DispatchOrderJob` | `OrderStateMachine` when an order enters `Preparing` | Calls `RiderDispatcher::dispatch($order)` — picks nearest available rider, marks them unavailable, sets `orders.rider_id`. Uses `lockForUpdate()` to prevent double-assignment. |
| `App\Jobs\RecalculateSurgePricingJob` | Order created / delivered / cancelled | Asks `SurgePricingEngine::compute()` for the latest multiplier and caches it for 90s under `surge:current`. |
| `App\Jobs\SendOrderNotificationJob` | Every FSM transition | Builds notification payloads for the customer, restaurant, and rider and hands them to whatever channel is configured (logger in dev, SMS/email/push in prod). |

### Why queue instead of running inline?

The HTTP request that confirms an order returns the moment the order is
saved. Picking the nearest rider, recomputing the surge multiplier, and
formatting the SMS/push to the customer are all done **after** the
response goes out. The user-perceived latency stays flat regardless of
how complex the side-effects get.

### Driver — Redis vs database

Laravel's queue API is identical regardless of the backend. Switching
backends is one line:

```env
QUEUE_CONNECTION=redis     # uses Predis (already installed)
QUEUE_CONNECTION=database  # uses the `jobs` table (default for demo)
```

The job code is **byte-for-byte the same**. There is no Redis-specific
code anywhere in the app.

### Running a worker

```powershell
# Default driver (whatever .env says)
php artisan queue:work

# Force a specific driver
php artisan queue:work redis
php artisan queue:work database

# Listen and auto-restart on file change (dev convenience)
php artisan queue:listen --tries=1
```

### Watching the queue

| Driver | Where to watch |
|---|---|
| `database` | `SELECT * FROM jobs ORDER BY id DESC;` in phpMyAdmin |
| `redis` | `redis-cli MONITOR` |

A row appears in `jobs` (or in Redis) **the instant the FSM dispatches
the job**. Run the worker and the row disappears as soon as the job
completes.

### Retries

Each job declares its own retry policy:

```php
public int $tries = 3;
public int $backoff = 10; // seconds between retries
```

A job that throws is retried up to 3 times with a 10-second backoff
before being marked `failed`. Failed jobs land in the `failed_jobs` table
where they can be inspected with `php artisan queue:failed` and
retried with `php artisan queue:retry <id>`.

### Installing Redis on Windows

Two easy options:

1. **Memurai** (the most painless): `winget install Memurai.MemuraiDeveloper`, then `net start Memurai`.
2. **WSL**: `wsl --install`, then `sudo apt-get install redis-server`, then `redis-server --daemonize yes`.

Both expose Redis on `127.0.0.1:6379`. After install, set
`QUEUE_CONNECTION=redis` in `.env` and start a worker:

```powershell
php artisan queue:work redis
```

---

## 4. End-to-end real-time + queue example

The most illustrative flow:

```
T+0ms   POST /api/owner/orders/17/start-preparing
        ├─ OrderStateMachine.transition(Preparing)
        │    ├─ DB: orders.status = 'preparing', preparing_at = now()
        │    ├─ DB: order_status_history append
        │    ├─ broadcast(OrderStateChanged)  ─► Reverb ─► every subscriber sees the status badge update
        │    ├─ dispatch(SendOrderNotificationJob)  ─► queue
        │    └─ dispatch(DispatchOrderJob)          ─► queue
        └─ HTTP 200 returned                                       (≈40 ms)

T+50ms  queue worker picks up SendOrderNotificationJob
        └─ logs the messages for customer/restaurant/rider

T+80ms  queue worker picks up DispatchOrderJob
        ├─ RiderDispatcher.dispatch()
        │    ├─ lockForUpdate on available riders
        │    ├─ sorts by DistanceCalculator.kilometers (Haversine or Google)
        │    ├─ orders.rider_id = best.id
        │    └─ rider.is_available = false
        └─ done

T+10s   rider1's app POSTs /api/rider/location
        ├─ RiderLocationUpdated fires ─► presence-control-tower channel
        └─ admin control tower map marker moves
```

The customer's order detail page shows the status badge updating from
**Preparing → On the way** without a page refresh, because the
`OrderStateChanged` event was broadcast to `private-order.17` and the
page's Echo subscription patched the DOM.

---

## 5. Configuration cheat sheet

```env
# === Real-time ===
BROADCAST_CONNECTION=reverb        # or "log" for offline
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# These are read by Vite into the JS bundle:
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# === Queues ===
QUEUE_CONNECTION=redis             # or "database"
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

## 6. Common pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| Order placed but no rider assigned | Queue worker not running | `php artisan queue:work` in another terminal |
| Live map markers don't move | Reverb not running, or `BROADCAST_CONNECTION` is `log` | Start `php artisan reverb:start` and set `BROADCAST_CONNECTION=reverb` in `.env`, then rebuild assets: `npm run build` |
| Subscribed to a private channel but nothing arrives | Channel auth in `routes/channels.php` returned `false` | Confirm the user is the right role / owns the resource |
| Job stuck in `jobs` table | Worker crashed mid-job, or job exceeded `$tries` | Check `failed_jobs`; rerun `php artisan queue:retry <id>` |
| `redis-cli` fails to connect | Memurai / WSL Redis not running | Start the service; verify `redis-cli ping` returns `PONG` |

---

## 7. How to test the real-time + queue layer

### Test — broadcasting

```powershell
php artisan test --filter=BroadcastingTest
```

The test (`tests/Feature/Realtime/BroadcastingTest.php`) uses
`Event::fake()` to verify that the FSM fires `OrderStateChanged` on
status changes and `RiderLocationUpdated` on rider GPS updates.

### Test — queueable bindings

```powershell
php artisan test --filter=RealServiceBindingsTest::three_queueable_jobs_exist_per_description
php artisan test --filter=RealServiceBindingsTest::predis_package_is_installed_for_redis_queue_support
```

These tests assert the three jobs exist, implement `ShouldQueue`, and
that the Predis client is installed.

### Test — order volume spike (queue under load)

```powershell
php artisan test --filter=OrderVolumeSpikeTest
```

Simulates 50 orders entering `preparing` against 10 riders. The
dispatcher's `lockForUpdate` prevents any rider from being double-assigned.

### Manual — observe in phpMyAdmin

```sql
USE fooddelivery;
SELECT id, queue, payload, attempts, created_at FROM jobs ORDER BY id DESC LIMIT 5;
SELECT id, status, rider_id FROM orders ORDER BY id DESC LIMIT 5;
```

You'll see a job appear, then disappear (after `queue:work` runs), and
the order's `rider_id` populate.
