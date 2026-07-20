<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Set the moment a checkout decrement first takes this variant to
            // or below InventoryController::DEFAULT_THRESHOLD and an admin is
            // notified (see App\Notifications\LowStockAlert). Kept null again
            // once the variant is restocked above the threshold, so the next
            // time it crosses back down it alerts again instead of staying
            // permanently silenced.
            $table->timestamp('low_stock_alerted_at')->nullable()->after('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('low_stock_alerted_at');
        });
    }
};
