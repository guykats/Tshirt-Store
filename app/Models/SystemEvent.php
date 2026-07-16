<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['event_type', 'description', 'actor_type', 'actor_name', 'metadata'])]
class SystemEvent extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Convenience helper for logging a system event from anywhere in the app.
     */
    public static function log(string $eventType, string $description, ?string $actorName = null, string $actorType = 'system', array $metadata = []): self
    {
        return static::create([
            'event_type' => $eventType,
            'description' => $description,
            'actor_type' => $actorType,
            'actor_name' => $actorName,
            'metadata' => $metadata,
        ]);
    }
}
