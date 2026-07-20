<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "Order invoice PDF (resources/views/invoices/order.blade.php) is unbranded, unlike the order emails it's attached to")
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "Order invoice PDF (resources/views/invoices/order.blade.php) is unbranded, unlike the order emails it's attached to")
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
