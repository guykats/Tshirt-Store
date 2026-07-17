<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
}
