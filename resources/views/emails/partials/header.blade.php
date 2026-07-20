{{-- Shared brand header for transactional order emails. Inline styles only —
     mail clients strip <style> blocks and don't support CSS custom properties,
     so the resources/css/app.css token values are hex-coded directly here:
     --color-ink: #17140f, --color-brass: #8c6a3f. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; border-collapse: collapse; margin: 0 0 24px;">
    <tr>
        <td align="center" style="padding: 0 0 14px; font-family: Georgia, 'Times New Roman', serif; font-size: 22px; letter-spacing: 3px; color: #17140f;">
            {{ __('mail.brand_name') }}
        </td>
    </tr>
    <tr>
        <td align="center" style="padding: 0;">
            <table role="presentation" width="60" cellpadding="0" cellspacing="0" border="0" style="width: 60px; border-collapse: collapse;">
                <tr>
                    <td style="height: 2px; line-height: 2px; font-size: 2px; background-color: #8c6a3f;">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
