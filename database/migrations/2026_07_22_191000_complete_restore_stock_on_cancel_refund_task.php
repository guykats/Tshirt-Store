<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Cancelling or refunding an order never restores the reserved stock it decremented')
            ->update([
                'status' => 'done',
                'commit_sha' => 'c9d61bd95435b518da9803ca43cbfd893a7c4116',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Cancelling or refunding an order never restores the reserved stock it decremented')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
