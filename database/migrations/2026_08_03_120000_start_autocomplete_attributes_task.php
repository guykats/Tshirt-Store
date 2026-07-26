<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'No form field anywhere in the app sets an autoComplete attribute, breaking password-manager autofill and failing WCAG 1.3.5')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'No form field anywhere in the app sets an autoComplete attribute, breaking password-manager autofill and failing WCAG 1.3.5')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
