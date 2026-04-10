<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'level',
        'grade',
        'subject',
        'description',
        'instructions',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'status',
        'passing_grade',
        'show_result_to_student',
        'shuffle_questions',
        'shuffle_options',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'show_result_to_student' => 'boolean',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_number');
    }

    public function stimuli(): HasMany
    {
        return $this->hasMany(Stimulus::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(ExamToken::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
