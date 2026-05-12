<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'license_plate',
        'current_latitude',
        'current_longitude',
        'last_location_at',
        'is_on_duty',
        'is_available',
        'stripe_account_id',
    ];

    protected $casts = [
        'current_latitude' => 'decimal:7',
        'current_longitude' => 'decimal:7',
        'last_location_at' => 'datetime',
        'is_on_duty' => 'boolean',
        'is_available' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ratings(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }
}
