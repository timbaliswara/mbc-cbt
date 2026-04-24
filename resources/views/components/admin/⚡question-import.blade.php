<?php

use App\Models\Exam;
use App\Support\QuestionSpreadsheetImporter;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?int $exam_id = null;
    public $sheet;
    public bool $replaceExisting = false;
    public array $lastImportSummary = [];

    public function mount(): void
    {
        $this->exam_id = Exam::latest()->value('id');
    }

    public function import(QuestionSpreadsheetImporter $importer): void
    {
        $data = $this->validate([
            'exam_id' => ['required', 'exists:exams,id'],
            'sheet' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
            'replaceExisting' => ['boolean'],
        ]);

        $exam = Exam::findOrFail($data['exam_id']);
        $summary = $importer->import($exam, $this->sheet->getRealPath(), $this->replaceExisting);

        $this->lastImportSummary = $summary;
        $this->reset('sheet');

        session()->flash('message', 'Import soal selesai. Paket '.$exam->title.' sekarang sudah diperbarui.');
    }

    public function with(): array
    {
        return [
            'exams' => Exam::latest()->get(),
            'typeExamples' => [
                [
                    'label' => 'Pilihan ganda',
                    'type' => 'multiple_choice',
                    'format' => 'Isi option_a sampai option_e seperlunya, lalu correct_answer = A/B/C/D/E.',
                    'sample' => 'A',
                ],
                [
                    'label' => 'Pilihan ganda kompleks',
                    'type' => 'multiple_choice_complex',
                    'format' => 'Isi beberapa opsi benar, lalu correct_answer = A|C atau B|D|E.',
                    'sample' => 'A|B',
                ],
                [
                    'label' => 'Benar / Salah',
                    'type' => 'true_false',
                    'format' => 'Tidak perlu isi opsi. Cukup correct_answer = Benar atau Salah.',
                    'sample' => 'Benar',
                ],
                [
                    'label' => 'Matriks pernyataan',
                    'type' => 'true_false_group',
                    'format' => 'Isi statement_positive_label, statement_negative_label, dan statement_rows seperti Teks 1::1 | Teks 2::0.',
                    'sample' => 'Kalimat 1::1 | Kalimat 2::0',
                ],
                [
                    'label' => 'Esai',
                    'type' => 'essay',
                    'format' => 'Cukup isi question_text, score_weight, dan explanation bila perlu. correct_answer boleh kosong.',
                    'sample' => '-',
                ],
            ],
            'columns' => [
                ['name' => 'order_number', 'required' => 'Opsional', 'description' => 'Nomor urut soal. Kalau kosong, sistem melanjutkan nomor otomatis.'],
                ['name' => 'type', 'required' => 'Wajib', 'description' => 'Jenis soal: multiple_choice, multiple_choice_complex, true_false, true_false_group, atau essay.'],
                ['name' => 'question_text', 'required' => 'Wajib', 'description' => 'Teks pertanyaan utama.'],
                ['name' => 'score_weight', 'required' => 'Opsional', 'description' => 'Bobot nilai per soal. Default 1.'],
                ['name' => 'explanation', 'required' => 'Opsional', 'description' => 'Catatan pembahasan atau arahan koreksi.'],
                ['name' => 'stimulus_key', 'required' => 'Opsional', 'description' => 'Kode pengelompokan stimulus jika satu bacaan dipakai beberapa soal.'],
                ['name' => 'stimulus_title', 'required' => 'Opsional', 'description' => 'Judul stimulus. Wajib jika stimulus_key dipakai di baris pertama.'],
                ['name' => 'stimulus_type', 'required' => 'Opsional', 'description' => 'text, image, atau mixed. Untuk import awal, paling aman pakai text atau mixed.'],
                ['name' => 'stimulus_content', 'required' => 'Opsional', 'description' => 'Isi bacaan atau keterangan stimulus.'],
                ['name' => 'option_a ... option_e', 'required' => 'Tergantung tipe', 'description' => 'Dipakai untuk pilihan ganda dan pilihan ganda kompleks.'],
                ['name' => 'correct_answer', 'required' => 'Tergantung tipe', 'description' => 'A untuk PG, A|C untuk PG kompleks, Benar/Salah untuk true_false.'],
                ['name' => 'statement_positive_label', 'required' => 'Khusus matriks', 'description' => 'Label kolom kiri, misalnya Tepat.'],
                ['name' => 'statement_negative_label', 'required' => 'Khusus matriks', 'description' => 'Label kolom kanan, misalnya Tidak Tepat.'],
                ['name' => 'statement_rows', 'required' => 'Khusus matriks', 'description' => 'Format pernyataan::1 atau pernyataan::0, dipisah tanda | atau baris baru.'],
                ['name' => 'is_active', 'required' => 'Opsional', 'description' => 'Isi 1 untuk aktif atau 0 untuk nonaktif. Default aktif.'],
            ],
        ];
    }
};
?>

<div class="space-y-6">
    @if (session('message'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('message') }}</div>
    @endif

    <section class="hero-panel rounded-md p-6 shadow-2xl shadow-emerald-950/10">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-3xl">
                <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-50/75">Import Bank Soal TIM MBC</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">Upload soal lewat Excel, lalu cek hasilnya dari satu halaman yang jelas.</h2>
                <p class="mt-3 text-sm leading-6 text-emerald-50/80">Halaman ini cocok saat tim sudah punya bank soal dalam format spreadsheet. Download template dulu, isi sesuai contoh, lalu import ke paket ujian yang dipilih.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.questions.import.template') }}" class="rounded-md border border-white/20 bg-white/12 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/16">Download template Excel</a>
                <a href="{{ route('admin.questions.import.sample') }}" class="rounded-md border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/14">Download contoh IPS SD</a>
                <a href="{{ route('admin.questions') }}" class="rounded-md border border-white/20 bg-white/8 px-4 py-2.5 text-sm font-semibold text-white/90 backdrop-blur transition hover:bg-white/12">Kembali ke manajemen soal</a>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <form wire:submit="import" class="surface rounded-md p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-700">Form Import</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Masukkan file dan pilih paket tujuan.</h3>
                </div>
                @if ($lastImportSummary !== [])
                    <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-right text-sm text-emerald-900">
                        <p class="font-semibold">{{ $lastImportSummary['imported_questions'] }} soal masuk</p>
                        <p class="mt-1">{{ $lastImportSummary['created_stimuli'] }} stimulus dibuat</p>
                    </div>
                @endif
            </div>

            <div class="mt-6 grid gap-4">
                <div>
                    <label class="text-sm font-medium text-zinc-800">Paket ujian tujuan</label>
                    <select wire:model="exam_id" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                        @foreach ($exams as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-zinc-800">File Excel atau CSV</label>
                    <input wire:model="sheet" type="file" accept=".xlsx,.xls,.csv,text/csv" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                    <p class="mt-2 text-xs leading-5 text-zinc-500">Ukuran maksimal 5 MB. Sistem membaca sheet pertama saja.</p>
                    @error('sheet') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-start gap-3 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                    <input wire:model="replaceExisting" type="checkbox" class="mt-1 rounded border-amber-300 text-emerald-700">
                    <span>
                        <span class="block font-semibold">Ganti bank soal paket ini dulu</span>
                        Pakai opsi ini kalau soal lama memang ingin dibersihkan lebih dulu. Demi keamanan, sistem akan menolak opsi ini jika paket tersebut sudah punya data pengerjaan siswa.
                    </span>
                </label>
                @error('replaceExisting') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <button class="premium-button rounded-md px-4 py-3 text-sm font-semibold text-white transition hover:brightness-105">Import soal ke paket ini</button>
            </div>

            @if ($lastImportSummary !== [])
                <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ([
                        ['label' => 'Pilihan ganda', 'key' => 'multiple_choice'],
                        ['label' => 'PG kompleks', 'key' => 'multiple_choice_complex'],
                        ['label' => 'Benar / Salah', 'key' => 'true_false'],
                        ['label' => 'Matriks', 'key' => 'true_false_group'],
                        ['label' => 'Esai', 'key' => 'essay'],
                    ] as $item)
                        <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4">
                            <p class="text-sm text-zinc-500">{{ $item['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold text-zinc-950">{{ $lastImportSummary['types'][$item['key']] ?? 0 }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </form>

        <div class="space-y-6">
            <section class="surface rounded-md p-6">
                <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-700">Cara Pakai</p>
                <div class="mt-4 grid gap-4">
                    @foreach ([
                        '1. Download template Excel agar nama kolom tetap sesuai format TIM MBC.',
                        '2. Pilih satu paket ujian tujuan sebelum upload file.',
                        '3. Isi satu baris untuk satu soal. Jika ada stimulus bersama, pakai stimulus_key yang sama di beberapa baris.',
                        '4. Cek penulisan type dan correct_answer, lalu simpan file sebagai XLSX atau CSV.',
                        '5. Upload file, lalu klik Import soal ke paket ini.',
                        '6. Setelah berhasil, buka menu Soal untuk cek urutan, stimulus, dan pembahasan yang ikut masuk.',
                    ] as $step)
                        <div class="rounded-md border border-zinc-200 bg-white/80 p-4 text-sm leading-6 text-zinc-700">{{ $step }}</div>
                    @endforeach
                </div>
            </section>

            <section class="surface rounded-md p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-700">Jenis Soal</p>
                        <h3 class="mt-2 text-xl font-semibold tracking-tight text-zinc-950">Format yang bisa langsung dipakai dari spreadsheet.</h3>
                    </div>
                    <div class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-900">Sesuai engine ujian TIM MBC</div>
                </div>

                <div class="mt-4 grid gap-4">
                    @foreach ($typeExamples as $item)
                        <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <h4 class="font-semibold text-zinc-950">{{ $item['label'] }}</h4>
                                <span class="rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-600">{{ $item['type'] }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $item['format'] }}</p>
                            <div class="mt-3 rounded-md border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-600">
                                <span class="font-semibold text-zinc-800">Contoh isian:</span> {{ $item['sample'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </section>

    <section class="surface rounded-md p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-700">Daftar Kolom</p>
                <h3 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950">Pegangan cepat saat isi template.</h3>
            </div>
            <div class="rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900">
                Untuk soal bergambar, upload teks dulu lewat Excel, lalu lengkapi gambar di menu Soal jika memang perlu.
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-md border border-zinc-200">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-100 text-left text-zinc-700">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Kolom</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Cara pakai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white">
                    @foreach ($columns as $column)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-zinc-800">{{ $column['name'] }}</td>
                            <td class="px-4 py-3 text-zinc-600">{{ $column['required'] }}</td>
                            <td class="px-4 py-3 leading-6 text-zinc-600">{{ $column['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
