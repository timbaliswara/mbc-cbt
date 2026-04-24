<?php

use App\Models\ExamAttempt;
use App\Support\ExamScoring;
use App\Support\QuestionImportTemplateExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    return redirect()->route('student.token');
});

Route::get('/admin/login', fn () => view('pages.login'))->name('login');

Route::post('/admin/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => view('pages.admin-dashboard'))->name('dashboard');
    Route::get('/exams', fn () => view('pages.admin-exams'))->name('exams');
    Route::get('/questions', fn () => view('pages.admin-questions'))->name('questions');
    Route::get('/questions/import', fn () => view('pages.admin-question-import'))->name('questions.import');
    Route::get('/questions/import-template', fn () => Excel::download(new QuestionImportTemplateExport, 'template-import-soal-mbc.xlsx'))->name('questions.import.template');
    Route::get('/questions/import-sample', fn () => response()->download(
        base_path('resources/examples/import-soal-ips-sd-10.csv'),
        'contoh-import-soal-ips-sd-10.csv',
        ['Content-Type' => 'text/csv; charset=UTF-8']
    ))->name('questions.import.sample');
    Route::get('/tokens', fn () => view('pages.admin-tokens'))->name('tokens');
    Route::get('/results', fn () => view('pages.admin-results'))->name('results');
    Route::get('/results-export', function () {
        $search = trim((string) request('search', ''));
        $attempts = ExamAttempt::query()
            ->with(['exam.questions', 'student', 'result', 'answers'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($query) use ($like) {
                    $query
                        ->whereHas('student', function ($studentQuery) use ($like) {
                            $studentQuery
                                ->where('name', 'like', $like)
                                ->orWhere('school', 'like', $like)
                                ->orWhere('grade', 'like', $like)
                                ->orWhere('phone', 'like', $like);
                        })
                        ->orWhereHas('exam', fn ($examQuery) => $examQuery->where('title', 'like', $like));
                });
            })
            ->latest()
            ->limit(500)
            ->get();

        return response()->streamDownload(function () use ($attempts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nama', 'Sekolah', 'Kelas', 'Nomor HP', 'Paket Ujian', 'Status', 'Progres', 'Benar', 'Salah', 'Kosong', 'Nilai PG', 'Nilai Esai', 'Nilai Total', 'Maksimal', 'Mulai', 'Selesai']);

            foreach ($attempts as $attempt) {
                fputcsv($handle, [
                    $attempt->student->name,
                    $attempt->student->school,
                    $attempt->student->grade,
                    $attempt->student->phone,
                    $attempt->exam->title,
                    $attempt->status,
                    ExamScoring::progressPercentage($attempt).'%',
                    $attempt->result?->correct_count ?? 0,
                    $attempt->result?->wrong_count ?? 0,
                    $attempt->result?->blank_count ?? 0,
                    $attempt->result?->multiple_choice_score ?? 0,
                    $attempt->result?->essay_score ?? 0,
                    $attempt->result?->total_score ?? 0,
                    ExamScoring::maxScore($attempt),
                    $attempt->started_at?->format('Y-m-d H:i:s'),
                    $attempt->finished_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 'rekap-hasil-mbc.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    })->name('results.export');
    Route::get('/results/{attempt}/pdf', function (ExamAttempt $attempt) {
        $attempt->load([
            'exam.questions.options',
            'student',
            'token',
            'result',
            'answers.question.options',
            'answers.option',
        ]);

        $answersByQuestion = $attempt->answers->keyBy('question_id');
        $wrongNumbers = [];
        $blankNumbers = [];

        foreach ($attempt->exam->questions as $question) {
            $answer = $answersByQuestion->get($question->id);

            if (! ExamScoring::answerIsFilled($question, $answer)) {
                $blankNumbers[] = $question->order_number;

                continue;
            }

            if ($question->isEssay()) {
                continue;
            }

            if (! $answer?->is_correct) {
                $wrongNumbers[] = $question->order_number;
            }
        }

        $result = $attempt->result;
        $pdf = Pdf::loadView('pdf.result-certificate', [
            'attempt' => $attempt,
            'student' => [
                'token' => $attempt->token?->token ?? '-',
                'name' => $attempt->student->name ?? '-',
                'phone' => $attempt->student->phone ?: '-',
                'grade' => $attempt->student->grade ?: '-',
                'school' => $attempt->student->school ?: '-',
            ],
            'examData' => [
                'title' => $attempt->exam->title ?? '-',
                'status' => str($attempt->status ?? '-')->replace('_', ' ')->title()->toString(),
                'started_at' => $attempt->started_at?->format('d M Y H:i') ?? '-',
                'finished_at' => $attempt->finished_at?->format('d M Y H:i') ?? '-',
            ],
            'summary' => [
                'correct_count' => $result?->correct_count ?? 0,
                'wrong_count' => $result?->wrong_count ?? 0,
                'blank_count' => $result?->blank_count ?? 0,
                'multiple_choice_score' => $result?->multiple_choice_score ?? 0,
                'essay_score' => $result?->essay_score ?? 0,
                'total_score' => $result?->total_score ?? 0,
                'max_score' => ExamScoring::maxScore($attempt),
            ],
            'wrongNumbers' => $wrongNumbers,
            'blankNumbers' => $blankNumbers,
            'contact' => [
                'address' => 'Jl. Cempaka No.40, Ngepos, Klaten, Kec. Klaten Tengah, Kabupaten Klaten, Jawa Tengah 57411',
                'instagram' => 'https://www.instagram.com/bimbelklubmbc',
            ],
        ])->setPaper('a4', 'portrait');
        $safeFilename = str('hasil-'.$attempt->student->name.'-'.$attempt->id)->slug('-')->toString().'.pdf';

        return $pdf->download($safeFilename);
    })->name('results.pdf');
    Route::get('/results/{attempt}', fn (ExamAttempt $attempt) => view('pages.admin-result-detail', compact('attempt')))->name('results.show');
    Route::get('/guide', fn () => view('pages.admin-guide'))->name('guide');
});

Route::get('/ujian', fn () => view('pages.student-token'))->name('student.token');
Route::get('/ujian/{attempt}', fn (ExamAttempt $attempt) => view('pages.student-exam', compact('attempt')))->name('student.exam');
Route::get('/hasil/{attempt}', fn (ExamAttempt $attempt) => view('pages.student-result', compact('attempt')))->name('student.result');
