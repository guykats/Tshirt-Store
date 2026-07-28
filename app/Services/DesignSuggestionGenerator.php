<?php

namespace App\Services;

use App\Models\DesignSuggestion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Produces a batch of design-suggestion candidates by rotating across the
 * configured motif list (config/design_suggestions.php, mirroring DesignArt
 * .jsx's REGISTRY). Used by both the nightly `app:generate-design-suggestions`
 * schedule and the admin "Generate now" button (DesignSuggestionController::
 * generateNow) so there's exactly one code path that decides what a batch
 * looks like.
 */
class DesignSuggestionGenerator
{
    /**
     * @return Collection<int, DesignSuggestion>
     */
    public function generate(?Carbon $batchDate = null): Collection
    {
        $motifs = config('design_suggestions.motifs', []);
        $batchSize = (int) config('design_suggestions.batch_size', 20);
        $batchDate ??= Carbon::today();
        $motifCount = max(count($motifs), 1);

        $suggestions = new Collection();

        for ($i = 0; $i < $batchSize; $i++) {
            $suggestions->push(DesignSuggestion::create([
                'batch_date' => $batchDate,
                'motif' => $motifs[$i % $motifCount] ?? null,
                // `style` is intentionally left null — reserved for the
                // separate, unapproved "Style-Variant Design System
                // Expansion" epic.
                'status' => 'pending',
            ]));
        }

        return $suggestions;
    }
}
