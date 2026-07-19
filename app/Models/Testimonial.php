<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'author_name', 'author_context_en', 'author_context_he',
    'quote_en', 'quote_he', 'sort_order', 'is_active',
])]
class Testimonial extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
