<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Color and size selector buttons on ProductDetail.jsx break the app\'s established aria-pressed toggle pattern')
            ->update([
                'status' => 'done',
                'commit_sha' => '5b3a449cbd12fb7b5b5f995b07f18363fb14f08e',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Color and size selector buttons on ProductDetail.jsx break the app\'s established aria-pressed toggle pattern')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
