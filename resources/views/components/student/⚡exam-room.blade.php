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
    public array $incompleteQuestionNumbers = [];
    public ?string $submitWarning = null;
    public ?string $focusWarning = null;
    public int $focusLimit = 3;

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
                $this->answers[$answer->question_id] = ExamScoring::selectedOptionIds($answer);
            } else {
                $this->answers[$answer->question_id] = $answer->question_option_id ?: $answer->answer_text;
            }

            $this->flags[$answer->question_id] = $answer->is_flagged;
        }

        $this->refreshRemainingTime();
        $this->refreshProgress();
    }

    public function updatedAnswers($value, ?string $name = null): void
    {
        if (! is_string($name) || $name === '') {
            $this->refreshProgress();
            $this->submitWarning = null;

            return;
        }

        $questionId = (int) explode('.', $name)[0];
        $question = $this->questions()->firstWhere('id', $questionId);

        if (! $question) {
            return;
        }

        $this->saveAnswer($questionId);
        $this->submitWarning = null;
        $this->refreshProgress();
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

    public function registerFocusViolation(): void
    {
        $this->attempt->increment('focus_violation_count');
        $count = $this->attempt->refresh()->focus_violation_count;

        if ($count >= $this->focusLimit) {
            $this->focusWarning = 'Ruang ujian terdeteksi beberapa kali ditinggalkan. Jawaban akan langsung dikumpulkan oleh TIM MBC.';
            $this->finish(true);

            return;
        }

        $this->focusWarning = 'Ruang ujian terdeteksi berpindah tab/jendela '.$count.' dari '.$this->focusLimit.' kali. Kalau terulang terus, ujian akan langsung dikumpulkan.';
    }

    public function saveAnswer(int $questionId): void
    {
        $question = $this->questions()->firstWhere('id', $questionId);

        if (! $question) {
            return;
        }

        $value = $this->answers[$questionId] ?? null;
        $payload = [
            'question_option_id' => null,
            'answer_text' => null,
            'answer_payload' => null,
            'is_flagged' => $this->flags[$questionId] ?? false,
        ];

        if ($question->usesSingleOptionAnswer()) {
            $payload['question_option_id'] = filled($value) ? (int) $value : null;
        } elseif ($question->usesStatementTruthAnswer()) {
            $selections = collect((array) $value)
                ->filter(fn ($item, $key) => filled($item) && is_numeric((string) $key))
                ->map(fn ($item) => in_array($item, ['Benar', 'Salah'], true) ? $item : null)
                ->filter()
                ->all();

            $payload['answer_payload'] = $selections !== [] ? $selections : null;
        } elseif ($question->usesMultipleOptionAnswer()) {
            $selectedIds = collect((array) $value)
                ->filter(fn ($item) => filled($item))
                ->map(fn ($item) => (int) $item)
                ->values()
                ->all();

            $payload['answer_payload'] = $selectedIds !== [] ? $selectedIds : null;
        } else {
            $payload['answer_text'] = filled($value) ? trim((string) $value) : null;
        }

        StudentAnswer::updateOrCreate(
            ['exam_attempt_id' => $this->attempt->id, 'question_id' => $questionId],
            $payload,
        );

        $this->attempt->load('answers');
    }

    public function finish(bool $force = false)
    {
        $this->refreshRemainingTime();

        foreach ($this->questions() as $question) {
            $this->saveAnswer($question->id);
        }

        $this->attempt->load('answers.question.options', 'exam.questions.options');
        $this->incompleteQuestionNumbers = $this->blankQuestionNumbers();

        if (! $force && $this->incompleteQuestionNumbers !== []) {
            $firstBlankNumber = $this->incompleteQuestionNumbers[0];
            $this->currentIndex = max(0, $firstBlankNumber - 1);
            $this->submitWarning = 'Masih ada '.count($this->incompleteQuestionNumbers).' soal kosong. Lengkapi dulu soal nomor '.implode(', ', array_slice($this->incompleteQuestionNumbers, 0, 8)).(count($this->incompleteQuestionNumbers) > 8 ? ', dan lainnya.' : '.');

            return null;
        }

        $this->submitWarning = null;
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

    private function refreshProgress(): void
    {
        $this->attempt->load('answers', 'exam.questions');
        $this->progressPercent = ExamScoring::progressPercentage($this->attempt);
    }

    private function saveCurrentAnswer(): void
    {
        $question = $this->questions()->get($this->currentIndex);

        if ($question) {
            $this->saveAnswer($question->id);
        }
    }

    private function blankQuestionNumbers(): array
    {
        $answers = $this->attempt->answers->keyBy('question_id');

        return $this->questions()
            ->filter(fn (Question $question) => ! ExamScoring::answerIsFilled($question, $answers->get($question->id)))
            ->map(fn ($question, $index) => $index + 1)
            ->values()
            ->all();
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
        watchFocus() {
            document.addEventListener('visibilitychange', () => {
                if (! document.hidden || this.submitted) {
                    return;
                }

                this.focusViolations += 1;
                this.$wire.registerFocusViolation();

                if (this.focusViolations >= this.focusLimit) {
                    this.submitted = true;
                }
            });
        },
    }"
    x-init="startTimer(); watchFocus()"
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
                    <button wire:click="toggleFlag({{ $question->id }})" class="rounded-md border border-zinc-200 px-3 py-2 text-sm font-medium {{ ($flags[$question->id] ?? false) ? 'bg-amber-50 text-amber-800 border-amber-300' : 'text-zinc-700' }}">Ragu-ragu</button>
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
                                    wire:model.live="answers.{{ $question->id }}"
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
                    <div class="mt-6 overflow-hidden rounded-md border border-zinc-200">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-zinc-50 text-zinc-600">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Pernyataan</th>
                                    <th class="px-4 py-3 text-center font-semibold">Benar</th>
                                    <th class="px-4 py-3 text-center font-semibold">Salah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 bg-white">
                                @foreach ($question->options as $statement)
                                    <tr wire:key="statement-{{ $question->id }}-{{ $statement->id }}">
                                        <td class="px-4 py-4 leading-6 text-zinc-800">{{ $statement->option_text }}</td>
                                        @foreach (['Benar', 'Salah'] as $choice)
                                            <td class="px-4 py-4 text-center">
                                                <input
                                                    wire:model.live="answers.{{ $question->id }}.{{ $statement->id }}"
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
                                    wire:model.live="answers.{{ $question->id }}"
                                    name="answer_{{ $question->id }}[]"
                                    value="{{ $option->id }}"
                                    type="checkbox"
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
                        wire:model.live.debounce.700ms="answers.{{ $question->id }}"
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
                            <button wire:click="goTo({{ $index }})" class="aspect-square rounded-md text-sm font-semibold {{ $classes }}">{{ $index + 1 }}</button>
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
                    <p class="mt-2 text-sm leading-6 text-emerald-900">Tombol ini hanya dipakai kalau semua soal sudah terisi. Kalau masih ada yang kosong, sistem akan mengarahkanmu ke soal pertama yang belum dijawab.</p>
                    <button wire:click="finish" wire:confirm="Kumpulkan ujian sekarang? Pastikan semua jawaban sudah terisi." class="premium-button mt-4 w-full rounded-md px-4 py-3 text-sm font-semibold text-white hover:brightness-105">Kumpulkan ujian</button>
                </div>
            </aside>
        </div>
    @else
        <div class="rounded-md border border-zinc-200 bg-white p-10 text-center text-zinc-500">Belum ada soal pada ujian ini.</div>
    @endif
</div>
