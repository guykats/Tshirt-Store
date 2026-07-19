<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Default number of units at or below which a variant is considered
     * "low stock". Callers can narrow (never widen beyond a sane cap) this
     * with a ?threshold= query param, e.g. for a stricter "critical" view.
     */
    public const DEFAULT_THRESHOLD = 5;

    /**
     * Admin-only visibility into variants running low on stock — the only way
     * an admin would otherwise learn about this today is a customer failing
     * to check out once stock_quantity hits zero (see CheckoutController).
     */
    public function lowStock(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'threshold' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $threshold = $data['threshold'] ?? self::DEFAULT_THRESHOLD;

        $variants = ProductVariant::query()
            ->with('product')
            ->where('stock_quantity', '<=', $threshold)
            ->orderBy('stock_quantity')
            ->orderBy('id')
            ->get();

        return ProductVariantResource::collection($variants);
    }
}
