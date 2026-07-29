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
     * Kept is just a checkpoint now — it no longer creates a Design row.
     * Promotion into a real Design happens later, explicitly, via publish()
     * or publishAll(), so the owner has a separate "kept, awaiting publish"
     * state before anything lands on the main Pending Designs queue.
     */
    public function keep(Request $request, DesignSuggestion $designSuggestion)
    {
        $this->authorize('update', $designSuggestion);

        if ($designSuggestion->status !== 'pending') {
            return response()->json(['message' => 'Only a pending suggestion can be kept.'], 422);
        }

        $designSuggestion->update(['status' => 'kept']);

        SystemEvent::log(
            'design_suggestion.kept',
            "Design suggestion #{$designSuggestion->id} ({$designSuggestion->motif}) kept by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['design_suggestion_id' => $designSuggestion->id],
        );

        return new DesignSuggestionResource($designSuggestion);
    }

    /**
     * Promotes a kept suggestion into a real Design row (status=
     * pending_approval) so it flows into the existing DesignController
     * approve/reject/product-attachment pipeline unchanged, rather than
     * inventing a second parallel workflow just for suggestions. This is
     * the step that used to happen automatically inside keep().
     */
    public function publish(Request $request, DesignSuggestion $designSuggestion)
    {
        $this->authorize('update', $designSuggestion);

        if ($designSuggestion->status !== 'kept') {
            return response()->json(['message' => 'Only a kept suggestion can be published.'], 422);
        }

        $design = DB::transaction(fn () => $this->promote($designSuggestion));

        SystemEvent::log(
            'design_suggestion.published',
            "Design suggestion #{$designSuggestion->id} ({$designSuggestion->motif}) published by {$request->user()->name} to design \"{$design->title}\".",
            $request->user()->name,
            'user',
            ['design_suggestion_id' => $designSuggestion->id, 'design_id' => $design->id],
        );

        return new DesignResource($design);
    }

    /**
     * Publishes every currently-kept suggestion in the latest batch (the
     * same batch the Discover grid shows first — see Discover.jsx's
     * batch_date-desc ordering) in one call, for the "Publish all kept"
     * bulk button.
     */
    public function publishAll(Request $request)
    {
        $this->authorize('create', DesignSuggestion::class);

        $latestBatchDate = DesignSuggestion::query()->max('batch_date');

        $designs = DB::transaction(function () use ($latestBatchDate) {
            return DesignSuggestion::query()
                ->where('status', 'kept')
                ->where('batch_date', $latestBatchDate)
                ->get()
                ->map(fn (DesignSuggestion $designSuggestion) => $this->promote($designSuggestion));
        });

        SystemEvent::log(
            'design_suggestion.published_all',
            "{$designs->count()} kept design suggestion(s) published by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['count' => $designs->count()],
        );

        return DesignResource::collection($designs);
    }

    private function promote(DesignSuggestion $designSuggestion): Design
    {
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
            'status' => 'published',
        ]);

        return $design;
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
