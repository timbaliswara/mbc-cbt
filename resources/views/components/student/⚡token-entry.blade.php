<?php

use Livewire\Component;
use App\Models\ExamAttempt;
use App\Models\ExamToken;
use App\Models\Student;

new class extends Component
{
    public string $token = '';
    public string $name = '';
    public string $phone = '';
    public string $school = '';
    public string $grade = '';
    public string $resultToken = '';

    public function start()
    {
        $data = $this->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'school' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:50'],
        ]);

        $token = ExamToken::with('exam')->where('token', strtoupper(trim($data['token'])))->first();

        if (! $token || ! $token->canBeUsed() || $token->exam->status !== 'active') {
            $this->addError('token', 'Token tidak valid, sudah dipakai, kedaluwarsa, atau ujian belum aktif.');
            return;
        }

        $student = Student::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'school' => $data['school'],
            'grade' => $data['grade'],
        ]);

        $attempt = ExamAttempt::create([
            'exam_id' => $token->exam_id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $token->update(['student_id' => $student->id, 'status' => 'in_progress', 'used_at' => now()]);

        return redirect()->route('student.exam', $attempt);
    }

    public function checkResult()
    {
        $data = $this->validate([
            'resultToken' => ['required', 'string'],
        ]);

        $token = ExamToken::with('attempt.result')->where('token', strtoupper(trim($data['resultToken'])))->first();

        if (! $token || ! $token->attempt) {
            $this->addError('resultToken', 'Token belum ditemukan. Cek lagi token dari TIM MBC.');
            return;
        }

        if ($token->attempt->status === 'in_progress') {
            return redirect()->route('student.exam', $token->attempt);
        }

        if ($token->attempt->status !== 'finished') {
            $this->addError('resultToken', 'Hasil untuk token ini belum tersedia.');
            return;
        }

        return redirect()->route('student.result', $token->attempt);
    }
};
?>

<section class="mx-auto grid min-h-[calc(100vh-73px)] max-w-6xl items-center gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[0.92fr_1.08fr] lg:px-8">
    <div>
        <div class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-emerald-800">Portal Siswa</div>
        <h1 class="mt-5 max-w-xl text-5xl font-semibold tracking-tight text-zinc-950">Masukkan token dari TIM MBC, lalu mulai saat kamu sudah siap.</h1>
        <p class="mt-5 max-w-xl text-base leading-7 text-zinc-600">Token diberikan setelah pembayaran dikonfirmasi oleh TIM MBC. Pastikan koneksi internet stabil dan data diri sudah benar sebelum mulai.</p>
        <div class="mt-8 grid gap-3 sm:grid-cols-3">
            <div class="surface rounded-md p-4"><p class="text-sm font-semibold text-zinc-950">Timer</p><p class="mt-1 text-sm text-zinc-500">Berjalan saat ujian dimulai</p></div>
            <div class="surface rounded-md p-4"><p class="text-sm font-semibold text-zinc-950">Auto-submit</p><p class="mt-1 text-sm text-zinc-500">Otomatis saat waktu habis</p></div>
            <div class="surface rounded-md p-4"><p class="text-sm font-semibold text-zinc-950">Media</p><p class="mt-1 text-sm text-zinc-500">Soal bisa memakai gambar</p></div>
        </div>
    </div>

    <div class="space-y-4">
        <form wire:submit="start" class="surface rounded-md p-7 shadow-sm backdrop-blur">
            <div class="rounded-md bg-zinc-950 p-5 text-white">
                <p class="text-sm font-medium text-emerald-200">Halo, selamat datang di tes MBC</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight">Data peserta</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-300">Isi data dengan benar ya. Setelah ujian dimulai, token ini akan terkunci untuk satu sesi.</p>
            </div>
            <div class="mt-5 grid gap-4">
                <div>
                    <label class="text-sm font-medium text-zinc-800">Token</label>
                    <input wire:model="token" class="premium-input mt-2 w-full rounded-md px-3 py-2.5 text-sm uppercase transition" placeholder="XXXX-XXXX-XXXX">
                    @error('token') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <input wire:model="name" placeholder="Nama lengkap" class="premium-input rounded-md px-3 py-2.5 text-sm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <input wire:model="grade" placeholder="Kelas" class="premium-input rounded-md px-3 py-2.5 text-sm">
                    <input wire:model="phone" placeholder="Nomor HP" class="premium-input rounded-md px-3 py-2.5 text-sm">
                </div>
                <input wire:model="school" placeholder="Asal sekolah" class="premium-input rounded-md px-3 py-2.5 text-sm">
                @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <button class="premium-button rounded-md px-4 py-2.5 text-sm font-semibold text-white hover:brightness-105">Mulai ujian</button>
            </div>
        </form>

        <form wire:submit="checkResult" class="surface rounded-md border border-emerald-100 p-5 shadow-sm">
            <p class="text-sm font-semibold text-zinc-950">Sudah pernah mengerjakan?</p>
            <p class="mt-2 text-sm leading-6 text-zinc-600">Masukkan token yang sama untuk melihat hasil ujian. Kalau ujian belum selesai, kamu akan diarahkan kembali ke ruang ujian.</p>
            <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                <input wire:model="resultToken" class="premium-input w-full rounded-md px-3 py-2.5 text-sm uppercase transition" placeholder="Token yang sudah dipakai">
                <button class="rounded-md border border-emerald-200 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50">Cek hasil</button>
            </div>
            @error('resultToken') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </form>
    </div>
</section>
