<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('project_tasks')->insert([
            [
                'epic_id' => null,
                'title' => "AnthropicClient's tool-round cap can execute a side-effecting tool call on its final round with no signal to the admin that anything was cut short",
                'description' => "converse()'s for loop (app/Services/AnthropicClient.php:41-83) is bounded by MAX_TOOL_ROUNDS = 5 (line 15). If round 5 itself comes back with stop_reason === 'tool_use', the loop still executes that round's tool handler -- e.g. propose_epic, which writes a real Epic row -- and appends the tool result to \$messages, but then exits on the for condition without ever sending that result back to the model for a natural-language reply. VisionerChatController (app/Http/Controllers/Api/VisionerChatController.php:45-69) is left with whatever \$textParts happened to accumulate from earlier rounds, often empty, falling back to its own '(No reply text -- check the epics board...)' placeholder, with zero indication the round cap was hit. An epic can get silently proposed to the board mid-conversation with no explanation ever surfacing in the chat transcript. Fix: detect stop_reason === 'tool_use' on the final round and either run one more round-trip or surface an explicit truncation notice.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'A shopper who abandons the PayPal popup mid-checkout has no way to resume that order -- only wait out the reservation window or start over',
                'description' => "Checkout.jsx:149-174 creates the order and PayPal order and moves it to a 'paying' state before the PayPal popup opens. If the shopper closes the popup, loses connectivity, or navigates away before onApprove()/capture() runs, the order persists server-side with a valid paypal_order_id and reserved stock, but Orders.jsx:56-115's per-order footer only ever renders an invoice link (payment_status === 'paid') or a cancel button (isCancellable) -- there is no button or route anywhere to re-enter the PayPal flow for that specific order. CheckoutController::capture() (app/Http/Controllers/Api/CheckoutController.php:243-295) is already fully callable again against the same order and OrderPolicy::capture only checks ownership, so the backend supports a resume; only the UI affordance is missing. The customer's only paths today are waiting up to checkout.reservation_minutes (default 60) for ExpireAbandonedOrders to auto-cancel, or starting an entirely new checkout, both of which lose an otherwise-recoverable sale. Fix: add a 'Complete payment' action on stalled orders in Orders.jsx that re-invokes the existing capture flow.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "About.jsx's Star-of-David hero art is the only primary page illustration in the app with no accessible label",
                'description' => "Every other page that uses a large, centered DesignArt as its primary illustrative motif passes a label prop -- Faq.jsx:97 (t('faq_art_label')), SizeGuide.jsx:36, Terms.jsx:32, Privacy.jsx:30, NotFound.jsx:14, Checkout.jsx:37-41 -- which DesignArt.jsx uses to set role=\"img\"/aria-label per its own documented convention of passing label when a DesignArt is the primary visual for what it depicts. About.jsx:16's <DesignArt motif=\"star-of-david\" className=\"h-40 w-40 rounded\" /> passes no label at all, so it renders aria-hidden=\"true\" -- silently decorative -- even though it's the single largest visual element on the page, and there is no unused about_art_label key anywhere in resources/js/i18n/index.js either; this was simply never wired up unlike every sibling page. Fix: add an about_art_label i18n key (EN + real Hebrew) and pass it as the label prop.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "DesignSettings.jsx's live motif preview updates visually as an admin changes the dropdown but stays invisible to screen readers",
                'description' => "DesignSettings.jsx:283-299 pairs a <select id=\"design-hero-motif\"> with a live <DesignArt motif={form.hero_motif} className=\"rounded\" /> preview at line 298 that re-renders on every dropdown change, but passes no label prop, so it's aria-hidden like the purely-decorative case -- except here the preview is the only visual confirmation of which motif is now selected. Every other place in the app that shows a DesignArt/GarmentMockup as a meaningful, non-decorative preview passes a label (e.g. ProductGallery.jsx's mainLabel); this admin-only live preview is the one exception, so a screen-reader-using admin gets no confirmation the selection actually changed beyond the already-read <option> text. Fix: pass a label prop (e.g. reflecting the currently selected motif's i18n name) so the live update is announced.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            "AnthropicClient's tool-round cap can execute a side-effecting tool call on its final round with no signal to the admin that anything was cut short",
            'A shopper who abandons the PayPal popup mid-checkout has no way to resume that order -- only wait out the reservation window or start over',
            "About.jsx's Star-of-David hero art is the only primary page illustration in the app with no accessible label",
            "DesignSettings.jsx's live motif preview updates visually as an admin changes the dropdown but stays invisible to screen readers",
        ])->delete();
    }
};
