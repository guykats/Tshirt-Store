<?php

namespace Tests\Feature;

use App\Models\DesignSuggestion;
use App\Services\DesignSuggestionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSuggestionGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_produces_exactly_20_suggestions_for_a_batch(): void
    {
        $batch = app(DesignSuggestionGenerator::class)->generate();

        $this->assertCount(20, $batch);
        $this->assertSame(20, DesignSuggestion::query()->count());
    }

    public function test_every_suggestion_uses_a_motif_from_the_configured_list_and_is_pending(): void
    {
        $motifs = config('design_suggestions.motifs');

        $batch = app(DesignSuggestionGenerator::class)->generate();

        foreach ($batch as $suggestion) {
            $this->assertContains($suggestion->motif, $motifs);
            $this->assertSame('pending', $suggestion->status);
            $this->assertNull($suggestion->style);
            $this->assertNull($suggestion->promoted_design_id);
        }
    }

    public function test_it_stamps_every_suggestion_in_the_batch_with_the_same_batch_date(): void
    {
        $batch = app(DesignSuggestionGenerator::class)->generate();

        $dates = $batch->pluck('batch_date')->map(fn ($date) => $date->toDateString())->unique();

        $this->assertCount(1, $dates);
        $this->assertSame(now()->toDateString(), $dates->first());
    }
}
