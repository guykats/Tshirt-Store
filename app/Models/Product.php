<?php

namespace App\Models;

use App\Services\CatalogCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['design_id', 'name', 'slug', 'description', 'base_price', 'currency', 'sku', 'status'])]
class Product extends Model
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
            'base_price' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Design, $this>
     */
    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Ordered gallery images — `position` first (admin-controlled order), then `id` as a
     * stable, portable (no FIELD()/no MySQL-only tie-break) fallback for images created
     * with the same position.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<WishlistItem, $this>
     */
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }
}
