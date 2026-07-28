<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kept deliberately separate from `designs` (see the "Daily Design
        // Suggestion Feed" epic) so a high-volume nightly churn of candidates
        // doesn't compound the already-logged Design bugs (missing archive
        // path, no re-approve/re-reject guard, cache not flushed on
        // decision). Only a `keep()`d suggestion ever produces a real Design
        // row, via `promoted_design_id`.
        Schema::create('design_suggestions', function (Blueprint $table) {
            $table->id();
            $table->date('batch_date');
            $table->string('motif');
            // Reserved for the separate, still-proposed "Style-Variant Design
            // System Expansion" epic (not approved) — left unused/null by
            // this task on purpose.
            $table->string('style')->nullable();
            $table->enum('status', ['pending', 'kept', 'discarded'])->default('pending');
            $table->foreignId('promoted_design_id')->nullable()->constrained('designs')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_suggestions');
    }
};
