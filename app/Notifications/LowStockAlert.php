<?php

namespace App\Notifications;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pushed to admins the moment a checkout decrement (see
 * CheckoutController::store) takes a variant to or below
 * InventoryController::DEFAULT_THRESHOLD, so restocking doesn't depend on an
 * admin remembering to open /dashboard's "Low Stock" section. De-duplicated
 * by CheckoutController via ProductVariant::low_stock_alerted_at — this
 * notification itself fires every time it's dispatched, the caller is
 * responsible for only dispatching it once per threshold-crossing.
 */
class LowStockAlert extends Notification
{
    use Queueable;

    public function __construct(public ProductVariant $variant)
    {
        $this->variant->loadMissing('product');
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $variant = $this->variant;
        $label = trim("{$variant->size}/{$variant->color}");

        // Rendered through the site's own branded layout (see
        // resources/views/emails/low-stock-alert.blade.php, which reuses the
        // same emails.partials.header component as the order emails) rather
        // than Laravel's default blue-button notification theme.
        return (new MailMessage)
            ->subject(__('mail.low_stock_alert_subject', ['product' => $variant->product->name]))
            ->view('emails.low-stock-alert', [
                'name' => $notifiable->name,
                'productName' => $variant->product->name,
                'variantLabel' => $label,
                'quantity' => $variant->stock_quantity,
                'sku' => $variant->sku,
                'url' => rtrim((string) config('app.url'), '/').'/dashboard',
            ]);
    }
}
