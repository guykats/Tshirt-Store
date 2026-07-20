<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'he' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
</head>
<body style="margin: 0; padding: 0; background-color: #f7f4ee; font-family: 'DejaVu Sans', Arial, sans-serif; color: #17140f;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; background-color: #f7f4ee; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; max-width: 480px; border-collapse: collapse; text-align: {{ app()->getLocale() === 'he' ? 'right' : 'left' }};">
                    <tr>
                        <td>
                            @include('emails.partials.header')

                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #17140f;">{{ __('mail.greeting', ['name' => $name]) }}</p>
                            <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #17140f;">{{ __('mail.low_stock_alert_intro', ['product' => $productName, 'variant' => $variantLabel]) }}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; margin: 0 0 24px; border-collapse: collapse; border: 1px solid #e4dfd4;">
                                <tr>
                                    <td style="padding: 10px 14px; background-color: #efeadf; color: #4a453c; font-size: 13px; font-weight: bold; border-bottom: 1px solid #e4dfd4;">{{ __('mail.low_stock_alert_quantity', ['count' => $quantity]) }}</td>
                                    <td style="padding: 10px 14px; background-color: #f7f4ee; color: #8c6a3f; font-size: 15px; font-weight: bold; text-align: {{ app()->getLocale() === 'he' ? 'left' : 'right' }}; border-bottom: 1px solid #e4dfd4;">
                                        {{ $quantity }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; background-color: #efeadf; color: #4a453c; font-size: 13px;">{{ __('mail.low_stock_alert_sku', ['sku' => $sku]) }}</td>
                                    <td style="padding: 10px 14px; background-color: #f7f4ee; color: #17140f; font-size: 13px; text-align: {{ app()->getLocale() === 'he' ? 'left' : 'right' }};">{{ $sku }}</td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px; border-collapse: collapse;">
                                <tr>
                                    <td style="border-radius: 3px; background-color: #8c6a3f;">
                                        <a href="{{ $url }}" style="display: inline-block; padding: 12px 32px; font-size: 14px; font-weight: bold; color: #f7f4ee; text-decoration: none; letter-spacing: 0.5px;">{{ __('mail.low_stock_alert_action') }}</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #4a453c;">{{ __('mail.regards', ['app_name' => config('app.name')]) }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
