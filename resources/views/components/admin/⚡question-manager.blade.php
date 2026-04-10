<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Stimulus;

new class extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;
    public ?int $exam_id = null;
    public ?int $stimulus_id = null;
    public int $order_number = 1;
    public string $type = 'multiple_choice';
    public string $question_text = '';
    public string $answer_key = 'A';
    public int $score_weight = 1;
    public string $explanation = '';
    public $questionImage;
    public ?string $existingQuestionImage = null;
    public array $optionTexts = ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''];
    public array $optionImages = [];
    public array $existingOptionImages = [];

    public string $stimulusTitle = '';
    public string $stimulusType = 'text';
    public string $stimulusContent = '';
    public $stimulusFile;

    public function mount(): void
    {
        $this->exam_id = Exam::latest()->value('id');
        $this->order_number = (Question::where('exam_id', $this->exam_id)->max('order_number') ?? 0) + 1;
    }

    public function updatedExamId(): void
    {
        $this->stimulus_id = null;
        $this->order_number = (Question::where('exam_id', $this->exam_id)->max('order_number') ?? 0) + 1;
    }

    public function saveStimulus(): void
    {
        $data = $this->validate([
            'exam_id' => ['required', 'exists:exams,id'],
            'stimulusTitle' => ['required', 'string', 'max:255'],
            'stimulusType' => ['required', 'in:text,image,mixed'],
            'stimulusContent' => ['nullable', 'string'],
            'stimulusFile' => ['nullable', 'image', 'max:2048'],
        ]);

        $path = $this->stimulusFile ? $this->stimulusFile->store('stimuli', 'public') : null;

        Stimulus::create([
            'exam_id' => $data['exam_id'],
            'title' => $data['stimulusTitle'],
            'type' => $data['stimulusType'],
            'content' => $data['stimulusContent'],
            'file_path' => $path,
        ]);

        $this->reset(['stimulusTitle', 'stimulusContent', 'stimulusFile']);
        $this->stimulusType = 'text';
        session()->flash('message', 'Stimulus tersimpan.');
    }

    public function saveQuestion(): void
    {
        $data = $this->validate([
            'exam_id' => ['required', 'exists:exams,id'],
            'stimulus_id' => ['nullable', 'exists:stimuli,id'],
            'order_number' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:multiple_choice,essay'],
            'question_text' => ['required', 'string'],
            'answer_key' => ['nullable', 'in:A,B,C,D,E'],
            'score_weight' => ['required', 'integer', 'min:1'],
            'explanation' => ['nullable', 'string'],
            'questionImage' => ['nullable', 'image', 'max:2048'],
        ]);

        $payload = [
            'exam_id' => $data['exam_id'],
            'stimulus_id' => $data['stimulus_id'],
            'order_number' => $data['order_number'],
            'type' => $data['type'],
            'question_text' => $data['question_text'],
            'answer_key' => $data['type'] === 'multiple_choice' ? $data['answer_key'] : null,
            'score_weight' => $data['score_weight'],
            'explanation' => $data['explanation'],
            'is_active' => true,
        ];

        if ($this->questionImage) {
            $payload['image_path'] = $this->questionImage->store('questions', 'public');
        } elseif ($this->editingId && $this->existingQuestionImage) {
            $payload['image_path'] = $this->existingQuestionImage;
        }

        $question = Question::updateOrCreate(['id' => $this->editingId], $payload);
        $question->options()->delete();

        if ($question->type === 'multiple_choice') {
            foreach (['A', 'B', 'C', 'D', 'E'] as $index => $label) {
                $text = trim($this->optionTexts[$label] ?? '');
                $image = $this->optionImages[$label] ?? null;

                if ($text === '' && ! $image) {
                    continue;
                }

                $question->options()->create([
                    'label' => $label,
                    'option_text' => $text ?: null,
                    'image_path' => $image ? $image->store('options', 'public') : ($this->existingOptionImages[$label] ?? null),
                    'is_correct' => $this->answer_key === $label,
                    'order_number' => $index + 1,
                ]);
            }
        }

        $this->resetQuestionForm();
        session()->flash('message', 'Soal tersimpan.');
    }

    public function editQuestion(int $id): void
    {
        $question = Question::with('options')->findOrFail($id);
        $this->editingId = $question->id;
        $this->exam_id = $question->exam_id;
        $this->stimulus_id = $question->stimulus_id;
        $this->order_number = $question->order_number;
        $this->type = $question->type;
        $this->question_text = $question->question_text;
        $this->answer_key = $question->answer_key ?: 'A';
        $this->score_weight = $question->score_weight;
        $this->explanation = (string) $question->explanation;
        $this->existingQuestionImage = $question->image_path;
        $this->optionTexts = ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''];
        $this->existingOptionImages = [];

        foreach ($question->options as $option) {
            $this->optionTexts[$option->label] = (string) $option->option_text;
            $this->existingOptionImages[$option->label] = $option->image_path;
        }

        $this->dispatch('question-editing');
    }

    public function deleteQuestion(int $id): void
    {
        Question::findOrFail($id)->delete();
    }

    public function resetQuestionForm(): void
    {
        $this->reset(['editingId', 'stimulus_id', 'question_text', 'explanation', 'questionImage', 'existingQuestionImage', 'optionImages', 'existingOptionImages']);
        $this->type = 'multiple_choice';
        $this->answer_key = 'A';
        $this->score_weight = 1;
        $this->optionTexts = ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''];
        $this->order_number = (Question::where('exam_id', $this->exam_id)->max('order_number') ?? 0) + 1;
    }

    public function with(): array
    {
        return [
            'exams' => Exam::latest()->get(),
            'stimuli' => Stimulus::where('exam_id', $this->exam_id)->latest()->get(),
            'questions' => Question::with(['options', 'stimulus'])->where('exam_id', $this->exam_id)->orderBy('order_number')->get(),
        ];
    }
};
?>

<div class="space-y-6">
    @if (session('message'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('message') }}</div>
    @endif

    <div class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="text-sm font-medium text-zinc-800">Paket yang sedang disiapkan TIM MBC</label>
                <select wire:model.live="exam_id" class="mt-2 w-full rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                    @foreach ($exams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4">
                <p class="text-sm text-zinc-500">Total soal</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-950">{{ $questions->count() }}</p>
            </div>
            <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4">
                <p class="text-sm text-zinc-500">Stimulus</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-950">{{ $stimuli->count() }}</p>
            </div>
        </div>
    </div>

    @if ($exam_id)
        <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <div class="space-y-6">
                <form wire:submit="saveStimulus" class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-zinc-950">Materi bersama / stimulus</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-600">Bagian ini dipakai kalau TIM MBC punya satu bacaan, gambar, tabel, grafik, atau denah untuk beberapa soal sekaligus. Kalau soalnya berdiri sendiri, lewati bagian ini dan pilih <span class="font-medium text-zinc-950">Tanpa stimulus</span>.</p>
                    <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm leading-6 text-emerald-900">
                        Contoh: satu bacaan Bahasa Indonesia untuk soal nomor 1-5, satu gambar rantai makanan untuk beberapa soal IPA, atau satu denah taman untuk beberapa soal Matematika.
                    </div>
                    <div class="mt-4 grid gap-4">
                        <input wire:model="stimulusTitle" placeholder="Judul stimulus" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                        <select wire:model="stimulusType" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                            <option value="text">Bacaan teks</option><option value="image">Gambar</option><option value="mixed">Teks + gambar</option>
                        </select>
                        <textarea wire:model="stimulusContent" rows="4" placeholder="Isi bacaan atau keterangan" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm"></textarea>
                        <input wire:model="stimulusFile" type="file" accept="image/*" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                        <button class="rounded-md bg-zinc-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-zinc-800">Simpan stimulus</button>
                    </div>
                </form>

                <form
                    wire:submit="saveQuestion"
                    x-data
                    @question-editing.window="$el.scrollIntoView({ behavior: 'smooth', block: 'start' }); setTimeout(() => $refs.questionText?.focus(), 450)"
                    class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-zinc-950">{{ $editingId ? 'Edit soal' : 'Tulis soal baru' }}</h2>
                        @if ($editingId)
                            <button type="button" wire:click="resetQuestionForm" class="rounded-md border border-zinc-200 px-3 py-2 text-sm font-medium">Batal</button>
                        @endif
                    </div>
                    <div class="mt-4 grid gap-4">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <input wire:model="order_number" type="number" min="1" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                            <select wire:model.live="type" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                                <option value="multiple_choice">Pilihan ganda</option>
                                <option value="essay">Esai</option>
                            </select>
                            <input wire:model="score_weight" type="number" min="1" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                        </div>
                        <select wire:model="stimulus_id" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                            <option value="">Tanpa stimulus</option>
                            @foreach ($stimuli as $stimulus)
                                <option value="{{ $stimulus->id }}">{{ $stimulus->title }}</option>
                            @endforeach
                        </select>
                        <textarea x-ref="questionText" wire:model="question_text" rows="4" placeholder="Tulis pertanyaan" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm"></textarea>
                        @if ($existingQuestionImage)
                            <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">Preview gambar soal saat ini</p>
                                <img src="{{ Storage::url($existingQuestionImage) }}" class="mt-3 max-h-48 rounded-md border border-emerald-200 bg-white object-contain">
                            </div>
                        @endif
                        <input wire:model="questionImage" type="file" accept="image/*" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                        @if ($existingQuestionImage)
                            <p class="text-xs text-zinc-500">Upload gambar baru hanya jika ingin mengganti gambar soal saat ini.</p>
                        @endif

                        @if ($type === 'multiple_choice')
                            <div class="space-y-3">
                                @foreach (['A','B','C','D','E'] as $label)
                                    <div class="grid gap-2 rounded-md border border-zinc-200 p-3">
                                        <div class="flex items-center gap-2">
                                            <input wire:model="answer_key" value="{{ $label }}" type="radio" class="text-emerald-700">
                                            <span class="w-6 text-sm font-semibold text-zinc-800">{{ $label }}</span>
                                            <input wire:model="optionTexts.{{ $label }}" placeholder="Teks opsi {{ $label }}" class="flex-1 rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                                        </div>
                                        @if ($existingOptionImages[$label] ?? null)
                                            <div class="rounded-md border border-zinc-200 bg-zinc-50 p-3">
                                                <p class="text-xs font-medium text-zinc-500">Preview gambar opsi {{ $label }} saat ini</p>
                                                <img src="{{ Storage::url($existingOptionImages[$label]) }}" class="mt-2 max-h-28 rounded-md border border-zinc-200 bg-white object-contain">
                                            </div>
                                        @endif
                                        <input wire:model="optionImages.{{ $label }}" type="file" accept="image/*" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm">
                                        @if ($existingOptionImages[$label] ?? null)
                                            <p class="text-xs text-zinc-500">Upload gambar baru hanya jika ingin mengganti gambar opsi {{ $label }}.</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <textarea wire:model="explanation" rows="2" placeholder="Pembahasan opsional" class="rounded-md border border-zinc-200 px-3 py-2 text-sm shadow-sm"></textarea>
                        <button class="rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan soal</button>
                    </div>
                </form>
            </div>

            <div class="rounded-md border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 p-5">
                    <h2 class="text-base font-semibold text-zinc-950">Daftar soal</h2>
                    <p class="mt-1 text-sm text-zinc-500">TIM MBC bisa membuat pilihan ganda, esai, soal bergambar, opsi bergambar, dan stimulus.</p>
                </div>
                <div class="divide-y divide-zinc-100">
                    @forelse ($questions as $question)
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700">No. {{ $question->order_number }}</span>
                                        <span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-800">{{ $question->type === 'essay' ? 'Esai' : 'Pilihan ganda' }}</span>
                                        @if ($question->stimulus)
                                            <span class="rounded-md bg-sky-50 px-2 py-1 text-xs font-medium text-sky-800">{{ $question->stimulus->title }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-3 text-sm font-medium leading-6 text-zinc-950">{{ $question->question_text }}</p>
                                    @if ($question->image_path)
                                        <img src="{{ Storage::url($question->image_path) }}" class="mt-3 max-h-40 rounded-md border border-zinc-200 object-contain">
                                    @endif
                                    @if ($question->stimulus)
                                        <div class="mt-3 rounded-md border border-emerald-100 bg-emerald-50/70 p-3 text-sm leading-6 text-zinc-700">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Stimulus dari TIM MBC</p>
                                            <p class="mt-1 font-semibold text-emerald-950">{{ $question->stimulus->title }}</p>
                                            @if ($question->stimulus->content)
                                                <p class="mt-2 whitespace-pre-line">{{ $question->stimulus->content }}</p>
                                            @endif
                                            @if ($question->stimulus->file_path)
                                                <img src="{{ Storage::url($question->stimulus->file_path) }}" class="mt-2 max-h-36 rounded-md border border-emerald-100 bg-white object-contain" alt="Gambar stimulus">
                                            @endif
                                            @if ($question->stimulus->caption)
                                                <p class="mt-2 text-xs text-emerald-800">{{ $question->stimulus->caption }}</p>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($question->isMultipleChoice())
                                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                            @foreach ($question->options as $option)
                                                <div class="rounded-md border border-zinc-200 p-3 text-sm {{ $option->is_correct ? 'bg-emerald-50 text-emerald-900' : 'text-zinc-600' }}">
                                                    <span class="font-semibold">{{ $option->label }}.</span> {{ $option->option_text }}
                                                    @if ($option->image_path)
                                                        <img src="{{ Storage::url($option->image_path) }}" class="mt-2 max-h-28 rounded-md border border-zinc-200 object-contain">
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <button wire:click="editQuestion({{ $question->id }})" class="rounded-md border border-zinc-200 px-3 py-2 text-sm font-medium hover:bg-zinc-50">Edit</button>
                                    <button wire:click="deleteQuestion({{ $question->id }})" wire:confirm="Hapus soal ini?" class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Hapus</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-10 text-center text-sm text-zinc-500">Belum ada soal pada paket ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <div class="rounded-md border border-dashed border-zinc-300 bg-white p-10 text-center text-sm text-zinc-500">Buat paket ujian terlebih dahulu.</div>
    @endif
</div>
