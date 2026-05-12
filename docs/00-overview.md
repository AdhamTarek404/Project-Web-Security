# 00 — Overview

## What is FoodDelivery?

**FoodDelivery** is a three-sided online marketplace, like Talabat or Uber
Eats. The same Laravel application serves three roles plus an
administrator:

| Role | What they do |
|---|---|
| **Customer** | Browses restaurants, places orders, tracks them live, rates the experience. |
| **Restaurant owner** | Manages their restaurants, menus, categories, items, variants, and accepts/prepares incoming orders. |
| **Rider** | Goes on duty, broadcasts GPS, accepts assigned orders, picks them up, delivers. |
| **Admin** | Inspects the whole system, sees the live order map, tunes the surge engine. |

Everything is wired together through one central **finite state machine**
for orders, one **service container** binding (interface → implementation)
for every external integration, and one **queue + WebSocket** layer for
background and real-time work.

---

## What was built (in one paragraph)

A complete Laravel 12 backend serving both a Sanctum-protected JSON API
(`/api/*`) and a server-rendered browser UI (`/`). All business rules live
in `app/Services/*`. Every order moves through `placed → confirmed →
preparing → on_the_way → delivered` (or `cancelled`) under FSM guards that
reject illegal transitions and append every state change to an
event-sourced history table. The dispatch engine assigns the
geographically nearest available rider (real Google Distance Matrix API or
offline Haversine — one env switch). Pricing is computed by a Strategy-
pattern surge engine that combines a demand/supply ratio, rush-hour, and
weather, capped at 3× and rolling back automatically. Payments are split
three ways via real Stripe Connect (PaymentIntents + Transfers). Customer
ratings are polymorphic across Restaurant and Rider. Real-time order and
rider updates fan out over Laravel Reverb (Pusher protocol). Three queued
jobs (dispatch, surge recalculation, notifications) implement
`ShouldQueue` and run over Redis (Predis) or the database driver. An
admin Livewire control tower shows the live map. **52 feature tests with
133 assertions cover the lot.**

---

## The seven-step life of an order

```
1. Customer browses          ─► HomeController, PublicRestaurantController
   (no auth required)            resources/views/{home, restaurants/show}.blade.php

2. Customer places order     ─► PlaceOrder service
                                 - SurgePricingEngine.compute()  (Strategy pattern)
                                 - PriceCalculator.compute()     (integer cents)
                                 - PaymentSplitter.splitFor()    (3-way split)
                                 - PaymentGateway.chargeAndSplit()  (Stripe / Fake)
                                 - OrderStateMachine.initialize() (status=placed)
                                 - emits SendOrderNotificationJob + RecalculateSurgePricingJob

3. Restaurant accepts        ─► OrderStateMachine.transition(Confirmed)
                                 - validates the move with allowedNextStates()
                                 - appends history row, fires notifications

4. Restaurant prepares       ─► OrderStateMachine.transition(Preparing)
                                 - dispatches DispatchOrderJob onto the queue

5. Dispatcher assigns        ─► DispatchOrderJob → RiderDispatcher.dispatch()
   (background, queued)         - DistanceCalculator picks nearest available rider
                                 - lockForUpdate prevents double-assignment

6. Rider delivers            ─► OrderStateMachine.transition(OnTheWay) → Delivered
                                 - rider GPS broadcasts via RiderLocationUpdated event
                                 - OrderStateChanged event fans out to UI

7. Customer rates            ─► RatingController.store()
                                 - polymorphic: Restaurant OR Rider
                                 - unique constraint stops duplicates
```

This map appears in every doc that touches a specific step.

---

## How to read the codebase, in three minutes

1. Open `routes/api.php` — that's the whole external API surface in one
   file. Every controller method is one HTTP entry point.
2. Open `app/Services/Orders/OrderStateMachine.php` — that's the heart of
   the system. Every status change in the entire app goes through this
   class.
3. Open `tests/Feature/Orders/PlaceOrderTest.php` — that's the canonical
   end-to-end happy-path test. If you understand this test, you understand
   the project.

Everything else is implementation detail of those three files.
