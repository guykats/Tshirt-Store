<?php

namespace App\Services;

use App\Exceptions\InvalidCouponException;
use App\Models\Coupon;
use App\Models\User;

class CouponService
{
    /**
     * Order statuses that already released whatever coupon redemption they
     * consumed back to the coupon's global redemptions_count (see
     * OrderStockService::releaseCoupon) — excluded here too so a buyer whose
     * order was cancelled/refunded isn't permanently penalized against their
     * own per-customer cap for a redemption that no longer counts globally.
     */
    protected const RELEASED_ORDER_STATUSES = ['cancelled', 'refunded'];

    /**
     * Validate that a looked-up coupon can be redeemed right now, optionally
     * against a specific buyer's own redemption history. Used by
     * `lockAndValidate` below, which row-locks the coupon first so it can't
     * be over-redeemed by two concurrent checkouts racing on the same
     * `max_redemptions` limit.
     *
     * @throws InvalidCouponException when the code is unknown, inactive,
     *   expired, already fully redeemed globally, or the given buyer has
     *   already hit the coupon's per-customer cap.
     */
    public function validate(?Coupon $coupon, ?User $buyer = null): Coupon
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

        if ($buyer && $this->customerLimitReached($coupon, $buyer)) {
            throw new InvalidCouponException('You have already used this coupon the maximum number of times allowed.');
        }

        return $coupon;
    }

    /**
     * Row-lock the coupon (for use inside the checkout DB::transaction) and
     * validate it, so the redemption-count increment is race-safe. Pass the
     * buyer placing the order so a per-customer cap (if set) can be enforced
     * against their own past orders.
     */
    public function lockAndValidate(string $code, ?User $buyer = null): Coupon
    {
        // Codes are stored uppercase and matched case-insensitively so
        // shoppers typing "save10" or "SAVE10" both work.
        $coupon = Coupon::where('code', strtoupper(trim($code)))->lockForUpdate()->first();

        return $this->validate($coupon, $buyer);
    }

    /**
     * Whether this buyer has already redeemed this coupon on
     * max_redemptions_per_user (or more) of their own orders. Orders whose
     * redemption was already released back to the coupon (cancelled/
     * refunded) don't count against the cap. No-op (always false) for
     * coupons with no per-customer cap configured.
     */
    protected function customerLimitReached(Coupon $coupon, User $buyer): bool
    {
        if ($coupon->max_redemptions_per_user === null) {
            return false;
        }

        $usedCount = $buyer->orders()
            ->where('discount_code', $coupon->code)
            ->whereNotIn('status', self::RELEASED_ORDER_STATUSES)
            ->count();

        return $usedCount >= $coupon->max_redemptions_per_user;
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
