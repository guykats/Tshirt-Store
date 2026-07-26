<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "DesignSettings.jsx's live motif preview updates visually as an admin changes the dropdown but stays invisible to screen readers")
            ->update([
                'status' => 'done',
                'commit_sha' => '7fd282f92e9c19c80fd37f0b0b76c92b73f5376a',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "DesignSettings.jsx's live motif preview updates visually as an admin changes the dropdown but stays invisible to screen readers")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
