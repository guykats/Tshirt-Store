<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SystemEvent;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validated($request);

        $maxPosition = $product->images()->max('position');
        $nextPosition = $maxPosition === null ? 0 : $maxPosition + 1;

        $image = $product->images()->create([...$data, 'position' => $nextPosition]);

        SystemEvent::log(
            'product_image.created',
            "Image added to \"{$product->name}\" by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return (new ProductImageResource($image))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Product $product, ProductImage $image)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($image->product_id === $product->id, 404);

        $data = $this->validated($request);

        $image->update($data);

        SystemEvent::log(
            'product_image.updated',
            "Image on \"{$product->name}\" updated by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return new ProductImageResource($image->fresh());
    }

    /**
     * Reorders the whole gallery in one shot: the admin UI sends the full list of image
     * ids in the desired display order, and this assigns position = index within it. This
     * avoids the ambiguity of a single "move this image to position N" endpoint (what
     * happens to whichever image already held N?) at the cost of the client always
     * resubmitting the entire ordering, which the small per-product image counts here make
     * cheap.
     */
    public function reorder(Request $request, Product $product)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'image_ids' => ['required', 'array'],
            'image_ids.*' => ['integer'],
        ]);

        $existingIds = $product->images()->pluck('id')->sort()->values()->all();
        $requestedIds = collect($data['image_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($existingIds !== $requestedIds) {
            return response()->json([
                'message' => 'image_ids must include exactly this product\'s current image ids, once each.',
            ], 422);
        }

        foreach (array_values($data['image_ids']) as $position => $id) {
            ProductImage::where('id', $id)->update(['position' => $position]);
        }

        SystemEvent::log(
            'product_image.reordered',
            "Image gallery reordered for \"{$product->name}\" by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return ProductImageResource::collection($product->images()->get());
    }

    public function destroy(Request $request, Product $product, ProductImage $image)
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($image->product_id === $product->id, 404);

        $image->delete();

        SystemEvent::log(
            'product_image.deleted',
            "Image removed from \"{$product->name}\" by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return response()->json(['message' => 'Deleted.']);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'url' => ['required', 'string', 'max:500'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
