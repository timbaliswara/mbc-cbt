<?php

use Livewire\Component;
use App\Models\Exam;
use App\Models\ExamToken;
use Illuminate\Support\Str;

new class extends Component
{
    public ?int $exam_id = null;
    public int $quantity = 1;
    public string $expires_at = '';

    public function mount(): void
    {
        $this->exam_id = Exam::latest()->value('id');
    }

    public function generate(): void
    {
        $data = $this->validate([
            'exam_id' => ['required', 'exists:exams,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'expires_at' => ['nullable', 'date'],
        ]);

        for ($i = 0; $i < $data['quantity']; $i++) {
            ExamToken::create([
                'exam_id' => $data['exam_id'],
                'token' => $this->generateTokenCode(),
                'expires_at' => $data['expires_at'] ?: null,
                'status' => 'active',
            ]);
        }

        $this->quantity = 1;
        session()->flash('message', 'Token berhasil dibuat.');
    }

    public function cancel(int $id): void
    {
        ExamToken::findOrFail($id)->update(['status' => 'cancelled']);
    }

    private function generateTokenCode(): string
    {
        do {
            $token = strtoupper(Str::random(6));
        } while (ExamToken::where('token', $token)->exists());

        return $token;
    }

    public function with(): array
    {
        return [
            'exams' => Exam::latest()->get(),
            'tokens' => ExamToken::with(['exam'])->withCount('attempts')->latest()->limit(100)->get(),
        ];
    }
};
?>

<div class="grid gap-6 xl:grid-cols-[0.7fr_1.3fr]">
    <form wire:submit="generate" class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-zinc-950">Buat token peserta</h2>
        <p class="mt-1 text-sm text-zinc-500">Token diberikan setelah pembayaran manual dikonfirmasi oleh TIM MBC. Satu token sekarang bisa dipakai beberapa siswa untuk paket ujian yang sama.</p>
        @if (session('message'))
            <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('message') }}</div>
        @endif
        <div class="mt-5 grid gap-4">
            <select wire:model="exam_id" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                @foreach ($exams as $exam)
                    <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                @endforeach
            </select>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-zinc-800">Jumlah</label>
                    <input wire:model="quantity" type="number" min="1" max="100" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-800">Kedaluwarsa</label>
                    <input wire:model="expires_at" type="datetime-local" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                </div>
            </div>
            <button class="rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Buat token</button>
            <p class="text-xs leading-5 text-zinc-500">Format token dibuat lebih singkat, 6 karakter huruf kapital, supaya lebih enak dikirim lewat chat.</p>
        </div>
    </form>

    <div class="rounded-md border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 p-5">
            <h2 class="text-base font-semibold text-zinc-950">Daftar token</h2>
            <p class="mt-1 text-sm text-zinc-500">Token terbaru ada di atas agar mudah dibagikan oleh TIM MBC.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500">
                    <tr><th class="px-5 py-3">Token</th><th class="px-5 py-3">Ujian</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Dipakai</th><th class="px-5 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($tokens as $token)
                        <tr>
                            <td class="px-5 py-4 font-mono font-semibold text-zinc-950">{{ $token->token }}</td>
                            <td class="px-5 py-4 text-zinc-600">{{ $token->exam->title }}</td>
                            <td class="px-5 py-4"><span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700">{{ $token->status === 'active' ? 'aktif' : $token->status }}</span></td>
                            <td class="px-5 py-4 text-zinc-600">{{ $token->attempts_count }} sesi</td>
                            <td class="px-5 py-4 text-right">
                                @if ($token->status !== 'cancelled')
                                    <button wire:click="cancel({{ $token->id }})" class="rounded-md border border-red-200 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50">Batalkan</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-zinc-500">Belum ada token.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
