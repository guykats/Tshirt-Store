<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SystemEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Admin management listing — every status (draft/active/archived), not just
     * the public catalog's "active" slice (see App\Http\Controllers\Api\ProductController::index),
     * so an admin can find and edit a product before it's published or after it's retired.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $products = Product::query()
            ->with(['design', 'variants'])
            ->latest()
            ->paginate(50);

        return ProductResource::collection($products);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $product = Product::create($data);

        SystemEvent::log(
            'product.created',
            "Product \"{$product->name}\" created by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return (new ProductResource($product->load(['design', 'variants'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validated($request, $product);

        $product->update($data);

        SystemEvent::log(
            'product.updated',
            "Product \"{$product->name}\" updated by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return new ProductResource($product->fresh(['design', 'variants']));
    }

    /**
     * Products don't support hard deletion once any of their variants has shipped in a
     * real order. product_variants.product_id cascades on delete (see
     * create_product_variants_table), but order_items.product_variant_id is a RESTRICT
     * foreign key (see create_order_items_table) — the database would refuse this delete
     * anyway once it tried to cascade into a referenced variant. We check explicitly first
     * so the admin gets a clear, actionable 422 instead of a raw constraint-violation 500,
     * and point them at the existing "archived" status as the sanctioned way to retire a
     * product from the catalog without breaking historical order references.
     */
    public function destroy(Request $request, Product $product)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $hasOrderHistory = ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereHas('orderItems')
            ->exists();

        if ($hasOrderHistory) {
            return response()->json([
                'message' => 'This product has existing orders and cannot be deleted. Set its status to "archived" to remove it from the catalog instead.',
            ], 422);
        }

        $name = $product->name;
        $product->delete();

        SystemEvent::log(
            'product.deleted',
            "Product \"{$name}\" deleted by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return response()->json(['message' => 'Deleted.']);
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'design_id' => ['required', 'integer', 'exists:designs,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
        ]);
    }

    /**
     * Slugs are derived from the name rather than admin-typed: Product's route-model-binding
     * key is the slug (see Product::getRouteKeyName), and the public product-detail page/links
     * depend on it staying stable and URL-safe.
     */
    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 1;

        while (Product::where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
