<?php

use App\Models\ExamAttempt;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public function durationLabel(ExamAttempt $attempt): string
    {
        if (! $attempt->started_at) {
            return '-';
        }

        $finishedAt = $attempt->finished_at ?? now();
        $seconds = max(0, (int) $attempt->started_at->diffInSeconds($finishedAt));

        return floor($seconds / 60).' menit';
    }

    public function with(): array
    {
        $attempts = ExamAttempt::query()
            ->with(['exam:id,title,passing_grade', 'student:id,name,school,grade,phone', 'result'])
            ->when(trim($this->search) !== '', function ($query) {
                $search = '%'.trim($this->search).'%';
                $query->where(function ($query) use ($search) {
                    $query
                        ->whereHas('student', function ($studentQuery) use ($search) {
                            $studentQuery
                                ->where('name', 'like', $search)
                                ->orWhere('school', 'like', $search)
                                ->orWhere('grade', 'like', $search)
                                ->orWhere('phone', 'like', $search);
                        })
                        ->orWhereHas('exam', function ($examQuery) use ($search) {
                            $examQuery->where('title', 'like', $search);
                        });
                });
            })
            ->latest()
            ->limit(50)
            ->get();

        return ['attempts' => $attempts];
    }
};
?>

<div class="space-y-6">
    <section class="hero-panel rounded-md p-6 shadow-2xl shadow-emerald-950/10">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-3xl">
                <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-50/75">Rekap Nilai TIM MBC</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">Hasil peserta dibuat ringkas agar mudah dibaca.</h2>
                <p class="mt-3 text-sm leading-6 text-emerald-50/75">Daftar ini sengaja dibuat ringan. Kalau TIM MBC perlu melihat jawaban per soal, buka halaman detail masing-masing peserta.</p>
            </div>
            <div class="rounded-md border border-white/15 bg-white/10 p-4 text-sm text-emerald-50/80 backdrop-blur">
                <p class="font-semibold text-white">{{ $attempts->count() }} data tampil</p>
                <p class="mt-1">Menampilkan 50 data terbaru</p>
            </div>
        </div>
    </section>

    <div class="surface rounded-md p-5">
        <label class="text-sm font-medium text-zinc-800">Cari hasil</label>
        <input
            wire:model.live.debounce.400ms="search"
            type="search"
            class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm"
            placeholder="Cari nama siswa, sekolah, kelas, nomor HP, atau paket ujian"
        >
    </div>

    <div class="surface overflow-hidden rounded-md">
        <div class="border-b border-zinc-200 p-5">
            <h2 class="text-base font-semibold text-zinc-950">Ringkasan hasil</h2>
            <p class="mt-1 text-sm text-zinc-500">Buka detail untuk melihat jawaban, kunci, status benar/salah, dan koreksi esai.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-5 py-3">Peserta</th>
                        <th class="px-5 py-3">Paket ujian</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Benar/Salah/Kosong</th>
                        <th class="px-5 py-3">Nilai</th>
                        <th class="px-5 py-3">Durasi</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($attempts as $attempt)
                        @php
                            $result = $attempt->result;
                            $passLabel = is_null($result?->is_passed) ? 'Tanpa passing grade' : ($result->is_passed ? 'Lulus' : 'Belum lulus');
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-zinc-950">{{ $attempt->student->name }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $attempt->student->school ?: 'Sekolah belum diisi' }} · {{ $attempt->student->grade ?: 'Kelas -' }}</p>
                            </td>
                            <td class="px-5 py-4 text-zinc-700">{{ $attempt->exam->title }}</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700">{{ $attempt->status }}</span>
                                    <span @class([
                                        'rounded-md px-2 py-1 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-800' => $result?->is_passed === true,
                                        'bg-red-50 text-red-700' => $result?->is_passed === false,
                                        'bg-zinc-100 text-zinc-700' => is_null($result?->is_passed),
                                    ])>{{ $passLabel }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-zinc-700">{{ $result?->correct_count ?? 0 }} / {{ $result?->wrong_count ?? 0 }} / {{ $result?->blank_count ?? 0 }}</td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-zinc-950">{{ $result?->total_score ?? 0 }}</p>
                                <p class="mt-1 text-xs text-zinc-500">PG {{ $result?->multiple_choice_score ?? 0 }} · Esai {{ $result?->essay_score ?? 0 }}</p>
                            </td>
                            <td class="px-5 py-4 text-zinc-700">{{ $this->durationLabel($attempt) }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.results.show', $attempt) }}" class="rounded-md bg-emerald-700 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-zinc-500">Tidak ada hasil yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
