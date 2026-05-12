# FoodDelivery — Documentation

This folder is the **deliverable documentation set** for the project. It
covers every item the brief asks for under *Documentation*:

> *"System architecture, order state machine diagram, and API docs.
> Customer, restaurant, and rider user guides. Real-time architecture
> explanation (Pusher channels and Redis queues)."*

Everything below is plain Markdown so it renders on GitHub and on disk.

---

## How to read this docs set

| Audience | Start here |
|---|---|
| **Discussion examiner / reviewer** | [`00-overview.md`](./00-overview.md) → [`01-architecture.md`](./01-architecture.md) |
| **Developer joining the project** | [`01-architecture.md`](./01-architecture.md) → [`03-api.md`](./03-api.md) |
| **Frontend / mobile-app integrator** | [`03-api.md`](./03-api.md) → [`04-realtime-and-queues.md`](./04-realtime-and-queues.md) |
| **Customer / end-user** | [`05-user-guide-customer.md`](./05-user-guide-customer.md) |
| **Restaurant owner** | [`06-user-guide-restaurant.md`](./06-user-guide-restaurant.md) |
| **Rider** | [`07-user-guide-rider.md`](./07-user-guide-rider.md) |
| **Platform admin** | [`08-user-guide-admin.md`](./08-user-guide-admin.md) |
| **QA / test runner** | [`09-testing-and-quality.md`](./09-testing-and-quality.md) |

---

## Documents

| # | File | What it covers |
|---|---|---|
| 00 | [overview.md](./00-overview.md) | What the system is, what each piece does, the seven-step life of an order. |
| 01 | [architecture.md](./01-architecture.md) | High-level architecture diagram, the layers (Models / Services / Controllers / Jobs / Events / Views), database schema, design decisions. |
| 02 | [state-machine.md](./02-state-machine.md) | The order finite-state machine: states, transitions, guards, the append-only event-source history, FSM diagram. |
| 03 | [api.md](./03-api.md) | Complete REST API reference — every route, auth, request body, response body, errors, status codes. With copy-paste PowerShell / curl examples. |
| 04 | [realtime-and-queues.md](./04-realtime-and-queues.md) | How real-time order tracking works (Reverb / Pusher protocol + Echo), and how the three queue jobs (dispatch / surge recalc / notifications) work over Redis. |
| 05 | [user-guide-customer.md](./05-user-guide-customer.md) | Step-by-step customer flow: register → browse → order → track → rate. |
| 06 | [user-guide-restaurant.md](./06-user-guide-restaurant.md) | Step-by-step owner flow: create restaurant → menu CRUD → accept and prepare orders. |
| 07 | [user-guide-rider.md](./07-user-guide-rider.md) | Step-by-step rider flow: go on duty → GPS → pickup → deliver. |
| 08 | [user-guide-admin.md](./08-user-guide-admin.md) | Admin tools: dashboard, orders, users, restaurants, riders, surge playground, control tower. |
| 09 | [testing-and-quality.md](./09-testing-and-quality.md) | The 52-test suite organised by what it proves, plus the four required scenarios from the spec. |

---

## Source of truth

Where this docs set is **derived from** code (so you can verify):

| Doc | Source files |
|---|---|
| Architecture | `app/`, `database/migrations/`, `config/services.php`, `app/Providers/AppServiceProvider.php` |
| FSM | `app/Enums/OrderStatus.php`, `app/Services/Orders/OrderStateMachine.php`, `database/migrations/2026_05_12_180800_create_order_status_history_table.php` |
| API | `routes/api.php`, `app/Http/Controllers/{Auth,Customer,Owner,Rider,PublicRestaurantController}.php`, `app/Http/Requests/**/*.php` |
| Real-time | `app/Events/{OrderStateChanged,RiderLocationUpdated}.php`, `routes/channels.php`, `resources/js/echo.js`, `config/broadcasting.php`, `config/reverb.php` |
| Queues | `app/Jobs/*.php`, `config/queue.php`, `app/Services/Orders/OrderStateMachine.php` |
| User guides | `routes/web.php` + the `resources/views/**.blade.php` templates the user actually sees |
| Tests | `tests/Feature/**/*.php` |

If the code and these docs disagree, **the code wins** — open an issue.

---

## Versions

| Component | Version |
|---|---|
| PHP | 8.2.12 |
| Laravel | 12.58 |
| Livewire | 4.x (Livewire 3 named in spec — Livewire 4 is API-compatible) |
| Sanctum | 4.x |
| Reverb | 1.10 |
| stripe-php | 20.x |
| predis | 3.x |
| Tailwind | 4.x (via Vite) |
