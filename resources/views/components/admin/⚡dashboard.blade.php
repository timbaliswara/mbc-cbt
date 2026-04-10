<?php

use Livewire\Component;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\ExamToken;
use App\Models\Question;

new class extends Component
{
    public function with(): array
    {
        return [
            'examCount' => Exam::count(),
            'questionCount' => Question::count(),
            'tokenCount' => ExamToken::count(),
            'attemptCount' => ExamAttempt::count(),
            'pendingEssays' => ExamResult::where('essay_status', 'pending')->count(),
            'latestAttempts' => ExamAttempt::with(['exam', 'student', 'result'])->latest()->limit(6)->get(),
        ];
    }
};
?>

<div class="space-y-6">
    <section class="hero-panel rounded-md p-6 shadow-2xl shadow-emerald-950/10">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl">
                <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-50/75">Overview</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">Operasional CBT hari ini dalam satu layar.</h2>
                <p class="mt-3 text-sm leading-6 text-emerald-50/75">Pantau paket aktif, token yang sudah dibuat, sesi siswa, dan antrean koreksi esai tanpa berpindah konteks.</p>
            </div>
            <a href="{{ route('admin.questions') }}" class="rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50">Kelola soal</a>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Paket ujian', 'value' => $examCount],
            ['label' => 'Soal aktif', 'value' => $questionCount],
            ['label' => 'Token dibuat', 'value' => $tokenCount],
            ['label' => 'Sesi siswa', 'value' => $attemptCount],
            ['label' => 'Esai antre', 'value' => $pendingEssays],
        ] as $stat)
            <div class="surface rounded-md p-5 transition hover:-translate-y-0.5 hover:shadow-xl">
                <p class="text-sm text-zinc-500">{{ $stat['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950">{{ $stat['value'] }}</p>
                <div class="mt-4 h-1 rounded-full bg-zinc-100">
                    <div class="h-1 w-2/3 rounded-full bg-emerald-500"></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.4fr_0.8fr]">
        <div class="surface overflow-hidden rounded-md">
            <div class="border-b border-zinc-200 p-5">
                <h2 class="text-base font-semibold text-zinc-950">Aktivitas terbaru</h2>
                <p class="mt-1 text-sm text-zinc-500">Sesi ujian yang baru berjalan atau selesai.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-5 py-3">Siswa</th>
                            <th class="px-5 py-3">Ujian</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($latestAttempts as $attempt)
                            <tr>
                                <td class="px-5 py-4 font-medium text-zinc-950">{{ $attempt->student->name }}</td>
                                <td class="px-5 py-4 text-zinc-600">{{ $attempt->exam->title }}</td>
                                <td class="px-5 py-4"><span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-800">{{ $attempt->status }}</span></td>
                                <td class="px-5 py-4 text-zinc-600">{{ $attempt->result?->total_score ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-zinc-500">Belum ada sesi ujian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="surface rounded-md p-5">
            <h2 class="text-base font-semibold text-zinc-950">Langkah cepat</h2>
            <div class="mt-4 space-y-3">
                <a href="{{ route('admin.exams') }}" class="surface-muted block rounded-md p-4 text-sm font-medium text-zinc-700 transition hover:border-emerald-200 hover:text-emerald-800">1. Buat paket ujian</a>
                <a href="{{ route('admin.questions') }}" class="surface-muted block rounded-md p-4 text-sm font-medium text-zinc-700 transition hover:border-emerald-200 hover:text-emerald-800">2. Isi soal dan stimulus</a>
                <a href="{{ route('admin.tokens') }}" class="surface-muted block rounded-md p-4 text-sm font-medium text-zinc-700 transition hover:border-emerald-200 hover:text-emerald-800">3. Generate token siswa</a>
                <a href="{{ route('admin.results') }}" class="surface-muted block rounded-md p-4 text-sm font-medium text-zinc-700 transition hover:border-emerald-200 hover:text-emerald-800">4. Cek hasil dan esai</a>
            </div>
        </div>
    </div>
</div>
