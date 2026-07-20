<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
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
        // Rendered through the site's own branded layout (see
        // resources/views/emails/reset-password.blade.php, which reuses the
        // same emails.partials.header component as the order emails) rather
        // than Laravel's default blue-button notification theme.
        return (new MailMessage)
            ->subject(__('mail.reset_password_subject'))
            ->view('emails.reset-password', [
                'name' => $notifiable->name,
                'url' => $this->resetUrl($notifiable),
                'expireMinutes' => (int) config('auth.passwords.users.expire'),
            ]);
    }

    protected function resetUrl(mixed $notifiable): string
    {
        $base = rtrim((string) config('app.url'), '/');

        $query = http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return "{$base}/reset-password?{$query}";
    }
}
