<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            // Nullable: no logo asset exists yet (the brand currently renders as text,
            // see Layout.jsx's app_name link) — admins can add one without a deploy.
            $table->string('logo_path')->nullable();
            $table->string('accent_color', 7)->default('#8c6a3f');
            // Bilingual pairs, not a single field: the hero currently renders from
            // en/he i18n keys (hero_title / hero_subtitle) and this site is bilingual
            // by design (see CLAUDE.md) — a single-language settings field would make
            // the Hebrew homepage regress the moment an admin edits it.
            // Column defaults mirror the seed data migration (and, in turn, what
            // Catalog.jsx's hero currently hardcodes via i18n) so a fallback row
            // created outside that migration is never a visual regression. Only the
            // (short) string columns get a schema-level default — MySQL's historical
            // restriction on TEXT/BLOB column defaults makes that unportable for the
            // longer subheading fields, so those are filled in explicitly wherever a
            // row is created (the seed migration, and SiteSetting::current()'s
            // fallback) instead.
            $table->string('hero_tagline_en')->default('Jewish identity, worn with quiet pride.');
            $table->string('hero_tagline_he')->default('זהות יהודית, נלבשת בגאווה שקטה.');
            $table->text('hero_subheading_en');
            $table->text('hero_subheading_he');
            // Keys into the REGISTRY in resources/js/components/DesignArt.jsx
            // (star-of-david, menorah, chai, shalom, hamsa, pomegranate, aleph,
            // olive-branch, hebrew-script) rather than a raster image reference —
            // this catalog has no uploaded image assets, only inline SVG line art.
            $table->string('hero_motif')->default('star-of-david');
            $table->unsignedInteger('stat_pieces_shipped')->default(12500);
            $table->decimal('stat_rating', 2, 1)->default(4.9);
            $table->unsignedInteger('stat_countries')->default(24);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
