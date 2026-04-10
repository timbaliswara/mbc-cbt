<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionMedia extends Model
{
    protected $fillable = ['question_id', 'media_type', 'file_path', 'caption'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
