<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentGeneration extends Model
{
    protected $fillable = [
        'user_id',
        'content_type',
        'topic',
        'keywords',
        'target_audience',
        'tone',
        'language',
        'generated_content',
        'word_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}