# Demo script: runs the complete end-to-end flow against a running dev server.
#
# Usage (in a separate terminal, after "php artisan serve"):
#   .\demo.ps1
#
# What it does:
#   1. Browses public restaurants & menu (no auth)
#   2. Logs in as a customer
#   3. Places an order (2 large pizzas + 1 shawarma)
#   4. Logs in as the restaurant owner and transitions the order
#      placed -> confirmed -> preparing  (this enqueues a dispatch job)
#   5. Processes the queue once, so the nearest rider is auto-assigned
#   6. Logs in as the assigned rider and walks the order
#      preparing -> picked_up -> delivered
#   7. Customer leaves a 5-star rating for the restaurant
#
# Final state: a fully completed order with full audit trail.

$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8000/api'
$h    = @{ 'Content-Type'='application/json'; 'Accept'='application/json' }

function Login($email, $pwd) {
    $r = Invoke-RestMethod -Uri "$base/login" -Method POST -Headers $h `
         -Body (@{ email=$email; password=$pwd; device_name='demo' } | ConvertTo-Json)
    return $r.token
}
function AuthHeaders($token) {
    $a = $h.Clone(); $a.Authorization = "Bearer $token"; return $a
}

Write-Host "`n=== 1. Public: list restaurants ===" -ForegroundColor Cyan
(Invoke-RestMethod -Uri "$base/restaurants" -Headers $h).data |
    Format-Table id, slug, name, is_open

Write-Host "=== 2. Public: view menu of demo-bistro ===" -ForegroundColor Cyan
$menu = Invoke-RestMethod -Uri "$base/restaurants/demo-bistro/menu" -Headers $h
foreach ($cat in $menu.data.categories) {
    Write-Host ("  {0}:" -f $cat.name)
    foreach ($i in $cat.menu_items) {
        Write-Host ("    #{0,-3} {1,-22} base={2} cents  available={3}" -f $i.id, $i.name, $i.base_price, $i.is_available)
    }
}

Write-Host "`n=== 3. Customer logs in & places an order ===" -ForegroundColor Cyan
$ch = AuthHeaders (Login 'customer1@demo.test' 'password')
$body = @{
    restaurant_id        = 1
    delivery_address     = '5 Customer St, Cairo'
    delivery_latitude    = 30.0500
    delivery_longitude   = 31.2400
    items = @(
        @{ menu_item_id = 1; variant_id = 3; quantity = 2 },
        @{ menu_item_id = 3; quantity = 1 }
    )
} | ConvertTo-Json -Depth 5
$order = (Invoke-RestMethod -Uri "$base/customer/orders" -Method POST -Headers $ch -Body $body).data
$oid = $order.id
Write-Host ("  order #{0,-3} subtotal={1}c  delivery={2}c  surge={3}x  TOTAL={4}c  paymentRef={5}" `
            -f $oid, $order.subtotal, $order.delivery_fee, $order.surge_multiplier, $order.total, $order.payment_intent_id)

Write-Host "`n=== 4. Owner transitions: placed -> confirmed -> preparing ===" -ForegroundColor Cyan
$oh = AuthHeaders (Login 'owner@demo.test' 'password')
$s1 = (Invoke-RestMethod -Uri "$base/owner/orders/$oid/confirm"         -Method POST -Headers $oh).data.status
$s2 = (Invoke-RestMethod -Uri "$base/owner/orders/$oid/start-preparing" -Method POST -Headers $oh).data.status
Write-Host "  after confirm:         $s1"
Write-Host "  after start-preparing: $s2  (DispatchOrderJob queued)"

Write-Host "`n=== 5. Drain the queue so the dispatcher picks the nearest rider ===" -ForegroundColor Cyan
php artisan queue:work --stop-when-empty 2>&1 | Select-Object -Last 8

$o = (Invoke-RestMethod -Uri "$base/customer/orders/$oid" -Headers $ch).data
Write-Host ("  -> assigned rider_id={0}, status={1}" -f $o.rider_id, $o.status)

Write-Host "`n=== 6. Rider walks the order: preparing -> picked_up -> delivered ===" -ForegroundColor Cyan
# Find the assigned rider's email (rider_id maps to riders.id; users 3..5 are riders).
$riderEmail = "rider$($o.rider_id)@demo.test"
$rh = AuthHeaders (Login $riderEmail 'password')
$p1 = (Invoke-RestMethod -Uri "$base/rider/orders/$oid/picked-up" -Method POST -Headers $rh).data.status
$p2 = (Invoke-RestMethod -Uri "$base/rider/orders/$oid/delivered" -Method POST -Headers $rh).data.status
Write-Host "  after picked-up: $p1"
Write-Host "  after delivered: $p2"

Write-Host "`n=== 7. Customer rates the restaurant ===" -ForegroundColor Cyan
$rate = @{ target='restaurant'; stars=5; comment='Fast and tasty!' } | ConvertTo-Json
$r = (Invoke-RestMethod -Uri "$base/customer/orders/$oid/rate" -Method POST -Headers $ch -Body $rate).data
Write-Host ("  rating saved: {0} stars for {1} #{2}" -f $r.stars, $r.rateable_type, $r.rateable_id)

Write-Host "`n=== 8. Final order audit trail ===" -ForegroundColor Cyan
$final = (Invoke-RestMethod -Uri "$base/customer/orders/$oid" -Headers $ch).data
$final.status_history | Format-Table from_status, to_status, actor_type, occurred_at

Write-Host "DONE. Order #$oid is now: $($final.status)" -ForegroundColor Green
