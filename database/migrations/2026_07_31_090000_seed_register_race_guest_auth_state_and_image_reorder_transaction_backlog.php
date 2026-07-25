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
                'title' => 'AuthController::register has no protection against two concurrent registrations for the same brand-new email, crashing with a raw 500',
                'description' => "app/Http/Controllers/Api/AuthController.php:29-72's register() validates email uniqueness via Rule::unique('users','email')->where(fn (\$q) => \$q->where('is_guest', false)) (line 39), then — for a genuinely new (non-guest) email — calls User::create(\$data) at line 65 with no try/catch. Between the validation pass and the insert there is no locking, unlike CheckoutController's guest-email path (already tracked as ticket 136's TOCTOU) or WishlistController/ReviewController's exists-then-insert pattern, both of which catch QueryException around the actual write. Concrete scenario: two registration requests for the same brand-new email land close enough together that both pass the uniqueness check before either commits; the second User::create() hits the users.email unique constraint and throws an uncaught QueryException, returning a raw 500 instead of a clean validation error telling the second submitter the email is now taken. Fix: wrap the User::create() call in a catch (QueryException \$e) that re-throws as a ValidationException on the email field, mirroring the pattern WishlistController::store() and ReviewController::store() already use.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "AuthContext's user state is never refreshed after guest checkout's server-side auto-login, so a just-purchased guest still appears logged out",
                'description' => "app/Http/Controllers/Api/CheckoutController.php:122-126 creates a guest User row and calls Auth::login(\$buyer) + \$request->session()->regenerate() during store(), giving the browser a real authenticated session — but resources/js/pages/Checkout.jsx never calls anything on useAuth() (resources/js/lib/AuthContext.jsx) after that happens. AuthContext only populates `user` once, from a single /api/me fetch on initial app mount (AuthContext.jsx:10-15); none of its exposed methods (login/register/logout/etc.) are called anywhere in Checkout.jsx's guest flow or in OrderConfirmation. Concrete scenario: a guest checks out without registering, lands on OrderConfirmation with a valid session cookie already set server-side, but the SPA's `user` stays null for the rest of that browser session — the nav still shows Login/Register, and clicking Orders or Wishlist bounces them to /login even though the server would recognize their session as authenticated if only the client re-fetched /api/me. Fix: expose a refreshUser()-style method on AuthContext and call it after a successful checkout capture (or guest order creation), the same way login()/register() already re-fetch /api/me after establishing a session.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'ProductImageController::reorder updates each image position in a bare loop with no DB::transaction, unlike every other multi-step mutation in the codebase',
                'description' => "app/Http/Controllers/Api/Admin/ProductImageController.php:82-84's reorder() runs foreach (array_values(\$data['image_ids']) as \$position => \$id) { ProductImage::where('id', \$id)->update(['position' => \$position]); } as N separate, unwrapped UPDATE statements — no DB::transaction() around the loop, in contrast to every other multi-row mutation elsewhere in the app (e.g. order status changes, refund flows) which use transactions specifically to avoid partial writes. Concrete scenario: a gallery with 5+ images is reordered and the request is interrupted mid-loop (worker timeout, DB connection drop, deploy restart) after 2 of 5 UPDATEs have committed — the gallery is left in a mixed state with two images at their new positions and three still at their old ones, and no SystemEvent or error surfaces to the admin since the interruption happens after the response would have been considered successful up to that point. Fix: wrap the foreach loop in DB::transaction(), matching the pattern already established for other multi-step admin mutations in this codebase.",
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
            'AuthController::register has no protection against two concurrent registrations for the same brand-new email, crashing with a raw 500',
            "AuthContext's user state is never refreshed after guest checkout's server-side auto-login, so a just-purchased guest still appears logged out",
            'ProductImageController::reorder updates each image position in a bare loop with no DB::transaction, unlike every other multi-step mutation in the codebase',
        ])->delete();
    }
};
