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
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject(__('mail.reset_password_subject'))
            ->greeting(__('mail.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.reset_password_intro'))
            ->action(__('mail.reset_password_action'), $url)
            ->line(__('mail.reset_password_expire', ['count' => (int) config('auth.passwords.users.expire')]))
            ->line(__('mail.reset_password_no_action'))
            ->line(__('mail.regards', ['app_name' => config('app.name')]));
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
