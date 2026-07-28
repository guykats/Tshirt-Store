<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'batch_date', 'motif', 'style', 'status', 'promoted_design_id',
])]
class DesignSuggestion extends Model
{
    protected function casts(): array
    {
        return [
            'batch_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Design, $this>
     */
    public function promotedDesign(): BelongsTo
    {
        return $this->belongsTo(Design::class, 'promoted_design_id');
    }
}
