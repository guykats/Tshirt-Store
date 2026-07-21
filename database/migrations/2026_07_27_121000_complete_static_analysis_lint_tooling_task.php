<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $title = 'No static analysis/lint tooling configured for either half of the stack';

    private string $note = "\n\nDone: larastan/larastan (level 4) with a generated phpstan-baseline.neon "
        . "for the pre-existing findings, plus eslint 9 (flat config) + eslint-plugin-react + "
        . "eslint-plugin-react-hooks, both wired to `composer analyse` / `npm run lint`. "
        . "CI wiring (a lint/analyse step in .github/workflows/tests.yml) is NOT included and is "
        . "blocked on the same GitHub App workflows-permission wall that blocked tasks 61/65/67/69/94 "
        . "— this repo's token cannot push changes to .github/workflows/*.yml. A follow-up task should "
        . "add that step once workflow-file access is available.";

    public function up(): void
    {
        $task = DB::table('project_tasks')->where('title', $this->title)->first();

        DB::table('project_tasks')
            ->where('title', $this->title)
            ->update([
                'status' => 'done',
                'description' => ($task->description ?? '') . $this->note,
                'commit_sha' => '5b6eadb1b69c83817958ddd74c0a8d7a8cf06038',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', $this->title)
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
