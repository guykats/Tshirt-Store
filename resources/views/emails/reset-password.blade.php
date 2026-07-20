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
                            <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #17140f;">{{ __('mail.reset_password_intro') }}</p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px; border-collapse: collapse;">
                                <tr>
                                    <td style="border-radius: 3px; background-color: #8c6a3f;">
                                        <a href="{{ $url }}" style="display: inline-block; padding: 12px 32px; font-size: 14px; font-weight: bold; color: #f7f4ee; text-decoration: none; letter-spacing: 0.5px;">{{ __('mail.reset_password_action') }}</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 8px; font-size: 13px; line-height: 1.6; color: #4a453c;">{{ __('mail.reset_password_expire', ['count' => $expireMinutes]) }}</p>
                            <p style="margin: 0 0 20px; font-size: 13px; line-height: 1.6; color: #4a453c;">{{ __('mail.reset_password_no_action') }}</p>

                            <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #4a453c;">{{ __('mail.regards', ['app_name' => config('app.name')]) }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
