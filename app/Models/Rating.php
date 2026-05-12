<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// Polymorphic: a rating points at either a Restaurant or a Rider.
class Rating extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id',
        'rateable_type',
        'rateable_id',
        'stars',
        'comment',
    ];

    public function rateable(): MorphTo
    {
        return $this->morphTo();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
