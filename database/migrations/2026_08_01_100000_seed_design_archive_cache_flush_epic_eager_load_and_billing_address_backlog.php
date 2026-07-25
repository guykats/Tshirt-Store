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
                'title' => "Design records have no delete/archive path, so the schema's own 'archived' status is dead code",
                'description' => "app/Http/Controllers/Api/DesignController.php only implements index(), approve(), and reject() -- routes/api.php:120-122 confirms there is no store/update/destroy route for /designs at all. But database/migrations/2026_07_16_100200_create_designs_table.php:18 defines status as enum(['pending_approval', 'approved', 'rejected', 'archived']) -- a fourth state clearly intended as the sanctioned way to retire a rejected or duplicate design, mirroring how Product::status already uses 'archived' instead of hard deletion. No controller, policy, or route anywhere in app/ ever assigns 'archived' to a Design. Concrete scenario: the Visioner/Creative Agent pipeline generates a duplicate or already-rejected design, and that row sits in the admin /designs review queue forever with zero way to remove or archive it, cluttering the queue an admin has to page through indefinitely. Fix: add a DesignController::archive() (or destroy()) endpoint that transitions to the existing 'archived' status, and exclude archived designs from the default admin listing the same way ProductController's admin index already treats archived products as a distinct, de-prioritized state.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Design model never flushes CatalogCache, so approving or rejecting a design leaves stale design data cached on its product',
                'description' => "Every model whose fields get embedded into a cached catalog response explicitly busts CatalogCache on write: Product::booted() (app/Models/Product.php:14-19), ProductVariant::booted(), ProductImage::booted(), and Review::booted() (app/Models/Review.php:18-23) each wire static::created/updated/deleted to CatalogCache::flush(). app/Models/Design.php has no booted() hook at all, despite ProductController::index()/show() eager-loading design and ProductResource.php:21 embedding a full DesignResource (title, category, mockup_url, status, rejection_reason) into every cached catalog listing and product-detail payload via CatalogCache::remember() (300s TTL, app/Services/CatalogCache.php:18). Concrete scenario: an admin approves or rejects a Design through DesignController::approve()/reject() -- that write never bumps the catalog cache version, so any product listing or detail page that already cached the pre-decision design snapshot keeps serving stale design status/mockup data for up to 5 minutes. Fix: add a booted() method to Design mirroring its siblings -- static::updated(fn () => CatalogCache::flush()) at minimum, since approve/reject are updates.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "ProjectTaskController::index never eager-loads the epic relation, so ProjectTaskResource's epic_title is always empty",
                'description' => "app/Http/Controllers/Api/ProjectTaskController.php:17-24's index() builds its query with a ->when(\$request->query('epic_id'), ...) filter but never calls ->with('epic') before ->get(). ProjectTaskResource.php:16 reads 'epic_title' => \$this->whenLoaded('epic', fn () => \$this->epic?->title) -- since the relation is never loaded, this key is omitted from every task in every response, regardless of whether ?epic_id= was passed. This is the same class of bug as the already-fixed OrderResource::approved_by eager-load gap, just on a different resource: the API supports scoping tasks to a specific epic and exposes an epic_title field for exactly that purpose, but that field is structurally guaranteed to come back missing. Concrete effect: ProjectProgress.jsx (the admin task board) has no way to show or filter by which epic a given task belongs to, even though the backend column and filter both exist. Fix: add ->with('epic') to the index() query, and surface epic_title as a visible column/filter in ProjectProgress.jsx.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "addresses.type's 'billing' value is dead code -- there is no way to record a billing address distinct from shipping",
                'description' => "database/migrations/2026_07_16_100100_create_addresses_table.php:14 defines type as enum(['shipping', 'billing']), and Order has separate shipping_address_id/billing_address_id columns -- the data model clearly anticipates a shopper billing to a different address than they ship to. In practice every address-creating code path hardcodes 'shipping': AddressController::store() (app/Http/Controllers/Api/AddressController.php:66, 'type' => 'shipping') and CheckoutController::store() (app/Http/Controllers/Api/CheckoutController.php:185, same literal), and checkout itself always assigns the identical address row to both shipping_address_id and billing_address_id (CheckoutController.php:196-197). No form, endpoint, or admin action anywhere ever sets type => 'billing' or lets a buyer pick a different billing address. Concrete effect: a shopper whose card's billing address genuinely differs from their shipping address (common, and relevant to PayPal fraud/AVS checks) has no way to express that anywhere in the app -- the 'billing' enum value and the whole billing/shipping split modeled on Order and AddressResource is unreachable dead schema. Fix: either add a real 'use a different billing address' option to checkout and account addresses, or, if intentionally out of scope, drop the unused 'billing' enum value and billing_address_id distinction so the schema stops promising a capability the product doesn't have.",
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
            "Design records have no delete/archive path, so the schema's own 'archived' status is dead code",
            'Design model never flushes CatalogCache, so approving or rejecting a design leaves stale design data cached on its product',
            "ProjectTaskController::index never eager-loads the epic relation, so ProjectTaskResource's epic_title is always empty",
            "addresses.type's 'billing' value is dead code -- there is no way to record a billing address distinct from shipping",
        ])->delete();
    }
};
