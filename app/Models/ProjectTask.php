<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

#[Fillable([
    'title', 'description', 'agent_name', 'status', 'task_type',
    'commit_sha', 'screenshot_path', 'blocked_reason', 'completed_at',
])]
class ProjectTask extends Model
{
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * No write endpoint exists for this yet, but ProjectTaskResource turns this straight
     * into a public Storage::url() with no other sanitization — constrain it now so a
     * future create/edit endpoint can't become a path-disclosure vector by construction.
     */
    protected function screenshotPath(): Attribute
    {
        return Attribute::make(
            set: function (?string $value) {
                if ($value === null) {
                    return null;
                }

                if (str_contains($value, '..') || ! preg_match('#^task-screenshots/[\w.\-]+\.(png|jpe?g)$#', $value)) {
                    throw new InvalidArgumentException("Invalid screenshot_path: {$value}");
                }

                return $value;
            },
        );
    }
}
