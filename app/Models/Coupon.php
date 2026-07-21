<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code', 'type', 'value', 'expires_at', 'max_redemptions', 'max_redemptions_per_user', 'redemptions_count', 'active',
])]
class Coupon extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'expires_at' => 'datetime',
            'max_redemptions' => 'integer',
            'max_redemptions_per_user' => 'integer',
            'redemptions_count' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Codes are always stored uppercase so lookups can match case-insensitively
     * (shoppers may type "save10" or "SAVE10") without relying on the
     * database's collation.
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtoupper(trim($value)),
        );
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_redemptions !== null && $this->redemptions_count >= $this->max_redemptions;
    }

    /**
     * Whether this coupon can currently be redeemed at all (ignoring the
     * specific subtotal it would be applied to).
     */
    public function isRedeemable(): bool
    {
        return $this->active && ! $this->isExpired() && ! $this->isExhausted();
    }

    /**
     * The discount this coupon yields against the given subtotal, capped so
     * it never pushes the total below zero.
     */
    public function discountFor(float $subtotal): float
    {
        $raw = $this->type === 'percent'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min(max($raw, 0), $subtotal), 2);
    }
}
