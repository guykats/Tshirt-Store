<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('agent_name', 100);
            $table->enum('status', ['todo', 'in_progress', 'blocked', 'done'])->default('todo');
            $table->string('task_type', 50)->default('feature');
            $table->string('commit_sha', 40)->nullable();
            $table->string('screenshot_path')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['agent_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
