<?php

use Livewire\Component;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\ExamResult;

new class extends Component
{
    public ExamAttempt $attempt;
    public int $currentIndex = 0;
    public int $remainingSeconds = 0;
    public array $answers = [];
    public array $flags = [];
    public array $incompleteQuestionNumbers = [];
    public ?string $submitWarning = null;

    public function mount(ExamAttempt $attempt): void
    {
        $this->attempt = $attempt->load('exam.questions.options', 'exam.questions.stimulus');

        foreach ($this->attempt->answers as $answer) {
            $this->answers[$answer->question_id] = $answer->question_option_id ?: $answer->answer_text;
            $this->flags[$answer->question_id] = $answer->is_flagged;
        }

        $this->refreshRemainingTime();
    }

    public function toggleFlag(int $questionId): void
    {
        $this->flags[$questionId] = ! ($this->flags[$questionId] ?? false);
        $this->saveAnswer($questionId);
    }

    public function goTo(int $index): void
    {
        $this->saveCurrentAnswer();
        $this->currentIndex = max(0, min($index, $this->questions()->count() - 1));
    }

    public function next(): void
    {
        $this->saveCurrentAnswer();
        $this->currentIndex = min($this->currentIndex + 1, $this->questions()->count() - 1);
    }

    public function previous(): void
    {
        $this->saveCurrentAnswer();
        $this->currentIndex = max($this->currentIndex - 1, 0);
    }

    public function saveAnswer(int $questionId): void
    {
        $question = Question::with('options')->findOrFail($questionId);
        $value = $this->answers[$questionId] ?? null;

        StudentAnswer::updateOrCreate(
            ['exam_attempt_id' => $this->attempt->id, 'question_id' => $questionId],
            [
                'question_option_id' => $question->isMultipleChoice() ? ($value ?: null) : null,
                'answer_text' => $question->isMultipleChoice() ? null : $value,
                'is_flagged' => $this->flags[$questionId] ?? false,
            ],
        );
    }

    public function finish(bool $force = false)
    {
        $this->refreshRemainingTime();

        foreach ($this->questions() as $question) {
            $this->saveAnswer($question->id);
        }

        $answers = $this->attempt->answers()->with(['question.options'])->get();
        $this->incompleteQuestionNumbers = $this->blankQuestionNumbers($answers);

        if (! $force && $this->incompleteQuestionNumbers !== []) {
            $firstBlankNumber = $this->incompleteQuestionNumbers[0];
            $this->currentIndex = max(0, $firstBlankNumber - 1);
            $this->submitWarning = 'Masih ada '.count($this->incompleteQuestionNumbers).' soal kosong. Lengkapi dulu soal nomor '.implode(', ', array_slice($this->incompleteQuestionNumbers, 0, 8)).(count($this->incompleteQuestionNumbers) > 8 ? ', dan lainnya.' : '.');

            return null;
        }

        $this->submitWarning = null;
        $correct = 0;
        $wrong = 0;
        $blank = 0;
        $score = 0;
        $hasEssay = false;

        foreach ($this->questions() as $question) {
            $answer = $answers->firstWhere('question_id', $question->id);

            if (! $answer || (! $answer->question_option_id && ! $answer->answer_text)) {
                $blank++;
                continue;
            }

            if ($question->isMultipleChoice()) {
                $option = $question->options->firstWhere('id', (int) $answer->question_option_id);
                $isCorrect = (bool) $option?->is_correct;
                $answer->update(['is_correct' => $isCorrect, 'score' => $isCorrect ? $question->score_weight : 0]);
                $isCorrect ? $correct++ : $wrong++;
                $score += $isCorrect ? $question->score_weight : 0;
            } else {
                $hasEssay = true;
            }
        }

        $this->attempt->update(['finished_at' => now(), 'status' => 'finished']);
        $this->attempt->token->update(['status' => 'used']);

        ExamResult::updateOrCreate(
            ['exam_attempt_id' => $this->attempt->id],
            [
                'correct_count' => $correct,
                'wrong_count' => $wrong,
                'blank_count' => $blank,
                'multiple_choice_score' => $score,
                'essay_score' => 0,
                'total_score' => $score,
                'is_passed' => $this->attempt->exam->passing_grade ? $score >= $this->attempt->exam->passing_grade : null,
                'essay_status' => $hasEssay ? 'pending' : 'not_needed',
            ],
        );

        return redirect()->route('student.result', $this->attempt);
    }

    public function questions()
    {
        $questions = $this->attempt->exam->questions->where('is_active', true)->values();

        return $this->attempt->exam->shuffle_questions
            ? $questions->sortBy(fn ($question) => crc32($this->attempt->id.'-'.$question->id))->values()
            : $questions;
    }

    private function refreshRemainingTime(): void
    {
        $deadline = $this->attempt->started_at->copy()->addMinutes($this->attempt->exam->duration_minutes);
        $this->remainingSeconds = (int) max(0, floor(now()->diffInSeconds($deadline, false)));
    }

    private function saveCurrentAnswer(): void
    {
        $question = $this->questions()->get($this->currentIndex);

        if ($question) {
            $this->saveAnswer($question->id);
        }
    }

    private function blankQuestionNumbers($answers): array
    {
        return $this->questions()
            ->filter(function ($question) use ($answers) {
                $answer = $answers->firstWhere('question_id', $question->id);

                return ! $answer || (! $answer->question_option_id && ! filled($answer->answer_text));
            })
            ->map(fn ($question, $index) => $index + 1)
            ->values()
            ->all();
    }
};
?>

@php
    $questions = $this->questions();
    $question = $questions[$currentIndex] ?? null;
    $answered = collect($answers)->filter(fn ($value) => filled($value))->count();
@endphp

<div
    x-data="{
        remaining: {{ $remainingSeconds }},
        submitted: false,
        timerLabel() {
            const hours = Math.floor(this.remaining / 3600).toString().padStart(2, '0');
            const minutes = Math.floor((this.remaining % 3600) / 60).toString().padStart(2, '0');
            const seconds = Math.floor(this.remaining % 60).toString().padStart(2, '0');
            return `${hours}:${minutes}:${seconds}`;
        },
        startTimer() {
            const interval = setInterval(() => {
                if (this.remaining > 0) {
                    this.remaining -= 1;
                    return;
                }

                clearInterval(interval);
                if (! this.submitted) {
                    this.submitted = true;
                    this.$wire.finish(true);
                }
            }, 1000);
        },
    }"
    x-init="startTimer()"
    class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"
>
    @if ($question)
        <div class="surface mb-5 rounded-md p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-emerald-700">{{ $attempt->exam->title }}</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950">{{ $attempt->student->name }}</h1>
                </div>
                <div class="flex flex-wrap gap-2">
                    <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800" x-text="timerLabel()">{{ gmdate('H:i:s', $remainingSeconds) }}</div>
                    <div class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white">{{ $answered }} / {{ $questions->count() }} terjawab</div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
            <section wire:key="question-panel-{{ $question->id }}" class="surface rounded-md p-6">
                @if ($question->stimulus)
                    <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Bacaan/gambar bersama dari TIM MBC</p>
                        <h2 class="mt-2 text-base font-semibold text-emerald-950">{{ $question->stimulus->title }}</h2>
                        @if ($question->stimulus->content)
                            <p class="mt-3 whitespace-pre-line rounded-md border border-emerald-100 bg-white/80 p-4 text-sm leading-7 text-zinc-800">{{ $question->stimulus->content }}</p>
                        @endif
                        @if ($question->stimulus->file_path)
                            <img src="{{ Storage::url($question->stimulus->file_path) }}" class="mt-3 max-h-96 rounded-md border border-emerald-200 bg-white object-contain" alt="Gambar stimulus">
                        @endif
                        @if ($question->stimulus->caption)
                            <p class="mt-2 text-xs leading-5 text-emerald-800">{{ $question->stimulus->caption }}</p>
                        @endif
                    </div>
                @endif

                <div class="flex items-center justify-between gap-3">
                    <span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-800">Soal {{ $currentIndex + 1 }}</span>
                    <button wire:click="toggleFlag({{ $question->id }})" class="rounded-md border border-zinc-200 px-3 py-2 text-sm font-medium {{ ($flags[$question->id] ?? false) ? 'bg-amber-50 text-amber-800' : 'text-zinc-700' }}">Ragu-ragu</button>
                </div>
                <p class="mt-4 whitespace-pre-line text-base leading-7 text-zinc-950">{{ $question->question_text }}</p>
                @if ($question->image_path)<img src="{{ Storage::url($question->image_path) }}" class="mt-4 max-h-96 rounded-md border border-zinc-200 object-contain">@endif

                @if ($question->isMultipleChoice())
                    <div class="mt-6 grid gap-3">
                        @foreach ($question->options as $option)
                            <label wire:key="question-{{ $question->id }}-option-{{ $option->id }}" class="flex cursor-pointer gap-3 rounded-md border border-zinc-200 bg-white/80 p-4 transition hover:border-emerald-200 hover:bg-emerald-50/40">
                                <input
                                    wire:key="answer-input-{{ $question->id }}-{{ $option->id }}"
                                    x-on:change="$wire.set('answers.{{ $question->id }}', $event.target.value, false)"
                                    name="answer_{{ $question->id }}"
                                    value="{{ $option->id }}"
                                    type="radio"
                                    class="mt-1 text-emerald-700"
                                    @checked((string) ($answers[$question->id] ?? '') === (string) $option->id)
                                >
                                <span class="text-sm text-zinc-700"><span class="font-semibold text-zinc-950">{{ $option->label }}.</span> {{ $option->option_text }}
                                    @if ($option->image_path)<img src="{{ Storage::url($option->image_path) }}" class="mt-2 max-h-40 rounded-md border border-zinc-200 object-contain">@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <textarea
                        wire:model="answers.{{ $question->id }}"
                        x-on:input.debounce.250ms="$wire.set('answers.{{ $question->id }}', $event.target.value, false)"
                        rows="8"
                        class="mt-6 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm"
                        placeholder="Tulis jawaban esai di sini"
                    ></textarea>
                @endif

                <div class="mt-6 flex flex-wrap justify-between gap-3">
                    <button wire:click="previous" class="rounded-md border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Sebelumnya</button>
                    <div class="flex gap-3">
                        @if ($currentIndex < $questions->count() - 1)
                        <button wire:click="next" class="rounded-md border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Berikutnya</button>
                        @else
                            <span class="rounded-md bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800">Ini soal terakhir</span>
                        @endif
                    </div>
                </div>
            </section>

            <aside class="surface rounded-md p-5">
                <h2 class="text-sm font-semibold text-zinc-950">Navigasi soal</h2>
                <div class="mt-4 grid grid-cols-5 gap-2">
                    @foreach ($questions as $index => $item)
                        <button wire:click="goTo({{ $index }})" class="aspect-square rounded-md text-sm font-semibold {{ $index === $currentIndex ? 'bg-emerald-700 text-white' : (filled($answers[$item->id] ?? null) ? 'bg-emerald-50 text-emerald-800' : 'bg-zinc-100 text-zinc-600') }}">{{ $index + 1 }}</button>
                    @endforeach
                </div>
                <div class="mt-5 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Jawaban disimpan saat kamu pindah soal atau mengumpulkan ujian, supaya halaman tetap ringan.</div>

                @if ($submitWarning)
                    <div class="mt-5 rounded-md border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-800">
                        <p class="font-semibold text-red-950">Masih ada jawaban kosong</p>
                        <p class="mt-1">{{ $submitWarning }}</p>
                    </div>
                @endif

                <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm font-semibold text-emerald-950">Sudah siap dikumpulkan?</p>
                    <p class="mt-2 text-sm leading-6 text-emerald-900">Kumpulkan hanya setelah semua jawaban terisi. TIM MBC akan menerima hasilmu setelah tombol ini ditekan.</p>
                    <button wire:click="finish" wire:confirm="Kumpulkan ujian sekarang? Pastikan semua jawaban sudah terisi." class="premium-button mt-4 w-full rounded-md px-4 py-3 text-sm font-semibold text-white hover:brightness-105">Kumpulkan ujian</button>
                </div>
            </aside>
        </div>
    @else
        <div class="rounded-md border border-zinc-200 bg-white p-10 text-center text-zinc-500">Belum ada soal pada ujian ini.</div>
    @endif
</div>
