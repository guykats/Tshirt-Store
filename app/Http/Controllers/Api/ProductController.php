<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->where('status', 'active')
            ->with(['design', 'variants']);

        if ($search = trim((string) $request->query('search'))) {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(name) LIKE ?', [mb_strtolower($like)])
                    ->orWhereRaw('LOWER(description) LIKE ?', [mb_strtolower($like)]);
            });
        }

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('base_price', 'asc'),
            'price_desc' => $query->orderBy('base_price', 'desc'),
            'newest' => $query->latest(),
            default => $query->latest(),
        };

        return ProductResource::collection(
            $query->paginate(20)->withQueryString()
        );
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'active', 404);

        return new ProductResource($product->load(['design', 'variants']));
    }
}
