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
                'title' => "VisionerChatController fetches the OLDEST messages once the chat log passes its limit, not the most recent ones",
                'description' => "VisionerChatController::index (app/Http/Controllers/Api/VisionerChatController.php:19) runs VisionerChatMessage::query()->orderBy('created_at')->limit(200)->get(), and store()'s history-building query (lines 39-40) does the identical orderBy('created_at')->limit(40)->get(). Ordering ascending then applying limit() selects the first N rows chronologically -- the oldest messages -- not the most recent N. Concrete scenario: once the Visioner chat log passes 200 total messages, /dashboard's VisionerChat.jsx panel permanently shows the earliest conversation from launch day and never displays anything newer; more seriously, once it passes 40 messages, every subsequent call to Anthropic in store() is given the oldest 40 messages as context instead of the recent conversation, so the AI's replies silently stop reflecting anything said in the last N messages. Fix: order descending with the limit, then reverse the collection before use/return.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "AddressController::store()'s 'first address becomes default' check has no lock, letting two concurrent saves both become the default",
                'description' => "AddressController::store (app/Http/Controllers/Api/AddressController.php:63-68) computes \$isFirstAddress = \$user->addresses()->doesntExist() and creates the address with is_default => \$isFirstAddress, with no transaction or row lock -- unlike setDefault() in the same file (lines 135-137), which correctly wraps its unset-then-set in DB::transaction. Concrete scenario: a brand-new customer's slow connection causes a double form submit (or two open tabs), and both store() requests evaluate doesntExist() as true before either insert commits, producing two Address rows for the same user both with is_default = true -- violating the single-default invariant checkout's default preselection and destroy()'s promotion logic both assume holds. Fix: wrap the first-address check and create in a transaction/lock, or enforce it with a partial unique index and re-check under lock.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Product gallery images and Design mockups accept any string as their motif key with no validation against the actual DesignArt registry",
                'description' => "ProductImage.url (a DesignArt motif keyword like star-of-david/menorah/hamsa, per its use as <GarmentMockup motif={image.url} .../> in ProductGallery.jsx) is validated in ProductImageController::validated (app/Http/Controllers/Api/Admin/ProductImageController.php:116) only as ['required','string','max:500'], and Design.mockup_url has no format/enum check anywhere in DesignController either. DesignArt.jsx's REGISTRY recognizes a fixed set of keys and silently falls back to star-of-david for anything else (DesignArt.jsx:169: REGISTRY[motif] || REGISTRY['star-of-david']). Concrete scenario: an admin adding a gallery image for a 'hamsa' product typos it as 'hamsa-' or 'Hamsa', the server accepts it with a 201, and every shopper who views that product/thumbnail silently sees the generic Star-of-David print instead -- with no error anywhere, admin or customer side, that the motif was wrong. Fix: validate url/mockup_url with Rule::in([...]) against the same key list the frontend registry uses, or centralize the list server-side.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Dashboard's admin order-search widget has no request sequencing, so a slower in-flight search can overwrite a newer one",
                'description' => "OrderSearchSection's runSearch() (resources/js/pages/Dashboard.jsx:463-480), fired from a useEffect on every search/page change, calls api.get('/api/orders', {params: {search, page}}) and unconditionally does setResults(res.data.data) in .then() with no request-id/abort-controller guard and no .catch() -- distinct from the already-tracked 'Every Dashboard admin widget fetch has no .catch()' ticket, which only covers the mount-time summary-widget loaders, not this search subcomponent. Concrete scenario: an admin searching orders types 'smith' then quickly refines to 'smithson'; if the first request's response arrives after the second's (plausible under real network jitter), the broader 'smith' results silently replace and stick over the correct 'smithson' results while the search box still shows 'smithson' -- and a failed search just leaves the table looking empty/stale with zero feedback. Fix: add a request-sequence guard (ignore stale responses) and a .catch() with an inline error state, mirroring whatever fix lands for the equivalent Catalog.jsx sequencing ticket.",
                'agent_name' => 'Dev Agent',
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
            "VisionerChatController fetches the OLDEST messages once the chat log passes its limit, not the most recent ones",
            "AddressController::store()'s 'first address becomes default' check has no lock, letting two concurrent saves both become the default",
            "Product gallery images and Design mockups accept any string as their motif key with no validation against the actual DesignArt registry",
            "Dashboard's admin order-search widget has no request sequencing, so a slower in-flight search can overwrite a newer one",
        ])->delete();
    }
};
