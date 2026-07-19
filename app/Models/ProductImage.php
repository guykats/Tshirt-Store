<?php

namespace App\Models;

use App\Services\CatalogCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'url', 'alt_text', 'color', 'position'])]
class ProductImage extends Model
{
    protected static function booted(): void
    {
        static::created(fn () => CatalogCache::flush());
        static::updated(fn () => CatalogCache::flush());
        static::deleted(fn () => CatalogCache::flush());
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
