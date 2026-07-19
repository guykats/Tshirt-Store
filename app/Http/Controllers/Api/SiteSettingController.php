<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteSettingResource;
use App\Models\SiteSetting;
use App\Models\SystemEvent;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    /**
     * Public bootstrap read — the homepage itself needs this to render the hero and
     * stats for anonymous visitors, so this route sits outside the auth:sanctum group.
     * There is nothing sensitive in this row (no secrets, no PII); every field here is
     * already destined for the public page.
     */
    public function show()
    {
        // Explicit 200: JsonResource otherwise auto-returns 201 when the underlying
        // model's wasRecentlyCreated flag is set, which happens here on the very first
        // request after a fresh deploy/migration lazily creates the singleton row —
        // this is a read endpoint and should always look like one.
        return (new SiteSettingResource(SiteSetting::current()))
            ->response()
            ->setStatusCode(200);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'logo_path' => ['nullable', 'string', 'max:2048'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'hero_tagline_en' => ['required', 'string', 'max:255'],
            'hero_tagline_he' => ['required', 'string', 'max:255'],
            'hero_subheading_en' => ['required', 'string', 'max:2000'],
            'hero_subheading_he' => ['required', 'string', 'max:2000'],
            'hero_motif' => ['required', 'string', 'in:star-of-david,menorah,chai,shalom,hamsa,pomegranate,aleph,olive-branch,hebrew-script'],
            'stat_pieces_shipped' => ['required', 'integer', 'min:0'],
            'stat_rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'stat_countries' => ['required', 'integer', 'min:0'],
        ]);

        $settings = SiteSetting::current();
        $settings->update($data);

        SystemEvent::log(
            'site_settings.updated',
            "Site design settings updated by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return new SiteSettingResource($settings->fresh());
    }
}
