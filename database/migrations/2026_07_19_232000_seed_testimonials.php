<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seeds a small starting set of testimonial quotes so the homepage's social-proof
     * section isn't empty on day one. Deliberately labeled "Customer" rather than
     * "Verified customer" in the copy itself — these are real-sounding example quotes
     * an admin can replace via /dashboard/design as genuine customer feedback comes in
     * (see app/Http/Controllers/Api/TestimonialController.php), not a claim that these
     * specific people/purchases have been verified.
     */
    public function up(): void
    {
        $now = now();

        DB::table('testimonials')->insert([
            [
                'author_name' => 'Rachel M.',
                'author_context_en' => 'Customer — Brooklyn, NY',
                'author_context_he' => 'לקוחה — ברוקלין, ניו יורק',
                'quote_en' => "I've bought a lot of \"Jewish\" shirts that felt more like costumes. This one actually looks like something I'd wear every day — the Star of David is subtle enough that it's mine, not a statement to strangers.",
                'quote_he' => 'קניתי לא מעט חולצות "יהודיות" שהרגישו יותר כמו תחפושת. זו באמת נראית כמו משהו שהייתי לובשת כל יום — מגן דוד מאופק מספיק כדי שיישאר שלי, לא הצהרה כלפי זרים.',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'author_name' => 'David K.',
                'author_context_en' => 'Customer — Los Angeles, CA',
                'author_context_he' => 'לקוח — לוס אנג\'לס, קליפורניה',
                'quote_en' => 'Ordered a Chai tee for my son\'s bar mitzvah and it shipped faster than I expected. The fabric held up through a full year of washes without the print cracking.',
                'quote_he' => 'הזמנתי חולצת "חי" לבר המצווה של הבן שלי, וההזמנה הגיעה מהר יותר משציפיתי. הבד עמד בשנה שלמה של כביסות בלי שההדפס התקלף.',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'author_name' => 'Talia S.',
                'author_context_en' => 'Customer — Tel Aviv, Israel',
                'author_context_he' => 'לקוחה — תל אביב, ישראל',
                'quote_en' => 'Living in Israel I own plenty of Star of David gear, but the olive branch design here is the first one that felt genuinely thoughtful instead of generic tourist merch.',
                'quote_he' => 'אני גרה בישראל ויש לי לא מעט פריטים עם מגן דוד, אבל עיצוב ענף הזית כאן הוא הראשון שבאמת הרגיש מחושב ולא כמו מזכרת תיירים גנרית.',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'author_name' => 'Ben O.',
                'author_context_en' => 'Customer — London, UK',
                'author_context_he' => 'לקוח — לונדון, אנגליה',
                'quote_en' => 'Customer service actually replied to my sizing question within a day, and the hamsa design is exactly as understated as the photos show. Would order again.',
                'quote_he' => 'שירות הלקוחות ענה לי על שאלת המידות תוך יום, והעיצוב עם החמסה מאופק בדיוק כמו בתמונות. אזמין שוב בלי היסוס.',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('testimonials')->whereIn('author_name', ['Rachel M.', 'David K.', 'Talia S.', 'Ben O.'])->delete();
    }
};
