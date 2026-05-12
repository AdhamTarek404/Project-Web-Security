<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Fleshed out in Phase 3 (state machine) and Phase 5 (ordering flow).
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'restaurant_id',
        'rider_id',
        'status',
        'subtotal',
        'delivery_fee',
        'surge_multiplier',
        'platform_fee',
        'restaurant_payout',
        'rider_payout',
        'total',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'placed_at',
        'confirmed_at',
        'preparing_at',
        'on_the_way_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
        'payment_intent_id',
    ];

    protected $casts = [
        // Cast 'status' as the OrderStatus enum so $order->status is a
        // typed value, not a raw string. Eloquent auto-converts on read/write.
        'status' => OrderStatus::class,
        'surge_multiplier' => 'decimal:2',
        'delivery_latitude' => 'decimal:7',
        'delivery_longitude' => 'decimal:7',
        'placed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'on_the_way_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('occurred_at');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
