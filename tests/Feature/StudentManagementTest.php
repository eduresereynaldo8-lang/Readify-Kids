<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    private User $teacherUser;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        // Explicitly isolate all writes. Never run migrations or use the project DB.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Carbon::setTestNow('2026-09-22 12:00:00');
        $this->createIsolatedSchema();
        $this->teacherUser = User::create(['username' => 'test_teacher', 'password' => 'unused',
            'role' => 'teacher', 'status' => 'active']);
        $this->teacher = Teacher::create(['user_id' => $this->teacherUser->id,
            'firstname' => 'Test', 'lastname' => 'Teacher', 'school_name' => 'Test School']);
        $this->actingAs($this->teacherUser);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_create_preserves_leading_zero_lrn_and_calculates_age_and_hashes_password(): void
    {
        $this->post(route('teacher.students.store'), $this->input())->assertRedirect(route('teacher.students.index'));
        $student = Student::firstOrFail();
        $this->assertSame('012345678901', $student->lrn_no);
        $this->assertSame('2018-09-23', $student->birthday->toDateString());
        $this->assertSame(7, $student->age);
        $this->assertSame(7, (int) $student->getRawOriginal('age'));
        $this->assertSame('Female', $student->gender);
        $this->assertSame($this->teacher->id, $student->teacher_id);
        $this->assertSame(0, $student->total_points);
        $this->assertNull($student->student_number);
        $this->assertTrue(Hash::check('secret123', $student->user->password));
        $this->assertSame('student', $student->user->role);
        $this->assertSame('active', $student->user->status);
        $this->assertSame(1, ActivityLog::where('action', 'ADD_STUDENT')->count());
    }

    public function test_new_account_can_log_in_with_the_existing_login_flow(): void
    {
        $this->post(route('teacher.students.store'), $this->input())->assertRedirect();
        $user = Student::firstOrFail()->user;
        auth()->logout();
        $this->post(route('login.post'), ['username' => 'new_learner', 'password' => 'secret123'])
            ->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public static function invalidFields(): array
    {
        return [
            'missing lrn' => ['lrn_no', null],
            'short lrn' => ['lrn_no', '12345678901'],
            'long lrn' => ['lrn_no', '1234567890123'],
            'letter in lrn' => ['lrn_no', '12345678901x'],
            'missing birthday' => ['birthday', null],
            'future birthday' => ['birthday', '2026-09-23'],
            'impossible birthday' => ['birthday', '2026-02-30'],
            'incorrect date format' => ['birthday', '09/22/2018'],
            'missing gender' => ['gender', null],
            'invalid gender' => ['gender', 'Other'],
            'missing section' => ['section', null],
            'invalid level' => ['current_level', 6],
            'missing firstname' => ['firstname', null],
            'missing lastname' => ['lastname', null],
            'missing username' => ['username', null],
            'password mismatch' => ['password_confirmation', 'different'],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_input_does_not_create_a_user_or_student(string $field, mixed $value): void
    {
        $errorField = $field === 'password_confirmation' ? 'password' : $field;
        $this->postJson(route('teacher.students.store'), array_replace($this->input(), [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($errorField);
        $this->assertSame(1, User::count());
        $this->assertSame(0, Student::count());
    }

    public function test_duplicate_lrn_is_rejected_on_create_and_update_but_self_update_is_allowed(): void
    {
        $this->post(route('teacher.students.store'), $this->input())->assertRedirect();
        $first = Student::firstOrFail();
        $this->postJson(route('teacher.students.store'), array_replace($this->input(), ['username' => 'another']))
            ->assertUnprocessable()->assertJsonValidationErrors('lrn_no');
        $this->post(route('teacher.students.store'), array_replace($this->input(), [
            'lrn_no' => '999999999999', 'username' => 'another',
        ]))->assertRedirect();
        $second = Student::latest('id')->firstOrFail();
        $this->putJson(route('teacher.students.update', $second->id), $this->input())
            ->assertUnprocessable()->assertJsonValidationErrors('lrn_no');
        $this->put(route('teacher.students.update', $first->id), $this->input())->assertRedirect();
        $this->assertSame(2, Student::count());
    }

    public function test_existing_legacy_profile_can_be_completed_without_changing_login_or_old_id(): void
    {
        $student = $this->legacyStudent();
        $password = $student->user->password;
        $this->get(route('teacher.students.edit', $student->id))->assertOk()->assertSee('value="7"', false);
        $this->put(route('teacher.students.update', $student->id), array_replace($this->input(), [
            'birthday' => '2018-09-22', 'current_level' => 7,
            'username' => 'do_not_change', 'password' => 'do_not_change',
            'teacher_id' => 999, 'total_points' => 999999, 'student_number' => 'do_not_change',
        ]))->assertRedirect();
        $student->refresh();
        $this->assertSame('OLD-001', $student->student_number);
        $this->assertSame('legacy_learner', $student->user->username);
        $this->assertSame($password, $student->user->password);
        $this->assertSame($this->teacher->id, $student->teacher_id);
        $this->assertSame(650, $student->total_points);
        $this->assertSame(7, $student->current_level);
        $this->assertSame(8, $student->age);
        $this->assertSame('Female', $student->gender);
    }

    public function test_update_validation_and_ownership_are_enforced(): void
    {
        $student = $this->legacyStudent();
        $this->putJson(route('teacher.students.update', $student->id), array_replace($this->input(), [
            'birthday' => '2026-09-23', 'gender' => null,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['birthday', 'gender']);
        $student->update(['teacher_id' => 999]);
        $this->get(route('teacher.students.edit', $student->id))->assertNotFound();
        $this->put(route('teacher.students.update', $student->id), $this->input())->assertNotFound();
        $this->get(route('teacher.students.show', $student->id))->assertNotFound();
        $this->assertNull($student->fresh()->lrn_no);
    }

    public function test_account_and_profile_creation_roll_back_together(): void
    {
        Student::creating(function () {
            throw new \RuntimeException('Simulated profile save failure');
        });
        try {
            $this->withoutExceptionHandling()->post(route('teacher.students.store'), $this->input());
            $this->fail('Expected profile failure.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Simulated profile save failure', $error->getMessage());
        } finally {
            Student::flushEventListeners();
        }
        $this->assertSame(1, User::count());
        $this->assertSame(0, Student::count());
        $this->assertSame(0, ActivityLog::count());
    }

    public function test_age_stays_current_without_rewriting_the_saved_snapshot(): void
    {
        $this->post(route('teacher.students.store'), $this->input());
        $student = Student::firstOrFail();
        $this->assertSame(7, $student->age);
        Carbon::setTestNow('2026-09-23 12:00:00');
        $this->assertSame(8, $student->age);
        $this->assertSame(7, (int) $student->getRawOriginal('age'));
        Carbon::setTestNow('2027-09-23 12:00:00');
        $this->assertSame(9, $student->age);
    }

    public function test_birthday_today_and_leap_day_have_correct_ages(): void
    {
        $this->post(route('teacher.students.store'), array_replace($this->input(), [
            'birthday' => '2026-09-22',
        ]))->assertRedirect();
        $student = Student::firstOrFail();
        $this->assertSame(0, $student->age);
        $student->update(['birthday' => '2020-02-29']);
        Carbon::setTestNow('2026-02-28 12:00:00');
        $this->assertSame(5, $student->age);
        Carbon::setTestNow('2026-03-01 12:00:00');
        $this->assertSame(6, $student->age);
    }

    public function test_forms_list_and_profile_render_new_fields_and_legacy_blanks(): void
    {
        $this->get(route('teacher.students.create'))->assertOk()->assertSee('LRN No.')
            ->assertSee('type="date"', false)->assertSee('name="gender"', false);
        $this->get(route('teacher.students.index'))->assertOk()->assertSee('No students yet.');
        $legacy = $this->legacyStudent();
        $this->assertNull($legacy->age);
        $this->get(route('teacher.students.index'))->assertOk()->assertSee('LRN No.')->assertSee('Gender');
        $this->get(route('teacher.students.edit', $legacy->id))->assertOk()
            ->assertSee('name="current_level"', false)->assertSee('Existing Section');
        $this->get(route('teacher.students.show', $legacy->id))->assertOk()->assertSee('Birthday');
        $this->post(route('teacher.students.store'), $this->input());
        $student = Student::latest('id')->firstOrFail();
        $this->get(route('teacher.students.edit', $student->id))->assertOk()
            ->assertSee('value="2018-09-23"', false)->assertSee('012345678901')->assertSee('new_learner');
        $this->get(route('teacher.students.index'))->assertOk()->assertSee('012345678901')->assertSee('Female');
        $this->get(route('teacher.students.show', $student->id))->assertOk()->assertSee('Sep 23, 2018');
    }

    private function input(): array
    {
        return ['firstname' => 'New', 'lastname' => 'Learner', 'lrn_no' => '012345678901',
            'birthday' => '2018-09-23', 'age' => 99, 'gender' => 'Female', 'section' => 'Section A',
            'current_level' => 2, 'username' => 'new_learner', 'password' => 'secret123',
            'password_confirmation' => 'secret123'];
    }

    private function legacyStudent(): Student
    {
        $user = User::create(['username' => 'legacy_learner', 'password' => Hash::make('oldsecret'),
            'role' => 'student', 'status' => 'active']);

        return Student::create(['user_id' => $user->id, 'teacher_id' => $this->teacher->id,
            'firstname' => 'Legacy', 'lastname' => 'Learner', 'student_number' => 'OLD-001',
            'section' => 'Existing Section', 'current_level' => 7, 'total_points' => 650]);
    }

    private function createIsolatedSchema(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('username', 100)->unique();
            $t->string('email')->nullable();
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
            $t->string('student_number', 50)->nullable()->unique();
            $t->string('firstname', 100);
            $t->string('lastname', 100);
            $t->string('section', 50);
            $t->integer('current_level')->default(1);
            $t->integer('total_points')->default(0);
            $t->string('lrn_no', 12)->nullable()->unique();
            $t->date('birthday')->nullable();
            $t->unsignedInteger('age')->nullable();
            $t->string('gender', 10)->nullable();
            $t->timestamps();
        });
        Schema::create('activity_results', function (Blueprint $t) {
            $t->id();
            $t->integer('student_id');
            $t->integer('activity_id');
            $t->decimal('score', 5, 2)->nullable();
            $t->string('status')->default('completed');
            $t->dateTime('completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->string('activity_name');
            $t->string('activity_type');
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
