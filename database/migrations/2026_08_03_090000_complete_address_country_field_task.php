<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "Checkout and account address forms hardcode country: US with no country field ever rendered")
            ->update([
                'status' => 'done',
                'commit_sha' => 'feb45c6eeca8da7caf07a09da1eb53b7071966fa',
                'screenshot_path' => 'task-screenshots/address-country-field-checkout-he.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "Checkout and account address forms hardcode country: US with no country field ever rendered")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
