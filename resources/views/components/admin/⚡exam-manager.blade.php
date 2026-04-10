<?php

use Livewire\Component;
use App\Models\Exam;

new class extends Component
{
    public ?int $editingId = null;
    public string $title = '';
    public string $level = 'SD';
    public string $grade = '';
    public string $subject = '';
    public string $description = '';
    public string $instructions = '';
    public string $starts_at = '';
    public string $ends_at = '';
    public int $duration_minutes = 90;
    public string $status = 'draft';
    public ?int $passing_grade = null;
    public bool $show_result_to_student = false;
    public bool $shuffle_questions = false;
    public bool $shuffle_options = false;

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'level' => ['required', 'in:SD,SMP'],
            'grade' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,active,finished,archived'],
            'passing_grade' => ['nullable', 'integer', 'min:0'],
            'show_result_to_student' => ['boolean'],
            'shuffle_questions' => ['boolean'],
            'shuffle_options' => ['boolean'],
        ]);

        $data['starts_at'] = $data['starts_at'] ?: null;
        $data['ends_at'] = $data['ends_at'] ?: null;

        Exam::updateOrCreate(['id' => $this->editingId], $data);
        $this->resetForm();
        session()->flash('message', 'Paket ujian tersimpan.');
    }

    public function edit(int $id): void
    {
        $exam = Exam::findOrFail($id);
        $this->editingId = $exam->id;
        $this->title = $exam->title;
        $this->level = $exam->level;
        $this->grade = (string) $exam->grade;
        $this->subject = (string) $exam->subject;
        $this->description = (string) $exam->description;
        $this->instructions = (string) $exam->instructions;
        $this->starts_at = $exam->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at = $exam->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->duration_minutes = $exam->duration_minutes;
        $this->status = $exam->status;
        $this->passing_grade = $exam->passing_grade;
        $this->show_result_to_student = $exam->show_result_to_student;
        $this->shuffle_questions = $exam->shuffle_questions;
        $this->shuffle_options = $exam->shuffle_options;
    }

    public function delete(int $id): void
    {
        Exam::findOrFail($id)->delete();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'title',
            'grade',
            'subject',
            'description',
            'instructions',
            'starts_at',
            'ends_at',
            'passing_grade',
            'show_result_to_student',
            'shuffle_questions',
            'shuffle_options',
        ]);
        $this->level = 'SD';
        $this->duration_minutes = 90;
        $this->status = 'draft';
    }

    public function with(): array
    {
        return ['exams' => Exam::withCount(['questions', 'tokens', 'attempts'])->latest()->get()];
    }
};
?>

<div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
    <form wire:submit="save" class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-zinc-950">{{ $editingId ? 'Edit paket ujian' : 'Buat paket ujian' }}</h2>
                <p class="mt-1 text-sm text-zinc-500">TIM MBC bisa mengatur jadwal, durasi, dan aturan hasil dari sini.</p>
            </div>
            @if ($editingId)
                <button type="button" wire:click="resetForm" class="rounded-md border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700">Batal</button>
            @endif
        </div>

        @if (session('message'))
            <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('message') }}</div>
        @endif

        <div class="mt-5 grid gap-4">
            <div>
                <label class="text-sm font-medium text-zinc-800">Nama ujian</label>
                <input wire:model="title" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100">
                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="text-sm font-medium text-zinc-800">Jenjang</label>
                    <select wire:model="level" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm"><option>SD</option><option>SMP</option></select>
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-800">Kelas</label>
                    <input wire:model="grade" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-800">Mapel</label>
                    <input wire:model="subject" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-zinc-800">Mulai</label>
                    <input wire:model="starts_at" type="datetime-local" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-800">Selesai</label>
                    <input wire:model="ends_at" type="datetime-local" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="text-sm font-medium text-zinc-800">Durasi menit</label>
                    <input wire:model="duration_minutes" type="number" min="1" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-800">Status</label>
                    <select wire:model="status" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                        <option value="draft">Draft</option><option value="active">Aktif</option><option value="finished">Selesai</option><option value="archived">Arsip</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-800">Passing grade</label>
                    <input wire:model="passing_grade" type="number" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-zinc-800">Instruksi siswa</label>
                <textarea wire:model="instructions" rows="3" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm"></textarea>
            </div>
            <div class="grid gap-3 text-sm text-zinc-700">
                <label class="flex items-center gap-2"><input wire:model="show_result_to_student" type="checkbox" class="rounded border-zinc-300 text-emerald-700"> Tampilkan hasil ke siswa</label>
                <label class="flex items-center gap-2"><input wire:model="shuffle_questions" type="checkbox" class="rounded border-zinc-300 text-emerald-700"> Acak soal</label>
                <label class="flex items-center gap-2"><input wire:model="shuffle_options" type="checkbox" class="rounded border-zinc-300 text-emerald-700"> Acak opsi</label>
            </div>
            <button class="rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">Simpan paket</button>
        </div>
    </form>

    <div class="rounded-md border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-200 p-5">
            <h2 class="text-base font-semibold text-zinc-950">Daftar paket</h2>
            <p class="mt-1 text-sm text-zinc-500">Pilih paket yang ingin disunting, diisi soal, atau dibuatkan tokennya.</p>
        </div>
        <div class="divide-y divide-zinc-100">
            @forelse ($exams as $exam)
                <div class="p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-zinc-950">{{ $exam->title }}</h3>
                            <p class="mt-1 text-sm text-zinc-500">{{ $exam->level }} {{ $exam->grade }} · {{ $exam->subject ?: 'Mapel umum' }} · {{ $exam->duration_minutes }} menit</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-md bg-emerald-50 px-2 py-1 font-medium text-emerald-800">{{ $exam->status }}</span>
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-zinc-600">{{ $exam->questions_count }} soal</span>
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-zinc-600">{{ $exam->tokens_count }} token</span>
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-zinc-600">{{ $exam->attempts_count }} sesi</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="edit({{ $exam->id }})" class="rounded-md border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">Edit</button>
                            <button wire:click="delete({{ $exam->id }})" wire:confirm="Hapus paket ujian ini?" class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Hapus</button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-zinc-500">Belum ada paket ujian.</div>
            @endforelse
        </div>
    </div>
</div>
