<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name', 100)->unique();
            $table->enum('status', ['idle', 'pending_approval', 'executing'])->default('idle');
            $table->string('current_task', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_statuses');
    }
};
