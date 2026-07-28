<?php

namespace Tests\Feature\Console;

use App\Models\DesignSuggestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class GenerateDesignSuggestionsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Deliberately shells out to a real `php artisan schedule:list` in a
     * fresh process rather than resolving `Schedule::class` in-process:
     * Illuminate\Foundation\Providers\FoundationServiceProvider registers
     * that container binding as a singleton built off the shared Console
     * Kernel, and once *any* other test in the same PHPUnit process has
     * already run an artisan command (almost guaranteed somewhere in this
     * suite), the container's `afterResolving(Schedule::class, ...)` hook
     * bootstrap/app.php relies on stops firing for later tests, silently
     * yielding zero events with no exception — a framework/test-isolation
     * quirk unrelated to whether the command is actually registered. A real
     * subprocess sidesteps that shared-process state entirely.
     */
    public function test_it_is_registered_on_the_schedule(): void
    {
        $process = new Process(['php', 'artisan', 'schedule:list'], base_path());
        $process->run();

        $this->assertStringContainsString(
            'app:generate-design-suggestions',
            $process->getOutput(),
            'Expected app:generate-design-suggestions to be registered on the schedule.'
        );
    }

    public function test_it_is_callable_directly_and_generates_a_batch(): void
    {
        $this->artisan('app:generate-design-suggestions')->assertExitCode(0);

        $this->assertSame(20, DesignSuggestion::query()->count());
        $this->assertDatabaseHas('system_events', [
            'event_type' => 'design_suggestions.generated',
        ]);
    }
}
