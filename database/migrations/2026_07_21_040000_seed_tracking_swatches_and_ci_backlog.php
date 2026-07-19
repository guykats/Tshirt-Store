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
                'title' => 'Shipment tracking number and carrier on orders',
                'description' => 'The FAQ\'s "Order Tracking" category (resources/js/pages/Faq.jsx, faq_cat_tracking_title) tells shoppers to check their Orders page for status, but there is no tracking number or carrier anywhere in the schema — orders only have a status enum (pending_approval/approved/processing/shipped/delivered), and OrderController::advanceStatus (app/Http/Controllers/Api/OrderController.php) sends OrderShippedMail (app/Mail/OrderShippedMail.php, resources/views/emails/order-shipped.blade.php) with no way to reference an actual carrier tracking page. Add nullable tracking_number and carrier columns to orders (migration + fillable on App\Models\Order), let an admin supply both when advancing an order to "shipped" (extend the advanceStatus request validation and the admin dashboard\'s advance-status control to accept them, required only when the target status is shipped), surface both in OrderResource, on the customer Orders page (resources/js/pages/Orders.jsx) and the order invoice (InvoiceService / OrderController::invoice), and include a real clickable tracking link in OrderShippedMail\'s bilingual email view — a carrier-to-URL-pattern map for at least USPS, UPS, FedEx, and Israel Post (falling back to just showing the plain tracking number and carrier name if the carrier isn\'t recognized) rather than a fabricated/guessed tracking URL. Write feature tests covering: advancing to shipped without a tracking number is rejected, advancing to shipped with tracking number + carrier succeeds and the mail contains the tracking info, and the invoice/order resource expose the new fields once set.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Frontend automated test harness (Vitest + React Testing Library)',
                'description' => 'There is no JavaScript test tooling in this repo at all — package.json (resources/js) has zero test dependencies and no "test" script, so every one of the ~30 React pages/components (i18n locale/RTL toggling in Layout.jsx, the Faq/SizeGuide/Privacy/Terms static pages, ProductDetail\'s variant selection logic, Checkout) is verified only by hand or transitively through PHPUnit feature tests that never actually render React. Add Vitest + @testing-library/react + @testing-library/jest-dom (jsdom environment), wire a "test" script into package.json and a step into .github/workflows/tests.yml (after the existing "Install JS dependencies" step, before the PHP test step, so a broken frontend test fails CI same as a broken PHP one), and write an initial real test suite covering: (1) Layout.jsx\'s toggleLocale actually swaps i18n language, localStorage, and document.dir/lang between en/rtl and he/rtl, (2) Faq.jsx\'s accordion expands/collapses a question and renders the size-guide link for the sizing category, (3) ProductDetail\'s size/color selector disables genuinely out-of-stock combinations and enables the buy button only once a variant is fully selected. Mock resources/js/lib/api.js calls with vi.mock rather than hitting a real server. This is a tooling foundation task — keep scope to the harness plus these three suites, not a full-coverage rewrite.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'infra',
            ],
            [
                'title' => 'Real color swatches on the product variant selector',
                'description' => 'ProductDetail.jsx\'s color selector (see the buttons rendered from product.variants.map for color, around the "Color" label) renders every variant color as a plain text pill ("Black", "Sand", etc.) with no visual swatch, even though app/Http/Controllers/Api/AdminProductVariantController.php and resources/js/pages/ProductManagement.jsx accept color as a free-text string an admin can set to anything. Add a small color-name-to-swatch mapping (a reasonable static dictionary of common apparel color names to hex values, e.g. black/white/sand/heather-grey/navy/olive/burgundy, following DesignArt.jsx\'s restraint — a plain circular swatch, not a photo) rendered inside or beside each color pill button, with a graceful fallback (text-only pill, exactly like today) for any color name not in the dictionary so an admin typing an arbitrary color never breaks the page. The swatch itself is decorative next to the visible color name text, so mark it aria-hidden (the existing text label remains the accessible name) — do not let a bare colored circle be the only indication of which color is which for screen reader users. Apply the same swatch treatment anywhere else a bare color name is rendered as a selectable option (check ProductManagement.jsx\'s variant table/form for consistency, though the admin form itself can stay plain text input). Add a Playwright screenshot of the updated product page as evidence.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'content',
            ],
            [
                'title' => 'CI dependency vulnerability scanning',
                'description' => 'The "Merge pending Dependabot dependency PRs" task (project_tasks id with title of that name, currently blocked on GitHub App workflow-write permission) showed this repo relies entirely on Dependabot\'s weekly PR cadence to learn about vulnerable dependencies — there is no `composer audit` or `npm audit` anywhere in .github/workflows/tests.yml or deploy.yml, so a CVE disclosed against an already-merged dependency between Dependabot runs would go completely unnoticed. Add a `composer audit` step and an `npm audit --audit-level=high` step to tests.yml\'s existing job (tests.yml already runs on push/PR/a daily 0 0 * * * cron, so this also gives daily scanning of main for newly disclosed CVEs against unchanged dependencies, not just at PR time), failing the build on high/critical findings. Since composer.lock/package-lock.json may already carry some known-low-severity advisories with no fix available yet, check current audit output first and, if anything unfixable and low-risk is already present, use each tool\'s documented per-advisory ignore/allow-list mechanism (not a blanket disable of the whole check) so the gate is meaningful going forward rather than immediately red. Document in a short PR/commit description which (if any) advisories were allow-listed and why.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'infra',
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
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Shipment tracking number and carrier on orders',
            'Frontend automated test harness (Vitest + React Testing Library)',
            'Real color swatches on the product variant selector',
            'CI dependency vulnerability scanning',
        ])->delete();
    }
};
