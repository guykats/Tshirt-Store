<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reservation Timeout
    |--------------------------------------------------------------------------
    |
    | CheckoutController::store() decrements product_variants.stock_quantity
    | the moment an order is created, before the buyer has actually paid
    | (PayPal capture happens in a separate request). If they never come
    | back to finish paying, that reservation would otherwise be lost
    | forever. app:expire-abandoned-orders treats any order still unpaid and
    | not yet fulfilled past this many minutes old as abandoned: it's moved
    | to 'cancelled' and its stock is restored. 60 minutes mirrors a typical
    | PayPal order/session validity window and gives a slow-but-genuine
    | buyer (bad connection, double-checking an address, stepping away
    | mid-checkout) ample room without leaving stock locked up indefinitely.
    |
    */

    'reservation_minutes' => (int) env('CHECKOUT_RESERVATION_MINUTES', 60),

];
