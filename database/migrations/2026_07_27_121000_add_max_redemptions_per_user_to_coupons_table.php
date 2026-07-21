<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Nullable = no per-customer cap (today's unlimited-per-customer
            // behavior, preserved by default). When set, CouponService
            // rejects redemption once the buyer's own prior orders using
            // this code (excluding cancelled/refunded ones, which already
            // release their redemption back to the global count — see
            // OrderStockService::releaseCoupon) reach this number, on top of
            // the existing global max_redemptions cap.
            $table->unsignedInteger('max_redemptions_per_user')->nullable()->after('max_redemptions');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('max_redemptions_per_user');
        });
    }
};
