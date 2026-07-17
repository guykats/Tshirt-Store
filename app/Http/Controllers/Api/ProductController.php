<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return ProductResource::collection(
            Product::query()
                ->where('status', 'active')
                ->with(['design', 'variants'])
                ->latest()
                ->paginate(20)
        );
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'active', 404);

        return new ProductResource($product->load(['design', 'variants']));
    }
}
