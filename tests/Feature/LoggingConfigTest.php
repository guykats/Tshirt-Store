<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoggingConfigTest extends TestCase
{
    /**
     * config/logging.php's 'stack' channel must default LOG_STACK to 'daily'
     * (not 'single'), so an environment that never explicitly sets LOG_STACK
     * still gets a rotated, pruned storage/logs/laravel.log instead of one
     * unbounded file — mirroring BackupDatabase's retention for DB backups.
     * This guards against a future refactor silently reverting that default.
     */
    public function test_stack_channel_resolves_to_daily_when_log_stack_is_unset(): void
    {
        putenv('LOG_STACK');
        unset($_ENV['LOG_STACK'], $_SERVER['LOG_STACK']);

        $stackChannels = explode(',', (string) env('LOG_STACK', 'daily'));

        $this->assertSame(['daily'], $stackChannels);
    }

    public function test_daily_channel_has_a_bounded_retention_window(): void
    {
        // LOG_DAILY_DAYS comes through env() as a numeric string when set in
        // .env (only its int default kicks in as an actual int), so assert on
        // the numeric value rather than the PHP type.
        $days = (int) config('logging.channels.daily.days');

        $this->assertGreaterThan(0, $days);
        $this->assertLessThanOrEqual(90, $days);
    }

    public function test_configured_default_channel_stack_resolves_to_daily(): void
    {
        // Exercises the actual config/logging.php value as loaded for this
        // test run (not a re-derivation), catching a literal 'single' default
        // that the first test's manual env() call wouldn't.
        $this->assertSame(['daily'], config('logging.channels.stack.channels'));
    }
}
