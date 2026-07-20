<?php

namespace App\Console\Commands;

use App\Models\SystemEvent;
use App\Models\User;
use App\Notifications\BackupFailed;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

#[Signature('app:backup-database')]
#[Description('Dump the production MySQL database to a timestamped file outside the git-deployed path, and prune backups beyond the retention window.')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        // Backups are always taken via mysqldump against the connection literally
        // named 'mysql', regardless of what connection the app's default is set
        // to. Production only ever runs on mysql (a single Hostinger instance);
        // reading a hard-coded connection name here (instead of database.default)
        // keeps this command decoupled from whatever the app happens to be
        // configured to use for its own queries, e.g. sqlite in tests.
        $connection = config('database.connections.mysql', []);

        $backupDir = rtrim((string) config('backup.path'), '/');
        $keep = (int) config('backup.keep', 14);

        if (! is_dir($backupDir) && ! @mkdir($backupDir, 0755, true) && ! is_dir($backupDir)) {
            return $this->failLoudly("Could not create backup directory: {$backupDir}");
        }

        $filename = 'backup_'.now()->format('Y-m-d_His').'.sql';
        $path = "{$backupDir}/{$filename}";

        $result = Process::env(['MYSQL_PWD' => (string) ($connection['password'] ?? '')])
            ->timeout(300)
            ->run([
                'mysqldump',
                '--host='.($connection['host'] ?? '127.0.0.1'),
                '--port='.($connection['port'] ?? '3306'),
                '--user='.($connection['username'] ?? 'root'),
                '--single-transaction',
                '--quick',
                '--result-file='.$path,
                $connection['database'] ?? '',
            ]);

        if (! $result->successful()) {
            if (file_exists($path)) {
                @unlink($path);
            }

            return $this->failLoudly(
                "Database backup failed (exit {$result->exitCode()}): ".trim($result->errorOutput() ?: $result->output())
            );
        }

        $size = file_exists($path) ? filesize($path) : 0;

        SystemEvent::log(
            'backup.completed',
            "Database backup written to {$filename} ({$size} bytes).",
            'schedule:run',
            'system',
            ['path' => $path, 'size_bytes' => $size],
        );

        $this->info("Backup written to {$path}");

        $this->uploadOffsite($path, $filename, $size);

        $this->rotate($backupDir, $keep);

        return self::SUCCESS;
    }

    /**
     * Copy the just-completed local dump to an off-site disk, so a full loss
     * of the Hostinger box doesn't take every backup down with it. This is
     * strictly additive to the local backup, which has already succeeded by
     * the time this runs: an off-site failure is logged and (loudly) emailed
     * to admins, but it must never delete the local dump or flip the
     * command's overall result to failure — see config/backup.php for why
     * 'offsite_disk' defaults to null (deferred off-site credentials, same
     * posture as the PayPal/SMTP secrets documented in CLAUDE.md).
     */
    private function uploadOffsite(string $path, string $filename, int $size): void
    {
        $disk = config('backup.offsite_disk');

        if (! $disk) {
            Log::info('Off-site backup upload skipped: backup.offsite_disk is not configured.');

            return;
        }

        try {
            $uploaded = Storage::disk($disk)->put($filename, fopen($path, 'r'));
        } catch (\Throwable $e) {
            $uploaded = false;
            report($e);
        }

        if (! $uploaded) {
            $message = "Off-site backup upload to disk '{$disk}' failed for {$filename}. The local backup itself succeeded and was left in place.";

            SystemEvent::log(
                'backup.offsite_failed',
                $message,
                'schedule:run',
                'system',
                ['disk' => $disk, 'path' => $path, 'size_bytes' => $size],
            );

            $this->warn($message);

            try {
                // Reuses BackupFailed rather than a separate notification class:
                // the message text itself makes clear this is the lesser-severity
                // off-site-only failure (local backup intact), so admins still
                // get an inbox alert without a second notification class/view/
                // lang-string set to maintain for what is, in substance, the
                // same "a backup step silently broke" situation.
                Notification::send(User::where('role', 'admin')->get(), new BackupFailed($message));
            } catch (\Throwable $e) {
                report($e);
                Log::warning('Off-site backup failure notification failed to send.', ['reason' => $message]);
            }

            return;
        }

        SystemEvent::log(
            'backup.offsite_uploaded',
            "Database backup {$filename} ({$size} bytes) uploaded to off-site disk '{$disk}'.",
            'schedule:run',
            'system',
            ['disk' => $disk, 'path' => $filename, 'size_bytes' => $size],
        );
    }

    /**
     * Log a SystemEvent, email every admin, AND print + return failure — the
     * whole point is that a broken backup job is actually noticed rather than
     * silently assumed to be working. The audit-log entry and the email are
     * complementary (mirrors CheckoutController's low-stock alert), not a
     * replacement for each other: the SystemEvent is there if nobody opens
     * their inbox, and the email doesn't depend on an admin remembering to
     * check the dashboard.
     */
    private function failLoudly(string $message): int
    {
        SystemEvent::log('backup.failed', $message, 'schedule:run', 'system');

        try {
            Notification::send(User::where('role', 'admin')->get(), new BackupFailed($message));
        } catch (\Throwable $e) {
            report($e);
            Log::warning('Backup failure notification failed to send.', ['reason' => $message]);
        }

        $this->error($message);

        return self::FAILURE;
    }

    /**
     * Keep only the $keep most-recent dumps. Filenames are timestamp-prefixed
     * (backup_Y-m-d_His.sql) so a plain string sort is also a chronological
     * sort — no filesystem mtime reliance needed.
     */
    private function rotate(string $backupDir, int $keep): void
    {
        $files = collect(glob("{$backupDir}/backup_*.sql") ?: [])
            ->sortByDesc(fn (string $file) => $file)
            ->values();

        $stale = $files->slice($keep);

        if ($stale->isEmpty()) {
            return;
        }

        foreach ($stale as $file) {
            @unlink($file);
        }

        SystemEvent::log(
            'backup.rotated',
            "Pruned {$stale->count()} backup(s) beyond the retention window of {$keep}.",
            'schedule:run',
            'system',
            ['deleted' => $stale->map(fn (string $file) => basename($file))->values()->all()],
        );
    }
}
