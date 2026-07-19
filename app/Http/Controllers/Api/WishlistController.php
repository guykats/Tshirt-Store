<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistItemResource;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * The current user's saved products, newest first.
     */
    public function index(Request $request)
    {
        $items = $request->user()
            ->wishlistItems()
            ->with('product.design', 'product.variants')
            ->latest()
            ->get();

        return response()->json([
            'data' => WishlistItemResource::collection($items),
        ]);
    }

    /**
     * Save a product to the current user's wishlist. Idempotent-ish: re-saving
     * an already-wishlisted product just returns the existing row rather than
     * erroring, since the "unsave" affordance is a separate DELETE.
     */
    public function store(Request $request, Product $product)
    {
        $user = $request->user();

        $existing = WishlistItem::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            return (new WishlistItemResource($existing->load('product.design', 'product.variants')))
                ->response()
                ->setStatusCode(200);
        }

        try {
            $item = WishlistItem::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
        } catch (QueryException $e) {
            // Guards the race between the exists() check above and the insert
            // (e.g. a doubled-up click) — the unique(user_id, product_id)
            // constraint is the real source of truth here.
            $item = WishlistItem::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->firstOrFail();
        }

        return (new WishlistItemResource($item->load('product.design', 'product.variants')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove a product from the current user's wishlist. Removing a product
     * that was never saved is a no-op success, not an error — the frontend
     * toggle button doesn't need to track exact prior state to call this safely.
     */
    public function destroy(Request $request, Product $product)
    {
        $request->user()
            ->wishlistItems()
            ->where('product_id', $product->id)
            ->delete();

        return response()->json(null, 204);
    }
}
