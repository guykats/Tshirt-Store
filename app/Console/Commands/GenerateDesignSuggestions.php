<?php

namespace App\Console\Commands;

use App\Models\SystemEvent;
use App\Services\DesignSuggestionGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:generate-design-suggestions')]
#[Description('Generate the nightly batch of design suggestions for the admin Discover feed.')]
class GenerateDesignSuggestions extends Command
{
    public function __construct(private readonly DesignSuggestionGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $batch = $this->generator->generate();

        SystemEvent::log(
            'design_suggestions.generated',
            "Generated a batch of {$batch->count()} design suggestion(s) for ".now()->toDateString().'.',
            'schedule:run',
            'system',
            ['count' => $batch->count()],
        );

        $this->info("Generated {$batch->count()} design suggestion(s).");

        return self::SUCCESS;
    }
}
