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
                'title' => 'Nightly backup rotation deletes local dumps even when their off-site upload never succeeded',
                'description' => "BackupDatabase::handle() (app/Console/Commands/BackupDatabase.php, lines 75-77) calls \$this->uploadOffsite(\$path, \$filename, \$size) immediately followed by \$this->rotate(\$backupDir, \$keep), with no dependency between the two. uploadOffsite() (lines 92-141) only ever uploads *today's* freshly-made dump; if it fails (bad off-site credentials, network blip, disk quota), it logs a 'backup.offsite_failed' SystemEvent and emails admins (lines 109-127), but does not stop rotate() from running. rotate() (lines 172-200) unconditionally globs every backup_*.sql file in \$backupDir, sorts descending, and unlinks everything past config('backup.keep') (default 14, config/backup.php:29) -- with zero awareness of whether any given file was ever successfully replicated off-site. config/backup.php's offsite_disk (line 49) is env(BACKUP_OFFSITE_DISK) and currently unset in production (deferred credential per CLAUDE.md), so this is latent today, but once real off-site credentials are added it activates with no code change -- and so does this bug. If BACKUP_OFFSITE_DISK stays misconfigured or the off-site disk becomes unreachable for more than 'keep' days, every night uploadOffsite() fails (correctly alerting admins) but rotate() keeps deleting the oldest local file anyway; after day 15 a backup that was never actually copied off-site is permanently gone, silently defeating the off-site feature's entire purpose while the recurring alerts look routine rather than 'you are now losing real backups.' This is distinct from the already-shipped 'backups stored only locally' and 'failed backups aren't proactively surfaced' fixes -- those are about absence of off-site/notification; this is the retention logic actively destroying the local copy of a backup that off-site replication never got a chance to protect. Fix: track (DB row or sidecar marker) whether each local dump has a confirmed off-site copy, and have rotate() only prune files that are either off-site-confirmed or older than a second, longer hard-retention window -- never delete the newest local-only copy purely because 'keep' was exceeded while off-site is failing.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Editing a variant's size/color (or a product's name) after it has shipped in a real order retroactively rewrites every past order's displayed details",
                'description' => "order_items (database/migrations/*_create_order_items_table.php) stores only product_variant_id, quantity, unit_price, subtotal -- no snapshot of the variant's size/color or the product's name at purchase time. Every place that renders an order's line items (OrderItemResource, invoices, confirmation/shipped/delivered/refunded emails, the admin fulfillment/refund queues, OrderController::show) derives size/color/name live via \$item->productVariant->size / ->color / ->product->name. Admin\\ProductVariantController::update() (app/Http/Controllers/Api/Admin/ProductVariantController.php, lines 39-65) lets an admin change an existing variant's size and color (validated at lines 100-102) with no check for existing order history -- compare this to destroy() on the very same controller (lines 74-91), which explicitly blocks deletion via \$variant->orderItems()->exists() specifically because order history depends on that row; update() has no equivalent guard. The same gap exists in Admin\\ProductController::update() for a product's name. Failure scenario: a customer orders the 'M / Black' variant of a t-shirt. Before it ships, an admin edits that same variant row (correcting a typo, or repurposing the SKU) and changes its color from Black to Navy. The order itself is never touched, but every subsequent view of that historical order -- the admin fulfillment queue, the customer's own order-history page, a freshly regenerated invoice PDF, and any not-yet-sent status email -- now shows 'M / Navy,' even though the customer paid for and expects Black. Warehouse staff pulling the order for shipping could package/ship the wrong color based on this now-incorrect record, and the invoice a customer downloads later permanently misrepresents what they bought. Fix: snapshot size, color, and product_name (or a denormalized display string) onto order_items at order-creation time (CheckoutController::store), and render from that snapshot everywhere instead of the live productVariant relation -- mirroring how unit_price/subtotal are already frozen at purchase time. As a stopgap, block size/color edits on a variant that has any orderItems(), the same way destroy() already does.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'ErrorBoundary never resets on route change, so one crashed page wedges the entire SPA behind the fallback screen until a hard reload',
                'description' => "ErrorBoundary (resources/js/components/ErrorBoundary.jsx, the whole 49-line file) is a class component whose state.hasError is only ever set to true in getDerivedStateFromError (lines 10-12) and only ever reset to false inside handleReset (lines 21-24), which does a full page navigation via window.location.assign('/'). There is no componentDidUpdate or key-based reset tied to the router location, so once an error is caught, hasError stays true for the lifetime of that mounted component instance. In App.jsx, <ErrorBoundary> (line 86) wraps the entire <Suspense><Routes>...</Routes></Suspense> tree (lines 87-229) as a single, permanently-mounted sibling inside <Layout> -- it is never remounted by React Router when the URL changes, since it sits outside Routes and isn't given a key derived from location.pathname. Failure scenario: a shopper hits a rendering error on any one page (e.g. a malformed API response throws while rendering ProductDetail). The boundary catches it and shows the generic fallback (lines 33-45 of ErrorBoundary.jsx). The user then clicks a header nav link (Layout.jsx, e.g. 'Catalog' or 'About') that lives outside the boundary and still works -- but because ErrorBoundary itself never remounted or cleared hasError, the entire app keeps rendering the same fallback screen for every subsequent in-app navigation, even to pages that would have rendered fine, until the user notices the one specific 'go home' button that does a hard reload. Effectively, one transient error on one product page can make the whole storefront look completely broken to that visitor for the rest of their session. Fix: reset hasError whenever the route changes -- e.g. wrap the boundary usage with <ErrorBoundary key={location.pathname}> (remounting it per route) via a small wrapper component that calls useLocation(), or add a prop-change-driven reset (compare a resetKey prop in componentDidUpdate and call this.setState({ hasError: false }) when it changes) instead of relying on a full-page reload as the only recovery path.",
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
        DB::table('project_tasks')->where('title', 'Nightly backup rotation deletes local dumps even when their off-site upload never succeeded')->delete();
        DB::table('project_tasks')->where('title', "Editing a variant's size/color (or a product's name) after it has shipped in a real order retroactively rewrites every past order's displayed details")->delete();
        DB::table('project_tasks')->where('title', 'ErrorBoundary never resets on route change, so one crashed page wedges the entire SPA behind the fallback screen until a hard reload')->delete();
    }
};
