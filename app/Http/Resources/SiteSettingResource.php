<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SiteSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'logo_path' => $this->logo_path,
            // logo_path may be a full URL (no image upload endpoint exists yet, so
            // admins point at an already-hosted asset) or a storage-relative path —
            // only resolve the latter through Storage::url().
            'logo_url' => $this->logo_path
                ? (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')
                    ? $this->logo_path
                    : Storage::url($this->logo_path))
                : null,
            'accent_color' => $this->accent_color,
            'hero_tagline_en' => $this->hero_tagline_en,
            'hero_tagline_he' => $this->hero_tagline_he,
            'hero_subheading_en' => $this->hero_subheading_en,
            'hero_subheading_he' => $this->hero_subheading_he,
            'hero_motif' => $this->hero_motif,
            'updated_at' => $this->updated_at,
        ];
    }
}
