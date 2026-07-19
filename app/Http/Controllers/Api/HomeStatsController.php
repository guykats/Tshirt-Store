<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;

class HomeStatsController extends Controller
{
    /**
     * Backs the homepage's stats strip with real numbers instead of the free-typed
     * "1,200+ pieces shipped, 4.9/5 rating" admin fields it replaced (see
     * database/migrations/2026_07_19_233000_drop_fabricated_stat_columns_from_site_settings.php).
     * Public/anonymous read — nothing here is more sensitive than what's already shown
     * on the homepage itself.
     */
    public function show()
    {
        // "Completed" = actually paid, mirroring the same payment_status the reviews
        // eligibility check already treats as proof of a real purchase (see
        // ReviewController::purchaseOrderFor) rather than a shipment-stage status like
        // "delivered", which would undercount genuine completed sales still in transit.
        $completedOrders = Order::where('payment_status', 'paid')->count();

        $reviewCount = Review::count();
        // Never show a rating when there are zero reviews yet — the frontend is
        // expected to render an empty/"new" state instead of a fabricated number.
        $averageRating = $reviewCount > 0 ? round((float) Review::avg('rating'), 1) : null;

        $countriesServed = Order::where('payment_status', 'paid')
            ->join('addresses', 'orders.shipping_address_id', '=', 'addresses.id')
            ->distinct()
            ->count('addresses.country');

        return response()->json([
            'data' => [
                'completed_orders' => $completedOrders,
                'average_rating' => $averageRating,
                'review_count' => $reviewCount,
                'countries_served' => $countriesServed,
            ],
        ]);
    }
}
