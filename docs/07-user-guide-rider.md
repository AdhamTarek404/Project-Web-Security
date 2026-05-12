# 07 — Rider Guide

This is the end-user walkthrough for **delivery riders**.

Riders can self-register at `/register` and pick the **Rider** role.
Demo accounts are pre-seeded:

| Email | Password | Vehicle |
|---|---|---|
| `rider1@demo.test` | `password` | bike |
| `rider2@demo.test` | `password` | scooter |
| `rider3@demo.test` | `password` | car |

---

## 1. What you can do as a rider

| Capability | URL |
|---|---|
| Register (with vehicle type + license plate) | `/register` |
| Rider dashboard (duty status, current order, location) | `/dashboard` |
| Go on duty / off duty | dashboard → **Go on duty / Go off duty** |
| Update GPS coordinates | dashboard → **Update location** |
| See the order assigned to you | dashboard (the "Active delivery" card) |
| Confirm pickup (preparing → on_the_way) | active delivery → **Picked up** |
| Confirm delivery (on_the_way → delivered) | active delivery → **Delivered** |

---

## 2. Going on duty

Open `/dashboard`. The page shows:

```
┌─────────────────────────────────────────────────────────┐
│ Rider Ahmed                              [OFF DUTY]    │
│ Vehicle: scooter · License: B-1234                      │
│ Last location: —                                        │
│                                                         │
│ [Go on duty]                                            │
└─────────────────────────────────────────────────────────┘
```

Click **Go on duty**. Behind the scenes:

- `POST /rider/duty` (web) or `POST /api/rider/duty` (API).
- Sets `riders.is_on_duty = true`.
- Sets `riders.is_available = true` (if not currently delivering).
- You become eligible for dispatch.

The badge flips to **ON DUTY**.

---

## 3. Updating your GPS

Riders must publish their location for two reasons:

1. **Dispatch picks the nearest rider**, so a stale GPS leaves money on
   the table.
2. **Customers and the admin map** watch you move in real time via the
   Reverb broadcast.

There are two ways to update:

### Browser (manual, for testing)

The dashboard has a small **"Update location"** form: lat / long fields
+ a **Save** button. Useful when you don't have a real GPS source.

### API (mobile app)

Real-world clients call:

```http
POST /api/rider/location
Authorization: Bearer <token>
Content-Type: application/json

{ "latitude": 30.0444112, "longitude": 31.2357116 }
```

Mobile apps should call this every 5–15 seconds while on duty. Every
call:

- Writes the new lat/long to `riders.current_latitude / current_longitude`.
- Stamps `last_location_at = now()`.
- Fires the `RiderLocationUpdated` broadcast — admin/control-tower
  markers move within a second.

---

## 4. Getting assigned an order

You don't *accept* orders — the platform auto-assigns the nearest
available rider when an order enters `preparing`. The flow:

```
T+0    Restaurant clicks "Start preparing"
T+50ms FSM dispatches DispatchOrderJob onto the queue
T+~1s  Queue worker (php artisan queue:work) picks the job up
       ↓
       RiderDispatcher:
         - SELECT * FROM riders WHERE is_available AND is_on_duty
         - sort by DistanceCalculator.kilometers(rider, restaurant)
         - pick the closest one
         - lockForUpdate to prevent double-assignment
         - set riders.is_available = false
         - set orders.rider_id = you
T+~2s  Your dashboard shows the new "Active delivery" card
```

If you're the closest rider, the order pops up:

```
┌─────────────────────────────────────────────────────────┐
│ Active delivery                                         │
│ Pickup: Demo Bistro · 12 Garden City                    │
│ Drop:   21 Tahrir St, Cairo                             │
│ 2 × Margherita Pizza, 1 × Tiramisu                      │
│ Payout for this delivery: 50.00 EGP                     │
│                                                         │
│ [Picked up]    [Open in maps]                           │
└─────────────────────────────────────────────────────────┘
```

---

## 5. Pickup and delivery

### **Picked up** (preparing → on_the_way)

You're at the restaurant, you have the food, you tap **Picked up**. The
FSM moves the order to `on_the_way`, fires the broadcast (customer's app
shows "On the way" without a refresh), and starts logging your GPS for
the live trip.

### **Delivered** (on_the_way → delivered)

You handed the food to the customer. Tap **Delivered**:

- Order moves to `delivered` (terminal).
- Your `is_available` flips back to `true` so dispatch can pick you for
  a new order.
- The customer's app shows the rating panel.

---

## 6. The payout

Every delivered order stores a `rider_payout` field in integer cents.
That's your cut of the delivery fee on that order. The dashboard shows
two totals:

- **Today's payout** — sum across orders you delivered today.
- **Lifetime payout** — sum across all your delivered orders.

If the platform is wired to real **Stripe Connect** (`PAYMENT_DRIVER=stripe`
+ your `riders.stripe_account_id` set), every order's `rider_payout` is
transferred to your connected Stripe account automatically as part of
the `StripeConnectGateway::chargeAndSplit()` call. Otherwise, the math
is still recorded for offline reconciliation.

---

## 7. Why was I (not) picked?

`RiderDispatcher` only considers riders meeting **all** of:

- `is_on_duty = true`
- `is_available = true` (you're not currently mid-delivery)
- `current_latitude` and `current_longitude` are not null

If you have no recent GPS, you're skipped (the dispatcher needs to know
where you are to compute distance).

If you and another rider are equally close, the SQL returns whichever
row comes first; the platform doesn't favour anyone.

---

## 8. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Never assigned an order | Off duty, no GPS, or further than other riders | Toggle on duty, push GPS, place a test order yourself |
| Stuck on a delivered order | A rare race condition — `is_available` didn't flip | Admin can fix it via phpMyAdmin or `php artisan tinker` |
| Map markers don't move on the admin page | Reverb is not running | Admin must start `php artisan reverb:start` |
| "Picked up" button greyed out | Order is not in `preparing` yet (restaurant hasn't started preparing) | Wait for the restaurant to click "Start preparing" |
| Can't go on duty | Your rider profile is missing (registration didn't complete) | Re-register or have admin create your rider row |

---

## 9. Mobile-app equivalents

Every browser action on the rider dashboard maps to one API endpoint:

| Browser action | API call |
|---|---|
| Login | `POST /api/login` |
| Go on duty / off duty | `POST /api/rider/duty` |
| Update GPS | `POST /api/rider/location` |
| Get my profile + active order | `GET /api/rider/me` |
| Picked up | `POST /api/rider/orders/{order}/picked-up` |
| Delivered | `POST /api/rider/orders/{order}/delivered` |

A mobile app talks Sanctum tokens; see [`03-api.md`](./03-api.md) § 6 for
the full payload shapes.
