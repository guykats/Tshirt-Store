<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DesignResource;
use App\Http\Resources\DesignSuggestionResource;
use App\Models\Design;
use App\Models\DesignSuggestion;
use App\Models\SystemEvent;
use App\Services\DesignSuggestionGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DesignSuggestionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', DesignSuggestion::class);

        return DesignSuggestionResource::collection(
            DesignSuggestion::query()
                ->orderByDesc('batch_date')
                ->orderByDesc('id')
                ->paginate(20)
        );
    }

    /**
     * Same generator the nightly `app:generate-design-suggestions` schedule
     * uses (see bootstrap/app.php) — the admin "Generate now" button is just
     * an on-demand trigger of the identical code path, not a second
     * implementation of batch generation.
     */
    public function generateNow(Request $request, DesignSuggestionGenerator $generator)
    {
        $this->authorize('create', DesignSuggestion::class);

        $batch = $generator->generate();

        SystemEvent::log(
            'design_suggestions.generated',
            "Generated a batch of {$batch->count()} design suggestion(s) on demand by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['count' => $batch->count()],
        );

        return DesignSuggestionResource::collection($batch);
    }

    /**
     * Promotes a suggestion into a real Design row (status=pending_approval)
     * so it flows into the existing DesignController approve/reject/product-
     * attachment pipeline unchanged, rather than inventing a second parallel
     * workflow just for suggestions.
     */
    public function keep(Request $request, DesignSuggestion $designSuggestion)
    {
        $this->authorize('update', $designSuggestion);

        if ($designSuggestion->status !== 'pending') {
            return response()->json(['message' => 'Only a pending suggestion can be kept.'], 422);
        }

        $design = DB::transaction(function () use ($designSuggestion) {
            // mockup_url stores the raw motif key here, matching how Design
            // already does elsewhere (see app/Models/Design.php) — not an
            // actual image URL.
            $design = Design::create([
                'title' => Str::headline($designSuggestion->motif).' Design',
                'mockup_url' => $designSuggestion->motif,
                'status' => 'pending_approval',
                'source_agent' => 'design_suggestion',
            ]);

            $designSuggestion->update([
                'promoted_design_id' => $design->id,
                'status' => 'kept',
            ]);

            return $design;
        });

        SystemEvent::log(
            'design_suggestion.kept',
            "Design suggestion #{$designSuggestion->id} ({$designSuggestion->motif}) kept by {$request->user()->name} and promoted to design \"{$design->title}\".",
            $request->user()->name,
            'user',
            ['design_suggestion_id' => $designSuggestion->id, 'design_id' => $design->id],
        );

        return new DesignResource($design);
    }

    public function discard(Request $request, DesignSuggestion $designSuggestion)
    {
        $this->authorize('update', $designSuggestion);

        if ($designSuggestion->status !== 'pending') {
            return response()->json(['message' => 'Only a pending suggestion can be discarded.'], 422);
        }

        $designSuggestion->update(['status' => 'discarded']);

        SystemEvent::log(
            'design_suggestion.discarded',
            "Design suggestion #{$designSuggestion->id} ({$designSuggestion->motif}) discarded by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['design_suggestion_id' => $designSuggestion->id],
        );

        return new DesignSuggestionResource($designSuggestion);
    }
}
