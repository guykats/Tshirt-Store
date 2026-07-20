<?php

use Illuminate\Support\Number;

?>
@php
    // Fall back to the orders table's own 'USD' column default in case an
    // in-memory Order instance (e.g. one built via ->create() in a test and
    // never refreshed from the DB) hasn't picked up the default yet — keeps
    // Number::currency() from throwing on a null currency code.
    $invoiceCurrency = $order->currency ?? 'USD';
    $isRtl = app()->getLocale() === 'he';
    $startAlign = $isRtl ? 'right' : 'left';
    $endAlign = $isRtl ? 'left' : 'right';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('invoice.title') }} {{ $order->order_number }}</title>
    <style>
        {{-- dompdf's CSS support is limited (no flexbox/grid, patchy custom
             properties) so brand tokens from resources/css/app.css are
             hex-coded directly here, same approach as
             resources/views/emails/partials/header.blade.php:
             --color-ink: #17140f, --color-ink-soft: #4a453c,
             --color-parchment: #f7f4ee, --color-parchment-dim: #efeadf,
             --color-brass: #8c6a3f, --color-brass-light: #b79868. DejaVu
             Sans is used throughout (not Georgia, and not DejaVu Serif —
             its bold cut is missing full Hebrew glyph coverage in this
             dompdf install and silently renders Hebrew as tofu boxes)
             because it is the one bundled dompdf font confirmed to carry
             Hebrew glyphs in every weight used here. --}}
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #17140f; margin: 0; }
        .sheet { padding: 6px 4px; }

        .brand-mark { text-align: center; padding: 0 0 6px; }
        .brand-name {
            text-align: center;
            font-family: 'DejaVu Sans', sans-serif;
            font-weight: bold;
            font-size: 22px;
            letter-spacing: 3px;
            color: #17140f;
            text-transform: uppercase;
            padding: 0 0 10px;
        }
        .brand-rule-wrap { text-align: center; padding: 0 0 8px; }
        .brand-rule { width: 60px; height: 2px; line-height: 2px; font-size: 2px; background-color: #8c6a3f; }
        .doc-title {
            text-align: center;
            font-size: 11px;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #8c6a3f;
            padding: 0 0 22px;
        }

        .meta-box { width: 100%; border-collapse: collapse; border: 1px solid #e4dfd4; margin-bottom: 20px; }
        .meta-box td { padding: 8px 12px; font-size: 11px; }
        .meta-box .meta-label { color: #4a453c; background-color: #efeadf; width: 34%; }
        .meta-box .meta-value { color: #17140f; background-color: #f7f4ee; }

        .addresses { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .addresses td { vertical-align: top; width: 50%; padding: 0 10px 0 0; }
        .addresses td.end { padding: 0 0 0 10px; }
        .addr-heading {
            display: block;
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #8c6a3f;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .addr-body { color: #4a453c; line-height: 1.5; }
        .addr-name { color: #17140f; font-weight: bold; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th {
            background: #efeadf;
            color: #4a453c;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 8px;
            border-bottom: 2px solid #8c6a3f;
        }
        table.items td {
            border-bottom: 1px solid #e4dfd4;
            padding: 8px;
            color: #17140f;
        }

        .totals { width: 100%; border-collapse: collapse; }
        .totals td { padding: 5px 8px; font-size: 12px; color: #4a453c; }
        .totals .label { font-weight: normal; }
        .grand-total td { border-top: 2px solid #8c6a3f; padding-top: 10px; font-weight: bold; font-size: 15px; color: #17140f; }
        .grand-total .label { color: #17140f; }

        .footer { margin-top: 30px; text-align: center; }
        .footer-rule-wrap { text-align: center; padding: 0 0 10px; }
        .footer-rule { width: 60px; height: 1px; line-height: 1px; font-size: 1px; background-color: #b79868; }
        .footer-text { color: #4a453c; font-size: 11px; }
    </style>
</head>
<body>
    <div class="sheet">
        {{-- Brand mark: same restrained single-stroke motif language as
             resources/js/components/DesignArt.jsx's StarOfDavid. The
             barryvdh/laravel-dompdf install here has no inline-<svg> support
             (only raster <img>), so the mark is pre-rendered once to a
             transparent PNG (resources/images/brand/star-of-david-mark.png,
             regenerated via GD — see the two-triangle hexagram construction
             in DesignArt.jsx) and embedded as a base64 data URI, which
             dompdf always resolves regardless of its filesystem chroot. --}}
        <div class="brand-mark">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('images/brand/star-of-david-mark.png'))) }}"
                 width="26" height="26" alt="{{ __('mail.brand_name') }}">
        </div>
        <div class="brand-name">{{ __('mail.brand_name') }}</div>
        <div class="brand-rule-wrap"><div class="brand-rule">&nbsp;</div></div>
        <div class="doc-title">{{ __('invoice.title') }}</div>

        <table class="meta-box">
            <tr>
                <td class="meta-label">{{ __('invoice.invoice_number') }}</td>
                <td class="meta-value">{{ $order->order_number }}</td>
            </tr>
            <tr>
                <td class="meta-label">{{ __('invoice.date') }}</td>
                <td class="meta-value">{{ $order->created_at->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td class="meta-label">{{ __('invoice.payment_status') }}</td>
                <td class="meta-value">{{ ucfirst($order->payment_status) }}</td>
            </tr>
            @if($order->tracking_number)
                <tr>
                    <td class="meta-label">{{ __('invoice.carrier') }}</td>
                    <td class="meta-value">{{ $order->carrier }}</td>
                </tr>
                <tr>
                    <td class="meta-label">{{ __('invoice.tracking_number') }}</td>
                    <td class="meta-value">{{ $order->tracking_number }}</td>
                </tr>
            @endif
        </table>

        <table class="addresses">
            <tr>
                <td>
                    <span class="addr-heading">{{ __('invoice.bill_to') }}</span>
                    <div class="addr-body">
                        <span class="addr-name">{{ $order->billingAddress->full_name }}</span><br>
                        {{ $order->billingAddress->line1 }}<br>
                        @if($order->billingAddress->line2)
                            {{ $order->billingAddress->line2 }}<br>
                        @endif
                        {{ $order->billingAddress->city }}, {{ $order->billingAddress->state }} {{ $order->billingAddress->postal_code }}<br>
                        {{ $order->billingAddress->country }}
                    </div>
                </td>
                <td class="end">
                    <span class="addr-heading">{{ __('invoice.ship_to') }}</span>
                    <div class="addr-body">
                        <span class="addr-name">{{ $order->shippingAddress->full_name }}</span><br>
                        {{ $order->shippingAddress->line1 }}<br>
                        @if($order->shippingAddress->line2)
                            {{ $order->shippingAddress->line2 }}<br>
                        @endif
                        {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->postal_code }}<br>
                        {{ $order->shippingAddress->country }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="text-align: {{ $startAlign }};">{{ __('invoice.item') }}</th>
                    <th style="text-align: {{ $endAlign }};">{{ __('invoice.quantity') }}</th>
                    <th style="text-align: {{ $endAlign }};">{{ __('invoice.unit_price') }}</th>
                    <th style="text-align: {{ $endAlign }};">{{ __('invoice.subtotal_line') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td style="text-align: {{ $startAlign }};">
                            {{ $item->productVariant->product->name }}
                            ({{ $item->productVariant->size }} / {{ $item->productVariant->color }})
                        </td>
                        <td style="text-align: {{ $endAlign }};">{{ $item->quantity }}</td>
                        <td style="text-align: {{ $endAlign }};">{{ Number::currency($item->unit_price ?? 0, in: $invoiceCurrency, locale: app()->getLocale()) }}</td>
                        <td style="text-align: {{ $endAlign }};">{{ Number::currency($item->subtotal ?? 0, in: $invoiceCurrency, locale: app()->getLocale()) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="label" style="text-align: {{ $endAlign }};" width="80%">{{ __('invoice.subtotal') }}</td>
                <td style="text-align: {{ $endAlign }};">{{ Number::currency($order->subtotal ?? 0, in: $invoiceCurrency, locale: app()->getLocale()) }}</td>
            </tr>
            @if($order->discount_amount > 0)
                <tr>
                    <td class="label" style="text-align: {{ $endAlign }};">{{ __('invoice.discount') }}{{ $order->discount_code ? ' ('.$order->discount_code.')' : '' }}</td>
                    <td style="text-align: {{ $endAlign }};">-{{ Number::currency($order->discount_amount ?? 0, in: $invoiceCurrency, locale: app()->getLocale()) }}</td>
                </tr>
            @endif
            <tr>
                <td class="label" style="text-align: {{ $endAlign }};">{{ __('invoice.tax') }}</td>
                <td style="text-align: {{ $endAlign }};">{{ Number::currency($order->tax_amount ?? 0, in: $invoiceCurrency, locale: app()->getLocale()) }}</td>
            </tr>
            <tr>
                <td class="label" style="text-align: {{ $endAlign }};">{{ __('invoice.shipping') }}</td>
                <td style="text-align: {{ $endAlign }};">{{ Number::currency($order->shipping_amount ?? 0, in: $invoiceCurrency, locale: app()->getLocale()) }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label" style="text-align: {{ $endAlign }};">{{ __('invoice.total') }}</td>
                <td style="text-align: {{ $endAlign }};">{{ Number::currency($order->total_amount ?? 0, in: $invoiceCurrency, locale: app()->getLocale()) }}</td>
            </tr>
        </table>

        <div class="footer">
            <div class="footer-rule-wrap"><div class="footer-rule">&nbsp;</div></div>
            <div class="footer-text">{{ __('invoice.thank_you') }}</div>
        </div>
    </div>
</body>
</html>
