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

        return (new MailMessage)
            ->subject(__('mail.low_stock_alert_subject', ['product' => $variant->product->name]))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.low_stock_alert_intro', [
                'product' => $variant->product->name,
                'variant' => $label,
            ]))
            ->line(__('mail.low_stock_alert_quantity', ['count' => $variant->stock_quantity]))
            ->line(__('mail.low_stock_alert_sku', ['sku' => $variant->sku]))
            ->action(__('mail.low_stock_alert_action'), rtrim((string) config('app.url'), '/').'/dashboard')
            ->line(__('mail.regards', ['app_name' => config('app.name')]));
    }
}
