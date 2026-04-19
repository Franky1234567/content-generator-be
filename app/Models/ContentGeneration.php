<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentGeneration extends Model
{
    protected $fillable = [
        'user_id',
        'product_name',
        'description',
        'features',
        'target_audience',
        'price',
        'usp',
        'generated_json',
        'style_template',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}