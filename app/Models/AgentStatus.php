<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['agent_name', 'status', 'current_task'])]
class AgentStatus extends Model
{
    //
}
