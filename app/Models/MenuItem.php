<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'base_price',
        'image_path',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MenuItemVariant::class);
    }

    // Accessor: $item->current_price returns the base price plus the
    // DEFAULT variant's modifier (the one preselected in the UI).
    // If no default variant exists, it's just the base price.
    // Used by the customer-facing menu so the displayed price is
    // what the customer will actually pay if they don't change the variant.
    protected function currentPrice(): Attribute
    {
        return Attribute::get(function () {
            $default = $this->variants->firstWhere('is_default', true);
            return $this->base_price + ($default?->price_modifier ?? 0);
        });
    }

    // Used at checkout time (Phase 5) to compute the unit price
    // for a specific (item, variant) pair the customer chose.
    public function priceForVariant(?int $variantId): int
    {
        if ($variantId === null) {
            return $this->base_price;
        }

        $variant = $this->variants->firstWhere('id', $variantId);

        if (! $variant) {
            // Defensive: if a variant id doesn't belong to this item, fall back.
            return $this->base_price;
        }

        return $this->base_price + (int) $variant->price_modifier;
    }
}
