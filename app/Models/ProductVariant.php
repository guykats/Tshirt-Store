<?php

namespace App\Models;

use App\Services\CatalogCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['size', 'color', 'sku', 'stock_quantity', 'price_override'])]
class ProductVariant extends Model
{
    protected static function booted(): void
    {
        static::created(fn () => CatalogCache::flush());
        static::updated(fn () => CatalogCache::flush());
        static::deleted(fn () => CatalogCache::flush());
    }

    protected function casts(): array
    {
        return [
            'price_override' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
