<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widens design_suggestions.status from enum('pending','kept','discarded')
     * to also allow 'published'. SQLite (used in tests/CI) can't ALTER an
     * existing enum/check constraint the way MySQL can, so the column is
     * rebuilt via a temporary column + copy + swap instead of ->change().
     */
    public function up(): void
    {
        Schema::table('design_suggestions', function (Blueprint $table) {
            $table->string('status_new')->default('pending');
        });

        DB::statement('UPDATE design_suggestions SET status_new = status');

        Schema::table('design_suggestions', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('design_suggestions', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });
    }

    public function down(): void
    {
        Schema::table('design_suggestions', function (Blueprint $table) {
            $table->enum('status_old', ['pending', 'kept', 'discarded'])->default('pending');
        });

        DB::statement("UPDATE design_suggestions SET status_old = CASE WHEN status = 'published' THEN 'kept' ELSE status END");

        Schema::table('design_suggestions', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('design_suggestions', function (Blueprint $table) {
            $table->renameColumn('status_old', 'status');
        });
    }
};
