# 03 — API Reference

Complete reference for the **Sanctum-protected JSON API** at `/api/*`.

This is what a customer mobile app, a rider mobile app, or a third-party
integration would talk to. The browser UI does **not** use this API — it
talks directly to `routes/web.php` over sessions. Same business logic
underneath either way.

---

## 1. Conventions

| Item | Convention |
|---|---|
| Base URL | `http://127.0.0.1:8000/api` (dev) |
| Auth | Sanctum Bearer token in the `Authorization` header. |
| Content type | `application/json` for all requests with a body. |
| Money | Always **integer cents** (e.g. `1525` = 15.25 EGP). |
| Coordinates | Decimal degrees, 7 decimal places (e.g. `30.0444112`). |
| Timestamps | ISO-8601 UTC (e.g. `2026-05-13T09:30:00Z`). |
| Errors | Standard Laravel `{ "message": "...", "errors": { ... } }` envelope. |
| HTTP codes | 200 OK · 201 Created · 204 No Content · 401 Unauthenticated · 403 Forbidden · 404 Not Found · 422 Validation failed · 500 Server error. |

### Authenticating

1. Call `POST /api/login` with `{ email, password, device_name }`.
2. The response includes a `token` field.
3. Send every subsequent request with the header:
   ```
   Authorization: Bearer <token>
   ```

### Roles & route prefixes

| Prefix | Required role | Middleware |
|---|---|---|
| `/api/*` (public) | none | — |
| `/api/customer/*` | `customer` | `auth:sanctum`, `role:customer` |
| `/api/owner/*` | `restaurant_owner` or `admin` | `auth:sanctum`, `role:restaurant_owner,admin` |
| `/api/rider/*` | `rider` | `auth:sanctum`, `role:rider` |
| `/api/me`, `/api/logout` | any authenticated | `auth:sanctum` |

Hitting a route with the wrong role returns **403 Forbidden**.
Hitting a protected route with no/invalid token returns **401 Unauthenticated**
with a JSON body (no HTML redirect).

---

## 2. Endpoint index

| Method | Path | Auth | What it does |
|---|---|---|---|
| POST | `/register` | public | Create a customer or rider account. |
| POST | `/login`    | public | Exchange credentials for a Sanctum token. |
| GET  | `/restaurants`             | public | List open restaurants. |
| GET  | `/restaurants/{slug}/menu` | public | Get a restaurant's full menu. |
| GET  | `/me`     | any | Identity of the authenticated user. |
| POST | `/logout` | any | Revoke the current token. |
| POST | `/customer/orders`                  | customer | Place a new order. |
| GET  | `/customer/orders`                  | customer | List my orders. |
| GET  | `/customer/orders/{order}`          | customer | Get one of my orders. |
| POST | `/customer/orders/{order}/cancel`   | customer | Cancel (only while placed/confirmed). |
| POST | `/customer/orders/{order}/rate`     | customer | Rate the restaurant or the rider. |
| POST | `/rider/duty`                       | rider | Toggle on-duty status. |
| POST | `/rider/location`                   | rider | Update current GPS. |
| GET  | `/rider/me`                         | rider | Rider profile (incl. current assigned order). |
| POST | `/rider/orders/{order}/picked-up`   | rider | preparing → on_the_way. |
| POST | `/rider/orders/{order}/delivered`   | rider | on_the_way → delivered. |
| GET  | `/owner/restaurants`                | owner/admin | List my restaurants. |
| POST | `/owner/restaurants`                | owner/admin | Create a restaurant. |
| GET  | `/owner/restaurants/{restaurant}`   | owner/admin | One restaurant. |
| PATCH| `/owner/restaurants/{restaurant}`   | owner/admin | Update a restaurant. |
| POST | `/owner/categories`                 | owner/admin | Add a menu category. |
| PATCH| `/owner/categories/{category}`      | owner/admin | Update a category. |
| DELETE| `/owner/categories/{category}`     | owner/admin | Delete a category. |
| POST | `/owner/menu-items`                          | owner/admin | Add a menu item. |
| PATCH| `/owner/menu-items/{menuItem}`               | owner/admin | Update a menu item. |
| PATCH| `/owner/menu-items/{menuItem}/availability`  | owner/admin | Toggle is_available. |
| DELETE| `/owner/menu-items/{menuItem}`              | owner/admin | Delete a menu item. |
| POST | `/owner/variants`                   | owner/admin | Add a variant to a menu item. |
| PATCH| `/owner/variants/{variant}`         | owner/admin | Update a variant. |
| DELETE| `/owner/variants/{variant}`        | owner/admin | Delete a variant. |
| GET  | `/owner/orders`                          | owner/admin | List incoming orders for my restaurants. |
| POST | `/owner/orders/{order}/confirm`          | owner/admin | placed → confirmed. |
| POST | `/owner/orders/{order}/start-preparing`  | owner/admin | confirmed → preparing (triggers dispatch). |
| POST | `/owner/orders/{order}/cancel`           | owner/admin | Cancel any non-terminal order. |

That's **27 endpoints**. Source of truth: `routes/api.php`.

---

## 3. Auth endpoints

### `POST /api/register`

Public. Creates a customer **or** a rider account. Admins / restaurant
owners are seeded — they cannot register through the API.

```json
{
  "name": "Sara Customer",
  "email": "sara@example.com",
  "password": "password",
  "password_confirmation": "password",
  "phone": "+201234567890",
  "role": "customer"
}
```

`role` must be `customer` or `rider`. If `role` is `rider`, you may also
supply `vehicle_type` and `license_plate` (optional).

Response **201 Created**:

```json
{
  "user":  { "id": 7, "name": "Sara Customer", "email": "sara@example.com", "role": "customer" },
  "token": "8|abc...xyz"
}
```

### `POST /api/login`

```json
{ "email": "sara@example.com", "password": "password", "device_name": "iphone-13" }
```

Response **200 OK**:

```json
{
  "user":  { "id": 7, "role": "customer" },
  "token": "8|abc...xyz"
}
```

Errors: **422** on validation, **401** with `{"message":"Invalid credentials."}` on bad password.

### `GET /api/me`

Returns the authenticated user. No body.

```json
{ "id": 7, "name": "Sara Customer", "email": "sara@example.com", "role": "customer", "phone": "+201234567890" }
```

### `POST /api/logout`

Revokes the **current** Sanctum token. Returns **204 No Content**.

---

## 4. Public browsing

### `GET /api/restaurants`

Returns the list of currently open restaurants.

```json
[
  {
    "id": 1, "name": "Demo Bistro", "slug": "demo-bistro",
    "address": "12 Garden City", "latitude": "30.0444112", "longitude": "31.2357116",
    "is_open": true
  }
]
```

### `GET /api/restaurants/{slug}/menu`

```json
{
  "restaurant": { "id": 1, "name": "Demo Bistro", "is_open": true },
  "categories": [
    {
      "id": 1, "name": "Mains", "sort_order": 1,
      "menu_items": [
        {
          "id": 1, "name": "Margherita Pizza", "base_price": 8500, "is_available": true,
          "variants": [
            { "id": 1, "name": "Medium", "price_modifier": 0, "is_default": true },
            { "id": 2, "name": "Large",  "price_modifier": 2500 }
          ]
        }
      ]
    }
  ]
}
```

---

## 5. Customer endpoints

All require `Authorization: Bearer <token>` and `role:customer`.

### `POST /api/customer/orders` — Place an order

```json
{
  "restaurant_id": 1,
  "items": [
    { "menu_item_id": 1, "variant_id": 2, "quantity": 2, "special_instructions": "no onions" },
    { "menu_item_id": 3, "quantity": 1 }
  ],
  "delivery_address":   "21 Tahrir Street, Cairo",
  "delivery_latitude":  30.0500112,
  "delivery_longitude": 31.2400000
}
```

Response **201 Created**:

```json
{
  "id": 17, "status": "placed",
  "subtotal": 22000, "delivery_fee": 6250, "platform_fee": 3300,
  "restaurant_payout": 18700, "rider_payout": 5000,
  "surge_multiplier": "1.25", "total": 28250,
  "payment_intent_id": "pi_fake_a1b2c3...",
  "placed_at": "2026-05-13T09:30:00Z",
  "items": [ ... ]
}
```

All money fields are integer cents. `subtotal + delivery_fee = total`.

Errors:

- **422** when the cart is empty, an item id is invalid, a variant doesn't
  belong to the item, the restaurant is closed, or coordinates are missing.
- **403** if the user isn't a customer.

### `GET /api/customer/orders`

Returns the authenticated customer's orders, newest first. Paginated.

### `GET /api/customer/orders/{order}`

Returns one order with its items, restaurant, rider, ratings, and status
history.

### `POST /api/customer/orders/{order}/cancel`

Allowed only while `placed` or `confirmed`. Body (optional):

```json
{ "reason": "Changed my mind" }
```

Returns **200 OK** with the updated order, or **422** if the order is
already in a terminal or in-flight state.

### `POST /api/customer/orders/{order}/rate` — Rate restaurant or rider

```json
{ "target": "restaurant", "stars": 5, "comment": "Great food!" }
```

`target` is `restaurant` or `rider`. `stars` is an integer 1–5.

- **201 Created** with the new rating object.
- **422** if the order isn't delivered yet, the target has already been
  rated, or stars are out of range.

---

## 6. Rider endpoints

All require `auth:sanctum` and `role:rider`.

### `POST /api/rider/duty`

Toggles `is_on_duty`. Body: none. Returns the new state:

```json
{ "is_on_duty": true, "is_available": true }
```

### `POST /api/rider/location`

```json
{ "latitude": 30.0444112, "longitude": 31.2357116 }
```

Stores the GPS on the `riders` table, sets `last_location_at = now()`, and
fires the `RiderLocationUpdated` broadcast event so any admin or customer
listening on the rider's channel sees the marker move.

Returns **200 OK** with the updated rider.

### `GET /api/rider/me`

Returns the rider profile and the order they're currently assigned to, if
any.

### `POST /api/rider/orders/{order}/picked-up`

Moves the order from `preparing` to `on_the_way`. Returns **200 OK** with
the updated order, or **422** if the order isn't in `preparing`.

### `POST /api/rider/orders/{order}/delivered`

Moves the order from `on_the_way` to `delivered`. Sets the rider back to
`is_available = true` so they can be assigned to a new order.

---

## 7. Restaurant owner / admin endpoints

All require `auth:sanctum` and `role:restaurant_owner,admin`. A restaurant
owner can only manage **their own** restaurants — the `RestaurantPolicy`
enforces this. An admin can manage any restaurant.

### Restaurants

| Method | Path | Notes |
|---|---|---|
| GET   | `/owner/restaurants`                | Lists restaurants this user owns (all for admins). |
| POST  | `/owner/restaurants`                | Body: `name, address, latitude, longitude, commission_rate` |
| GET   | `/owner/restaurants/{restaurant}`   | One restaurant. |
| PATCH | `/owner/restaurants/{restaurant}`   | Partial update. Any of `name, address, lat/long, commission_rate, is_open`. |

### Categories

| Method | Path | Body |
|---|---|---|
| POST   | `/owner/categories`         | `{ restaurant_id, name, sort_order? }` |
| PATCH  | `/owner/categories/{category}` | `{ name?, sort_order? }` |
| DELETE | `/owner/categories/{category}` | (cascades to items) |

### Menu items

| Method | Path | Body / notes |
|---|---|---|
| POST   | `/owner/menu-items`                          | `{ category_id, name, description?, base_price, image_path? }` |
| PATCH  | `/owner/menu-items/{menuItem}`               | Any subset. `base_price` must be a positive integer (cents). |
| PATCH  | `/owner/menu-items/{menuItem}/availability`  | No body — flips `is_available`. |
| DELETE | `/owner/menu-items/{menuItem}`               | Soft-removes from menus. |

### Variants

| Method | Path | Body |
|---|---|---|
| POST   | `/owner/variants`              | `{ menu_item_id, name, price_modifier, is_default? }` |
| PATCH  | `/owner/variants/{variant}`    | Any subset. |
| DELETE | `/owner/variants/{variant}`    | — |

### Orders (restaurant side)

| Method | Path | What it does |
|---|---|---|
| GET   | `/owner/orders`                             | List orders for my restaurants. Optional `?status=placed,confirmed`. |
| POST  | `/owner/orders/{order}/confirm`             | placed → confirmed. |
| POST  | `/owner/orders/{order}/start-preparing`     | confirmed → preparing (this is the trigger for `DispatchOrderJob`). |
| POST  | `/owner/orders/{order}/cancel`              | Cancel any non-terminal order. Body: `{ reason? }`. |

---

## 8. Error envelope

Validation errors return **422 Unprocessable Entity**:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "items.0.quantity": ["The items.0.quantity field is required."],
    "delivery_latitude": ["The delivery latitude must be a number."]
  }
}
```

Auth errors (401):

```json
{ "message": "Unauthenticated." }
```

Forbidden (403):

```json
{ "message": "This action is unauthorized." }
```

Not found (404):

```json
{ "message": "No query results for model [App\\Models\\Order] 9999." }
```

FSM rejection (422 with a specific code):

```json
{ "message": "Cannot transition from delivered to preparing." }
```

---

## 9. Copy-paste examples

### PowerShell — login & place an order

```powershell
$base = 'http://127.0.0.1:8000/api'

# 1) Login
$login = Invoke-RestMethod -Uri "$base/login" -Method POST -ContentType 'application/json' `
  -Body (@{ email='customer1@demo.test'; password='password'; device_name='pwsh' } | ConvertTo-Json)
$token = $login.token
$h = @{ Authorization = "Bearer $token"; Accept = 'application/json' }

# 2) Browse restaurants
Invoke-RestMethod -Uri "$base/restaurants" -Headers $h

# 3) Place an order
$body = @{
  restaurant_id      = 1
  items              = @( @{ menu_item_id = 1; quantity = 2 } )
  delivery_address   = '21 Tahrir St'
  delivery_latitude  = 30.05
  delivery_longitude = 31.24
} | ConvertTo-Json -Depth 5
$order = Invoke-RestMethod -Uri "$base/customer/orders" -Method POST `
  -Headers $h -ContentType 'application/json' -Body $body
$order
```

### curl — same flow

```bash
TOKEN=$(curl -sS http://127.0.0.1:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"customer1@demo.test","password":"password","device_name":"curl"}' \
  | jq -r .token)

curl -sS http://127.0.0.1:8000/api/restaurants \
  -H "Authorization: Bearer $TOKEN" | jq

curl -sS http://127.0.0.1:8000/api/customer/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"restaurant_id":1,"items":[{"menu_item_id":1,"quantity":2}],
       "delivery_address":"21 Tahrir St","delivery_latitude":30.05,"delivery_longitude":31.24}' | jq
```

### Owner side — confirm → start-preparing

```powershell
# (after logging in as owner@demo.test)
Invoke-RestMethod -Uri "$base/owner/orders/17/confirm" -Method POST -Headers $h
Invoke-RestMethod -Uri "$base/owner/orders/17/start-preparing" -Method POST -Headers $h
```

This triggers `DispatchOrderJob`. Run `php artisan queue:work` to assign
the nearest rider in the background.

### Rider side — picked up → delivered

```powershell
# (after logging in as rider1@demo.test and being assigned to order 17)
Invoke-RestMethod -Uri "$base/rider/location" -Method POST -Headers $h `
  -ContentType 'application/json' -Body (@{ latitude = 30.05; longitude = 31.24 } | ConvertTo-Json)

Invoke-RestMethod -Uri "$base/rider/orders/17/picked-up" -Method POST -Headers $h
Invoke-RestMethod -Uri "$base/rider/orders/17/delivered" -Method POST -Headers $h
```

### Rating

```powershell
Invoke-RestMethod -Uri "$base/customer/orders/17/rate" -Method POST -Headers $h `
  -ContentType 'application/json' -Body (@{ target='restaurant'; stars=5; comment='Excellent' } | ConvertTo-Json)
```

---

## 10. The `demo.ps1` script

The repo ships a `demo.ps1` that runs the entire flow end-to-end against
the API. From the project root:

```powershell
php artisan serve   # in terminal 1
php artisan queue:work  # in terminal 2
.\demo.ps1          # in terminal 3
```

The script logs in as each role in sequence, places an order, walks it
through every state, and rates the restaurant + rider. It's the
executable form of this API doc.

---

## 11. Versioning & deprecations

This API is unversioned — the path is `/api/*`, not `/api/v1/*`. If a
breaking change is needed we'd add `/api/v2/*` and keep `/api/*` running
until clients migrate. None of that is necessary today.
