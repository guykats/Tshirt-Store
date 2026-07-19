<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * stat_pieces_shipped / stat_rating / stat_countries were free-typed admin numbers
     * (see 2026_07_18_170100_create_site_settings_table.php) — exactly the fabricated
     * "1,200+ pieces shipped, 4.9/5 rating" investor stats the "Social proof & traction
     * section" task flagged. The homepage now computes its stats strip from real data
     * (completed order count, average rating from the reviews table, distinct
     * countries shipped to — see App\Http\Controllers\Api\HomeStatsController), so an
     * admin should no longer be able to type in an arbitrary number for this section at
     * all, not just be discouraged from it.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['stat_pieces_shipped', 'stat_rating', 'stat_countries']);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->unsignedInteger('stat_pieces_shipped')->default(12500);
            $table->decimal('stat_rating', 2, 1)->default(4.9);
            $table->unsignedInteger('stat_countries')->default(24);
        });
    }
};
