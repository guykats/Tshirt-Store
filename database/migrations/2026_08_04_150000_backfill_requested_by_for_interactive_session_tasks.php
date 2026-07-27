<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $titles = [
        'Fix real crash: sync commands didn\'t catch ConnectionException',
        'Fix real crash: disable-if-idle curl step had no timeout',
        'Add an Approved status filter to the project board',
    ];

    public function up(): void
    {
        // Backfill only the tasks this interactive session can actually
        // attest were created directly at the owner's request - not a
        // guess applied across the whole historical board, most of which
        // was shipped by the unattended pm-agent.yml cron.
        DB::table('project_tasks')
            ->whereIn('title', $this->titles)
            ->update(['requested_by' => 'Guy', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->whereIn('title', $this->titles)
            ->update(['requested_by' => null, 'updated_at' => now()]);
    }
};
