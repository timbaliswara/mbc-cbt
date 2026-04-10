<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAnswer extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'question_option_id',
        'answer_text',
        'is_flagged',
        'is_correct',
        'score',
    ];

    protected function casts(): array
    {
        return ['is_flagged' => 'boolean', 'is_correct' => 'boolean'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }
}
