<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'description', 'agent_name', 'status', 'priority', 'decided_by', 'decided_at'])]
class Epic extends Model
{
    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
