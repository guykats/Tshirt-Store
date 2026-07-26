<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Checkout has no <form> element, so pressing Enter after filling in the address never submits the order')
            ->update([
                'status' => 'done',
                'commit_sha' => '92ce772bd0a19eda2970e94e2a35099f723f35e0',
                'screenshot_path' => 'task-screenshots/checkout-form-enter-submits.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Checkout has no <form> element, so pressing Enter after filling in the address never submits the order')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
