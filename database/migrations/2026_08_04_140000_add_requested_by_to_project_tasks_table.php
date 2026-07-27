<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            // Set to the owner's name when a task is created directly from an
            // interactive chat session at their explicit request, as opposed
            // to an epic breakdown (epic_id is set instead) or autonomous
            // backlog seeding (neither is set). Board UI shows whichever of
            // epic_title/requested_by is present in the Epic column.
            $table->string('requested_by')->nullable()->after('agent_name');
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn('requested_by');
        });
    }
};
