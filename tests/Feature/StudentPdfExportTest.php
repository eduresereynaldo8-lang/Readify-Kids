<?php

namespace Tests\Feature;

use App\Models\ActivityResult;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\StudentProgress;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentPdfExportTest extends TestCase
{
    private Teacher $teacher;
    private array $report = [];
    private string $html = '';
    private string $paper = '';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Carbon::setTestNow('2026-09-24 12:00:00');
        $this->createIsolatedSchema();
        $user = User::create(['username' => 'teacher', 'password' => 'unused', 'role' => 'teacher', 'status' => 'active']);
        $this->teacher = Teacher::create(['user_id' => $user->id, 'firstname' => 'Ada', 'lastname' => 'Teacher', 'school_name' => 'Reading School']);
        $this->actingAs($user);
        Model::preventLazyLoading();
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function statuses(): array
    {
        return [
            'no score' => [null, 'no_data'],
            'zero is a score' => [0.0, 'struggling'],
            'below fifty' => [49.999, 'struggling'],
            'exactly fifty' => [50.0, 'needs_help'],
            'below seventy-five' => [74.999, 'needs_help'],
            'exactly seventy-five' => [75.0, 'on_track'],
            'perfect' => [100.0, 'on_track'],
        ];
    }

    #[DataProvider('statuses')]
    public function test_shared_status_uses_exact_thresholds(?float $score, string $expected): void
    {
        $this->assertSame($expected, StudentProgress::status($score)['key']);
    }

    public static function filters(): array
    {
        return [
            ['all', ['OnTrack', 'NeedsHelp', 'Struggling', 'NoData']],
            ['on_track', ['OnTrack']],
            ['needs_help', ['NeedsHelp']],
            ['struggling', ['Struggling']],
        ];
    }

    #[DataProvider('filters')]
    public function test_exports_only_matching_owned_students(string $filter, array $expected): void
    {
        $this->seedClass();
        $this->fakePdf();
        $response = $this->get(route('teacher.students.exportClassPdf', ['status' => $filter, 'teacher_id' => 999]));
        $response->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertEqualsCanonicalizing($expected, $this->report['rows']->pluck('student.firstname')->all());
        $this->assertSame(count($expected), $this->report['summary']['total']);
        $this->assertSame('a4 landscape', $this->paper);
        $this->assertStringNotContainsString('OtherTeacherStudent', $this->html);
        $this->assertStringContainsString('2026-09-24.pdf', $response->headers->get('Content-Disposition'));
        if ($filter === 'all') {
            $this->assertSame(['total' => 4, 'on_track' => 1, 'needs_help' => 1, 'struggling' => 1, 'no_data' => 1], $this->report['summary']);
        } else {
            $this->assertSame(1, $this->report['summary'][$filter]);
            $this->assertSame(0, $this->report['summary']['no_data']);
        }
    }

    public function test_report_metrics_ignore_null_and_incomplete_scores_and_keep_leading_zero_lrn(): void
    {
        $students = $this->seedClass();
        $student = $students['OnTrack'];
        $this->assessment($student, 90, 80);
        $this->assessment($student, 70, null);
        $this->assessment($student, null, 0);
        $this->assessment($student, null, null, ['pronunciation_score' => 5, 'comprehension_score' => 5]);
        $this->assessment($student, 0, 0, ['teacher_id' => 999]);
        $this->addResult($student, 0, 'in_progress');
        $this->addResult($student, null);
        $this->fakePdf();

        $this->get(route('teacher.students.exportClassPdf'))->assertOk();
        $row = $this->report['rows']->firstWhere('student.id', $student->id);
        $this->assertSame(80.0, $row['average_score']);
        $this->assertSame(80.0, $row['average_oral_reading']);
        $this->assertSame(40.0, $row['average_comprehension']);
        $this->assertSame(3, $row['activities_completed']);
        $this->assertSame('on_track', $row['status']['key']);
        $this->assertSame(8, $row['student']->age);
        foreach (['012345678901', 'Female', 'Section A', 'Ada Teacher', '80.00%', '40.00%'] as $value) {
            $this->assertStringContainsString($value, $this->html);
        }
        $noData = $this->report['rows']->firstWhere('student.firstname', 'NoData');
        $this->assertNull($noData['average_score']);
        $this->assertNull($noData['average_oral_reading']);
        $this->assertNull($noData['average_comprehension']);
        $this->assertSame('no_data', $noData['status']['key']);
    }

    public function test_search_section_level_and_page_status_are_intersected(): void
    {
        $this->seedClass();
        $this->fakePdf();
        $url = route('teacher.students.exportClassPdf', [
            'status' => 'all', 'page_status' => 'on_track', 'section' => 'Section A', 'level' => 2,
            'search' => '012345678901',
        ]);
        $this->get($url)->assertOk();
        $this->assertSame(['OnTrack'], $this->report['rows']->pluck('student.firstname')->all());
        $this->assertStringContainsString('Page status: On Track', $this->report['reportFilter']);

        $this->get(route('teacher.students.exportClassPdf', ['status' => 'needs_help', 'page_status' => 'on_track']))->assertOk();
        $this->assertSame(0, $this->report['summary']['total']);
        $this->assertStringContainsString('No students matched this report.', $this->html);

        $this->get(route('teacher.students.exportClassPdf', ['page_status' => 'no_data']))->assertOk();
        $this->assertSame(['NoData'], $this->report['rows']->pluck('student.firstname')->all());
        $this->get(route('teacher.students.exportClassPdf', ['search' => 'ontrack learner']))->assertOk();
        $this->assertSame(['OnTrack'], $this->report['rows']->pluck('student.firstname')->all());
        $this->get(route('teacher.students.exportClassPdf', ['section' => 'Unassigned section']))->assertOk();
        $this->assertSame(0, $this->report['summary']['total']);
        $this->get(route('teacher.students.exportClassPdf', ['level' => 99]))->assertOk();
        $this->assertSame(0, $this->report['summary']['total']);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        foreach ([
            ['status' => 'unknown'], ['status' => 'no_data'], ['status' => ['all']],
            ['page_status' => 'unknown'], ['level' => 0], ['level' => 'bad'],
            ['section' => ['Section A']], ['search' => str_repeat('a', 201)],
        ] as $filters) {
            $this->getJson(route('teacher.students.exportClassPdf', $filters))
                ->assertUnprocessable()->assertJsonValidationErrors(array_key_first($filters));
        }
    }

    public function test_individual_report_contains_full_histories_and_excludes_other_teacher_assessments(): void
    {
        $student = $this->student('Profile');
        $this->addResult($student, 85);
        for ($i = 0; $i < 7; $i++) {
            $this->assessment($student, 90, 80, ['feedback' => 'Assessment '.$i.' <script>alert(1)</script>']);
        }
        $this->assessment($student, null, null, ['pronunciation_score' => 4, 'fluency_score' => 3,
            'accuracy_score' => 2, 'comprehension_score' => 1, 'feedback' => 'Legacy assessment']);
        $this->assessment($student, 0, 0, ['teacher_id' => 999, 'feedback' => 'Private assessment']);
        $this->fakePdf();

        $this->get(route('teacher.students.exportPdf', $student->id))->assertOk();
        $this->assertSame('a4 portrait', $this->paper);
        $this->assertCount(8, $this->report['student']->evaluations);
        foreach (['Student Information', 'Reading Summary', 'Recent Activity Results', 'Reading Assessment History',
            'Profile Learner', '012345678901', 'Sep 23, 2018', 'Female', 'Section A', 'Legacy assessment',
            'Assessment 0', 'Assessment 6', 'Ada Teacher', '85.00%', 'Reading Passage'] as $value) {
            $this->assertStringContainsString($value, $this->html);
        }
        $this->assertStringNotContainsString('Private assessment', $this->html);
        $this->assertStringNotContainsString('<script>', $this->html);
    }

    public function test_empty_class_and_individual_without_scores_render(): void
    {
        $this->fakePdf();
        $this->get(route('teacher.students.exportClassPdf'))->assertOk();
        $this->assertStringContainsString('No students matched this report.', $this->html);
        $student = $this->student('New');
        $this->get(route('teacher.students.exportPdf', $student->id))->assertOk();
        $this->assertStringContainsString('No Data', $this->html);
        $this->assertStringContainsString('No activity results yet.', $this->html);
        $this->assertStringContainsString('No reading assessments yet.', $this->html);
    }

    public function test_teacher_cannot_export_another_teachers_student(): void
    {
        $student = $this->student('Other', ['teacher_id' => 999]);
        $this->fakePdf();
        $this->get(route('teacher.students.exportPdf', $student->id))->assertNotFound();
        $this->get(route('teacher.students.exportPdf', 99999))->assertNotFound();
    }

    public function test_non_teachers_cannot_access_exports(): void
    {
        $student = $this->student('Learner');
        $user = User::create(['username' => 'student', 'password' => 'unused', 'role' => 'student', 'status' => 'active']);
        $this->actingAs($user);
        $this->get(route('teacher.students.exportClassPdf'))->assertForbidden();
        $this->get(route('teacher.students.exportPdf', $student->id))->assertForbidden();
        auth()->logout();
        $this->get(route('teacher.students.exportClassPdf'))->assertRedirect(route('login'));
    }

    public function test_missing_package_shows_clear_message_after_ownership_check(): void
    {
        unset($this->app['dompdf.wrapper']);
        $student = $this->student('Own');
        $this->get(route('teacher.students.exportClassPdf'))->assertRedirect(route('teacher.students.index'))
            ->assertSessionHas('error');
        $this->get(route('teacher.students.exportPdf', $student->id))->assertRedirect(route('teacher.students.index'))
            ->assertSessionHas('error');
        $student->update(['teacher_id' => 999]);
        $this->get(route('teacher.students.exportPdf', $student->id))->assertNotFound();
    }

    public function test_management_and_profile_use_the_same_status_and_offer_exports(): void
    {
        $student = $this->student('New');
        $this->addResult($student, 100, 'in_progress');
        $this->get(route('teacher.students.index'))->assertOk()->assertSee('Export Report')
            ->assertSee('data-status="no_data"', false)->assertSee(route('teacher.students.exportPdf', $student->id), false);
        $this->get(route('teacher.students.show', $student->id))->assertOk()->assertSee('No Data')->assertSee('Export PDF');
        $this->addResult($student, 74.99);
        $this->addResult($student, 75);
        $this->get(route('teacher.students.index'))->assertOk()->assertSee('data-status="needs_help"', false);
        $this->get(route('teacher.students.show', $student->id))->assertOk()->assertSee('Needs Help');
    }

    public function test_class_report_uses_a_constant_query_count(): void
    {
        $this->seedClass();
        $this->fakePdf();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get(route('teacher.students.exportClassPdf'))->assertOk();
        $initialCount = count(DB::getQueryLog());
        for ($i = 0; $i < 20; $i++) {
            $student = $this->student('Extra'.$i);
            $this->addResult($student, 80);
            $this->assessment($student, 80, 90);
        }
        DB::flushQueryLog();
        $this->get(route('teacher.students.exportClassPdf'))->assertOk();
        $this->assertLessThanOrEqual($initialCount, count(DB::getQueryLog()));
        $this->assertSame(24, $this->report['summary']['total']);
        DB::disableQueryLog();
    }

    public function test_real_class_and_individual_pdf_downloads_when_dompdf_is_installed(): void
    {
        if (! app()->bound('dompdf.wrapper')) {
            $this->markTestSkipped('Install barryvdh/laravel-dompdf manually to verify actual PDF rendering.');
        }
        $student = $this->student('Rendered');
        $this->addResult($student, 85);
        $this->assessment($student, 90, 80);
        foreach ([
            route('teacher.students.exportClassPdf'),
            route('teacher.students.exportClassPdf', ['status' => 'struggling']),
            route('teacher.students.exportPdf', $student->id),
        ] as $url) {
            $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertStringContainsString('%%EOF', $response->getContent());
        }
    }

    /** Render real Blade HTML, while explicitly replacing only the unavailable PDF engine. */
    private function fakePdf(): void
    {
        $pdf = \Mockery::mock();
        $pdf->shouldReceive('loadView')->andReturnUsing(function ($view, $data) use ($pdf) {
            $this->report = $data;
            $this->html = view($view, $data)->render();

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnUsing(function ($size, $orientation) use ($pdf) {
            $this->paper = $size.' '.$orientation;

            return $pdf;
        });
        $pdf->shouldReceive('download')->andReturnUsing(fn ($filename) =>
            response($this->html)->header('Content-Disposition', 'attachment; filename="'.$filename.'"'));
        $this->app->instance('dompdf.wrapper', $pdf);
    }

    private function seedClass(): array
    {
        $students = [];
        foreach (['OnTrack' => [75, 85], 'NeedsHelp' => [50, 70], 'Struggling' => [0, 40], 'NoData' => []] as $name => $scores) {
            $student = $this->student($name);
            $students[$name] = $student;
            foreach ($scores as $score) {
                $this->addResult($student, $score);
            }
        }
        $this->addResult($students['NoData'], 100, 'in_progress');
        $this->addResult($students['NoData'], null);
        $this->addResult($this->student('OtherTeacherStudent', ['teacher_id' => 999]), 90);

        return $students;
    }

    private function student(string $name, array $attributes = []): Student
    {
        return Student::create(array_merge([
            'user_id' => 100 + Student::count(), 'teacher_id' => $this->teacher->id,
            'firstname' => $name, 'lastname' => 'Learner', 'lrn_no' => Student::count() === 0 ? '012345678901' : sprintf('%012d', Student::count()),
            'birthday' => '2018-09-23', 'gender' => 'Female', 'section' => 'Section A',
            'current_level' => 2, 'total_points' => 650,
        ], $attributes));
    }

    private function addResult(Student $student, ?float $score, string $status = 'completed'): void
    {
        ActivityResult::create(['student_id' => $student->id, 'activity_id' => 1, 'score' => $score,
            'status' => $status, 'completed_at' => $status === 'completed' ? now() : null]);
    }

    private function assessment(Student $student, ?float $oral, ?float $comprehension, array $extra = []): void
    {
        $recording = DB::table('voice_recordings')->insertGetId([
            'student_id' => $student->id, 'activity_id' => 1, 'attempt_number' => 1,
            'status' => 'evaluated', 'created_at' => now(), 'updated_at' => now(),
        ]);
        Evaluation::create(array_merge(['teacher_id' => $this->teacher->id, 'recording_id' => $recording,
            'oral_reading_score' => $oral, 'comprehension_percentage' => $comprehension], $extra));
    }

    /** Test-only SQLite schema. No application migrations or persistent databases are used. */
    private function createIsolatedSchema(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['username', 'password', 'role', 'status'] as $column) {
                $t->string($column);
            }
            $t->timestamps();
        });
        Schema::create('teachers', function (Blueprint $t) {
            $t->id();
            $t->integer('user_id');
            foreach (['firstname', 'lastname', 'school_name'] as $column) {
                $t->string($column)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->integer('user_id');
            $t->integer('teacher_id');
            foreach (['firstname', 'lastname', 'section', 'lrn_no', 'gender', 'student_number'] as $column) {
                $t->string($column)->nullable();
            }
            $t->date('birthday')->nullable();
            $t->integer('age')->nullable();
            $t->integer('current_level')->default(1);
            $t->integer('total_points')->default(0);
            $t->timestamps();
        });
        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->string('activity_name');
            $t->string('activity_type');
            $t->timestamps();
        });
        DB::table('activities')->insert(['id' => 1, 'activity_name' => 'Reading Passage', 'activity_type' => 'Read Aloud']);
        Schema::create('activity_results', function (Blueprint $t) {
            $t->id();
            $t->integer('student_id');
            $t->integer('activity_id');
            $t->decimal('score', 8, 4)->nullable();
            $t->string('status');
            $t->dateTime('completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('voice_recordings', function (Blueprint $t) {
            $t->id();
            $t->integer('student_id');
            $t->integer('activity_id');
            $t->integer('attempt_number');
            $t->string('status');
            $t->timestamps();
        });
        Schema::create('evaluations', function (Blueprint $t) {
            $t->id();
            $t->integer('teacher_id');
            $t->integer('recording_id');
            foreach (['oral_reading_score', 'comprehension_percentage', 'pronunciation_score', 'fluency_score',
                'accuracy_score', 'comprehension_score'] as $column) {
                $t->decimal($column, 6, 2)->nullable();
            }
            foreach (['total_words', 'miscues', 'total_reading_seconds', 'correct_answers', 'total_questions',
                'observation_level', 'learner_experience'] as $column) {
                $t->integer($column)->nullable();
            }
            $t->string('proficiency_level')->nullable();
            $t->text('feedback')->nullable();
            $t->timestamps();
        });
    }
}
