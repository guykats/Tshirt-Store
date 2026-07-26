<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'No form field anywhere in the app sets an autoComplete attribute, breaking password-manager autofill and failing WCAG 1.3.5')
            ->update([
                'status' => 'done',
                'commit_sha' => 'da0daf43c6e0efd441990b5f1aea6e78bbee1cb1',
                'screenshot_path' => 'task-screenshots/autocomplete-attributes.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'No form field anywhere in the app sets an autoComplete attribute, breaking password-manager autofill and failing WCAG 1.3.5')
            ->update([
                'status' => 'todo',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
