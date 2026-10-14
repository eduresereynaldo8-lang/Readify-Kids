<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityResult;
use App\Models\Evaluation;
use App\Models\ReadingMaterial;
use App\Models\Student;
use App\Models\StudentBadge;
use App\Models\Teacher;
use App\Models\User;
use App\Models\VoiceRecording;
use App\Services\ReadingAssessment;
use App\Services\ReadingAssessmentMetrics;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ManualEvaluationWorkflowTest extends TestCase
{
    private User $teacherUser;

    private Teacher $teacher;

    private Student $student;

    private VoiceRecording $recording;

    protected function setUp(): void
    {
        parent::setUp();
        // These tests NEVER run migrations or connect to the project's database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->createIsolatedSchema();
        $this->teacherUser = User::create(['username' => 'test_teacher', 'email' => 'teacher@example.test',
            'password' => 'unused', 'role' => 'teacher', 'status' => 'active']);
        $this->teacher = Teacher::create(['user_id' => $this->teacherUser->id,
            'firstname' => 'Test', 'lastname' => 'Teacher', 'school_name' => 'Test School']);
        $user = User::create(['username' => 'test_student', 'email' => 'student@example.test',
            'password' => 'unused', 'role' => 'student', 'status' => 'active']);
        $this->student = Student::create(['user_id' => $user->id, 'teacher_id' => $this->teacher->id,
            'student_number' => 'TEST-001', 'firstname' => 'Test', 'lastname' => 'Learner',
            'section' => 'Test Class', 'current_level' => 1, 'total_points' => 0]);
        $material = ReadingMaterial::create(['teacher_id' => $this->teacher->id,
            'title' => 'Title excluded from word count', 'content' => str_repeat('word ', 65)]);
        $activity = Activity::create(['teacher_id' => $this->teacher->id, 'reading_material_id' => $material->id,
            'activity_name' => 'Test Reading', 'activity_type' => 'Read Aloud',
            'points_reward' => 25, 'battle_mode' => false, 'level' => 1, 'is_published' => true]);
        $this->recording = VoiceRecording::create(['student_id' => $this->student->id,
            'activity_id' => $activity->id, 'recording_path' => 'recordings/test.webm',
            'attempt_number' => 1, 'status' => 'pending']);
        $this->actingAs($this->teacherUser);
    }

    public function test_first_save_then_repeated_edits_are_atomic_and_reward_only_once(): void
    {
        DB::table('badges')->insert(['name' => 'First Score', 'criteria' => 'points_100']);
        $this->student->update(['total_points' => 90]);
        $this->post(route('teacher.evaluations.store'), $this->input())->assertRedirect(route('teacher.evaluations.index'));
        $evaluation = Evaluation::firstOrFail();
        $this->assertSame('76.92', $evaluation->oral_reading_score);
        $this->assertSame('57.14', $evaluation->comprehension_percentage);
        $this->assertSame(65, $evaluation->total_words);
        $this->assertSame(101, $evaluation->total_reading_seconds);
        $this->assertSame(67.03, (float) ActivityResult::firstOrFail()->score);
        $this->assertSame('evaluated', $this->recording->fresh()->status);
        $this->assertSame(115, $this->student->fresh()->total_points);
        $this->assertSame(1, DB::table('student_badges')->count());
        $completedAt = ActivityResult::firstOrFail()->completed_at;

        $this->post(route('teacher.evaluations.store'), array_replace($this->input(), [
            'mispronunciation' => 0, 'correct_answers' => 7, 'observation_level' => 4, 'learner_experience' => 5,
        ]))->assertRedirect();
        $this->post(route('teacher.evaluations.store'), $this->input())->assertRedirect();
        $this->assertSame(1, Evaluation::count());
        $this->assertSame(1, ActivityResult::count());
        $this->assertSame($completedAt, ActivityResult::firstOrFail()->completed_at);
        $this->assertSame(115, $this->student->fresh()->total_points);
        $this->assertSame(1, DB::table('student_badges')->count());
        $this->assertSame(3, DB::table('activity_logs')->where('action', 'EVALUATE')->count());
        $badgeLog = \App\Models\ActivityLog::where('action', 'EARN_BADGE')->sole();
        $this->assertSame($this->student->user_id, $badgeLog->user_id);
        $this->assertSame('student', $badgeLog->role);
    }

    public function test_badge_failure_rolls_back_evaluation_result_recording_and_points(): void
    {
        StudentBadge::creating(function () {
            throw new \RuntimeException('Simulated badge failure');
        });
        DB::table('badges')->insert(['name' => 'Points', 'criteria' => 'points_100']);
        $this->student->update(['total_points' => 90]);
        try {
            $this->withoutExceptionHandling()->post(route('teacher.evaluations.store'), $this->input());
            $this->fail('Expected badge failure.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Simulated badge failure', $error->getMessage());
        } finally {
            StudentBadge::flushEventListeners();
        }
        $this->assertSame(0, Evaluation::count());
        $this->assertSame(0, ActivityResult::count());
        $this->assertSame('pending', $this->recording->fresh()->status);
        $this->assertSame(90, $this->student->fresh()->total_points);
    }

    public function test_foreign_student_and_foreign_activity_are_rejected_on_get_and_post(): void
    {
        $this->student->update(['teacher_id' => 999]);
        $this->get(route('teacher.evaluations.show', $this->recording->id))->assertNotFound();
        $this->post(route('teacher.evaluations.store'), $this->input())->assertNotFound();
        $this->student->update(['teacher_id' => $this->teacher->id]);
        $this->recording->activity->update(['teacher_id' => 999]);
        $this->get(route('teacher.evaluations.show', $this->recording->id))->assertNotFound();
        $this->post(route('teacher.evaluations.store'), $this->input())->assertNotFound();
        $this->assertSame(0, Evaluation::count());
        $this->assertSame(0, ActivityResult::count());
    }

    public function test_student_cannot_submit_teacher_evaluation(): void
    {
        $this->actingAs($this->student->user)
            ->post(route('teacher.evaluations.store'), $this->input())->assertForbidden();
    }

    public function test_battle_activity_cannot_use_manual_evaluation(): void
    {
        $this->recording->activity->update(['battle_mode' => true]);
        $this->post(route('teacher.evaluations.store'), $this->input())->assertNotFound();
    }

    public function test_invalid_submission_does_not_change_any_saved_state(): void
    {
        $this->postJson(route('teacher.evaluations.store'), array_replace($this->input(), [
            'mispronunciation' => -1, 'correct_answers' => 8, 'observation_level' => null, 'learner_experience' => null,
        ]))->assertUnprocessable()->assertJsonValidationErrors([
            'mispronunciation', 'correct_answers', 'observation_level', 'learner_experience',
        ]);
        $this->assertSame(0, Evaluation::count());
        $this->assertSame('pending', $this->recording->fresh()->status);
        $this->assertSame(0, $this->student->fresh()->total_points);
    }

    public function test_missing_passage_blocks_save_with_business_error(): void
    {
        $this->recording->activity->readingMaterial->update(['content' => '']);
        $this->get(route('teacher.evaluations.show', $this->recording->id))->assertOk()
            ->assertSee('no readable passage');
        $this->postJson(route('teacher.evaluations.store'), $this->input())
            ->assertUnprocessable()->assertJsonValidationErrors('total_words');
    }

    public function test_legacy_edit_keeps_original_fields_without_awarding_points(): void
    {
        $legacy = Evaluation::create(['teacher_id' => $this->teacher->id, 'recording_id' => $this->recording->id,
            'pronunciation_score' => 3, 'fluency_score' => 4, 'accuracy_score' => 5, 'comprehension_score' => 2,
            'proficiency_level' => 'Developing']);
        $this->recording->update(['status' => 'evaluated']);
        $this->get(route('teacher.evaluations.show', $this->recording->id))->assertOk()->assertSee('legacy evaluation');
        $this->post(route('teacher.evaluations.store'), $this->input())->assertRedirect();
        $this->assertSame(0, $this->student->fresh()->total_points);
        $this->assertSame(3, $legacy->fresh()->pronunciation_score);
        $this->assertSame('Developing', $legacy->fresh()->proficiency_level);
        $this->assertSame('76.92', $legacy->fresh()->oral_reading_score);
    }

    public function test_metrics_exclude_nulls_include_zeros_and_keep_recording_history_scores(): void
    {
        $this->post(route('teacher.evaluations.store'), $this->input());
        $second = $this->recording->replicate();
        $second->attempt_number = 2;
        $second->status = 'pending';
        $second->save();
        $this->post(route('teacher.evaluations.store'), array_replace($this->input(), [
            'recording_id' => $second->id, 'mispronunciation' => 65, 'correct_answers' => 0,
        ]))->assertRedirect();
        Evaluation::create(['teacher_id' => $this->teacher->id, 'recording_id' => $this->recording->id,
            'pronunciation_score' => 5, 'fluency_score' => 5, 'accuracy_score' => 5, 'comprehension_score' => 5]);
        $report = ReadingAssessmentMetrics::report(ReadingAssessmentMetrics::forTeacher($this->teacher->id));
        $this->assertSame(3, $report['total']);
        $this->assertSame(38.46, $report['averages']['oral']);
        $this->assertSame(28.57, $report['averages']['comprehension']);
        $this->assertSame(2, $report['observations'][3]);
        $this->assertSame(2, $report['experiences'][4]);
        $this->assertSame(4.0, $report['averages']['experience']);
        $this->assertSame(38.46, $report['trend'][3]['oral']);
        $this->assertNull($report['trend'][0]['oral']);
        $this->assertSame(67.03, Evaluation::oldest('id')->firstOrFail()->final_score);
        $this->assertSame(0.0, (float) ActivityResult::firstOrFail()->score);
        $this->assertSame(1, ActivityResult::count());
        $this->assertSame(50, $this->student->fresh()->total_points);
        $this->assertSame(0, ReadingAssessmentMetrics::report(ReadingAssessmentMetrics::forTeacher(999))['total']);
    }

    public function test_teacher_pages_render_empty_populated_and_legacy_history(): void
    {
        $this->get(route('teacher.dashboard'))->assertOk()->assertSee('Reading Assessment Overview');
        $this->get(route('teacher.progress'))->assertOk()->assertSee('No evaluations yet');
        $this->get(route('teacher.evaluations.show', $this->recording->id))->assertOk()
            ->assertSee('recordings/test.webm')->assertSee('name="miscues"', false);
        $this->post(route('teacher.evaluations.store'), $this->input());
        $this->get(route('teacher.dashboard'))->assertOk()->assertSee('76.92%');
        $this->get(route('teacher.progress'))->assertOk()->assertSee('67.03%')->assertSee('1m 41s');
        $this->get(route('teacher.evaluations.index'))->assertOk()->assertSee('67.03%')->assertSee('Edit evaluation');
        $this->get(route('teacher.evaluations.show', $this->recording->id))->assertOk()->assertSee('value="15"', false);

        Evaluation::query()->update(['oral_reading_score' => null, 'comprehension_percentage' => null,
            'pronunciation_score' => 3, 'fluency_score' => 4, 'accuracy_score' => 5, 'comprehension_score' => 2]);
        $this->get(route('teacher.progress'))->assertOk()->assertSee('Legacy rubric')->assertSee('70.00%');
    }

    public function test_student_progress_and_dashboard_use_new_scores_without_teacher_feedback(): void
    {
        $this->post(route('teacher.evaluations.store'), $this->input());
        $this->actingAs($this->student->user);
        $this->get(route('student.progress'))->assertOk()->assertSee('Oral Reading')
            ->assertSee('76.92%')->assertDontSee('Private test feedback');
        $this->get(route('student.dashboard'))->assertOk()->assertSee('Oral Reading')
            ->assertSee('76.92%')->assertDontSee('Private test feedback');
    }

    public function test_recording_can_be_evaluated_without_questions_and_render_its_oral_only_score(): void
    {
        $input = $this->input();
        unset($input['correct_answers'], $input['total_questions']);
        $this->post(route('teacher.evaluations.store'), $input)->assertRedirect(route('teacher.evaluations.index'));
        $evaluation = Evaluation::sole();
        $this->assertNull($evaluation->correct_answers);
        $this->assertNull($evaluation->total_questions);
        $this->assertNull($evaluation->comprehension_percentage);
        $this->assertSame(76.92, $evaluation->final_score);
        $this->assertSame(76.92, (float) ActivityResult::sole()->score);
        $this->assertSame(25, $this->student->fresh()->total_points);
        $this->assertSame('evaluated', $this->recording->fresh()->status);
        $this->assertStringContainsString('Comprehension N/A (no questions)',
            \App\Models\ActivityLog::where('action', 'EVALUATE')->sole()->description);
        $report = ReadingAssessmentMetrics::report(ReadingAssessmentMetrics::forTeacher($this->teacher->id));
        $this->assertNull($report['averages']['comprehension']);
        $this->assertSame(76.92, $report['averages']['oral']);
        $response = $this->get(route('teacher.evaluations.show', $this->recording->id))->assertOk()
            ->assertSee('Correct answers (optional)')->assertSee('Total questions (optional)');
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="(?:correct_answers|total_questions)"[^>]*\brequired\b/', $response->getContent());
        $this->get(route('teacher.evaluations.index'))->assertOk()->assertSee('76.92%');
        $this->get(route('teacher.progress'))->assertOk()->assertSee('76.92%');
        $this->actingAs($this->student->user)->get(route('student.progress'))->assertOk()->assertSee('76.92%');
    }

    public function test_clearing_comprehension_on_edit_removes_previous_scores_without_extra_rewards(): void
    {
        $this->post(route('teacher.evaluations.store'), $this->input())->assertRedirect();
        $this->post(route('teacher.evaluations.store'), array_replace($this->input(), [
            'correct_answers' => '', 'total_questions' => '',
        ]))->assertRedirect();
        $this->assertNull(Evaluation::sole()->correct_answers);
        $this->assertNull(Evaluation::sole()->total_questions);
        $this->assertNull(Evaluation::sole()->comprehension_percentage);
        $this->assertSame(76.92, (float) ActivityResult::sole()->score);
        $this->assertSame(25, $this->student->fresh()->total_points);
        $this->post(route('teacher.evaluations.store'), $this->input())->assertRedirect();
        $this->assertSame('57.14', Evaluation::sole()->comprehension_percentage);
        $this->assertSame(67.03, (float) ActivityResult::sole()->score);
        $this->assertSame(25, $this->student->fresh()->total_points);
    }

    public function test_comprehension_averages_exclude_evaluations_without_questions(): void
    {
        $this->post(route('teacher.evaluations.store'), $this->input())->assertRedirect();
        $second = $this->recording->replicate();
        $second->attempt_number = 2;
        $second->status = 'pending';
        $second->save();
        $this->post(route('teacher.evaluations.store'), array_replace($this->input(), [
            'recording_id' => $second->id, 'correct_answers' => null, 'total_questions' => null,
        ]))->assertRedirect();
        $report = ReadingAssessmentMetrics::report(ReadingAssessmentMetrics::forTeacher($this->teacher->id));
        $this->assertSame(2, $report['total']);
        $this->assertSame(57.14, $report['averages']['comprehension']);
        $this->assertSame(57.14, $report['trend'][3]['comprehension']);
    }

    public static function detailedMiscueExamples(): array
    {
        return [
            '100 words and 10 miscues' => [100, [2, 3, 4, 1, 0, 0, 0], 10, '90.00'],
            '65 words and 15 miscues' => [65, [4, 3, 3, 2, 1, 1, 1], 15, '76.92'],
            'no miscues' => [13, [0, 0, 0, 0, 0, 0, 0], 0, '100.00'],
            'more miscues than words' => [10, [2, 3, 4, 1, 1, 1, 0], 12, '0.00'],
        ];
    }

    #[DataProvider('detailedMiscueExamples')]
    public function test_detailed_miscues_are_saved_authoritatively_and_loaded_for_editing(
        int $words, array $counts, int $total, string $oral
    ): void {
        $fields = $this->breakdown($counts);
        $this->recording->activity->readingMaterial->update(['content' => str_repeat('word ', $words)]);
        $this->post(route('teacher.evaluations.store'), array_replace($this->input(), $fields, [
            'miscues' => -999, 'oral_reading_score' => 999, 'total_words' => 999,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('teacher.evaluations.index'));

        $evaluation = Evaluation::sole();
        foreach ($fields as $field => $value) {
            $this->assertSame($value, $evaluation->{$field});
        }
        $this->assertSame($total, $evaluation->miscues);
        $this->assertSame($words, $evaluation->total_words);
        $this->assertSame($oral, $evaluation->oral_reading_score);
        $this->assertSame('57.14', $evaluation->comprehension_percentage);
        $this->assertSame(round(((float) $oral + 57.14) / 2, 2), (float) ActivityResult::sole()->score);
        $this->assertFalse($evaluation->has_legacy_miscues);
        $response = $this->get(route('teacher.evaluations.show', $this->recording->id))
            ->assertOk()->assertSee('Types of Miscues')->assertSee('Miscue Breakdown');
        foreach ($fields as $field => $value) {
            $response->assertSee(ucfirst($field));
            $this->assertMatchesRegularExpression('/<input[^>]*name="'.$field.'"[^>]*min="0"[^>]*step="1"[^>]*value="'.$value.'"/', $response->getContent());
        }
        $this->assertMatchesRegularExpression('/<input[^>]*name="miscues"[^>]*readonly[^>]*value="'.$total.'"/', $response->getContent());
        $this->get(route('teacher.progress'))->assertOk()->assertSee('Miscue Breakdown')->assertSee($oral.'%');
    }

    public static function invalidMiscueFields(): array
    {
        $cases = [];
        foreach (['mispronunciation', 'omission', 'substitution', 'insertion',
            'repetition', 'transposition', 'reversal'] as $field) {
            foreach (['negative' => -1, 'fractional' => 1.5, 'text' => 'invalid', 'overflow' => 4294967296] as $kind => $value) {
                $cases[$field.' '.$kind] = [$field, $value];
            }
        }
        $cases['array input'] = ['omission', [1]];
        $cases['invalid legacy replacement flag'] = ['replace_legacy_miscues', 'invalid'];

        return $cases;
    }

    #[DataProvider('invalidMiscueFields')]
    public function test_invalid_miscue_fields_do_not_save_or_award_points(string $field, mixed $value): void
    {
        $this->postJson(route('teacher.evaluations.store'), array_replace($this->input(), [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame(0, Evaluation::count());
        $this->assertSame(0, ActivityResult::count());
        $this->assertSame('pending', $this->recording->fresh()->status);
        $this->assertSame(0, $this->student->fresh()->total_points);
    }

    public static function emptyMiscueInputs(): array
    {
        return [['omitted'], [null], [''], [' ']];
    }

    #[DataProvider('emptyMiscueInputs')]
    public function test_empty_miscue_fields_default_to_zero_and_ignore_submitted_total(mixed $value): void
    {
        $input = $this->input();
        unset($input['mispronunciation']);
        if ($value !== 'omitted') {
            $input = array_replace($input, $this->breakdown(array_fill(0, 7, $value)));
        }
        $input['miscues'] = 1234;
        $this->post(route('teacher.evaluations.store'), $input)
            ->assertSessionHasNoErrors()->assertRedirect();
        $evaluation = Evaluation::sole();
        $this->assertSame(0, $evaluation->miscues);
        $this->assertSame('100.00', $evaluation->oral_reading_score);
        foreach (array_keys($this->breakdown(array_fill(0, 7, 0))) as $field) {
            $this->assertSame(0, $evaluation->{$field});
        }
    }

    public function test_sum_overflow_is_rejected_without_a_database_error(): void
    {
        $this->postJson(route('teacher.evaluations.store'), array_replace($this->input(), [
            'mispronunciation' => 4294967295, 'omission' => 1,
        ]))->assertUnprocessable()->assertJsonValidationErrors('miscues');
        $this->assertSame(0, Evaluation::count());
        $this->assertSame(0, $this->student->fresh()->total_points);
    }

    public function test_legacy_miscues_survive_unrelated_edits_and_can_be_replaced_with_a_breakdown(): void
    {
        $this->post(route('teacher.evaluations.store'), $this->input())->assertRedirect();
        $evaluation = Evaluation::sole();
        $evaluation->update($this->breakdown(array_fill(0, 7, 0)));
        $this->assertTrue($evaluation->has_legacy_miscues);
        $response = $this->get(route('teacher.evaluations.show', $this->recording->id))
            ->assertOk()->assertSee('Legacy total miscues:')->assertSee('data-legacy-miscues="15"', false);
        $this->assertMatchesRegularExpression('/<input[^>]*name="miscues"[^>]*readonly[^>]*value="15"/', $response->getContent());
        $input = array_replace($this->input(), $this->breakdown(array_fill(0, 7, 0)), [
            'miscues' => 999, 'feedback' => 'Updated feedback only.',
        ]);
        $this->post(route('teacher.evaluations.store'), $input)->assertSessionHasNoErrors()->assertRedirect();
        $evaluation->refresh();
        $this->assertSame(15, $evaluation->miscues);
        $this->assertSame('76.92', $evaluation->oral_reading_score);
        $this->assertSame('Updated feedback only.', $evaluation->feedback);
        $this->assertSame(25, $this->student->fresh()->total_points);
        $this->get(route('teacher.progress'))->assertOk()->assertSee('Legacy total miscues:');

        // Supplying actual details works even with JavaScript disabled.
        $this->post(route('teacher.evaluations.store'), array_replace($input, $this->breakdown([2, 1, 3, 1, 0, 1, 0])))
            ->assertSessionHasNoErrors()->assertRedirect();
        $evaluation->refresh();
        $this->assertSame(8, $evaluation->miscues);
        $this->assertSame('87.69', $evaluation->oral_reading_score);
        $this->assertFalse($evaluation->has_legacy_miscues);
        $this->get(route('teacher.evaluations.show', $this->recording->id))
            ->assertOk()->assertDontSee('Legacy total miscues:');

        // A detailed evaluation can be corrected to all zeros without a legacy flag.
        $this->post(route('teacher.evaluations.store'), $input)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(0, $evaluation->fresh()->miscues);
        $this->assertSame('100.00', $evaluation->fresh()->oral_reading_score);
    }

    public function test_legacy_total_can_explicitly_be_corrected_to_zero(): void
    {
        $this->post(route('teacher.evaluations.store'), $this->input())->assertRedirect();
        $evaluation = Evaluation::sole();
        $evaluation->update($this->breakdown(array_fill(0, 7, 0)));
        $this->post(route('teacher.evaluations.store'), array_replace($this->input(), $this->breakdown(array_fill(0, 7, 0)), [
            'replace_legacy_miscues' => 1, 'miscues' => 500,
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(0, $evaluation->fresh()->miscues);
        $this->assertSame('100.00', $evaluation->fresh()->oral_reading_score);
        $this->assertSame(25, $this->student->fresh()->total_points);
    }

    public function test_validation_retry_keeps_entered_breakdown_and_legacy_replacement_choice(): void
    {
        $this->post(route('teacher.evaluations.store'), $this->input())->assertRedirect();
        $evaluation = Evaluation::sole();
        $evaluation->update($this->breakdown(array_fill(0, 7, 0)));
        $editUrl = route('teacher.evaluations.show', $this->recording->id);
        $this->from($editUrl)->post(route('teacher.evaluations.store'), array_replace($this->input(), $this->breakdown([2, 1, 3, 1, 0, 1, 0]), [
            'replace_legacy_miscues' => 1, 'observation_level' => null, 'miscues' => 999,
        ]))->assertRedirect($editUrl)->assertSessionHasErrors('observation_level');
        $response = $this->get($editUrl)->assertOk();
        $this->assertMatchesRegularExpression('/<input[^>]*name="replace_legacy_miscues"[^>]*checked/', $response->getContent());
        $this->assertMatchesRegularExpression('/<input[^>]*name="miscues"[^>]*value="8"/', $response->getContent());
        $this->assertSame(15, $evaluation->fresh()->miscues);
        $this->assertSame('76.92', $evaluation->fresh()->oral_reading_score);
    }

    public function test_read_aloud_reward_triggers_level_up_using_the_saved_final_score(): void
    {
        $this->student->update(['total_points' => 475]);
        $input = array_replace($this->input(), ['mispronunciation' => 0, 'correct_answers' => 7]);
        $this->post(route('teacher.evaluations.store'), $input)->assertRedirect();
        $this->assertSame(100.0, (float) ActivityResult::sole()->score);
        $this->assertSame(500, (int) $this->student->fresh()->total_points);
        $this->assertSame(2, (int) $this->student->fresh()->current_level);
        $this->post(route('teacher.evaluations.store'), $input)->assertRedirect();
        $this->assertSame(500, (int) $this->student->fresh()->total_points);
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'LEVEL_UP')->count());

        $this->actingAs($this->student->user)->get(route('student.dashboard'))->assertOk()
            ->assertSee('Level Up! You reached Level 2!')->assertSee('Level 2 Reading Average:')
            ->assertSee('N/A');
        $this->get(route('student.dashboard'))->assertOk()->assertDontSee('Level Up! You reached Level 2!');
        $this->get(route('student.progress'))->assertOk()->assertSee('Level 2 Reading Average:');
    }

    public function test_teacher_score_correction_can_unlock_level_without_awarding_again(): void
    {
        $this->student->update(['total_points' => 475]);
        $this->recording->activity->readingMaterial->update(['content' => str_repeat('word ', 100)]);
        // Existing final-score formula: 98% oral + 100% comprehension = 99%.
        $input = array_replace($this->input(), ['mispronunciation' => 2, 'correct_answers' => 7]);
        $this->post(route('teacher.evaluations.store'), $input)->assertRedirect();
        $this->assertSame(99.0, (float) ActivityResult::sole()->score);
        $this->assertSame(1, (int) $this->student->fresh()->current_level);
        $this->assertSame(500, (int) $this->student->fresh()->total_points);
        $this->assertSame(0, DB::table('activity_logs')->where('action', 'LEVEL_UP')->count());

        $input['mispronunciation'] = 0;
        $this->post(route('teacher.evaluations.store'), $input)->assertRedirect();
        $this->assertSame(100.0, (float) ActivityResult::sole()->score);
        $this->assertSame(2, (int) $this->student->fresh()->current_level);
        $this->assertSame(500, (int) $this->student->fresh()->total_points);
        $this->assertSame(1, ActivityResult::count());
        $this->post(route('teacher.evaluations.store'), $input)->assertRedirect();
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'LEVEL_UP')->count());
    }

    private function breakdown(array $values): array
    {
        return array_combine(['mispronunciation', 'omission', 'substitution', 'insertion',
            'repetition', 'transposition', 'reversal'], $values);
    }

    private function input(): array
    {
        return ['recording_id' => $this->recording->id, 'reading_minutes' => 1,
            'reading_seconds' => 41, 'mispronunciation' => 15, 'correct_answers' => 4, 'total_questions' => 7,
            'observation_level' => 3, 'learner_experience' => 4, 'feedback' => 'Private test feedback',
            'total_words' => 1, 'oral_reading_score' => 100, 'comprehension_percentage' => 100];
    }

    private function createIsolatedSchema(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('username');
            $t->string('email');
            $t->string('password');
            $t->string('role');
            $t->string('status');
            $t->timestamps();
        });
        Schema::create('teachers', function (Blueprint $t) {
            $t->id();
            $t->integer('user_id');
            $t->string('firstname');
            $t->string('lastname');
            $t->string('school_name')->nullable();
            $t->timestamps();
        });
        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->integer('user_id');
            $t->integer('teacher_id');
            $t->string('student_number');
            $t->string('firstname');
            $t->string('lastname');
            $t->string('section');
            $t->integer('current_level');
            $t->integer('total_points');
            $t->integer('day_streak')->default(0);
            $t->date('last_active')->nullable();
            $t->timestamps();
        });
        Schema::create('reading_materials', function (Blueprint $t) {
            $t->id();
            $t->integer('teacher_id');
            $t->string('title');
            $t->text('content')->nullable();
            $t->timestamps();
        });
        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->integer('teacher_id');
            $t->integer('reading_material_id')->nullable();
            $t->string('activity_name');
            $t->string('activity_type');
            $t->integer('points_reward');
            $t->boolean('battle_mode')->default(false);
            $t->boolean('is_published')->default(true);
            $t->integer('level')->default(1);
            $t->integer('duration_minutes')->default(2);
            $t->timestamps();
        });
        Schema::create('voice_recordings', function (Blueprint $t) {
            $t->id();
            $t->integer('student_id');
            $t->integer('activity_id');
            $t->string('recording_path');
            $t->integer('attempt_number');
            $t->string('status');
            $t->timestamps();
        });
        Schema::create('evaluations', function (Blueprint $t) {
            $t->id();
            $t->integer('teacher_id');
            $t->integer('recording_id');
            foreach (['pronunciation_score', 'fluency_score', 'accuracy_score', 'comprehension_score',
                'reading_minutes', 'reading_seconds', 'total_reading_seconds', 'total_words', 'miscues',
                'correct_answers', 'total_questions', 'observation_level', 'learner_experience'] as $column) {
                $t->integer($column)->nullable();
            }
            foreach (array_keys(ReadingAssessment::MISCUE_TYPES) as $column) {
                $t->unsignedInteger($column)->default(0);
            }
            $t->decimal('oral_reading_score', 5, 2)->nullable();
            $t->decimal('comprehension_percentage', 5, 2)->nullable();
            $t->string('proficiency_level')->nullable();
            $t->text('feedback')->nullable();
            $t->timestamps();
        });
        Schema::create('activity_results', function (Blueprint $t) {
            $t->id();
            $t->integer('student_id');
            $t->integer('activity_id');
            $t->decimal('score', 5, 2)->nullable();
            $t->string('status');
            $t->dateTime('completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('badges', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('criteria')->nullable();
            $t->timestamps();
        });
        Schema::create('student_badges', function (Blueprint $t) {
            $t->id();
            $t->integer('student_id');
            $t->integer('badge_id');
            $t->dateTime('earned_at');
            $t->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->integer('user_id');
            $t->string('role');
            $t->string('action');
            $t->string('module');
            $t->text('description');
            $t->string('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamps();
        });
    }
}
