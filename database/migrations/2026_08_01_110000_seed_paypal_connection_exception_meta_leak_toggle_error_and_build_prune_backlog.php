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
                'title' => 'PayPalClient only catches RequestException, so a network-level failure during checkout crashes with a raw 500 instead of degrading gracefully',
                'description' => "createOrder(), captureOrder(), and refundCapture() in app/Services/PayPalClient.php (lines 52-65, 73-79, 106-110) each wrap their HTTP call in try { ... } catch (RequestException \$e) { throw new RuntimeException(...) }, and getOrder() (line 84-87) has no try/catch at all. But Illuminate\\Http\\Client\\ConnectionException (thrown on DNS failure, connect timeout, or TLS handshake failure -- exactly the kind of transient network blip CLAUDE.md already documents as a fact of life on this host) is a sibling of RequestException, not a subtype: both extend HttpClientException directly (vendor/laravel/framework/src/Illuminate/Http/Client/ConnectionException.php and RequestException.php). A connection failure to api-m.paypal.com during checkout therefore propagates as a raw, unwrapped ConnectionException past every one of these catch blocks and past CheckoutController::store()'s catch (RuntimeException \$e) (line 226) / capture()'s catch (RuntimeException \$e) (line 257), producing an uncaught 500 instead of the built 'Unable to start PayPal checkout right now' message. In store() this happens after the order-creation DB::transaction() has already committed (stock already decremented), leaving a stray reserved-stock order with no paypal_order_id. Fix: also catch \\Illuminate\\Http\\Client\\ConnectionException (or its shared HttpClientException base) in all four PayPalClient methods and wrap it into the same RuntimeException.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'useDocumentMeta never clears the meta description tag when a page passes none, so a stale description leaks across client-side navigation',
                'description' => "useDocumentMeta(title, description) in resources/js/hooks/useDocumentMeta.js:13-16 only calls setMetaDescription(description) 'if (description)' -- it never resets the tag otherwise. Most pages (Login.jsx, Register.jsx, Orders.jsx, Dashboard.jsx, AccountSettings.jsx, TrackOrder.jsx, Wishlist.jsx, and a dozen more) call it with only a title, no description, while others (ProductDetail.jsx, Catalog.jsx, About.jsx, Privacy.jsx, SizeGuide.jsx) pass real page-specific text. Since this is an SPA with client-side react-router navigation and no full page reload, navigating from a product page straight to Login leaves that product's description sitting in the DOM's meta[name=description] tag indefinitely, mismatched with the page actually rendered. The sibling hook useJsonLd.js (lines 21-23) already handles exactly this by removing its tag when passed falsy data -- useDocumentMeta should do the equivalent (fall back to a default site description, or remove the tag) instead of silently leaving the previous page's text in place.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "ProjectProgress.jsx's toggleApproval() has no error handling at all, so a failed approve/unapprove POST fails silently with no on-screen feedback",
                'description' => "toggleApproval(task) in resources/js/pages/ProjectProgress.jsx:42-45 is 'await api.post(...); load();' with no try/catch, unlike its sibling toggleAutomation() in the same file (lines 47-58), which properly sets automationBusy/automationError state around the equivalent kind of POST. Task 202 ('ProjectProgress.jsx's board and PM-automation-toggle fetches have no .catch()') covers only the two GET fetches load() and loadAutomation() -- its own description actually cites 'its own approved_for_dev toggle actions' as the existing good error-handling pattern to match, which is incorrect: toggleApproval has zero error handling. Concretely: if POST /api/project-tasks/{id}/approve (or /unapprove) 500s or times out, the promise rejects unhandled, the button gives no visual feedback, and the admin -- the human gate this whole board exists to enforce -- has no way to tell whether the approval actually went through, risking either a task silently staying unbuilt or an admin re-clicking into a confusing toggled state. Fix: wrap the POST in try/catch and surface an error message the same way toggleAutomation already does.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'deploy.yml never prunes old Vite-hashed build files, so public/build grows unbounded on the production host across every deploy',
                'description' => "/public/build is git-ignored (.gitignore:18), so deploy.yml's 'git reset --hard origin/main' step never touches whatever is already on disk there. Vite content-hashes every JS/CSS output filename on each build (laravel-vite-plugin's default behavior, e.g. app-<hash>.js), and the 'Upload built frontend assets' step (appleboy/scp-action@v0.1.7, source: public/build/*, target: \${{ secrets.DEPLOY_PATH }}/public/build) only copies the current build's files onto the server additively -- nothing in the pipeline ever deletes files that aren't part of the current build. Since manifest.json is the only fixed-name file (so the live site always picks up the latest build correctly), every deploy on this very-frequently-deployed repo leaves the previous build's uniquely-hashed JS/CSS files behind forever, an unbounded disk-usage leak on the same production Hostinger box that also hosts the nightly database backups (task 65/100). Fix: add an 'rm -rf public/build/*' (or equivalent) on the remote immediately before the scp upload, or have one of the existing SSH steps clear the directory as part of its script.",
                'agent_name' => 'Ops Agent',
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
            'PayPalClient only catches RequestException, so a network-level failure during checkout crashes with a raw 500 instead of degrading gracefully',
            'useDocumentMeta never clears the meta description tag when a page passes none, so a stale description leaks across client-side navigation',
            "ProjectProgress.jsx's toggleApproval() has no error handling at all, so a failed approve/unapprove POST fails silently with no on-screen feedback",
            'deploy.yml never prunes old Vite-hashed build files, so public/build grows unbounded on the production host across every deploy',
        ])->delete();
    }
};
