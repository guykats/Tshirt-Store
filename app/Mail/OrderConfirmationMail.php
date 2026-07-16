<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing(['billingAddress', 'shippingAddress', 'items.productVariant.product', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.order_confirmation_subject', ['number' => $this->order->order_number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
            with: ['order' => $this->order],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = app(InvoiceService::class)->generate($this->order);

        return [
            Attachment::fromData(fn () => $pdf->output(), "invoice-{$this->order->order_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
