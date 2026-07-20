<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\SystemEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Public listing of a product's reviews, plus the aggregate rating the
     * product page and (eventually) structured data draw from.
     */
    public function index(Product $product)
    {
        $reviews = $product->reviews()
            ->with('user')
            ->latest()
            ->limit(100)
            ->get();

        return response()->json([
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'average_rating' => round((float) $product->reviews()->avg('rating'), 1) ?: null,
                'count' => $product->reviews()->count(),
            ],
        ]);
    }

    /**
     * Tell the frontend whether the current user is allowed to leave a review,
     * so the form can be hidden/disabled instead of failing silently on submit.
     */
    public function eligibility(Request $request, Product $product)
    {
        $user = $request->user();

        $alreadyReviewed = Review::where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->exists();

        $hasPurchased = $this->purchaseOrderFor($user->id, $product->id) !== null;

        return response()->json([
            'can_review' => $hasPurchased && ! $alreadyReviewed,
            'has_purchased' => $hasPurchased,
            'already_reviewed' => $alreadyReviewed,
        ]);
    }

    /**
     * Create a review. Server-side purchase verification and duplicate
     * prevention are the actual guarantee here — the frontend eligibility
     * check is just UX, not the enforcement.
     */
    public function store(Request $request, Product $product)
    {
        $data = $this->validateRatingAndBody($request);

        $user = $request->user();

        if (Review::where('product_id', $product->id)->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You have already reviewed this product.'], 422);
        }

        $order = $this->purchaseOrderFor($user->id, $product->id);

        if (! $order) {
            return response()->json(['message' => 'You can only review products you have purchased.'], 403);
        }

        try {
            $review = Review::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'rating' => $data['rating'],
                'body' => $data['body'] ?? null,
            ]);
        } catch (QueryException $e) {
            // Guards the race between the exists() check above and the insert
            // (e.g. a doubled-up submit) — the unique(product_id, user_id)
            // constraint is the real source of truth here.
            return response()->json(['message' => 'You have already reviewed this product.'], 422);
        }

        return (new ReviewResource($review->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Self-service edit of the reviewer's own review (fix a typo, correct a
     * rating) without going through the unique(product_id, user_id) delete-
     * then-resubmit dance. Admin moderation of other people's reviews is a
     * separate grant — see ReviewPolicy::update().
     */
    public function update(Request $request, Product $product, Review $review)
    {
        $this->authorize('update', $review);

        abort_unless($review->product_id === $product->id, 404);

        $data = $this->validateRatingAndBody($request);

        $review->update([
            'rating' => $data['rating'],
            'body' => $data['body'] ?? null,
        ]);

        return new ReviewResource($review->load('user'));
    }

    /**
     * Admin moderation listing — every review across every product (not scoped
     * to one product like the public index above), so an admin can find and
     * remove an abusive/fake one without already knowing which product it's on.
     */
    public function manage(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $reviews = Review::query()
            ->with(['user', 'product'])
            ->latest()
            ->paginate(20);

        return ReviewResource::collection($reviews);
    }

    /**
     * Removal of a review — either an admin moderating abusive/fake content,
     * or the review's own author removing it themselves (see
     * ReviewPolicy::delete()). The unique(product_id, user_id) constraint
     * that blocks a second legitimate review from the same purchaser also
     * means deleting a bad one is the only way to let them submit a
     * corrected one afterwards.
     */
    public function destroy(Request $request, Product $product, Review $review)
    {
        $this->authorize('delete', $review);

        abort_unless($review->product_id === $product->id, 404);

        $review->loadMissing('user');
        $reviewerName = $review->user?->name ?? 'A user';

        $review->delete();

        SystemEvent::log(
            'review.deleted',
            "Review by {$reviewerName} for \"{$product->name}\" removed by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return response()->json(['message' => 'Deleted.']);
    }

    /**
     * Shared rating/body validation for both create (store) and self-service
     * edit (update).
     */
    protected function validateRatingAndBody(Request $request): array
    {
        return $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    /**
     * The most recent paid order this user has that included this product,
     * i.e. proof of purchase. Null if they never bought it.
     */
    protected function purchaseOrderFor(int $userId, int $productId): ?Order
    {
        return Order::where('user_id', $userId)
            ->where('payment_status', 'paid')
            ->whereHas('items.productVariant', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->latest()
            ->first();
    }
}
