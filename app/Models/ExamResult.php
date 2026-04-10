<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'correct_count',
        'wrong_count',
        'blank_count',
        'multiple_choice_score',
        'essay_score',
        'total_score',
        'is_passed',
        'essay_status',
    ];

    protected function casts(): array
    {
        return ['is_passed' => 'boolean'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }
}
