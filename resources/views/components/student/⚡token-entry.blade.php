<?php

use Livewire\Component;
use App\Models\ExamAttempt;
use App\Models\ExamToken;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

new class extends Component
{
    public string $token = '';
    public string $name = '';
    public string $phone = '';
    public string $school = '';
    public string $grade = '';
    public string $resultToken = '';
    public string $resultName = '';
    public string $resultPhone = '';

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
            $this->addError('token', 'Token belum bisa dipakai. Cek lagi token dari TIM MBC, jadwal ujian, atau masa berlakunya.');
            return;
        }

        $existingAttempt = $this->findAttemptByIdentity($token, $data);

        if ($existingAttempt?->status === 'in_progress') {
            return redirect()->route('student.exam', $existingAttempt);
        }

        if ($existingAttempt?->status === 'finished') {
            return redirect()->route('student.result', $existingAttempt);
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

        $token->update(['status' => 'active', 'used_at' => now()]);

        return redirect()->route('student.exam', $attempt);
    }

    public function checkResult()
    {
        $data = $this->validate([
            'resultToken' => ['required', 'string'],
            'resultName' => ['required', 'string', 'max:255'],
            'resultPhone' => ['nullable', 'string', 'max:50'],
        ]);

        $token = ExamToken::with('attempts.result')->where('token', strtoupper(trim($data['resultToken'])))->first();

        if (! $token) {
            $this->addError('resultToken', 'Token belum ditemukan. Cek lagi token dari TIM MBC.');
            return;
        }

        $attempt = $this->findAttemptByIdentity($token, [
            'name' => $data['resultName'],
            'phone' => $data['resultPhone'],
            'school' => null,
            'grade' => null,
        ]);

        if (! $attempt) {
            $this->addError('resultName', 'Data peserta untuk token ini belum cocok. Gunakan nama yang sama seperti saat mulai ujian.');
            return;
        }

        if ($attempt->status === 'in_progress') {
            return redirect()->route('student.exam', $attempt);
        }

        if ($attempt->status !== 'finished') {
            $this->addError('resultToken', 'Hasil untuk token ini belum tersedia.');
            return;
        }

        return redirect()->route('student.result', $attempt);
    }

    private function findAttemptByIdentity(ExamToken $token, array $identity): ?ExamAttempt
    {
        $name = trim((string) ($identity['name'] ?? ''));
        $phone = trim((string) ($identity['phone'] ?? ''));
        $school = trim((string) ($identity['school'] ?? ''));
        $grade = trim((string) ($identity['grade'] ?? ''));

        if ($name === '') {
            return null;
        }

        return ExamAttempt::query()
            ->with(['result', 'student'])
            ->where('exam_token_id', $token->id)
            ->whereHas('student', function (Builder $query) use ($grade, $name, $phone, $school) {
                $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);

                if ($phone !== '') {
                    $query->where('phone', $phone);
                }

                if ($school !== '') {
                    $query->whereRaw('LOWER(COALESCE(school, "")) = ?', [strtolower($school)]);
                }

                if ($grade !== '') {
                    $query->whereRaw('LOWER(COALESCE(grade, "")) = ?', [strtolower($grade)]);
                }
            })
            ->latest('id')
            ->first();
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
                <p class="mt-2 text-sm leading-6 text-zinc-300">Isi data dengan benar ya. Token dari TIM MBC bisa dipakai beberapa peserta, jadi nama dan nomor HP ini dipakai untuk melanjutkan ujian atau membuka hasilmu lagi.</p>
            </div>
            <div class="mt-5 grid gap-4">
                <div>
                    <label class="text-sm font-medium text-zinc-800">Token</label>
                    <input wire:model="token" class="premium-input mt-2 w-full rounded-md px-3 py-2.5 text-sm uppercase transition" placeholder="Misal MBCSD1">
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
            <p class="mt-2 text-sm leading-6 text-zinc-600">Masukkan token dan nama peserta yang sama. Kalau ujian belum selesai, kamu akan masuk lagi ke ruang ujian. Kalau sudah selesai, hasilnya langsung terbuka.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <input wire:model="resultToken" class="premium-input w-full rounded-md px-3 py-2.5 text-sm uppercase transition" placeholder="Token yang sudah dipakai">
                <input wire:model="resultName" class="premium-input w-full rounded-md px-3 py-2.5 text-sm transition" placeholder="Nama peserta">
            </div>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <input wire:model="resultPhone" class="premium-input w-full rounded-md px-3 py-2.5 text-sm transition" placeholder="Nomor HP yang sama (kalau tadi diisi)">
                <button class="rounded-md border border-emerald-200 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50">Cek hasil</button>
            </div>
            @error('resultToken') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('resultName') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </form>
    </div>
</section>
