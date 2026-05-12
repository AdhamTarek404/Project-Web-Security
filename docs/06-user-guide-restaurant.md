# 06 — Restaurant Owner Guide

This is the end-user walkthrough for **restaurant owners** — the people
running restaurants on the platform.

Restaurant accounts are seeded by the platform admin and **cannot be
self-registered** through the public form. Demo credentials:

| Email | Password |
|---|---|
| `owner@demo.test` | `password` |

This account owns the **Demo Bistro** restaurant.

---

## 1. What you can do as a restaurant owner

| Capability | URL |
|---|---|
| Owner dashboard (KPIs + list of your restaurants) | `/dashboard` |
| Create a new restaurant | `/owner/restaurants/create` |
| Manage a restaurant (everything below) | `/owner/restaurants/{r}/manage` |
| Edit restaurant basics (name, address, commission rate) | manage page → **Restaurant settings** |
| Toggle the restaurant open/closed (stop receiving orders) | manage page → **Toggle open** |
| Add / rename / delete menu categories | manage page → "Categories" section |
| Add / edit / delete menu items inside a category | manage page → inside each category |
| Toggle a menu item's availability | item card → **Toggle availability** |
| Add / edit / delete variants per menu item (Small / Medium / Large …) | item card → expand variants |
| See incoming orders + their status | `/dashboard` |
| Accept an order (placed → confirmed) | order card → **Confirm** |
| Start preparing (confirmed → preparing) — this triggers rider dispatch | order card → **Start preparing** |
| Cancel an order with a reason | order card → **Cancel** |
| See full order details + timeline | `/orders/{id}` |

---

## 2. Creating your first restaurant

1. Log in at `/login`.
2. The owner dashboard shows your restaurants (empty if this is your first
   time) and a **+ New restaurant** button.
3. Fill in:
   - **Name** — also used to auto-generate the URL slug.
   - **Address** — display only.
   - **Latitude / Longitude** — used by the dispatcher to find the
     nearest rider. Get these from Google Maps "right-click → copy
     coordinates".
   - **Commission rate (%)** — the platform's cut on each order. Default
     15%.
4. Click **Create restaurant**. You'll land on the manage page.

---

## 3. Building your menu

Open the manage page (`/owner/restaurants/{r}/manage`). The page has
three layered sections:

```
┌──────────────────────────────────────────────────┐
│ Restaurant: Demo Bistro    [Open]  [Toggle open] │
│  ↳ Settings (name, address, commission, gps)     │
├──────────────────────────────────────────────────┤
│ Categories                                       │
│  ↳ + Add category                                │
│                                                   │
│  ▾ Mains       (drag handle, edit, delete)       │
│      ┌────────────────────────────────────────┐  │
│      │ Menu items:                            │  │
│      │   • Margherita Pizza   85.00 EGP ✓     │  │
│      │       └─ Variants: Medium, Large       │  │
│      │   • Tiramisu           50.00 EGP ✓     │  │
│      │   + Add item                           │  │
│      └────────────────────────────────────────┘  │
│  ▸ Appetizers                                    │
└──────────────────────────────────────────────────┘
```

### Add a category

Inside the "Categories" section, click **+ Add category**, enter a name
("Appetizers", "Mains", "Drinks") and optionally a sort order, then
**Save**.

### Add a menu item

Inside the category card, click **+ Add item**. Required fields:

- **Name** — e.g. "Margherita Pizza"
- **Description** — short text shown on the menu
- **Base price** — entered in EGP (e.g. `85.00`), stored as integer cents
  (`8500`) internally.
- **Image path** — optional.

Click **Add item**. The item appears in the category list.

### Add a variant (Small / Medium / Large, etc.)

Expand the menu item card (the `▾` toggle) and click **+ Variant**:

- **Name** — e.g. "Large"
- **Price modifier** — added to base price. Can be negative (discount).
  E.g. `+25.00` means this variant costs base_price + 25.00 EGP.
- **Default** — tick if you want this variant pre-selected.

A menu item with variants shows a dropdown on the customer menu;
without variants it sells at base price only.

### Toggle item availability

Each item card has a **⏻ Toggle availability** button. Unavailable items
remain visible in your manage page but **disappear from the public menu**
and from the customer ordering form. Use this for "we just ran out of
mozzarella" without deleting the item.

### Delete

Trash icons on categories, items, and variants remove them. Categories
cascade — deleting a category removes its items. Confirmation prompts
prevent accidents.

---

## 4. Handling incoming orders

A new order arrives in `placed` state and shows up on your dashboard:

```
┌─────────────────────────────────────────────────────────┐
│ Order #17                                  [Placed]     │
│ 2 × Margherita Pizza, 1 × Tiramisu                      │
│ Total: 282.50 EGP                                       │
│                                                         │
│ [Confirm]   [Cancel]                                    │
└─────────────────────────────────────────────────────────┘
```

### Step 1 — **Confirm** (placed → confirmed)

Click **Confirm**. Tells the customer "yes, we'll make this." Timeline
row appended. No rider involvement yet.

### Step 2 — **Start preparing** (confirmed → preparing)

This is the **critical** transition: the moment status becomes
`preparing`, the FSM dispatches `DispatchOrderJob` onto the queue. A
queue worker (`php artisan queue:work`) picks it up, runs the
`RiderDispatcher`, and assigns the **nearest available rider** to your
order. The rider's app lights up with a pickup.

### Step 3 — **Cancel** (any non-terminal → cancelled)

If you can't fulfil the order, click **Cancel** and provide a reason.
The customer's app shows the cancellation.

### Step 4 — Watch it finish

Once the rider clicks **Picked up** and then **Delivered**, your order
shows status `delivered`. The customer can now rate you.

---

## 5. Your payout

Every order stores the payout breakdown in integer cents:

| Field | Meaning |
|---|---|
| `subtotal` | sum of (variant-adjusted unit price × quantity) for every line |
| `delivery_fee` | base delivery fee × surge multiplier |
| `platform_fee` | `subtotal × commission_rate` |
| `restaurant_payout` | `subtotal − platform_fee` |
| `rider_payout` | the rider's cut of the delivery fee |
| `total` | what the customer was charged |

The dashboard top strip shows **Total payout** — sum of all
`restaurant_payout` across delivered orders. The control flows through
`PaymentSplitter::splitFor()` → `PaymentGateway::chargeAndSplit()`. If you
have Stripe Connect configured (`PAYMENT_DRIVER=stripe` + your
`stripe_account_id` set on the restaurant), Stripe transfers your cut to
your connected account automatically. Otherwise the math is the same and
the payout is logged for accounting.

---

## 6. Closing the restaurant temporarily

Click **Toggle open** at the top of the manage page. With the restaurant
closed:

- The restaurant disappears from the public homepage.
- Anyone visiting your menu URL sees a banner: "We're not taking orders
  right now."
- The customer order endpoint rejects orders with HTTP 422: *"Restaurant
  is closed."*

Open it again to start taking orders.

---

## 7. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| No rider gets assigned after Start preparing | Queue worker isn't running | `php artisan queue:work` in another terminal |
| New order doesn't appear on dashboard | Dashboard cached, or order is for a different restaurant you don't own | Refresh; the dashboard only shows your orders |
| Menu item disappears for customers | You toggled it unavailable | Toggle it back available |
| Can't update commission rate | Form validation: commission rate must be 0–100 | Use a decimal like `15.50` |
| Customer complains about a high price | Surge in effect | Check `/admin/surge` — admin can see the multiplier |
