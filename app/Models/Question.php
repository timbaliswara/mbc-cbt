<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'exam_id',
        'stimulus_id',
        'order_number',
        'type',
        'question_text',
        'image_path',
        'answer_key',
        'score_weight',
        'explanation',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function stimulus(): BelongsTo
    {
        return $this->belongsTo(Stimulus::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_number');
    }

    public function media(): HasMany
    {
        return $this->hasMany(QuestionMedia::class);
    }

    public function isMultipleChoice(): bool
    {
        return $this->type === 'multiple_choice';
    }

    public function isTrueFalse(): bool
    {
        return $this->type === 'true_false';
    }

    public function isTrueFalseGroup(): bool
    {
        return $this->type === 'true_false_group';
    }

    public function isMultipleChoiceComplex(): bool
    {
        return $this->type === 'multiple_choice_complex';
    }

    public function isEssay(): bool
    {
        return $this->type === 'essay';
    }

    public function usesSingleOptionAnswer(): bool
    {
        return in_array($this->type, ['multiple_choice', 'true_false'], true);
    }

    public function usesMultipleOptionAnswer(): bool
    {
        return $this->type === 'multiple_choice_complex';
    }

    public function usesStatementTruthAnswer(): bool
    {
        return $this->type === 'true_false_group';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'multiple_choice_complex' => 'Pilihan ganda kompleks',
            'true_false_group' => 'Benar / salah per pernyataan',
            'true_false' => 'Benar / salah',
            'essay' => 'Esai',
            default => 'Pilihan ganda',
        };
    }
}
