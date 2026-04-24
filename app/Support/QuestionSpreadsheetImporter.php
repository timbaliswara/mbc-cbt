<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Stimulus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuestionSpreadsheetImporter
{
    private const SUPPORTED_TYPES = [
        'multiple_choice',
        'multiple_choice_complex',
        'true_false',
        'true_false_group',
        'essay',
    ];

    public function import(Exam $exam, string $filePath, bool $replaceExisting = false): array
    {
        $rows = $this->readRows($filePath);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'sheet' => 'File Excel kosong. Gunakan template TIM MBC agar kolomnya sesuai.',
            ]);
        }

        if ($replaceExisting && $exam->attempts()->exists()) {
            throw ValidationException::withMessages([
                'replaceExisting' => 'Paket ini sudah punya data pengerjaan siswa. Demi keamanan, buat paket baru atau arsipkan hasil lama dulu sebelum mengganti bank soal.',
            ]);
        }

        [$headings, $dataRows] = $this->extractHeadings($rows);
        $this->validateHeadings($headings);

        return DB::transaction(function () use ($exam, $replaceExisting, $headings, $dataRows) {
            if ($replaceExisting) {
                $exam->questions()->with('options')->get()->each(function (Question $question): void {
                    $question->options()->delete();
                    $question->delete();
                });
                $exam->stimuli()->delete();
            }

            $summary = [
                'imported_questions' => 0,
                'created_stimuli' => 0,
                'types' => [
                    'multiple_choice' => 0,
                    'multiple_choice_complex' => 0,
                    'true_false' => 0,
                    'true_false_group' => 0,
                    'essay' => 0,
                ],
            ];

            $stimulusCache = [];
            $currentOrder = (int) ($exam->questions()->max('order_number') ?? 0);

            foreach ($dataRows as $offset => $row) {
                $rowNumber = $offset + 2;
                $payload = $this->mapRow($headings, $row);

                if ($this->rowIsEmpty($payload)) {
                    continue;
                }

                $type = $this->normalizeType($payload['type'] ?? '');

                if (! in_array($type, self::SUPPORTED_TYPES, true)) {
                    $this->throwRowError($rowNumber, 'Jenis soal tidak dikenali. Pakai multiple_choice, multiple_choice_complex, true_false, true_false_group, atau essay.');
                }

                $questionText = trim((string) ($payload['question_text'] ?? ''));

                if ($questionText === '') {
                    $this->throwRowError($rowNumber, 'Kolom question_text wajib diisi.');
                }

                $scoreWeight = max(1, (int) ($payload['score_weight'] ?? 1));
                $currentOrder = $this->resolveOrderNumber($payload['order_number'] ?? null, $currentOrder, $rowNumber);
                $stimulus = $this->resolveStimulus($exam, $payload, $stimulusCache, $summary, $rowNumber);
                $questionData = $this->buildQuestionData($exam, $stimulus?->id, $currentOrder, $type, $questionText, $scoreWeight, $payload, $rowNumber);

                $question = Question::create($questionData);
                $this->storeQuestionOptions($question, $payload, $rowNumber);

                $summary['imported_questions']++;
                $summary['types'][$type]++;
            }

            if ($summary['imported_questions'] === 0) {
                throw ValidationException::withMessages([
                    'sheet' => 'Tidak ada baris soal yang berhasil dibaca. Cek lagi file dan pastikan isi berada di sheet pertama.',
                ]);
            }

            return $summary;
        });
    }

    private function readRows(string $filePath): array
    {
        $sheet = IOFactory::load($filePath)->getSheet(0);

        return $sheet->toArray('', false, false, false);
    }

    private function extractHeadings(array $rows): array
    {
        $rawHeadings = array_shift($rows) ?? [];
        $headings = array_map(fn ($heading) => $this->normalizeHeading((string) $heading), $rawHeadings);

        return [$headings, $rows];
    }

    private function validateHeadings(array $headings): void
    {
        foreach (['type', 'question_text'] as $requiredHeading) {
            if (! in_array($requiredHeading, $headings, true)) {
                throw ValidationException::withMessages([
                    'sheet' => 'Kolom '.$requiredHeading.' tidak ditemukan. Download ulang template agar formatnya pas.',
                ]);
            }
        }
    }

    private function mapRow(array $headings, array $row): array
    {
        $mapped = [];

        foreach ($headings as $index => $heading) {
            if ($heading === '') {
                continue;
            }

            $mapped[$heading] = is_string($row[$index] ?? null)
                ? trim((string) $row[$index])
                : $row[$index] ?? null;
        }

        return $mapped;
    }

    private function rowIsEmpty(array $payload): bool
    {
        return collect($payload)->every(function ($value) {
            if (is_numeric($value)) {
                return false;
            }

            return trim((string) $value) === '';
        });
    }

    private function normalizeHeading(string $heading): string
    {
        return Str::of($heading)
            ->lower()
            ->replace([' ', '-', '/'], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->trim('_')
            ->toString();
    }

    private function normalizeType(string $type): string
    {
        $normalized = Str::of($type)->lower()->replace([' ', '-'], '_')->toString();

        return match ($normalized) {
            'pg', 'pilihan_ganda' => 'multiple_choice',
            'pg_kompleks', 'pilihan_ganda_kompleks' => 'multiple_choice_complex',
            'benar_salah' => 'true_false',
            'matriks_pernyataan', 'statement_matrix' => 'true_false_group',
            'esai' => 'essay',
            default => $normalized,
        };
    }

    private function resolveOrderNumber(mixed $value, int &$currentOrder, int $rowNumber): int
    {
        if ($value === null || trim((string) $value) === '') {
            $currentOrder++;

            return $currentOrder;
        }

        if (! is_numeric($value) || (int) $value < 1) {
            $this->throwRowError($rowNumber, 'Kolom order_number harus berupa angka 1 atau lebih.');
        }

        $currentOrder = max($currentOrder, (int) $value);

        return (int) $value;
    }

    private function resolveStimulus(Exam $exam, array $payload, array &$stimulusCache, array &$summary, int $rowNumber): ?Stimulus
    {
        $stimulusKey = trim((string) ($payload['stimulus_key'] ?? $payload['stimulus_title'] ?? ''));

        if ($stimulusKey === '') {
            return null;
        }

        if (isset($stimulusCache[$stimulusKey])) {
            return $stimulusCache[$stimulusKey];
        }

        $title = trim((string) ($payload['stimulus_title'] ?? ''));

        if ($title === '') {
            $this->throwRowError($rowNumber, 'Jika stimulus_key diisi, stimulus_title juga perlu diisi minimal di baris pertama stimulus tersebut.');
        }

        $type = trim((string) ($payload['stimulus_type'] ?? 'text')) ?: 'text';

        if (! in_array($type, ['text', 'image', 'mixed'], true)) {
            $this->throwRowError($rowNumber, 'stimulus_type hanya boleh text, image, atau mixed.');
        }

        $stimulus = Stimulus::create([
            'exam_id' => $exam->id,
            'title' => $title,
            'type' => $type,
            'content' => trim((string) ($payload['stimulus_content'] ?? '')) ?: null,
        ]);

        $summary['created_stimuli']++;
        $stimulusCache[$stimulusKey] = $stimulus;

        return $stimulus;
    }

    private function buildQuestionData(Exam $exam, ?int $stimulusId, int $orderNumber, string $type, string $questionText, int $scoreWeight, array $payload, int $rowNumber): array
    {
        return [
            'exam_id' => $exam->id,
            'stimulus_id' => $stimulusId,
            'order_number' => $orderNumber,
            'type' => $type,
            'question_text' => $questionText,
            'answer_key' => $this->compileAnswerKey($type, $payload, $rowNumber),
            'score_weight' => $scoreWeight,
            'explanation' => trim((string) ($payload['explanation'] ?? '')) ?: null,
            'is_active' => ! in_array(Str::lower((string) ($payload['is_active'] ?? '1')), ['0', 'false', 'tidak', 'nonaktif'], true),
        ];
    }

    private function compileAnswerKey(string $type, array $payload, int $rowNumber): ?string
    {
        $correctAnswer = trim((string) ($payload['correct_answer'] ?? ''));

        return match ($type) {
            'multiple_choice' => $this->compileSingleMultipleChoiceKey($correctAnswer, $rowNumber),
            'multiple_choice_complex' => $this->compileMultipleChoiceComplexKey($correctAnswer, $rowNumber),
            'true_false' => $this->compileTrueFalseKey($correctAnswer, $rowNumber),
            'true_false_group' => json_encode([
                'positive' => $this->statementLabels($payload, $rowNumber)['positive'],
                'negative' => $this->statementLabels($payload, $rowNumber)['negative'],
            ], JSON_UNESCAPED_UNICODE),
            default => null,
        };
    }

    private function compileSingleMultipleChoiceKey(string $correctAnswer, int $rowNumber): string
    {
        $label = Str::upper($correctAnswer);

        if (! in_array($label, ['A', 'B', 'C', 'D', 'E'], true)) {
            $this->throwRowError($rowNumber, 'correct_answer untuk pilihan ganda harus satu huruf A sampai E.');
        }

        return $label;
    }

    private function compileMultipleChoiceComplexKey(string $correctAnswer, int $rowNumber): string
    {
        $labels = collect(preg_split('/[\|\;\,]+/', $correctAnswer))
            ->map(fn ($value) => Str::upper(trim((string) $value)))
            ->filter()
            ->unique()
            ->values();

        if ($labels->isEmpty() || $labels->contains(fn ($value) => ! in_array($value, ['A', 'B', 'C', 'D', 'E'], true))) {
            $this->throwRowError($rowNumber, 'correct_answer untuk pilihan ganda kompleks ditulis seperti A|C atau B|D|E.');
        }

        return $labels->implode(',');
    }

    private function compileTrueFalseKey(string $correctAnswer, int $rowNumber): string
    {
        $normalized = Str::lower($correctAnswer);

        return match ($normalized) {
            'benar', 'b', 'true', '1' => 'Benar',
            'salah', 's', 'false', '0' => 'Salah',
            default => $this->throwRowError($rowNumber, 'correct_answer untuk true_false harus Benar atau Salah.'),
        };
    }

    private function storeQuestionOptions(Question $question, array $payload, int $rowNumber): void
    {
        if ($question->usesStatementTruthAnswer()) {
            $labels = $question->statementTruthLabels();

            foreach ($this->parseStatementRows((string) ($payload['statement_rows'] ?? ''), $labels, $rowNumber) as $index => $row) {
                $question->options()->create([
                    'label' => (string) ($index + 1),
                    'option_text' => $row['text'],
                    'is_correct' => $row['is_correct'],
                    'order_number' => $index + 1,
                ]);
            }

            return;
        }

        if ($question->isEssay()) {
            return;
        }

        if ($question->isTrueFalse()) {
            foreach (['Benar', 'Salah'] as $index => $label) {
                $question->options()->create([
                    'label' => $label,
                    'option_text' => $label,
                    'is_correct' => $question->answer_key === $label,
                    'order_number' => $index + 1,
                ]);
            }

            return;
        }

        $filledOptions = collect(['A', 'B', 'C', 'D', 'E'])
            ->map(fn ($label, $index) => [
                'label' => $label,
                'option_text' => trim((string) ($payload['option_'.Str::lower($label)] ?? '')),
                'order_number' => $index + 1,
            ])
            ->filter(fn ($option) => $option['option_text'] !== '')
            ->values();

        if ($filledOptions->count() < 2) {
            $this->throwRowError($rowNumber, 'Pilihan ganda minimal punya dua opsi yang terisi.');
        }

        $correctLabels = $question->usesMultipleOptionAnswer()
            ? collect(explode(',', (string) $question->answer_key))->values()
            : collect([(string) $question->answer_key]);

        foreach ($correctLabels as $correctLabel) {
            if (! $filledOptions->pluck('label')->contains($correctLabel)) {
                $this->throwRowError($rowNumber, 'correct_answer mengarah ke opsi yang belum diisi.');
            }
        }

        foreach ($filledOptions as $option) {
            $question->options()->create([
                'label' => $option['label'],
                'option_text' => $option['option_text'],
                'is_correct' => $correctLabels->contains($option['label']),
                'order_number' => $option['order_number'],
            ]);
        }
    }

    private function statementLabels(array $payload, int $rowNumber): array
    {
        $positive = trim((string) ($payload['statement_positive_label'] ?? ''));
        $negative = trim((string) ($payload['statement_negative_label'] ?? ''));

        if ($positive === '' || $negative === '') {
            $this->throwRowError($rowNumber, 'Soal matriks pernyataan wajib mengisi statement_positive_label dan statement_negative_label.');
        }

        return ['positive' => $positive, 'negative' => $negative];
    }

    private function parseStatementRows(string $rawRows, array $labels, int $rowNumber): array
    {
        $segments = collect(preg_split('/\r\n|\r|\n|\|/', $rawRows))
            ->map(fn ($segment) => trim((string) $segment))
            ->filter()
            ->values();

        if ($segments->count() < 2) {
            $this->throwRowError($rowNumber, 'statement_rows minimal berisi dua pernyataan. Format contoh: Pernyataan 1::1 | Pernyataan 2::0');
        }

        return $segments->map(function ($segment) use ($labels, $rowNumber) {
            [$text, $answer] = array_pad(explode('::', $segment, 2), 2, null);
            $text = trim((string) $text);
            $answer = trim((string) $answer);

            if ($text === '' || $answer === '') {
                $this->throwRowError($rowNumber, 'Setiap item statement_rows harus memakai format Teks pernyataan::1 atau Teks pernyataan::0.');
            }

            $normalizedAnswer = Str::lower($answer);
            $positiveLabel = Str::lower($labels['positive']);
            $negativeLabel = Str::lower($labels['negative']);

            if (in_array($normalizedAnswer, ['1', 'true', 'left', $positiveLabel], true)) {
                $isCorrect = true;
            } elseif (in_array($normalizedAnswer, ['0', 'false', 'right', $negativeLabel], true)) {
                $isCorrect = false;
            } else {
                $this->throwRowError($rowNumber, 'Nilai kunci pada statement_rows hanya boleh 1/0 atau sama dengan label kolom kiri/kanan.');
            }

            return [
                'text' => $text,
                'is_correct' => $isCorrect,
            ];
        })->all();
    }

    private function throwRowError(int $rowNumber, string $message): never
    {
        throw ValidationException::withMessages([
            'sheet' => 'Baris '.$rowNumber.': '.$message,
        ]);
    }
}
