<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SystemEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validated($request, $product);

        if ($conflict = $this->duplicateCombo($product, $data)) {
            return $conflict;
        }

        $variant = $product->variants()->create($data);

        SystemEvent::log(
            'product_variant.created',
            "Variant {$variant->size}/{$variant->color} added to \"{$product->name}\" by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return (new ProductVariantResource($variant->load('product')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Product $product, ProductVariant $variant)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($variant->product_id === $product->id, 404);

        $data = $this->validated($request, $product, $variant);

        if ($conflict = $this->duplicateCombo($product, $data, $variant)) {
            return $conflict;
        }

        $variant->update($data);

        // Restocking above the low-stock threshold re-arms the alert (see
        // CheckoutController::store / App\Notifications\LowStockAlert) so
        // the next time this variant sells down to the threshold again, an
        // admin is notified rather than staying silenced from the last time.
        if ($variant->stock_quantity > InventoryController::DEFAULT_THRESHOLD && $variant->low_stock_alerted_at !== null) {
            $variant->forceFill(['low_stock_alerted_at' => null])->save();
        }

        SystemEvent::log(
            'product_variant.updated',
            "Variant {$variant->size}/{$variant->color} on \"{$product->name}\" updated by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return new ProductVariantResource($variant->fresh('product'));
    }

    /**
     * Same order-integrity concern as ProductController::destroy: order_items.product_variant_id
     * is a RESTRICT foreign key, so a variant that has shipped in a real order can't be
     * hard-deleted without breaking that historical record. Setting stock_quantity to 0 is the
     * sanctioned way to stop selling it instead — it disappears from purchase without touching
     * the row order_items still points at.
     */
    public function destroy(Request $request, Product $product, ProductVariant $variant)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($variant->product_id === $product->id, 404);

        if ($variant->orderItems()->exists()) {
            return response()->json([
                'message' => 'This variant has existing orders and cannot be deleted. Set its stock to 0 to stop selling it instead.',
            ], 422);
        }

        $label = "{$variant->size}/{$variant->color}";
        $variant->delete();

        SystemEvent::log(
            'product_variant.deleted',
            "Variant {$label} removed from \"{$product->name}\" by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return response()->json(['message' => 'Deleted.']);
    }

    protected function validated(Request $request, Product $product, ?ProductVariant $variant = null): array
    {
        return $request->validate([
            'size' => ['required', Rule::in(['S', 'M', 'L', 'XL', 'XXL'])],
            'color' => ['required', 'string', 'max:50'],
            'sku' => ['required', 'string', 'max:120', Rule::unique('product_variants', 'sku')->ignore($variant?->id)],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    /**
     * The (product_id, size, color) DB unique constraint (see create_product_variants_table)
     * would catch this too, but as a raw constraint-violation 500 rather than a clean 422 —
     * check explicitly first so the admin gets an actionable error.
     */
    protected function duplicateCombo(Product $product, array $data, ?ProductVariant $variant = null)
    {
        $exists = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('size', $data['size'])
            ->where('color', $data['color'])
            ->when($variant, fn ($q) => $q->where('id', '!=', $variant->id))
            ->exists();

        if (! $exists) {
            return null;
        }

        return response()->json([
            'message' => 'A variant with that size and color already exists for this product.',
            'errors' => ['color' => ['A variant with that size and color already exists for this product.']],
        ], 422);
    }
}
