<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data-only migration: the agent_statuses dashboard widget went live in Milestone 4 but was
     * never actually updated as work happened, so it sat showing IDLE for every agent through
     * Milestones 3-5 and the whole Phase 2 redesign despite real progress. This is a one-time
     * catch-up snapshot; see the accompanying /api/activity endpoint for a feed that stays
     * accurate automatically going forward instead of needing another migration like this one.
     */
    public function up(): void
    {
        $tasks = [
            'Orchestrator' => 'Coordinated Milestones 3-5 + Phase 2 brand redesign; shipped to store.guykats.com',
            'Trend Agent' => 'Wrote demo catalog copy for 7 products with cultural context',
            'Creative Agent' => 'Designed 9 original SVG motifs (Star of David, Menorah, Chai, Shalom, Hamsa, Rimon, Aleph, Hebrew Script, Olive Branch) + brand design system',
            'Dev Agent' => 'Built PayPal checkout, invoice/email automation, dashboard, 33 API tests',
            'Ops Agent' => 'Wired PDF invoice generation + localized order confirmation emails',
        ];

        foreach ($tasks as $agent => $task) {
            DB::table('agent_statuses')
                ->where('agent_name', $agent)
                ->update(['status' => 'idle', 'current_task' => $task, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('agent_statuses')->update(['status' => 'idle', 'current_task' => null]);
    }
};
