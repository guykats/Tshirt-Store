<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pushed to admins the moment a nightly backup fails (see
 * BackupDatabase::failLoudly), alongside — not instead of — the
 * SystemEvent::log('backup.failed', ...) audit-log entry. A broken backup job
 * left unnoticed for weeks means zero recoverable backups with nobody the
 * wiser, so this deserves the same "land in an inbox" treatment as
 * LowStockAlert rather than depending on an admin happening to open the
 * dashboard's System Events feed.
 */
class BackupFailed extends Notification
{
    use Queueable;

    public function __construct(public string $reason) {}

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
        // resources/views/emails/backup-failed.blade.php, which reuses the
        // same emails.partials.header component as the order and low-stock
        // emails) rather than Laravel's default blue-button notification theme.
        return (new MailMessage)
            ->subject(__('mail.backup_failed_subject'))
            ->view('emails.backup-failed', [
                'name' => $notifiable->name,
                'reason' => $this->reason,
                'url' => rtrim((string) config('app.url'), '/').'/dashboard/audit-log',
            ]);
    }
}
