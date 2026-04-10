<?php

use Livewire\Component;
use App\Models\ExamAttempt;

new class extends Component
{
    public ExamAttempt $attempt;

    public function mount(ExamAttempt $attempt): void
    {
        $this->attempt = $attempt->load(['exam', 'student', 'result']);
    }
};
?>

<section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="rounded-md border border-zinc-200 bg-white p-6 text-center shadow-sm">
        <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-700">Jawaban sudah diterima TIM MBC</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950">{{ $attempt->exam->title }}</h1>
        <p class="mt-2 text-zinc-600">{{ $attempt->student->name }}</p>

        @if ($attempt->exam->show_result_to_student && $attempt->result)
            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="rounded-md border border-zinc-200 p-4"><p class="text-sm text-zinc-500">Benar</p><p class="mt-2 text-2xl font-semibold text-zinc-950">{{ $attempt->result->correct_count }}</p></div>
                <div class="rounded-md border border-zinc-200 p-4"><p class="text-sm text-zinc-500">Kosong</p><p class="mt-2 text-2xl font-semibold text-zinc-950">{{ $attempt->result->blank_count }}</p></div>
                <div class="rounded-md border border-zinc-200 p-4"><p class="text-sm text-zinc-500">Nilai</p><p class="mt-2 text-2xl font-semibold text-emerald-700">{{ $attempt->result->total_score }}</p></div>
            </div>
            @if ($attempt->result->essay_status === 'pending')
                <p class="mt-5 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Nilai esai sedang menunggu koreksi dari TIM MBC.</p>
            @endif
        @else
            <p class="mt-6 rounded-md border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600">Jawabanmu sudah masuk. TIM MBC akan menginformasikan hasil sesuai ketentuan bimbel.</p>
        @endif

        <a href="{{ route('student.token') }}" class="mt-8 inline-flex rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Kembali ke portal</a>
    </div>
</section>
