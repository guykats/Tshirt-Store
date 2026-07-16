<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'he' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('invoice.title') }} {{ $order->order_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1b1b18; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .meta { color: #666; margin-bottom: 24px; }
        .addresses { width: 100%; margin-bottom: 24px; }
        .addresses td { vertical-align: top; width: 50%; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.items th, table.items td { border-bottom: 1px solid #ddd; padding: 6px 8px; text-align: {{ app()->getLocale() === 'he' ? 'right' : 'left' }}; }
        table.items th { background: #f5f5f4; }
        .totals { width: 100%; }
        .totals td { padding: 4px 8px; }
        .totals .label { text-align: {{ app()->getLocale() === 'he' ? 'left' : 'right' }}; }
        .grand-total { font-weight: bold; font-size: 14px; }
        .footer { margin-top: 32px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ __('invoice.title') }}</h1>
    <div class="meta">
        {{ __('invoice.invoice_number') }}: {{ $order->order_number }}<br>
        {{ __('invoice.date') }}: {{ $order->created_at->format('Y-m-d') }}<br>
        {{ __('invoice.payment_status') }}: {{ ucfirst($order->payment_status) }}
    </div>

    <table class="addresses">
        <tr>
            <td>
                <strong>{{ __('invoice.bill_to') }}</strong><br>
                {{ $order->billingAddress->full_name }}<br>
                {{ $order->billingAddress->line1 }}<br>
                @if($order->billingAddress->line2)
                    {{ $order->billingAddress->line2 }}<br>
                @endif
                {{ $order->billingAddress->city }}, {{ $order->billingAddress->state }} {{ $order->billingAddress->postal_code }}<br>
                {{ $order->billingAddress->country }}
            </td>
            <td>
                <strong>{{ __('invoice.ship_to') }}</strong><br>
                {{ $order->shippingAddress->full_name }}<br>
                {{ $order->shippingAddress->line1 }}<br>
                @if($order->shippingAddress->line2)
                    {{ $order->shippingAddress->line2 }}<br>
                @endif
                {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->postal_code }}<br>
                {{ $order->shippingAddress->country }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>{{ __('invoice.item') }}</th>
                <th>{{ __('invoice.quantity') }}</th>
                <th>{{ __('invoice.unit_price') }}</th>
                <th>{{ __('invoice.subtotal_line') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->productVariant->product->name }}
                        ({{ $item->productVariant->size }} / {{ $item->productVariant->color }})
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $order->currency }} {{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $order->currency }} {{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">{{ __('invoice.subtotal') }}</td>
            <td>{{ $order->currency }} {{ number_format($order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('invoice.tax') }}</td>
            <td>{{ $order->currency }} {{ number_format($order->tax_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('invoice.shipping') }}</td>
            <td>{{ $order->currency }} {{ number_format($order->shipping_amount, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td class="label">{{ __('invoice.total') }}</td>
            <td>{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</td>
        </tr>
    </table>

    <div class="footer">{{ __('invoice.thank_you') }}</div>
</body>
</html>
