<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\ExamToken;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentAnswer;
use App\Models\User;
use App\Support\ExamScoring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('student.token'));
    }

    public function test_admin_pages_render_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        foreach (['admin.dashboard', 'admin.exams', 'admin.questions', 'admin.questions.import', 'admin.tokens', 'admin.results', 'admin.guide'] as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
    }

    public function test_admin_can_download_question_import_template(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.questions.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('content-disposition', 'attachment; filename=template-import-soal-mbc.xlsx');
    }

    public function test_admin_can_download_question_import_sample(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.questions.import.sample'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename=contoh-import-soal-ips-sd-10.csv');
        $this->assertStringContainsString('multiple_choice', $response->streamedContent());
    }

    public function test_admin_result_detail_can_be_opened(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $attempt = ExamAttempt::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.results.show', $attempt))
            ->assertOk()
            ->assertSee('Detail jawaban per soal')
            ->assertSee('Jawaban siswa')
            ->assertSee('Kunci');
    }

    public function test_admin_can_download_result_pdf(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $attempt = ExamAttempt::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('admin.results.pdf', $attempt));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    public function test_admin_results_can_be_searched(): void
    {
        $this->seed();
        $attempt = ExamAttempt::with(['student', 'exam'])->firstOrFail();

        Livewire::test('admin.results')
            ->set('search', $attempt->student->name)
            ->assertSee($attempt->student->name)
            ->assertSee($attempt->exam->title);
    }

    public function test_admin_can_create_statement_matrix_question(): void
    {
        $exam = Exam::create([
            'title' => 'Paket Matrix Test',
            'level' => 'SD',
            'duration_minutes' => 90,
            'status' => 'active',
        ]);

        Livewire::test('admin.question-manager')
            ->set('exam_id', $exam->id)
            ->set('order_number', 1)
            ->set('type', 'true_false_group')
            ->set('question_text', 'Tentukan Tepat atau Tidak Tepat untuk setiap pernyataan berikut.')
            ->set('score_weight', 10)
            ->set('statementPositiveLabel', 'Tepat')
            ->set('statementNegativeLabel', 'Tidak Tepat')
            ->set('statementRows', [
                ['text' => 'Kalimat pertama sesuai konteks.', 'is_correct' => '1'],
                ['text' => 'Kalimat kedua bertentangan dengan bacaan.', 'is_correct' => '0'],
            ])
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $question = Question::with('options')->where('exam_id', $exam->id)->firstOrFail();

        $this->assertSame('true_false_group', $question->type);
        $this->assertSame(
            ['positive' => 'Tepat', 'negative' => 'Tidak Tepat'],
            $question->statementTruthLabels(),
        );
        $this->assertCount(2, $question->options);
        $this->assertSame('Kalimat pertama sesuai konteks.', $question->options[0]->option_text);
        $this->assertTrue($question->options[0]->is_correct);
        $this->assertFalse($question->options[1]->is_correct);
    }

    public function test_admin_can_import_questions_from_spreadsheet(): void
    {
        $exam = Exam::create([
            'title' => 'Paket Import Test',
            'level' => 'SD',
            'duration_minutes' => 90,
            'status' => 'active',
        ]);

        $csv = <<<'CSV'
order_number,type,question_text,score_weight,explanation,stimulus_key,stimulus_title,stimulus_type,stimulus_content,option_a,option_b,option_c,option_d,option_e,correct_answer,statement_positive_label,statement_negative_label,statement_rows,is_active
1,multiple_choice,Ide pokok bacaan adalah ...,10,Fokus pada gagasan utama,BAC01,Bacaan 1,text,Bacalah teks berikut,A benar,B salah,C salah,,,A,,,,1
2,multiple_choice_complex,Pilih semua pernyataan yang sesuai.,10,Boleh lebih dari satu jawaban benar,BAC01,Bacaan 1,text,Bacalah teks berikut,Pernyataan 1,Pernyataan 2,Pernyataan 3,,,A|B,,,,1
3,true_false,Kalimat tersebut efektif.,5,Periksa struktur kalimat,,,,,,,,,,Benar,,,,1
4,true_false_group,Tentukan Tepat atau Tidak Tepat.,15,Setiap baris dipilih satu,,,,,,,,,,,Tepat,Tidak Tepat,"Kalimat pertama::1 | Kalimat kedua::0",1
5,essay,Jelaskan manfaat gotong royong.,20,Dinilai admin,,,,,,,,,,,,,,1
CSV;

        $file = UploadedFile::fake()->createWithContent('template-soal.csv', $csv);

        Livewire::test('admin.question-import')
            ->set('exam_id', $exam->id)
            ->set('replaceExisting', true)
            ->set('sheet', $file)
            ->call('import')
            ->assertHasNoErrors();

        $exam->refresh();
        $questions = $exam->questions()->with('options', 'stimulus')->orderBy('order_number')->get();

        $this->assertCount(5, $questions);
        $this->assertSame('multiple_choice', $questions[0]->type);
        $this->assertSame('multiple_choice_complex', $questions[1]->type);
        $this->assertSame('true_false', $questions[2]->type);
        $this->assertSame('true_false_group', $questions[3]->type);
        $this->assertSame('essay', $questions[4]->type);
        $this->assertSame('Bacaan 1', $questions[0]->stimulus?->title);
        $this->assertSame('Tepat', $questions[3]->statementTruthLabels()['positive']);
        $this->assertCount(2, $questions[3]->options);
        $this->assertTrue($questions[3]->options[0]->is_correct);
        $this->assertFalse($questions[3]->options[1]->is_correct);
    }

    public function test_seeder_clears_old_attempt_data_when_question_bank_is_reimported(): void
    {
        $this->seed();

        $exam = Exam::firstOrFail();
        $student = Student::create(['name' => 'Legacy Attempt']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'token' => 'LEGACY1',
            'status' => 'finished',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now()->subHour(),
            'finished_at' => now(),
            'status' => 'finished',
        ]);

        StudentAnswer::create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $exam->questions()->firstOrFail()->id,
            'answer_text' => 'Jawaban lama',
        ]);

        ExamResult::create([
            'exam_attempt_id' => $attempt->id,
            'correct_count' => 1,
            'wrong_count' => 0,
            'blank_count' => 0,
            'multiple_choice_score' => 10,
            'essay_score' => 0,
            'total_score' => 10,
        ]);

        $this->seed();

        $this->assertDatabaseMissing('exam_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseMissing('student_answers', ['exam_attempt_id' => $attempt->id]);
        $this->assertDatabaseMissing('exam_results', ['exam_attempt_id' => $attempt->id]);
        $this->assertDatabaseMissing('exam_tokens', ['token' => 'LEGACY1']);
    }

    public function test_exam_room_saves_selected_non_a_option_when_moving_question(): void
    {
        $this->seed();

        $exam = Exam::with('questions.options')->where('status', 'active')->first();
        $student = Student::create(['name' => 'Flow Tester']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'token' => 'TEMP-FLOW-TEST',
            'status' => 'in_progress',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $question = $exam->questions->firstWhere('type', 'multiple_choice');
        $nonAOption = $question->options->firstWhere('label', 'B');

        Livewire::test('student.exam-room', ['attempt' => $attempt])
            ->set('answers.'.$question->id, $nonAOption->id)
            ->call('next');

        $this->assertDatabaseHas('student_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $nonAOption->id,
        ]);

        $this->assertDatabaseMissing('student_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $question->options->firstWhere('label', 'A')->id,
        ]);
    }

    public function test_exam_room_batches_answer_writes_until_autosave_or_navigation(): void
    {
        $this->seed();

        $exam = Exam::with('questions.options')->where('status', 'active')->firstOrFail();
        $student = Student::create(['name' => 'Batched Saver']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'token' => 'BATCHED1',
            'status' => 'in_progress',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $question = $exam->questions->firstWhere('type', 'multiple_choice');
        $option = $question->options->firstWhere('label', 'B');

        Livewire::test('student.exam-room', ['attempt' => $attempt])
            ->set('answers.'.$question->id, $option->id)
            ->assertSet('dirtyQuestionIds.'.$question->id, true);

        $this->assertDatabaseMissing('student_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);

        Livewire::test('student.exam-room', ['attempt' => $attempt->fresh()])
            ->set('answers.'.$question->id, $option->id)
            ->call('autosaveDirtyAnswers');

        $this->assertDatabaseHas('student_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
        ]);
    }

    public function test_exam_room_blocks_manual_submit_when_answers_are_blank(): void
    {
        $this->seed();

        $exam = Exam::with('questions.options')->where('status', 'active')->first();
        $student = Student::create(['name' => 'Incomplete Tester']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'token' => 'TEMP-INCOMPLETE-TEST',
            'status' => 'in_progress',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $question = $exam->questions->firstWhere('type', 'multiple_choice');
        $option = $question->options->first();

        Livewire::test('student.exam-room', ['attempt' => $attempt])
            ->set('answers.'.$question->id, $option->id)
            ->call('finish')
            ->assertSee('Masih ada jawaban kosong');

        $this->assertDatabaseHas('exam_attempts', [
            'id' => $attempt->id,
            'status' => 'in_progress',
            'finished_at' => null,
        ]);
    }

    public function test_exam_room_warns_once_then_auto_submits_on_second_focus_violation(): void
    {
        $this->seed();

        $exam = Exam::with('questions.options')->where('title', 'TKA SD Bahasa Indonesia - Paket B')->firstOrFail();
        $student = Student::create(['name' => 'Focus Tester']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'token' => 'FOCUS1',
            'status' => 'in_progress',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $component = Livewire::test('student.exam-room', ['attempt' => $attempt]);

        $component->call('registerFocusViolation')
            ->assertSet('focusWarning', 'Peringatan 1 dari 1: ruang ujian terdeteksi ditinggalkan. TIM MBC menyarankan tetap di halaman ujian. Jika kamu berpindah tab, jendela, atau aplikasi sekali lagi, ujian akan langsung dikumpulkan otomatis.');

        $this->assertDatabaseHas('exam_attempts', [
            'id' => $attempt->id,
            'status' => 'in_progress',
            'focus_violation_count' => 1,
        ]);

        $component->call('registerFocusViolation');

        $attempt->refresh();

        $this->assertSame('finished', $attempt->status);
        $this->assertNotNull($attempt->finished_at);
        $this->assertSame(2, $attempt->focus_violation_count);
    }

    public function test_exam_room_shows_stimulus_content_for_linked_question(): void
    {
        $this->seed();

        $exam = Exam::with('questions.stimulus')->where('status', 'active')->first();
        $question = $exam->questions->first(fn (Question $question) => $question->stimulus_id !== null);
        $student = Student::create(['name' => 'Stimulus Tester']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'token' => 'TEMP-STIMULUS-TEST',
            'status' => 'in_progress',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        Livewire::test('student.exam-room', ['attempt' => $attempt])
            ->assertSee('Perhatian sebelum mulai')
            ->assertSee('Mulai mengerjakan')
            ->assertSee('Bacaan/gambar bersama dari TIM MBC')
            ->assertSee($question->stimulus->title)
            ->assertSee(str($question->stimulus->content)->before("\n")->toString());
    }

    public function test_indonesian_question_six_uses_exact_tepat_tidak_tepat_labels_from_pdf(): void
    {
        $this->seed();

        $exam = Exam::with('questions.options')->where('title', 'TKA SD Bahasa Indonesia - Paket B')->firstOrFail();
        $question = $exam->questions->firstWhere('order_number', 6);

        $this->assertSame('true_false_group', $question->type);
        $this->assertSame(
            ['positive' => 'Tepat', 'negative' => 'Tidak Tepat'],
            $question->statementTruthLabels(),
        );
        $this->assertSame('Tepat / Tidak Tepat per pernyataan', $question->typeLabel());

        $student = Student::create(['name' => 'Label Tester']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'token' => 'LBLQ06',
            'status' => 'in_progress',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        Livewire::test('student.exam-room', ['attempt' => $attempt])
            ->set('currentIndex', 5)
            ->assertSee('Soal 6')
            ->assertSee('Tepat / Tidak Tepat per pernyataan')
            ->assertSee('Tepat')
            ->assertSee('Tidak Tepat');
    }

    public function test_first_five_indonesian_answers_score_correct_with_navigation_flow(): void
    {
        $this->seed();

        $exam = Exam::with('questions.options')->where('title', 'TKA SD Bahasa Indonesia - Paket B')->firstOrFail();
        $student = Student::create(['name' => 'Flow 1-5 Tester']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'token' => 'FLOW15',
            'status' => 'in_progress',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $questions = $exam->questions->keyBy('order_number');
        $q1 = $questions->get(1);
        $q2 = $questions->get(2);
        $q3 = $questions->get(3);
        $q4 = $questions->get(4);
        $q5 = $questions->get(5);

        Livewire::test('student.exam-room', ['attempt' => $attempt])
            ->set('answers.'.$q1->id, $q1->options->firstWhere('is_correct', true)->id)
            ->call('next')
            ->set('answers.'.$q2->id, [
                $q2->options->firstWhere('label', 'A')->id => true,
                $q2->options->firstWhere('label', 'C')->id => true,
            ])
            ->call('next')
            ->set('answers.'.$q3->id, $q3->options->mapWithKeys(
                fn ($option) => [$option->id => $option->is_correct ? 'Benar' : 'Salah']
            )->all())
            ->call('next')
            ->set('answers.'.$q4->id, $q4->options->firstWhere('is_correct', true)->id)
            ->call('next')
            ->set('answers.'.$q5->id, [
                $q5->options->firstWhere('label', 'A')->id => true,
                $q5->options->firstWhere('label', 'B')->id => true,
            ])
            ->call('saveAnswer', $q5->id);

        $summary = ExamScoring::evaluateAttempt(
            $attempt->fresh()->load('exam.questions.options', 'answers')
        );

        $this->assertSame(5, $summary['correct_count']);
        $this->assertSame(0, $summary['wrong_count']);
        $this->assertSame(50, $summary['multiple_choice_score']);
    }

    public function test_exam_room_scores_true_false_question(): void
    {
        $this->seed();

        $exam = Exam::with('questions.options')->where('status', 'active')->firstOrFail();
        $student = Student::create(['name' => 'True False Tester']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'token' => 'TRUEF1',
            'status' => 'active',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $question = Question::create([
            'exam_id' => $exam->id,
            'order_number' => 999,
            'type' => 'true_false',
            'question_text' => 'Pernyataan berikut benar atau salah: 2 + 2 = 4.',
            'answer_key' => 'Benar',
            'score_weight' => 10,
            'is_active' => true,
        ]);
        $question->options()->createMany([
            ['label' => 'Benar', 'option_text' => 'Benar', 'is_correct' => true, 'order_number' => 1],
            ['label' => 'Salah', 'option_text' => 'Salah', 'is_correct' => false, 'order_number' => 2],
        ]);
        $question->load('options');

        $correctOption = $question->options->firstWhere('is_correct', true);

        Livewire::test('student.exam-room', ['attempt' => $attempt])
            ->set('answers.'.$question->id, $correctOption->id)
            ->call('finish', true)
            ->assertRedirect(route('student.result', $attempt));

        $this->assertDatabaseHas('student_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $correctOption->id,
            'is_correct' => true,
            'score' => $question->score_weight,
        ]);
    }

    public function test_exam_room_saves_multiple_choice_complex_selection_payload(): void
    {
        $this->seed();

        $exam = Exam::with('questions.options')->where('status', 'active')->firstOrFail();
        $question = $exam->questions->firstWhere('type', 'multiple_choice_complex');
        $student = Student::create(['name' => 'Complex Tester']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'token' => 'CMPLEX',
            'status' => 'active',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $selectedIds = $question->options->whereIn('label', ['A', 'C'])->pluck('id')->values()->all();
        $selectionMap = array_fill_keys($selectedIds, true);

        Livewire::test('student.exam-room', ['attempt' => $attempt])
            ->set('answers.'.$question->id, $selectionMap)
            ->call('saveAnswer', $question->id);

        $answer = $attempt->answers()->where('question_id', $question->id)->firstOrFail();

        $this->assertSame($selectedIds, $answer->answer_payload);
    }

    public function test_multiple_choice_complex_checkboxes_do_not_toggle_all_options(): void
    {
        $this->seed();

        $exam = Exam::with('questions.options')->where('status', 'active')->firstOrFail();
        $question = $exam->questions->firstWhere('type', 'multiple_choice_complex');
        $student = Student::create(['name' => 'Checkbox Tester']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'token' => 'CMPBOX',
            'status' => 'active',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $firstOption = $question->options->firstWhere('label', 'A');
        $secondOption = $question->options->firstWhere('label', 'B');

        Livewire::test('student.exam-room', ['attempt' => $attempt])
            ->set('answers.'.$question->id.'.'.$firstOption->id, true)
            ->assertSet('answers.'.$question->id.'.'.$firstOption->id, true)
            ->assertSet('answers.'.$question->id.'.'.$secondOption->id, null);
    }

    public function test_student_can_reopen_finished_result_from_token_portal(): void
    {
        $this->seed();

        $attempt = ExamAttempt::where('status', 'finished')->with(['token', 'student'])->firstOrFail();

        Livewire::test('student.token-entry')
            ->set('resultToken', $attempt->token->token)
            ->set('resultName', $attempt->student->name)
            ->set('resultPhone', $attempt->student->phone)
            ->call('checkResult')
            ->assertRedirect(route('student.result', $attempt));
    }

    public function test_student_can_resume_in_progress_exam_from_token_portal(): void
    {
        $this->seed();

        $exam = Exam::query()->where('status', 'active')->firstOrFail();
        $student = Student::create(['name' => 'Resume Tester']);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'token' => 'TEMP-RESUME-TEST',
            'status' => 'in_progress',
            'used_at' => now(),
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        Livewire::test('student.token-entry')
            ->set('resultToken', $token->token)
            ->set('resultName', $student->name)
            ->call('checkResult')
            ->assertRedirect(route('student.exam', $attempt));
    }

    public function test_student_can_resume_existing_attempt_from_start_form_with_shared_token(): void
    {
        $this->seed();

        $exam = Exam::query()->where('status', 'active')->firstOrFail();
        $student = Student::create([
            'name' => 'Shared Token Student',
            'phone' => '08123456789',
            'school' => 'SMP MBC',
            'grade' => '9',
        ]);
        $token = ExamToken::create([
            'exam_id' => $exam->id,
            'token' => 'SHARED1',
            'status' => 'active',
            'used_at' => now(),
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'exam_token_id' => $token->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        Livewire::test('student.token-entry')
            ->set('token', $token->token)
            ->set('name', $student->name)
            ->set('phone', $student->phone)
            ->set('school', $student->school)
            ->set('grade', $student->grade)
            ->call('start')
            ->assertRedirect(route('student.exam', $attempt));
    }

    public function test_admin_results_export_csv_can_be_downloaded(): void
    {
        $this->seed();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.results.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_question_edit_shows_existing_image_preview(): void
    {
        $this->seed();

        $question = Question::query()
            ->whereNotNull('image_path')
            ->firstOrFail();

        Livewire::test('admin.question-manager')
            ->call('editQuestion', $question->id)
            ->assertSet('existingQuestionImage', $question->image_path)
            ->assertSee('Preview gambar soal saat ini');
    }
}
