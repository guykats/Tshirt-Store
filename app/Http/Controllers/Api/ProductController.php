<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CatalogCache;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $sort = $request->query('sort');
        $page = (int) $request->query('page', 1);

        $cacheKey = 'index:'.md5(json_encode(['search' => $search, 'sort' => $sort, 'page' => $page]));

        $payload = CatalogCache::remember($cacheKey, function () use ($search, $sort) {
            $query = Product::query()
                ->where('status', 'active')
                ->with(['design', 'variants', 'images']);

            if ($search !== '') {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';

                $query->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(name) LIKE ?', [mb_strtolower($like)])
                        ->orWhereRaw('LOWER(description) LIKE ?', [mb_strtolower($like)]);
                });
            }

            match ($sort) {
                'price_asc' => $query->orderBy('base_price', 'asc'),
                'price_desc' => $query->orderBy('base_price', 'desc'),
                'newest' => $query->latest(),
                default => $query->latest(),
            };

            return ProductResource::collection($query->paginate(20)->withQueryString())
                ->response()
                ->getData(true);
        });

        return response()->json($payload);
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'active', 404);

        $payload = CatalogCache::remember("show:{$product->id}", function () use ($product) {
            return (new ProductResource($product->load(['design', 'variants', 'images'])))
                ->response()
                ->getData(true);
        });

        return response()->json($payload);
    }
}
