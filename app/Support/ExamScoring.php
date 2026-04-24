<?php

namespace App\Support;

use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\StudentAnswer;

class ExamScoring
{
    public static function selectedOptionIds(?StudentAnswer $answer): array
    {
        if (! $answer) {
            return [];
        }

        if ($answer->question_option_id) {
            return [(int) $answer->question_option_id];
        }

        return collect($answer->answer_payload ?? [])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();
    }

    public static function answerIsFilled(Question $question, ?StudentAnswer $answer): bool
    {
        if (! $answer) {
            return false;
        }

        if ($question->usesSingleOptionAnswer()) {
            return filled($answer->question_option_id);
        }

        if ($question->usesMultipleOptionAnswer()) {
            return self::selectedOptionIds($answer) !== [];
        }

        return filled($answer->answer_text);
    }

    public static function progressPercentage(ExamAttempt $attempt): int
    {
        $questions = $attempt->exam->questions->where('is_active', true)->values();
        $total = $questions->count();

        if ($total === 0) {
            return 0;
        }

        $answers = $attempt->answers->keyBy('question_id');
        $answered = $questions
            ->filter(fn (Question $question) => self::answerIsFilled($question, $answers->get($question->id)))
            ->count();

        return (int) round(($answered / $total) * 100);
    }

    public static function maxScore(ExamAttempt $attempt): int
    {
        return (int) $attempt->exam->questions->where('is_active', true)->sum('score_weight');
    }

    public static function evaluateAttempt(ExamAttempt $attempt): array
    {
        $questions = $attempt->exam->questions->where('is_active', true)->values();
        $answers = $attempt->answers->keyBy('question_id');
        $correct = 0;
        $wrong = 0;
        $blank = 0;
        $multipleChoiceScore = 0;
        $essayScore = 0;
        $hasEssay = false;
        $hasPendingEssay = false;

        foreach ($questions as $question) {
            $answer = $answers->get($question->id);

            if (! self::answerIsFilled($question, $answer)) {
                $blank++;

                continue;
            }

            if ($question->isEssay()) {
                $hasEssay = true;
                $essayScore += (int) ($answer?->score ?? 0);
                $hasPendingEssay = $hasPendingEssay || (int) ($answer?->score ?? 0) === 0;

                continue;
            }

            $selectedIds = self::selectedOptionIds($answer);
            $correctIds = $question->options
                ->where('is_correct', true)
                ->pluck('id')
                ->map(fn ($value) => (int) $value)
                ->values()
                ->all();

            sort($selectedIds);
            sort($correctIds);

            $isCorrect = $selectedIds === $correctIds;
            $score = $isCorrect ? (int) $question->score_weight : 0;

            if ($answer) {
                $answer->update([
                    'is_correct' => $isCorrect,
                    'score' => $score,
                ]);
            }

            $isCorrect ? $correct++ : $wrong++;
            $multipleChoiceScore += $score;
        }

        return [
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'blank_count' => $blank,
            'multiple_choice_score' => $multipleChoiceScore,
            'essay_score' => $essayScore,
            'total_score' => $multipleChoiceScore + $essayScore,
            'has_essay' => $hasEssay,
            'has_pending_essay' => $hasPendingEssay,
            'max_score' => (int) $questions->sum('score_weight'),
        ];
    }
}
