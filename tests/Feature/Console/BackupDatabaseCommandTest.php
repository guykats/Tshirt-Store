<?php

namespace Tests\Feature\Console;

use App\Models\User;
use App\Notifications\BackupFailed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        // The command always reads the connection literally named 'mysql'
        // (never database.default, which stays sqlite here) purely for host/
        // user/password/database values to build the mysqldump argv — it never
        // actually connects, since the mysqldump call itself is faked below.
        $this->backupDir = sys_get_temp_dir().'/backup-test-'.uniqid();
        config(['backup.path' => $this->backupDir, 'backup.keep' => 3]);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->backupDir)) {
            File::deleteDirectory($this->backupDir);
        }

        Date::setTestNow();

        parent::tearDown();
    }

    public function test_it_writes_a_timestamped_dump_and_logs_a_system_event_on_success(): void
    {
        Date::setTestNow('2026-07-19 03:00:00');

        Process::fake(function ($process) {
            // Simulate mysqldump actually producing the --result-file it was
            // asked for, without needing a real mysql binary or database.
            foreach ($process->command as $arg) {
                if (str_starts_with($arg, '--result-file=')) {
                    file_put_contents(substr($arg, strlen('--result-file=')), "-- fake dump\n");
                }
            }

            return Process::result(output: '', errorOutput: '', exitCode: 0);
        });

        $this->artisan('app:backup-database')->assertExitCode(0);

        $expectedFile = $this->backupDir.'/backup_2026-07-19_030000.sql';
        $this->assertFileExists($expectedFile);

        $this->assertDatabaseHas('system_events', [
            'event_type' => 'backup.completed',
        ]);
    }

    public function test_it_fails_loudly_and_logs_a_system_event_when_mysqldump_is_unavailable(): void
    {
        Process::fake([
            '*mysqldump*' => Process::result(output: '', errorOutput: 'mysqldump: command not found', exitCode: 127),
        ]);

        $this->artisan('app:backup-database')->assertExitCode(1);

        $this->assertDatabaseHas('system_events', [
            'event_type' => 'backup.failed',
        ]);
    }

    public function test_it_emails_every_admin_exactly_once_when_a_backup_fails(): void
    {
        Notification::fake();

        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);
        User::factory()->create(); // non-admin, should not be notified

        Process::fake([
            '*mysqldump*' => Process::result(output: '', errorOutput: 'mysqldump: command not found', exitCode: 127),
        ]);

        $this->artisan('app:backup-database')->assertExitCode(1);

        Notification::assertSentTo($admin1, BackupFailed::class);
        Notification::assertSentTo($admin2, BackupFailed::class);
        Notification::assertSentTimes(BackupFailed::class, 2);
    }

    public function test_it_sends_no_backup_failed_notification_when_the_backup_succeeds(): void
    {
        Notification::fake();

        User::factory()->create(['role' => 'admin']);

        Process::fake(function ($process) {
            foreach ($process->command as $arg) {
                if (str_starts_with($arg, '--result-file=')) {
                    file_put_contents(substr($arg, strlen('--result-file=')), "-- fake dump\n");
                }
            }

            return Process::result(output: '', errorOutput: '', exitCode: 0);
        });

        $this->artisan('app:backup-database')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_it_prunes_backups_beyond_the_retention_window(): void
    {
        mkdir($this->backupDir, 0755, true);

        // Pre-seed 4 older dumps; retention is configured to 3 in setUp().
        foreach (['01', '02', '03', '04'] as $day) {
            file_put_contents("{$this->backupDir}/backup_2026-07-{$day}_030000.sql", 'old');
        }

        Date::setTestNow('2026-07-05 03:00:00');

        Process::fake(function ($process) {
            foreach ($process->command as $arg) {
                if (str_starts_with($arg, '--result-file=')) {
                    file_put_contents(substr($arg, strlen('--result-file=')), "-- fake dump\n");
                }
            }

            return Process::result(output: '', errorOutput: '', exitCode: 0);
        });

        $this->artisan('app:backup-database')->assertExitCode(0);

        $remaining = collect(glob("{$this->backupDir}/backup_*.sql"))
            ->map(fn (string $file) => basename($file))
            ->sort()
            ->values()
            ->all();

        // The new dump (07-05) plus the two most recent pre-seeded ones (07-03,
        // 07-04) survive; 07-01 and 07-02 get pruned to respect keep=3.
        $this->assertSame([
            'backup_2026-07-03_030000.sql',
            'backup_2026-07-04_030000.sql',
            'backup_2026-07-05_030000.sql',
        ], $remaining);

        $this->assertDatabaseHas('system_events', [
            'event_type' => 'backup.rotated',
        ]);
    }

    public function test_it_creates_the_backup_directory_if_missing(): void
    {
        $this->assertDirectoryDoesNotExist($this->backupDir);

        Process::fake([
            '*mysqldump*' => Process::result(output: '', errorOutput: '', exitCode: 0),
        ]);

        $this->artisan('app:backup-database')->assertExitCode(0);

        $this->assertDirectoryExists($this->backupDir);
    }
}
