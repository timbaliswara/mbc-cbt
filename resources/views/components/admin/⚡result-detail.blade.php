<?php

use App\Models\ExamAttempt;
use App\Models\StudentAnswer;
use App\Support\ExamScoring;
use Livewire\Component;

new class extends Component
{
    public ExamAttempt $attempt;

    public array $essayScores = [];

    public function mount(ExamAttempt $attempt): void
    {
        $this->attempt = $attempt;
        $this->loadAttempt();

        foreach ($this->attempt->answers as $answer) {
            if ($answer->question?->isEssay()) {
                $this->essayScores[$answer->id] = (int) $answer->score;
            }
        }
    }

    public function saveEssay(int $answerId): void
    {
        $answer = $this->attempt->answers->firstWhere('id', $answerId) ?? StudentAnswer::findOrFail($answerId);

        if ((int) $answer->exam_attempt_id !== (int) $this->attempt->id || ! $answer->question?->isEssay()) {
            return;
        }

        $score = max(0, min((int) $answer->question->score_weight, (int) ($this->essayScores[$answerId] ?? 0)));
        $answer->update([
            'score' => $score,
            'is_correct' => $score >= $answer->question->score_weight,
        ]);

        $this->recalculateResult();
        $this->loadAttempt();
        $this->dispatch('notify', message: 'Nilai esai disimpan.');
    }

    public function durationLabel(ExamAttempt $attempt): string
    {
        if (! $attempt->started_at) {
            return '-';
        }

        $finishedAt = $attempt->finished_at ?? now();
        $seconds = max(0, (int) $attempt->started_at->diffInSeconds($finishedAt));

        return floor($seconds / 60).' menit';
    }

    public function answerStatus(StudentAnswer $answer): string
    {
        if (! $answer->exists || ! ExamScoring::answerIsFilled($answer->question, $answer)) {
            return 'Kosong';
        }

        if ($answer->question?->isEssay()) {
            return 'Esai';
        }

        return $answer->is_correct ? 'Benar' : 'Salah';
    }

    public function answerText(StudentAnswer $answer): string
    {
        if (! $answer->exists) {
            return '-';
        }

        if ($answer->question?->usesSingleOptionAnswer()) {
            $prefix = $answer->question?->isTrueFalse() ? '' : (($answer->option?->label ? $answer->option->label.'. ' : ''));

            return trim($prefix.($answer->option?->option_text ?? '-'));
        }

        if ($answer->question?->usesStatementTruthAnswer()) {
            $selected = collect($answer->answer_payload ?? [])
                ->map(function ($value, $key) use ($answer) {
                    $statement = $answer->question->options->firstWhere('id', (int) $key);

                    return $statement ? $statement->option_text.': '.$value : null;
                })
                ->filter()
                ->values()
                ->all();

            return $selected !== [] ? implode(' | ', $selected) : '-';
        }

        if ($answer->question?->usesMultipleOptionAnswer()) {
            $selectedIds = ExamScoring::selectedOptionIds($answer);
            $labels = $answer->question->options
                ->whereIn('id', $selectedIds)
                ->map(fn ($option) => trim($option->label.'. '.$option->option_text))
                ->values()
                ->all();

            return $labels !== [] ? implode(', ', $labels) : '-';
        }

        return $answer->answer_text ?: '-';
    }

    public function keyText(StudentAnswer $answer): string
    {
        $question = $answer->question;

        if (! $question) {
            return '-';
        }

        if ($question->isEssay()) {
            return $question->answer_key ?: 'Dinilai manual';
        }

        if ($question->usesStatementTruthAnswer()) {
            $labels = $question->statementTruthLabels();
            $keys = $question->options
                ->map(fn ($option) => $option->option_text.': '.($option->is_correct ? $labels['positive'] : $labels['negative']))
                ->values()
                ->all();

            return implode(' | ', $keys);
        }

        $keys = $question->options
            ->where('is_correct', true)
            ->map(function ($option) use ($question) {
                $prefix = $question->isTrueFalse() ? '' : ($option->label.'. ');

                return trim($prefix.$option->option_text);
            })
            ->values()
            ->all();

        return $keys !== [] ? implode(', ', $keys) : '-';
    }

    private function loadAttempt(): void
    {
        $this->attempt->load([
            'student',
            'result',
            'exam.questions.options',
            'exam.questions.stimulus',
            'answers.question.options',
            'answers.option',
        ]);
    }

    private function recalculateResult(): void
    {
        $this->attempt->loadMissing(['result', 'exam.questions.options', 'answers.question.options', 'answers.option']);

        $summary = ExamScoring::evaluateAttempt($this->attempt);

        $this->attempt->result()->updateOrCreate(
            ['exam_attempt_id' => $this->attempt->id],
            [
                'correct_count' => $summary['correct_count'],
                'wrong_count' => $summary['wrong_count'],
                'blank_count' => $summary['blank_count'],
                'multiple_choice_score' => $summary['multiple_choice_score'],
                'essay_score' => $summary['essay_score'],
                'total_score' => $summary['total_score'],
                'is_passed' => $this->attempt->exam->passing_grade ? $summary['total_score'] >= $this->attempt->exam->passing_grade : null,
                'essay_status' => $summary['has_essay'] ? ($summary['has_pending_essay'] ? 'pending' : 'scored') : 'not_needed',
            ],
        );
    }
};
?>

<div class="space-y-6">
    @php
        $result = $attempt->result;
        $answersByQuestion = $attempt->answers->keyBy('question_id');
        $passLabel = is_null($result?->is_passed) ? 'Tanpa passing grade' : ($result->is_passed ? 'Lulus' : 'Belum lulus');
        $maxScore = \App\Support\ExamScoring::maxScore($attempt);
    @endphp

    <section class="hero-panel rounded-md p-6 shadow-2xl shadow-emerald-950/10">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="max-w-3xl">
                <a href="{{ route('admin.results') }}" class="inline-flex items-center rounded-md border border-white/20 bg-white/10 px-3 py-2 text-sm font-semibold text-white transition hover:bg-white/15">Kembali ke rekap</a>
                <p class="mt-5 text-sm font-medium uppercase tracking-[0.16em] text-emerald-50/75">Detail Hasil</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">{{ $attempt->student->name }}</h2>
                <p class="mt-3 text-sm leading-6 text-emerald-50/75">{{ $attempt->exam->title }} · {{ $attempt->student->school ?: 'Sekolah belum diisi' }} · {{ $attempt->student->grade ?: 'Kelas -' }}</p>
            </div>
            <div class="rounded-md border border-white/15 bg-white/10 p-4 text-sm text-emerald-50/80 backdrop-blur">
                <p class="font-semibold text-white">Nilai total {{ $result?->total_score ?? 0 }} / {{ $maxScore }}</p>
                <p class="mt-1">{{ $passLabel }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="surface rounded-md p-5">
            <p class="text-sm text-zinc-500">Pilihan ganda</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-950">{{ $result?->multiple_choice_score ?? 0 }}</p>
            <p class="mt-1 text-xs text-zinc-500">Benar {{ $result?->correct_count ?? 0 }} · Salah {{ $result?->wrong_count ?? 0 }} · Kosong {{ $result?->blank_count ?? 0 }}</p>
        </div>
        <div class="surface rounded-md p-5">
            <p class="text-sm text-zinc-500">Esai</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-950">{{ $result?->essay_score ?? 0 }}</p>
            <p class="mt-1 text-xs text-zinc-500">Status {{ $result?->essay_status ?? 'not_needed' }}</p>
        </div>
        <div class="surface rounded-md p-5">
            <p class="text-sm text-zinc-500">Durasi</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-950">{{ $this->durationLabel($attempt) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Mulai {{ $attempt->started_at?->format('d M Y H:i') ?? '-' }}</p>
        </div>
        <div class="surface rounded-md p-5">
            <p class="text-sm text-zinc-500">Paket soal</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-950">{{ $attempt->exam->questions->count() }}</p>
            <p class="mt-1 text-xs text-zinc-500">Passing grade {{ $attempt->exam->passing_grade ?? '-' }} · Maks {{ $maxScore }}</p>
        </div>
    </section>

    <section class="surface overflow-hidden rounded-md">
        <div class="border-b border-zinc-200 p-5">
            <h2 class="text-base font-semibold text-zinc-950">Detail jawaban per soal</h2>
            <p class="mt-1 text-sm text-zinc-500">Gunakan halaman ini untuk melihat jawaban, kunci, status benar/salah, dan koreksi nilai esai.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-5 py-3">Soal</th>
                        <th class="px-5 py-3">Jawaban siswa</th>
                        <th class="px-5 py-3">Kunci</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white align-top">
                    @foreach ($attempt->exam->questions as $question)
                        @php
                            $answer = $answersByQuestion->get($question->id) ?? new StudentAnswer(['question_id' => $question->id]);
                            $answer->setRelation('question', $question);
                            $status = $this->answerStatus($answer);
                        @endphp
                        <tr>
                            <td class="max-w-xl px-5 py-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Nomor {{ $question->order_number }} · {{ $question->typeLabel() }}</p>
                                @if ($question->stimulus)
                                    <div class="mt-3 rounded-md border border-emerald-100 bg-emerald-50/70 p-3 text-xs leading-5 text-emerald-950">
                                        <p class="font-semibold uppercase tracking-wide text-emerald-700">Stimulus dari TIM MBC</p>
                                        <p class="mt-1 font-semibold">{{ $question->stimulus->title }}</p>
                                        @if ($question->stimulus->content)
                                            <p class="mt-2 whitespace-pre-line text-zinc-700">{{ $question->stimulus->content }}</p>
                                        @endif
                                        @if ($question->stimulus->file_path)
                                            <img src="{{ Storage::url($question->stimulus->file_path) }}" class="mt-2 max-h-36 rounded-md border border-emerald-100 bg-white object-contain" alt="Gambar stimulus">
                                        @endif
                                        @if ($question->stimulus->caption)
                                            <p class="mt-2 text-emerald-800">{{ $question->stimulus->caption }}</p>
                                        @endif
                                    </div>
                                @endif
                                <p class="mt-3 leading-6 text-zinc-800">{{ $question->question_text }}</p>
                                @if ($question->image_path)
                                    <img src="{{ Storage::url($question->image_path) }}" class="mt-3 max-h-44 rounded-md border border-zinc-200 object-contain" alt="Gambar soal">
                                @endif
                            </td>
                            <td class="px-5 py-5 text-zinc-700">
                                <p class="max-w-md leading-6">{{ $this->answerText($answer) }}</p>
                                @if ($answer->option?->image_path)
                                    <img src="{{ Storage::url($answer->option->image_path) }}" class="mt-3 max-h-32 rounded-md border border-zinc-200 object-contain" alt="Gambar jawaban">
                                @endif
                            </td>
                            <td class="px-5 py-5 text-zinc-700">
                                <p class="max-w-md leading-6">{{ $this->keyText($answer) }}</p>
                                @php($keyOption = $question->usesSingleOptionAnswer() ? $question->options->firstWhere('is_correct', true) : null)
                                @if ($keyOption?->image_path)
                                    <img src="{{ Storage::url($keyOption->image_path) }}" class="mt-3 max-h-32 rounded-md border border-zinc-200 object-contain" alt="Gambar kunci">
                                @endif
                            </td>
                            <td class="px-5 py-5">
                                <span @class([
                                    'rounded-md px-2 py-1 text-xs font-medium',
                                    'bg-emerald-50 text-emerald-800' => $status === 'Benar',
                                    'bg-red-50 text-red-700' => $status === 'Salah',
                                    'bg-amber-50 text-amber-800' => $status === 'Esai',
                                    'bg-zinc-100 text-zinc-700' => $status === 'Kosong',
                                ])>{{ $status }}</span>
                            </td>
                            <td class="min-w-44 px-5 py-5">
                                @if ($question->isEssay() && $answer->exists)
                                    <div class="flex items-center gap-2">
                                        <input wire:model="essayScores.{{ $answer->id }}" type="number" min="0" max="{{ $question->score_weight }}" class="w-20 rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                                        <button wire:click="saveEssay({{ $answer->id }})" class="rounded-md bg-emerald-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800">Simpan</button>
                                    </div>
                                    <p class="mt-2 text-xs text-zinc-500">Maksimal {{ $question->score_weight }}</p>
                                @elseif ($question->isEssay())
                                    <p class="text-sm text-zinc-500">Belum dijawab</p>
                                @else
                                    <p class="font-semibold text-zinc-950">{{ $answer->score ?? 0 }}</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
