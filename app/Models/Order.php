<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number', 'status', 'subtotal', 'tax_amount', 'shipping_amount', 'total_amount', 'currency',
    'paypal_order_id', 'paypal_transaction_id', 'payment_status', 'shipping_address_id', 'billing_address_id', 'notes',
    'approved_by', 'approved_at',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Whether the order's owner can still self-service cancel it: only while
     * it's in a pre-fulfillment status and payment hasn't already been
     * captured. Once it's processing/shipped/delivered (or payment has been
     * captured), cancellation has to go through support instead.
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending_approval', 'approved'], true)
            && $this->payment_status !== 'paid';
    }
}
