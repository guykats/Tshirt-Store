<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'addresses.user_id has no explicit index, unlike orders.user_id which got one for the same reason')
            ->update([
                'status' => 'done',
                'commit_sha' => '061628e08d543e53ecbdd2deb7cceb72ae26df38',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'addresses.user_id has no explicit index, unlike orders.user_id which got one for the same reason')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
