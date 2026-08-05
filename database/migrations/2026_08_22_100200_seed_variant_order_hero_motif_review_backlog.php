<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $backlog = [
            [
                'title' => 'Product::variants() has no explicit ordering, so size/color buttons can render in a scrambled, unstable order',
                'description' => "app/Models/Product.php:44-47 defines `variants()` with no `->orderBy()` at all, unlike the sibling `images()` relation a few lines below (line 56-59), which explicitly chains `->orderBy('position')->orderBy('id')` specifically so gallery order is stable rather than relying on undefined DB row order. resources/js/pages/ProductDetail.jsx and Checkout.jsx both render their size/color pickers directly from `product.variants` with no client-side sort. Since product_variants has both a product_id index and a unique (product_id, size, color) index, MySQL is free to satisfy the eager-loaded query via either index rather than insertion order, and that ordering isn't guaranteed stable across query-plan changes, table growth, or an admin editing a variant. Failure scenario: a shopper sees size buttons rendered as e.g. \"XL, S, L, M, XXL\" instead of ascending order, and that order can change between page loads. Fix by adding an explicit ->orderBy() to variants() (e.g. a CASE expression matching the size enum's natural order, then id), the same way images() already does.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "hero_motif validation and Design Settings' motif dropdown were never updated after Catalog Depth added 7 new motifs",
                'description' => "SiteSettingController::update's hero_motif validation rule (app/Http/Controllers/Api/SiteSettingController.php:41, `in:star-of-david,menorah,chai,shalom,hamsa,pomegranate,aleph,olive-branch,hebrew-script`) and DesignSettings.jsx's MOTIF_OPTIONS constant (resources/js/pages/DesignSettings.jsx:7-17) both still only list the original 9 motifs. But DesignArt.jsx's REGISTRY (resources/js/components/DesignArt.jsx:170-175) has since grown 7 more real, live-product motifs (emunah, bracha, tikvah, ahava, simcha, emet, or) that are already used as actual product designs elsewhere in the app. Failure scenario: an admin opens Design Settings to set the homepage hero art to one of these newer motifs (e.g. \"ahava\"/love, already selling as a real product) and won't find it in the dropdown, and even a hand-crafted PATCH /api/site-settings with hero_motif=ahava is rejected with a 422 by the backend's stale in: rule. The homepage's hero branding is permanently capped at 9 of the now-16 available motifs with no error ever explaining why. Fix by extending both the validation rule and MOTIF_OPTIONS to the current full REGISTRY list.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "ProductReviews.jsx's stale success flag hides the write-review form after deleting your own review",
                'description' => "handleDelete() (resources/js/components/ProductReviews.jsx:114-125) resets eligibility to { can_review: true, already_reviewed: false } after a successful delete, but never resets the success state that handleSubmit() set to true when the review was originally submitted. Concrete scenario: a customer writes a review, success becomes true, the write-review form disappears behind the !success guard (line 255), replaced by a \"your review was submitted\" message (line 253). They then delete that same review from the list below. eligibility.can_review flips back to true, but since the form's render guard `user && eligibility?.can_review && !success` still evaluates !success as false, the write-review form never reappears — the customer is stuck seeing a stale \"submitted successfully\" message for a review that no longer exists, with no visible way to write a new one short of a full page reload. Fix by also calling setSuccess(false) inside handleDelete().",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
        ];

        foreach ($backlog as $task) {
            DB::table('project_tasks')->insert(array_merge([
                'epic_id' => null,
                'commit_sha' => null,
                'screenshot_path' => null,
                'blocked_reason' => null,
                'completed_at' => null,
            ], $task, [
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Product::variants() has no explicit ordering, so size/color buttons can render in a scrambled, unstable order',
            "hero_motif validation and Design Settings' motif dropdown were never updated after Catalog Depth added 7 new motifs",
            "ProductReviews.jsx's stale success flag hides the write-review form after deleting your own review",
        ])->delete();
    }
};
