<?php

namespace App\Services;

use App\Exceptions\InvalidCouponException;
use App\Models\Coupon;

class CouponService
{
    /**
     * Validate that a looked-up coupon can be redeemed right now. Used by
     * `lockAndValidate` below, which row-locks the coupon first so it can't
     * be over-redeemed by two concurrent checkouts racing on the same
     * `max_redemptions` limit.
     *
     * @throws InvalidCouponException when the code is unknown, inactive,
     *   expired, or already fully redeemed.
     */
    public function validate(?Coupon $coupon): Coupon
    {
        if (! $coupon) {
            throw new InvalidCouponException('This coupon code is not valid.');
        }

        if (! $coupon->active) {
            throw new InvalidCouponException('This coupon code is not valid.');
        }

        if ($coupon->isExpired()) {
            throw new InvalidCouponException('This coupon code has expired.');
        }

        if ($coupon->isExhausted()) {
            throw new InvalidCouponException('This coupon code has already been fully redeemed.');
        }

        return $coupon;
    }

    /**
     * Row-lock the coupon (for use inside the checkout DB::transaction) and
     * validate it, so the redemption-count increment is race-safe.
     */
    public function lockAndValidate(string $code): Coupon
    {
        // Codes are stored uppercase and matched case-insensitively so
        // shoppers typing "save10" or "SAVE10" both work.
        $coupon = Coupon::where('code', strtoupper(trim($code)))->lockForUpdate()->first();

        return $this->validate($coupon);
    }

    /**
     * The discount amount a validated coupon yields against the given
     * subtotal, rounded to 2 decimal places and never exceeding the subtotal.
     */
    public function discountFor(Coupon $coupon, float $subtotal): float
    {
        return $coupon->discountFor($subtotal);
    }
}
