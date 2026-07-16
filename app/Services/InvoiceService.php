<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;

class InvoiceService
{
    /**
     * Render the given order's invoice as a PDF, localized to the order owner's preferred locale.
     */
    public function generate(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order->loadMissing(['billingAddress', 'shippingAddress', 'items.productVariant.product', 'user']);

        $locale = $order->user->preferred_locale ?? config('app.locale');
        $previousLocale = App::getLocale();

        App::setLocale($locale);

        try {
            return Pdf::loadView('invoices.order', ['order' => $order]);
        } finally {
            App::setLocale($previousLocale);
        }
    }
}
