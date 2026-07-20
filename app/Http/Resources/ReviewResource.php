<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'body' => $this->body,
            'reviewer_name' => $this->whenLoaded('user', fn () => $this->user->name),
            // Lets the frontend show edit/delete controls on the current
            // user's own review without exposing the raw user_id.
            'is_own' => $request->user()?->id === $this->user_id,
            // Only populated for the admin moderation listing (see
            // ReviewController::manage), which eager-loads 'product' across many
            // products at once — the public per-product listing never loads this
            // relation since the product is already implied by the URL.
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'product_slug' => $this->whenLoaded('product', fn () => $this->product->slug),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
