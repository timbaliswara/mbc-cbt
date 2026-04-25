<?php

use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Support\ExamScoring;
use Livewire\Component;

new class extends Component
{
    public ExamAttempt $attempt;
    public int $currentIndex = 0;
    public int $remainingSeconds = 0;
    public int $progressPercent = 0;
    public array $answers = [];
    public array $flags = [];
    public array $dirtyQuestionIds = [];
    public array $savedAnswerSignatures = [];
    public array $incompleteQuestionNumbers = [];
    public ?string $submitWarning = null;
    public ?string $focusWarning = null;
    public int $focusLimit = 2;

    public function mount(ExamAttempt $attempt): void
    {
        $this->attempt = $attempt->load('answers', 'exam.questions.options', 'exam.questions.stimulus');

        foreach ($this->attempt->answers as $answer) {
            $question = $this->attempt->exam->questions->firstWhere('id', $answer->question_id);

            if (! $question) {
                continue;
            }

            if ($question->usesStatementTruthAnswer()) {
                $this->answers[$answer->question_id] = $answer->answer_payload ?? [];
            } elseif ($question->usesMultipleOptionAnswer()) {
                $this->answers[$answer->question_id] = collect(ExamScoring::selectedOptionIds($answer))
                    ->mapWithKeys(fn ($optionId) => [(int) $optionId => true])
                    ->all();
            } else {
                $this->answers[$answer->question_id] = $answer->question_option_id ?: $answer->answer_text;
            }

            $this->flags[$answer->question_id] = $answer->is_flagged;
        }

        $this->refreshRemainingTime();
        $this->syncSavedAnswerSignatures();
        $this->recalculateProgress();
    }

    public function updatedAnswers($value, ?string $name = null): void
    {
        if (! is_string($name) || $name === '') {
            $this->recalculateProgress();
            $this->submitWarning = null;

            return;
        }

        $questionId = (int) explode('.', $name)[0];
        $question = $this->questions()->firstWhere('id', $questionId);

        if (! $question) {
            return;
        }

        $this->markQuestionDirty($questionId);
        $this->submitWarning = null;
        $this->recalculateProgress();
    }

    public function toggleFlag(int $questionId): void
    {
        $this->flags[$questionId] = ! ($this->flags[$questionId] ?? false);
        $this->markQuestionDirty($questionId);
    }

    public function autosaveDirtyAnswers(): void
    {
        $this->flushDirtyAnswers();
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

    public function registerFocusViolation(): void
    {
        if ($this->attempt->status === 'finished') {
            return;
        }

        $this->flushDirtyAnswers();
        $this->attempt->increment('focus_violation_count');
        $count = $this->attempt->refresh()->focus_violation_count;

        if ($count >= $this->focusLimit) {
            $this->focusWarning = 'Ruang ujian kembali ditinggalkan setelah peringatan pertama. Jawaban langsung dikumpulkan oleh TIM MBC.';
            $this->finish(true);

            return;
        }

        $this->focusWarning = 'Peringatan 1 dari 1: ruang ujian terdeteksi ditinggalkan. TIM MBC menyarankan tetap di halaman ujian. Jika kamu berpindah tab, jendela, atau aplikasi sekali lagi, ujian akan langsung dikumpulkan otomatis.';
    }

    public function saveAnswer(int $questionId): void
    {
        $question = $this->questions()->firstWhere('id', $questionId);

        if (! $question) {
            return;
        }

        $this->persistAnswerPayloads([$question->id => $this->payloadForQuestion($question)]);
    }

    public function finish(bool $force = false)
    {
        $this->refreshRemainingTime();
        $this->flushDirtyAnswers();
        $this->incompleteQuestionNumbers = $this->blankQuestionNumbers();

        if (! $force && $this->incompleteQuestionNumbers !== []) {
            $firstBlankNumber = $this->incompleteQuestionNumbers[0];
            $this->currentIndex = max(0, $firstBlankNumber - 1);
            $this->submitWarning = 'Masih ada '.count($this->incompleteQuestionNumbers).' soal kosong. Lengkapi dulu soal nomor '.implode(', ', array_slice($this->incompleteQuestionNumbers, 0, 8)).(count($this->incompleteQuestionNumbers) > 8 ? ', dan lainnya.' : '.');

            return null;
        }

        $this->submitWarning = null;
        $this->attempt->load('answers.question.options', 'exam.questions.options', 'token');
        $summary = ExamScoring::evaluateAttempt($this->attempt);

        $this->attempt->update(['finished_at' => now(), 'status' => 'finished']);
        $this->attempt->token->update(['status' => 'active', 'used_at' => now()]);

        ExamResult::updateOrCreate(
            ['exam_attempt_id' => $this->attempt->id],
            [
                'correct_count' => $summary['correct_count'],
                'wrong_count' => $summary['wrong_count'],
                'blank_count' => $summary['blank_count'],
                'multiple_choice_score' => $summary['multiple_choice_score'],
                'essay_score' => $summary['essay_score'],
                'total_score' => $summary['total_score'],
                'is_passed' => $this->attempt->exam->passing_grade ? $summary['total_score'] >= $this->attempt->exam->passing_grade : null,
                'essay_status' => $summary['has_essay'] ? 'pending' : 'not_needed',
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

    public function optionsFor(Question $question)
    {
        $options = $question->options->values();

        if (! $this->attempt->exam->shuffle_options || $question->isTrueFalse()) {
            return $options;
        }

        return $options->sortBy(fn ($option) => crc32($this->attempt->id.'-'.$question->id.'-'.$option->id))->values();
    }

    private function refreshRemainingTime(): void
    {
        $deadline = $this->attempt->started_at->copy()->addMinutes($this->attempt->exam->duration_minutes);
        $this->remainingSeconds = (int) max(0, floor(now()->diffInSeconds($deadline, false)));
    }

    private function recalculateProgress(): void
    {
        $questions = $this->questions();
        $total = $questions->count();

        if ($total === 0) {
            $this->progressPercent = 0;

            return;
        }

        $answered = $questions
            ->filter(fn (Question $question) => ExamScoring::answerIsFilled($question, $this->localAnswerModel($question)))
            ->count();

        $this->progressPercent = (int) round(($answered / $total) * 100);
    }

    private function saveCurrentAnswer(): void
    {
        $question = $this->questions()->get($this->currentIndex);

        if ($question) {
            $this->flushDirtyAnswers([$question->id]);
        }
    }

    private function blankQuestionNumbers(): array
    {
        return $this->questions()
            ->filter(fn (Question $question) => ! ExamScoring::answerIsFilled($question, $this->localAnswerModel($question)))
            ->map(fn ($question, $index) => $index + 1)
            ->values()
            ->all();
    }

    private function payloadForQuestion(Question $question): array
    {
        $questionId = $question->id;
        $value = $this->answers[$questionId] ?? null;
        $payload = [
            'question_option_id' => null,
            'answer_text' => null,
            'answer_payload' => null,
            'is_flagged' => $this->flags[$questionId] ?? false,
        ];

        if ($question->usesSingleOptionAnswer()) {
            $payload['question_option_id'] = filled($value) ? (int) $value : null;

            return $payload;
        }

        if ($question->usesStatementTruthAnswer()) {
            $labels = $question->statementTruthLabels();
            $selections = collect((array) $value)
                ->filter(fn ($item, $key) => filled($item) && is_numeric((string) $key))
                ->map(fn ($item) => in_array($item, [$labels['positive'], $labels['negative']], true) ? $item : null)
                ->filter()
                ->all();

            $payload['answer_payload'] = $selections !== [] ? $selections : null;

            return $payload;
        }

        if ($question->usesMultipleOptionAnswer()) {
            $selectedIds = collect((array) $value)
                ->filter(fn ($item) => filter_var($item, FILTER_VALIDATE_BOOLEAN) || $item === true || $item === 1 || $item === '1')
                ->keys()
                ->map(fn ($item) => (int) $item)
                ->values()
                ->all();

            $payload['answer_payload'] = $selectedIds !== [] ? $selectedIds : null;

            return $payload;
        }

        $payload['answer_text'] = filled($value) ? trim((string) $value) : null;

        return $payload;
    }

    private function flushDirtyAnswers(array $questionIds = []): void
    {
        $dirtyIds = $questionIds === []
            ? array_map('intval', array_keys(array_filter($this->dirtyQuestionIds)))
            : array_values(array_unique(array_map('intval', $questionIds)));

        if ($dirtyIds === []) {
            return;
        }

        $payloads = [];

        foreach ($dirtyIds as $questionId) {
            $question = $this->questions()->firstWhere('id', $questionId);

            if (! $question) {
                continue;
            }

            $payloads[$questionId] = $this->payloadForQuestion($question);
        }

        if ($payloads === []) {
            return;
        }

        $this->persistAnswerPayloads($payloads);
        $this->dispatch('answers-flushed');
    }

    private function persistAnswerPayloads(array $payloads): void
    {
        foreach ($payloads as $questionId => $payload) {
            StudentAnswer::query()->updateOrCreate(
                [
                    'exam_attempt_id' => $this->attempt->id,
                    'question_id' => (int) $questionId,
                ],
                [
                    'question_option_id' => $payload['question_option_id'],
                    'answer_text' => $payload['answer_text'],
                    'answer_payload' => $payload['answer_payload'],
                    'is_flagged' => $payload['is_flagged'],
                ],
            );

            $this->savedAnswerSignatures[(int) $questionId] = $this->signatureForPayload($payload);
            unset($this->dirtyQuestionIds[(int) $questionId]);
        }
    }

    private function syncSavedAnswerSignatures(): void
    {
        foreach ($this->questions() as $question) {
            $this->savedAnswerSignatures[$question->id] = $this->signatureForPayload($this->payloadForQuestion($question));
        }

        $this->dirtyQuestionIds = [];
    }

    private function markQuestionDirty(int $questionId): void
    {
        $question = $this->questions()->firstWhere('id', $questionId);

        if (! $question) {
            return;
        }

        $currentSignature = $this->signatureForPayload($this->payloadForQuestion($question));
        $savedSignature = $this->savedAnswerSignatures[$questionId] ?? null;

        if ($currentSignature === $savedSignature) {
            unset($this->dirtyQuestionIds[$questionId]);

            return;
        }

        $this->dirtyQuestionIds[$questionId] = true;
    }

    private function signatureForPayload(array $payload): string
    {
        return json_encode([
            'question_option_id' => $payload['question_option_id'],
            'answer_text' => $payload['answer_text'],
            'answer_payload' => $payload['answer_payload'],
            'is_flagged' => (bool) $payload['is_flagged'],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function localAnswerModel(Question $question): StudentAnswer
    {
        $payload = $this->payloadForQuestion($question);

        return new StudentAnswer($payload);
    }
};
?>

@php
    $questions = $this->questions();
    $question = $questions[$currentIndex] ?? null;
    $answered = collect($questions)->filter(function ($item) use ($attempt, $answers) {
        $answer = new \App\Models\StudentAnswer([
            'question_option_id' => $item->usesSingleOptionAnswer() ? ($answers[$item->id] ?? null) : null,
            'answer_text' => $item->isEssay() ? ($answers[$item->id] ?? null) : null,
            'answer_payload' => ($item->usesMultipleOptionAnswer() || $item->usesStatementTruthAnswer()) ? ($answers[$item->id] ?? null) : null,
        ]);

        return \App\Support\ExamScoring::answerIsFilled($item, $answer);
    })->count();
@endphp

<div
    x-data="{
        remaining: {{ $remainingSeconds }},
        submitted: false,
        pendingChanges: false,
        focusViolations: {{ (int) $attempt->focus_violation_count }},
        focusLimit: {{ $focusLimit }},
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
        startAutosave() {
            const interval = setInterval(() => {
                if (this.submitted || ! this.pendingChanges) {
                    return;
                }

                this.$wire.autosaveDirtyAnswers().then(() => {
                    this.pendingChanges = false;
                });
            }, 8000);

            window.addEventListener('beforeunload', () => {
                if (! this.submitted && this.pendingChanges) {
                    this.$wire.autosaveDirtyAnswers();
                }
                clearInterval(interval);
            });
        },
        watchFocus() {
            document.addEventListener('visibilitychange', () => {
                if (! document.hidden || this.submitted) {
                    return;
                }

                this.focusViolations += 1;
                this.pendingChanges = false;
                this.$wire.registerFocusViolation();

                if (this.focusViolations >= this.focusLimit) {
                    this.submitted = true;
                }
            });
        },
    }"
    x-on:change.capture="pendingChanges = true"
    x-on:answers-flushed.window="pendingChanges = false"
    x-init="startTimer(); startAutosave(); watchFocus()"
    class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"
>
    @if ($question)
        <div class="surface mb-5 rounded-md p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-emerald-700">{{ $attempt->exam->title }}</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950">{{ $attempt->student->name }}</h1>
                    <p class="mt-2 text-sm text-zinc-500">{{ $progressPercent }}% progres pengerjaan · {{ $answered }} dari {{ $questions->count() }} soal sudah terisi</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800" x-text="timerLabel()">{{ gmdate('H:i:s', $remainingSeconds) }}</div>
                    <div class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white">{{ $progressPercent }}%</div>
                </div>
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-100">
                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all" style="width: {{ $progressPercent }}%"></div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
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
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-800">Soal {{ $currentIndex + 1 }}</span>
                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700">{{ $question->typeLabel() }}</span>
                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700">Bobot {{ $question->score_weight }}</span>
                    </div>
                    <button x-on:click="pendingChanges = true" wire:click="toggleFlag({{ $question->id }})" class="rounded-md border border-zinc-200 px-3 py-2 text-sm font-medium {{ ($flags[$question->id] ?? false) ? 'bg-amber-50 text-amber-800 border-amber-300' : 'text-zinc-700' }}">Ragu-ragu</button>
                </div>

                <p class="mt-4 whitespace-pre-line text-base leading-7 text-zinc-950">{{ $question->question_text }}</p>
                @if ($question->image_path)
                    <img src="{{ Storage::url($question->image_path) }}" class="mt-4 max-h-96 rounded-md border border-zinc-200 object-contain" alt="Gambar soal">
                @endif

                @if ($question->usesSingleOptionAnswer())
                    <div class="mt-6 grid gap-3">
                        @foreach ($this->optionsFor($question) as $option)
                            <label wire:key="question-{{ $question->id }}-option-{{ $option->id }}" class="flex cursor-pointer gap-3 rounded-md border border-zinc-200 bg-white/80 p-4 transition hover:border-emerald-200 hover:bg-emerald-50/40">
                                <input
                                    wire:model="answers.{{ $question->id }}"
                                    name="answer_{{ $question->id }}"
                                    value="{{ $option->id }}"
                                    type="radio"
                                    class="mt-1 text-emerald-700"
                                >
                                <span class="text-sm text-zinc-700">
                                    <span class="font-semibold text-zinc-950">{{ $question->isTrueFalse() ? $option->option_text : $option->label.'.' }}</span>
                                    @if ($option->option_text && ! $question->isTrueFalse())
                                        {{ ' '.$option->option_text }}
                                    @endif
                                    @if ($option->image_path)
                                        <img src="{{ Storage::url($option->image_path) }}" class="mt-2 max-h-40 rounded-md border border-zinc-200 object-contain" alt="Gambar opsi">
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @elseif ($question->usesStatementTruthAnswer())
                    @php
                        $labels = $question->statementTruthLabels();
                    @endphp
                    <div class="mt-6 overflow-hidden rounded-md border border-zinc-200">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-zinc-50 text-zinc-600">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Pernyataan</th>
                                    <th class="px-4 py-3 text-center font-semibold">{{ $labels['positive'] }}</th>
                                    <th class="px-4 py-3 text-center font-semibold">{{ $labels['negative'] }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 bg-white">
                                @foreach ($question->options as $statement)
                                    <tr wire:key="statement-{{ $question->id }}-{{ $statement->id }}">
                                        <td class="px-4 py-4 leading-6 text-zinc-800">{{ $statement->option_text }}</td>
                                        @foreach ([$labels['positive'], $labels['negative']] as $choice)
                                            <td class="px-4 py-4 text-center">
                                                <input
                                                    wire:model="answers.{{ $question->id }}.{{ $statement->id }}"
                                                    type="radio"
                                                    value="{{ $choice }}"
                                                    name="statement_{{ $question->id }}_{{ $statement->id }}"
                                                    class="text-emerald-700"
                                                >
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif ($question->usesMultipleOptionAnswer())
                    <div class="mt-6 rounded-md border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900">
                        Pilih semua opsi yang menurutmu benar. Nilai penuh diberikan jika kombinasi jawabanmu tepat.
                    </div>
                    <div class="mt-4 grid gap-3">
                        @foreach ($this->optionsFor($question) as $option)
                            <label wire:key="question-{{ $question->id }}-option-{{ $option->id }}" class="flex cursor-pointer gap-3 rounded-md border border-zinc-200 bg-white/80 p-4 transition hover:border-emerald-200 hover:bg-emerald-50/40">
                                <input
                                    wire:model="answers.{{ $question->id }}.{{ $option->id }}"
                                    type="checkbox"
                                    value="1"
                                    name="answer_{{ $question->id }}_{{ $option->id }}"
                                    class="mt-1 rounded border-zinc-300 text-emerald-700"
                                >
                                <span class="text-sm text-zinc-700">
                                    <span class="font-semibold text-zinc-950">{{ $option->label }}.</span> {{ $option->option_text }}
                                    @if ($option->image_path)
                                        <img src="{{ Storage::url($option->image_path) }}" class="mt-2 max-h-40 rounded-md border border-zinc-200 object-contain" alt="Gambar opsi">
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <textarea
                        wire:model.blur="answers.{{ $question->id }}"
                        rows="8"
                        class="mt-6 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm"
                        placeholder="Tulis jawaban esai di sini"
                    ></textarea>
                @endif

                <div class="mt-6 flex flex-wrap justify-between gap-3">
                    <button x-on:click="pendingChanges = false" wire:click="previous" class="rounded-md border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Sebelumnya</button>
                    <div class="flex gap-3">
                        @if ($currentIndex < $questions->count() - 1)
                            <button x-on:click="pendingChanges = false" wire:click="next" class="rounded-md border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Berikutnya</button>
                        @else
                            <span class="rounded-md bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800">Ini soal terakhir</span>
                        @endif
                    </div>
                </div>
            </section>

            <aside class="space-y-5">
                <div class="surface rounded-md p-5">
                    <h2 class="text-sm font-semibold text-zinc-950">Navigasi soal</h2>
                    <div class="mt-4 grid grid-cols-5 gap-2">
                        @foreach ($questions as $index => $item)
                            @php
                                $isCurrent = $index === $currentIndex;
                                $isFlagged = $flags[$item->id] ?? false;
                                $value = $answers[$item->id] ?? null;
                                $isAnswered = is_array($value) ? count(array_filter($value)) > 0 : filled($value);
                                $classes = $isCurrent
                                    ? 'bg-emerald-700 text-white'
                                    : ($isFlagged
                                        ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-300'
                                        : ($isAnswered ? 'bg-emerald-50 text-emerald-800' : 'bg-zinc-100 text-zinc-600'));
                            @endphp
                            <button x-on:click="pendingChanges = false" wire:click="goTo({{ $index }})" class="aspect-square rounded-md text-sm font-semibold {{ $classes }}">{{ $index + 1 }}</button>
                        @endforeach
                    </div>

                    <div class="mt-5 grid gap-2 text-xs text-zinc-600">
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-emerald-700"></span> Soal yang sedang dibuka</div>
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-emerald-100"></span> Soal sudah dijawab</div>
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-amber-100"></span> Soal ditandai ragu-ragu</div>
                    </div>
                </div>

                <div class="surface rounded-md p-5">
                    <p class="text-sm font-semibold text-zinc-950">Pantauan sesi</p>
                    <div class="mt-4 space-y-3 text-sm text-zinc-600">
                        <div class="flex items-center justify-between gap-3">
                            <span>Progres</span>
                            <span class="font-semibold text-zinc-950">{{ $progressPercent }}%</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Jawaban terisi</span>
                            <span class="font-semibold text-zinc-950">{{ $answered }}/{{ $questions->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Pindah tab/jendela</span>
                            <span class="font-semibold text-zinc-950">{{ $attempt->focus_violation_count }}/{{ $focusLimit }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-md border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900">
                    <p class="font-semibold text-sky-950">Sebelum menutup atau refresh halaman</p>
                    <p class="mt-1">Jawaban akan disimpan saat kamu pindah soal, klik nomor soal, menunggu autosimpan beberapa detik, atau mengumpulkan ujian. Supaya aman, tunggu sejenak setelah mengubah jawaban terakhir sebelum refresh atau keluar.</p>
                </div>

                @if ($focusWarning)
                    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                        <p class="font-semibold text-amber-950">Perhatian</p>
                        <p class="mt-1">{{ $focusWarning }}</p>
                    </div>
                @endif

                @if ($submitWarning)
                    <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-800">
                        <p class="font-semibold text-red-950">Masih ada jawaban kosong</p>
                        <p class="mt-1">{{ $submitWarning }}</p>
                    </div>
                @endif

                <div class="rounded-md border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm font-semibold text-emerald-950">Selesai mengerjakan?</p>
                    <p class="mt-2 text-sm leading-6 text-emerald-900">Tombol ini hanya dipakai kalau semua soal sudah terisi. Saat diklik, sistem akan menyimpan perubahan terakhir dulu lalu memeriksa apakah masih ada jawaban kosong.</p>
                    <button x-on:click="pendingChanges = false" wire:click="finish" wire:confirm="Kumpulkan ujian sekarang? Pastikan semua jawaban sudah terisi." class="premium-button mt-4 w-full rounded-md px-4 py-3 text-sm font-semibold text-white hover:brightness-105">Kumpulkan ujian</button>
                </div>
            </aside>
        </div>
    @else
        <div class="rounded-md border border-zinc-200 bg-white p-10 text-center text-zinc-500">Belum ada soal pada ujian ini.</div>
    @endif
</div>
