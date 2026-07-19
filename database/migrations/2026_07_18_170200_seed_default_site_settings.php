<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Defaults intentionally mirror what Catalog.jsx's hero currently renders from
     * resources/js/i18n/index.js (hero_title / hero_subtitle, en + he) so switching
     * the homepage over to reading from site_settings is not a visual regression.
     */
    public function up(): void
    {
        DB::table('site_settings')->insert([
            'id' => 1,
            'logo_path' => null,
            'accent_color' => '#8c6a3f',
            'hero_tagline_en' => 'Jewish identity, worn with quiet pride.',
            'hero_tagline_he' => 'זהות יהודית, נלבשת בגאווה שקטה.',
            'hero_subheading_en' => 'Understated apparel carrying real cultural symbols — for the moments you want to say who you are without saying a word.',
            'hero_subheading_he' => 'בגדים מאופקים הנושאים סמלים תרבותיים אמיתיים — לרגעים שבהם רוצים לומר מי אתם, בלי להגיד מילה.',
            'hero_motif' => 'star-of-david',
            'stat_pieces_shipped' => 12500,
            'stat_rating' => 4.9,
            'stat_countries' => 24,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('site_settings')->where('id', 1)->delete();
    }
};
