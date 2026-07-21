<?php

namespace App\Models;

use App\Services\CatalogCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'user_id', 'order_id', 'rating', 'body'])]
class Review extends Model
{
    /**
     * A review's rating/count feeds the product's cached aggregateRating in
     * JSON-LD (see ProductResource), so any write needs to invalidate the
     * same catalog cache that product/variant/image writes already bust.
     */
    protected static function booted(): void
    {
        static::created(fn () => CatalogCache::flush());
        static::updated(fn () => CatalogCache::flush());
        static::deleted(fn () => CatalogCache::flush());
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
