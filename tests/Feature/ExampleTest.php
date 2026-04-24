<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamToken;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        foreach (['admin.dashboard', 'admin.exams', 'admin.questions', 'admin.tokens', 'admin.results', 'admin.guide'] as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
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

    public function test_admin_results_can_be_searched(): void
    {
        $this->seed();
        $attempt = ExamAttempt::with(['student', 'exam'])->firstOrFail();

        Livewire::test('admin.results')
            ->set('search', $attempt->student->name)
            ->assertSee($attempt->student->name)
            ->assertSee($attempt->exam->title);
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
            ->assertSee('Bacaan/gambar bersama dari TIM MBC')
            ->assertSee($question->stimulus->title)
            ->assertSee(str($question->stimulus->content)->before("\n")->toString());
    }

    public function test_exam_room_scores_true_false_question(): void
    {
        $this->seed();

        $exam = Exam::with('questions.options')->where('status', 'active')->firstOrFail();
        $question = $exam->questions->firstWhere('type', 'true_false');
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

        Livewire::test('student.exam-room', ['attempt' => $attempt])
            ->set('answers.'.$question->id, $selectedIds)
            ->call('saveAnswer', $question->id);

        $answer = $attempt->answers()->where('question_id', $question->id)->firstOrFail();

        $this->assertSame($selectedIds, $answer->answer_payload);
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
