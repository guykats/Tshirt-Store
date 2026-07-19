<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'he' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: 'DejaVu Sans', Arial, sans-serif; color: #1b1b18; max-width: 480px; margin: 0 auto; padding: 24px;">
    <p>{{ __('mail.greeting', ['name' => $order->user->name]) }}</p>
    <p>{{ __('mail.order_delivered_intro') }}</p>

    <table style="width: 100%; margin: 24px 0; border-collapse: collapse;">
        <tr>
            <td style="padding: 4px 0; color: #666;">{{ __('mail.order_number') }}</td>
            <td style="padding: 4px 0; text-align: {{ app()->getLocale() === 'he' ? 'left' : 'right' }};">{{ $order->order_number }}</td>
        </tr>
    </table>

    <p>{{ __('mail.thanks') }}</p>
    <p>{{ __('mail.regards', ['app_name' => config('app.name')]) }}</p>
</body>
</html>
