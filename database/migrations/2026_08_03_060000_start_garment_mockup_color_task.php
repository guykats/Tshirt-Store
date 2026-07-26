<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "GarmentMockup only recognizes two hard-coded, case-sensitive color names - most variant colors render the wrong garment color")
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "GarmentMockup only recognizes two hard-coded, case-sensitive color names - most variant colors render the wrong garment color")
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
