<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            // Short attribution line (e.g. "Verified customer — Brooklyn, NY"), not a
            // review rating or any other number — testimonials are curated quotes, kept
            // deliberately separate from the reviews table's real, user-submitted ratings
            // so nothing on this card could be mistaken for an aggregated statistic.
            $table->string('author_context_en');
            $table->string('author_context_he');
            $table->text('quote_en');
            $table->text('quote_he');
            // Plain integer, ordered with a simple orderBy (not FIELD()/CASE — that's
            // only needed for ordering by a fixed set of string values, not a real
            // sortable column) so this stays SQLite-safe.
            $table->unsignedInteger('sort_order')->default(0);
            // Lets an admin retire a quote (e.g. it's gone stale) without losing the
            // row or renumbering everything else's sort_order.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
