<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestimonialResource;
use App\Models\SystemEvent;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    /**
     * Public read the homepage renders from: active quotes only, in curator-chosen
     * order. Sits outside auth:sanctum so anonymous visitors can see it.
     */
    public function index()
    {
        $testimonials = Testimonial::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return TestimonialResource::collection($testimonials);
    }

    /**
     * Admin management listing — includes inactive quotes too, so an admin can
     * re-enable one instead of only ever adding new rows.
     */
    public function manage(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $testimonials = Testimonial::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return TestimonialResource::collection($testimonials);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validated($request);

        $testimonial = Testimonial::create($data);

        SystemEvent::log(
            'testimonial.created',
            "Testimonial from \"{$testimonial->author_name}\" added by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return (new TestimonialResource($testimonial))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validated($request);

        $testimonial->update($data);

        SystemEvent::log(
            'testimonial.updated',
            "Testimonial from \"{$testimonial->author_name}\" updated by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return new TestimonialResource($testimonial->fresh());
    }

    public function destroy(Request $request, Testimonial $testimonial)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $authorName = $testimonial->author_name;
        $testimonial->delete();

        SystemEvent::log(
            'testimonial.deleted',
            "Testimonial from \"{$authorName}\" removed by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return response()->json(['message' => 'Deleted.']);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'author_name' => ['required', 'string', 'max:255'],
            'author_context_en' => ['required', 'string', 'max:255'],
            'author_context_he' => ['required', 'string', 'max:255'],
            'quote_en' => ['required', 'string', 'max:2000'],
            'quote_he' => ['required', 'string', 'max:2000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
