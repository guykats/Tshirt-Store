<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The prior "complete" migration recorded the sha of the implementation
        // commit before a required `git pull --rebase origin main` (to pick up
        // concurrently-shipped task #117) rewrote it. Correct the reference to
        // the actual, post-rebase sha of that commit.
        DB::table('project_tasks')
            ->where('title', 'Restructure the remaining store-admin sidebar so Store, Settings, and System read as visually distinct zones')
            ->update([
                'commit_sha' => '492ddfe4a626dcdaaae5b8b29240600d5973ffc4',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Data correction only; no reversible structural change.
    }
};
