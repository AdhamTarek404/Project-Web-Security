<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Fleshed out further in Phase 5 (ordering flow).
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'variant_id',
        'quantity',
        'unit_price',
        'line_total',
        'special_instructions',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MenuItemVariant::class, 'variant_id');
    }
}
