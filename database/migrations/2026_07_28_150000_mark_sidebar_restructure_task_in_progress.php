<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Restructure the remaining store-admin sidebar so Store, Settings, and System read as visually distinct zones')
            ->update([
                'status' => 'in_progress',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Status transition only; no reversible structural change.
    }
};
