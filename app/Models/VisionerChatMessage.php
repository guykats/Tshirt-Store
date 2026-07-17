<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'role', 'content', 'epic_id'])]
class VisionerChatMessage extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function epic()
    {
        return $this->belongsTo(Epic::class);
    }
}
