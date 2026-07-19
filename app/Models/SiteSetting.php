<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'logo_path', 'accent_color',
    'hero_tagline_en', 'hero_tagline_he',
    'hero_subheading_en', 'hero_subheading_he',
    'hero_motif',
    'stat_pieces_shipped', 'stat_rating', 'stat_countries',
])]
class SiteSetting extends Model
{
    protected function casts(): array
    {
        return [
            'stat_pieces_shipped' => 'integer',
            'stat_rating' => 'float',
            'stat_countries' => 'integer',
        ];
    }

    /**
     * This is a singleton settings row (id=1, seeded by a data migration). Fall back to
     * creating it on the fly if it's somehow missing, rather than 500ing the homepage.
     * Every column is passed explicitly (mirroring
     * database/migrations/2026_07_18_170200_seed_default_site_settings.php) rather than
     * relying on the schema's column defaults: Eloquent's in-memory attributes after
     * create() only reflect what was actually passed in, not what the database filled
     * in via a DEFAULT clause, so omitting a field here would leave it null on the
     * model even though the row itself has a sensible value.
     */
    public static function current(): self
    {
        return static::query()->find(1) ?? static::create([
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
        ]);
    }
}
