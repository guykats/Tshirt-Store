<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "A declined PayPal capture still counts against a coupon's per-customer redemption cap for up to an hour")
            ->update([
                'description' => DB::table('project_tasks')
                    ->where('title', "A declined PayPal capture still counts against a coupon's per-customer redemption cap for up to an hour")
                    ->value('description')
                    . " Overlaps with the broader ticket tracking that declined/failed captures never call OrderStockService::restore() at all (stock included, not just the coupon cap) -- implementing that fix (calling restore() from both failure paths) resolves this ticket's symptom as a side effect, so build whichever lands first and close the other as satisfied rather than duplicating the restore() call.",
                'updated_at' => now(),
            ]);

        DB::table('project_tasks')
            ->where('title', 'A declined or failed PayPal capture never releases the stock or coupon redemption it reserved')
            ->update([
                'description' => DB::table('project_tasks')
                    ->where('title', 'A declined or failed PayPal capture never releases the stock or coupon redemption it reserved')
                    ->value('description')
                    . " Subsumes the narrower ticket tracking the coupon-cap symptom of this same gap (counted against a customer's per-coupon redemption cap for up to an hour) -- this fix resolves that one too, so there's no need to build both.",
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Descriptive-text-only bookkeeping change; not meaningfully reversible.
    }
};
