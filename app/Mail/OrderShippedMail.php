<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\CarrierTracking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing(['items.productVariant.product', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.order_shipped_subject', ['number' => $this->order->order_number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-shipped',
            with: [
                'order' => $this->order,
                'trackingUrl' => CarrierTracking::url($this->order->carrier, $this->order->tracking_number),
            ],
        );
    }
}
