# 05 — Customer Guide

This is the end-user walkthrough for **customers** — the people ordering
food on the platform.

If you want to test the customer flow live, log in at
`http://127.0.0.1:8000/login` with either of:

| Email | Password |
|---|---|
| `customer1@demo.test` | `password` |
| `customer2@demo.test` | `password` |

…or **register a new account** at
`http://127.0.0.1:8000/register`.

---

## 1. What you can do as a customer

| Capability | URL |
|---|---|
| Browse open restaurants (no login needed) | `/` |
| See a restaurant's menu | `/restaurants/{slug}` |
| Register a new account | `/register` |
| Log in | `/login` |
| Place an order with quantities, variants, and special instructions | the menu page → **Place order** |
| See your orders + their live status | `/dashboard` |
| See a single order in detail (with timeline) | `/orders/{id}` |
| Cancel an order (only while `placed` or `confirmed`) | order detail page → **Cancel order** |
| Rate the restaurant **and** the rider after delivery | order detail page → "Rate your experience" |

---

## 2. The full happy path (step by step)

### Step 1 — Browse restaurants

Open `http://127.0.0.1:8000/`. You'll see a hero section and a grid of
restaurant cards. No login required. Click a restaurant card to see its
menu.

### Step 2 — Build your cart

On the restaurant's menu page (`/restaurants/{slug}`):

- Each menu item has a `−` / `+` quantity stepper.
- If an item has variants (e.g. Small / Medium / Large), choose one from
  the dropdown — the price label updates in real time.
- The sticky sidebar on the right shows your live cart total (powered by
  Alpine.js, no page reload).
- Add a **special instruction** below the cart if you want (e.g. "no
  onions, ring the back door bell").
- Add your delivery address. Coordinates are pre-filled with a sensible
  default — they can be tweaked in the collapsed **Advanced** section if
  needed.

### Step 3 — Place the order

Click **Place order**. Behind the scenes:

1. Form posts to `POST /restaurants/{r}/order`.
2. `WebOrderController::place` validates and calls the `PlaceOrder` service.
3. `SurgePricingEngine` computes the multiplier — *this is when surge
   pricing actually hits your wallet*.
4. `PriceCalculator` builds the integer-cents breakdown
   (subtotal, delivery_fee × surge, commission, payouts, total).
5. `PaymentGateway::chargeAndSplit()` runs (fake or real Stripe — your
   demo's choice).
6. The new order is created with status `placed`, and the first row is
   written to `order_status_history`.

You're redirected to `/orders/{id}` with a success message.

### Step 4 — Watch the order move

Your dashboard (`/dashboard`) shows every order with a colored status
badge. Refresh it (or, with Reverb running, watch live) as the status
moves:

```
placed   ─►   confirmed   ─►   preparing   ─►   on_the_way   ─►   delivered
```

The order detail page (`/orders/{id}`) shows the full **timeline at the
bottom** — every transition with the actor (customer / restaurant / rider)
and the timestamp. This is the event-source-inspired audit log
materialised in the UI.

### Step 5 — Receive your food

When the rider clicks **Delivered** in their app, your order moves to
`delivered`, and your order detail page now shows a **"Rate your
experience"** panel.

### Step 6 — Rate the restaurant + rider separately

The rating panel has **two five-star widgets**:

- One for the **restaurant** (food quality, accuracy).
- One for the **rider** (speed, courtesy).

Each can take an optional comment. Each is stored polymorphically via
`Rating::rateable` (`morphTo`). A unique constraint on
`(order_id, rateable_type, rateable_id)` prevents you from rating the
same target twice.

---

## 3. Cancelling an order

While the order is `placed` or `confirmed` you'll see a red **Cancel
order** button on the detail page. Click it (optionally with a reason);
the FSM moves the order to `cancelled` and the timeline records who
cancelled it.

Once the order is `preparing` or later, the button disappears — you can't
cancel an order that's already being cooked or delivered.

---

## 4. What you'll see on the order detail page

```
┌─────────────────────────────────────────────────────────┐
│ Order #17                            [On the way]      │
│ Demo Bistro · 21 Tahrir St                              │
├─────────────────────────────────────────────────────────┤
│ Items                                                   │
│   2 ×  Margherita Pizza (Medium)          —  170.00     │
│        no onions, please                                │
│   1 ×  Tiramisu                            —   50.00    │
│                                          ─────────      │
│ Subtotal                                       220.00   │
│ Delivery (1.25× surge)                          62.50   │
│ ──────────                                              │
│ Total                                          282.50   │
│ Payment ref:  pi_fake_a1b2c3d4e5f6                      │
├─────────────────────────────────────────────────────────┤
│ Timeline                                                │
│   • placed     09:30  by you                            │
│   • confirmed  09:33  by Demo Bistro                    │
│   • preparing  09:34  by Demo Bistro                    │
│   • on_the_way 09:48  by Rider Ahmed                    │
│   • delivered  ...                                      │
└─────────────────────────────────────────────────────────┘
```

---

## 5. Using the mobile app instead of the browser

A mobile app would talk to the **`/api/customer/*`** endpoints instead of
the browser routes. The flows are identical; the request bodies and
responses live in [`03-api.md`](./03-api.md) § 5.

You'd register / log in, store the returned token, then send it with
every request as `Authorization: Bearer <token>`.

---

## 6. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Menu items missing from a restaurant page | Items are toggled "unavailable" by the owner, or the restaurant is closed | Owner needs to flip availability/open in their dashboard |
| "Place order" button doesn't appear | You're not logged in | Click **Login** in the nav |
| Status doesn't change live | Reverb not running | Refresh the page; ask the admin to start Reverb |
| "Cancel order" button disappeared | Order is past `confirmed` | Once preparation has started, you can't unilaterally cancel — contact the restaurant |
| Stars widget not showing | Order isn't `delivered` yet | The rating panel appears only after delivery |
