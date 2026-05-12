# 08 — Admin Guide

The admin role has read access to **everything**, plus a small set of
operator tools (toggling restaurants, tuning surge, watching the live
map). Admin accounts are seeded; they cannot self-register.

| Email | Password |
|---|---|
| `admin@demo.test` | `password` |

---

## 1. What an admin can do

| Capability | URL |
|---|---|
| Operator dashboard with KPIs (orders, users, GMV, platform fees) | `/admin` |
| All orders with status filter | `/admin/orders` |
| All users with role filter | `/admin/users` |
| All restaurants (force open/close any of them) | `/admin/restaurants` |
| All riders with on-duty status and GPS | `/admin/riders` |
| **Surge pricing playground** — interactive sliders | `/admin/surge` |
| **Live control tower** — real-time map of riders + orders | `/admin/control-tower` |

The admin nav bar shows links to all of the above when you're logged in.

---

## 2. The operator dashboard `/admin`

Top KPI strip:

| KPI | Source |
|---|---|
| **Total orders** + active count | `Order::count()` + `Order::whereNotIn('status',['delivered','cancelled'])` |
| **Users** + breakdown by role | `User::count()` grouped by role |
| **Restaurants** + open count | `Restaurant::count()` + `where('is_open', true)` |
| **Riders on duty** + total | `Rider::where('is_on_duty', true)` |
| **GMV** (Gross Merchandise Value) | sum of `orders.total` where `status = 'delivered'` |
| **Platform fee earned** | sum of `orders.platform_fee` where `status = 'delivered'` |

Below the KPIs: quick links to the six admin subpages, and a "Latest
orders" table.

---

## 3. `/admin/orders` — all orders

A table with every order in the system. Status filter at the top
(`placed`, `confirmed`, `preparing`, `on_the_way`, `delivered`, `cancelled`).

Columns: `#`, customer, restaurant, status badge, surge, total, placed
time. Each row links to the full order detail page.

---

## 4. `/admin/users` — all users

Filter by role (`customer`, `restaurant_owner`, `rider`, `admin`).
Columns: id, name, email, role, phone, joined.

Use for support: "find user X" or "how many riders signed up this week".

---

## 5. `/admin/restaurants` — moderation

Lists every restaurant with:

- Owner
- Address + coordinates
- Open/closed badge
- Commission rate
- Number of menu items
- **Force open/close toggle** — POSTs to `/admin/restaurants/{r}/toggle-open`

Used to take a misbehaving restaurant offline without touching the DB.

---

## 6. `/admin/riders` — rider fleet

Lists every rider with:

- Vehicle / license plate
- On-duty / off-duty badge
- Available / busy
- Last reported GPS (lat/long) + relative time of the ping
- Number of completed deliveries

Useful for "is rider X actually on the road?" support questions.

---

## 7. `/admin/surge` — the surge pricing playground

This is the page that makes surge pricing **tangible** for everyone in
the discussion. Live sliders + a big multiplier on the right.

```
┌────────────────────────────────────────┐    ┌──────────────────┐
│  Live active orders: 3                 │    │  FINAL MULTIPLIER│
│  Live available riders: 5              │    │                  │
│                                        │    │     1.25 ×       │
│  ─────────────────────────────         │    │                  │
│  Demand: 20 ──────────●──── 100        │    │  Demand/supply:1.25
│  Supply: 1  ─────●─────────── 100      │    │  Time+weather:1.00
│  Weather: [Clear] [Rain] [Storm]       │    │                  │
│  Time of day: ───●────────── 23        │    │  Sample order:   │
│        12-14 lunch  19-22 dinner       │    │   Subtotal 30.00 │
│                                        │    │   Delivery 50.00→62.50│
└────────────────────────────────────────┘    └──────────────────┘
```

Move any slider → the page reloads → the multiplier and the breakdown
update. The colour of the multiplier card matches the intensity (green
= 1.00, amber = mild, red = high, purple = capped).

Below the form, a table lists the last 8 real orders with their actual
stored `surge_multiplier` — proof that the engine output is what the
customers were charged.

Use this for the discussion: "**Here, watch demand go up, the price goes
up. Now watch supply go up, the price goes down. Now watch the cap kick
in. Now watch it roll back.**"

---

## 8. `/admin/control-tower` — live map

A Livewire-powered Leaflet map showing:

- **Active orders** (placed, confirmed, preparing, on_the_way) as
  numbered markers at the restaurant and customer coordinates.
- **On-duty riders** as round markers at their last known GPS.

This page subscribes to Reverb's WebSocket channels. When a rider's app
POSTs to `/api/rider/location`, the marker moves **without a page
refresh**. When an order changes state, the marker recolours.

Requires `BROADCAST_CONNECTION=reverb` in `.env` and `php artisan
reverb:start` running in another terminal. With `BROADCAST_CONNECTION=log`
(the default for the demo) the page still loads but markers don't
auto-update — refresh manually.

---

## 9. Read-only access vs. operator powers

The admin role has **read access to everything** — orders, users,
restaurants, ratings, GPS history, jobs queue. The only **write** powers
exposed in the UI are:

1. **Toggle a restaurant open/closed** (`POST /admin/restaurants/{r}/toggle-open`).
2. **Cancel any order** via the order detail page on behalf of the user.

Anything more invasive (re-issuing tokens, force-deleting users) is left
out of the UI on purpose and must be done via `php artisan tinker` or
phpMyAdmin. Keeps the blast radius small.

---

## 10. How to escalate an incident

Common operator playbook:

| Problem | First action |
|---|---|
| Rider not getting orders | `/admin/riders` — check `is_on_duty`, `is_available`, last GPS time |
| Customer says "still no rider" | `/admin/orders/{id}` — check `rider_id`. If null, check the queue worker is running |
| Restaurant complains about surge | `/admin/surge` — show them the inputs in real time |
| Suspicious order | `/admin/orders/{id}` → timeline shows actor + time for every state change |
| Need to take a restaurant offline now | `/admin/restaurants` → click **Toggle open** |

---

## 11. Database access (when the UI isn't enough)

phpMyAdmin: `http://localhost/phpmyadmin` → `fooddelivery` database.

Useful queries:

```sql
-- All orders + their full state timeline:
SELECT o.id, o.status, h.from_status, h.to_status, h.actor_type, h.occurred_at
FROM   orders o
JOIN   order_status_history h ON h.order_id = o.id
ORDER BY o.id, h.occurred_at;

-- Today's GMV + platform fee:
SELECT SUM(total)/100 AS gmv_egp, SUM(platform_fee)/100 AS platform_egp
FROM   orders
WHERE  status = 'delivered' AND DATE(delivered_at) = CURDATE();

-- Riders sorted by deliveries:
SELECT r.id, u.name, COUNT(o.id) AS deliveries
FROM   riders r
JOIN   users u    ON u.id = r.user_id
LEFT  JOIN orders o ON o.rider_id = r.id AND o.status = 'delivered'
GROUP BY r.id, u.name
ORDER BY deliveries DESC;
```

---

## 12. Things not exposed in the admin UI

| Task | How to do it |
|---|---|
| Reset everything | `php artisan migrate:fresh --seed` |
| Wipe ratings | `DELETE FROM ratings;` |
| Re-issue a token for a user | `php artisan tinker` → `User::find($id)->createToken('reset')->plainTextToken` |
| Run any test in isolation | `php artisan test --filter=...` |
| Re-run a failed queue job | `php artisan queue:retry all` |
