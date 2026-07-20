<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "Order invoice PDF (resources/views/invoices/order.blade.php) is unbranded, unlike the order emails it's attached to")
            ->update([
                'status' => 'done',
                'commit_sha' => '78aca29af1b87279bf139c48e2ac20dec756a2b5',
                'screenshot_path' => 'task-screenshots/order-invoice-branded.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "Order invoice PDF (resources/views/invoices/order.blade.php) is unbranded, unlike the order emails it's attached to")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
