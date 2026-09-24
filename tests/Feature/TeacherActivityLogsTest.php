<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\Badge;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherActivityLogsTest extends TestCase
{
    private User $teacherUser;
    private Teacher $teacher;
    private Student $student;
    private Student $outsider;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $tables = [
            'users' => ['username', 'password', 'role', 'status'],
            'teachers' => ['user_id', 'firstname', 'lastname'],
            'students' => ['user_id', 'teacher_id', 'firstname', 'lastname', 'student_number', 'current_level', 'total_points'],
            'activity_logs' => ['user_id', 'role', 'action', 'module', 'description', 'ip_address', 'user_agent'],
            'badges' => ['badge_name', 'criteria'],
            'student_badges' => ['student_id', 'badge_id', 'earned_at'],
            'activities' => ['teacher_id', 'activity_name', 'points_reward', 'activity_type', 'is_published'],
            'voice_recordings' => ['student_id', 'activity_id', 'recording_path', 'attempt_number', 'status'],
            'activity_results' => ['student_id', 'activity_id', 'score', 'mistakes', 'time_spent', 'status', 'completed_at'],
        ];
        foreach ($tables as $table => $columns) {
            DB::connection()->getSchemaBuilder()->create($table, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $column) {
                    if (str_ends_with($column, '_id') || in_array($column, ['current_level', 'total_points', 'points_reward'])) {
                        $t->integer($column)->nullable();
                    } else {
                        $t->string($column)->nullable();
                    }
                }
                $t->timestamps();
            });
        }
        $this->teacherUser = $this->user('teacher_one', 'teacher');
        $this->teacher = Teacher::create(['user_id' => $this->teacherUser->id, 'firstname' => 'First', 'lastname' => 'Teacher']);
        $other = Teacher::create(['user_id' => $this->user('teacher_two', 'teacher')->id, 'firstname' => 'Other']);
        $this->student = $this->studentFor($this->teacher, 'Alice');
        $this->outsider = $this->studentFor($other, 'Outside');
        $this->actingAs($this->teacherUser);
    }

    private function user(string $username, string $role): User
    {
        return User::create(['username' => $username, 'password' => Hash::make('secret123'), 'role' => $role, 'status' => 'active']);
    }

    private function studentFor(Teacher $teacher, string $name): Student
    {
        return Student::create(['user_id' => $this->user($name, 'student')->id, 'teacher_id' => $teacher->id,
            'firstname' => $name, 'lastname' => 'Learner', 'student_number' => $name . '-001',
            'current_level' => 1, 'total_points' => 0]);
    }

    private function event(User $user, string $action, string $description, ?string $date = null): ActivityLog
    {
        $log = ActivityLog::create(['user_id' => $user->id, 'role' => $user->role, 'action' => $action,
            'module' => 'Testing', 'description' => $description]);
        if ($date) {
            $log->created_at = $date;
            $log->save();
        }
        return $log;
    }

    public function test_teacher_sees_own_and_classroom_events_only(): void
    {
        $mine = $this->event($this->teacherUser, 'LOGIN', 'Teacher signed in');
        $student = $this->event($this->student->user, 'SUBMIT_RECORDING', 'Read Aloud: Forest story');
        $this->event($this->outsider->user, 'PRIVATE_ACTION', 'Outside classroom secret');
        $this->event($this->outsider->teacher->user, 'OTHER_TEACHER', 'Other teacher secret');
        $this->get(route('teacher.logs'))->assertOk()->assertSee('Activity logs')->assertDontSee('My Activity Logs')
            ->assertSee('Alice Learner')->assertSee('Teacher signed in')->assertSee('Read Aloud: Forest story')
            ->assertDontSee('Outside classroom secret')->assertDontSee('Other teacher secret')->assertDontSee('PRIVATE_ACTION')
            ->assertViewHas('logs', fn ($logs) => $logs->pluck('id')->all() === [$student->id, $mine->id]);
    }

    public function test_source_student_action_and_date_filters(): void
    {
        $this->event($this->teacherUser, 'LOGIN', 'Own event');
        $match = $this->event($this->student->user, 'BATTLE_WON', 'Won Forest battle', '2026-09-20 10:00:00');
        $this->event($this->student->user, 'BATTLE_LOST', 'Lost Forest battle', '2026-09-20 11:00:00');
        $this->event($this->student->user, 'BATTLE_WON', 'Won yesterday', '2026-09-19 10:00:00');
        $classmate = $this->studentFor($this->teacher, 'Bob');
        $this->event($classmate->user, 'BATTLE_WON', 'Classmate win', '2026-09-20 10:00:00');
        $this->event($this->outsider->user, 'BATTLE_WON', 'Secret win', '2026-09-20 10:00:00');
        $this->get(route('teacher.logs', ['scope' => 'students', 'student_id' => $this->student->id,
            'action' => 'BATTLE_WON', 'date' => '2026-09-20']))->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->pluck('id')->all() === [$match->id]);
        $this->get(route('teacher.logs', ['scope' => 'mine']))->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->user_id === $this->teacherUser->id);
        $this->get(route('teacher.logs', ['scope' => 'students']))->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 4);
    }

    public function test_cross_class_student_filter_is_rejected(): void
    {
        $this->get(route('teacher.logs', ['student_id' => $this->outsider->id]))->assertForbidden();
    }

    public function test_invalid_filters_are_validation_errors(): void
    {
        $this->getJson(route('teacher.logs', ['date' => 'not-a-date', 'scope' => 'admin', 'student_id' => 'bad']))
            ->assertUnprocessable()->assertJsonValidationErrors(['date', 'scope', 'student_id']);
    }

    public function test_students_cannot_access_teacher_logs(): void
    {
        $this->actingAs($this->student->user)->get(route('teacher.logs'))->assertForbidden();
    }

    public function test_pagination_preserves_filters_and_empty_classroom_renders(): void
    {
        $this->get(route('teacher.logs'))->assertOk()->assertSee('No activity logs match these filters.');
        for ($i = 0; $i < 21; $i++) {
            $this->event($this->student->user, 'LOGIN', 'Login ' . $i);
        }
        $this->get(route('teacher.logs', ['scope' => 'students', 'action' => 'LOGIN']))->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 21 && $logs->count() === 20
                && str_contains($logs->nextPageUrl(), 'scope=students') && str_contains($logs->nextPageUrl(), 'action=LOGIN'));
    }

    public function test_badge_is_logged_once_for_student_when_awarded_by_teacher(): void
    {
        $this->student->update(['total_points' => 100]);
        Badge::create(['badge_name' => 'Points Star', 'criteria' => 'points_100']);
        BadgeService::checkAndAward($this->student);
        BadgeService::checkAndAward($this->student);
        $log = ActivityLog::where('action', 'EARN_BADGE')->sole();
        $this->assertSame($this->student->user_id, $log->user_id);
        $this->assertSame('student', $log->role);
        $this->assertSame('Earned badge: Points Star', $log->description);
    }

    public function test_level_change_is_logged_once_for_student(): void
    {
        $this->student->update(['total_points' => 1000]);
        $this->student->checkAndUpdateLevel();
        $this->student->checkAndUpdateLevel();
        $log = ActivityLog::where('action', 'LEVEL_UP')->sole();
        $this->assertSame($this->student->user_id, $log->user_id);
        $this->assertStringContainsString('level 1 to level 3', $log->description);
    }

    public function test_student_login_and_logout_are_visible_to_teacher(): void
    {
        auth()->logout();
        $this->post(route('login.post'), ['username' => 'Alice', 'password' => 'secret123'])->assertRedirect();
        $this->post(route('logout'))->assertRedirect();
        $this->actingAs($this->teacherUser)->get(route('teacher.logs', ['scope' => 'students']))->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->pluck('action')->all() === ['LOGOUT', 'LOGIN']);
    }

    public function test_read_aloud_submission_logs_activity_and_attempt_for_teacher(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $activity = Activity::create(['teacher_id' => $this->teacher->id, 'activity_name' => 'Forest story',
            'activity_type' => 'Read Aloud', 'is_published' => true, 'points_reward' => 25]);
        Badge::create(['badge_name' => 'First Reading', 'criteria' => 'first_recording']);
        $this->actingAs($this->student->user)->postJson(route('student.readaloud.upload', $activity->id), [
            'recording' => \Illuminate\Http\UploadedFile::fake()->create('reading.wav', 1, 'audio/wav'),
        ])->assertCreated();
        $log = ActivityLog::where('action', 'SUBMIT_RECORDING')->sole();
        $this->assertSame($this->student->user_id, $log->user_id);
        $this->assertStringContainsString('Forest story (ID ' . $activity->id . ') - Attempt 1', $log->description);
        $this->actingAs($this->teacherUser)->get(route('teacher.logs', ['scope' => 'students']))->assertOk()
            ->assertSee('Submitted Read Aloud recording')->assertSee('Earned badge: First Reading');
    }

    public function test_activity_completion_records_activity_score_and_points(): void
    {
        $activity = Activity::create(['teacher_id' => $this->teacher->id, 'activity_name' => 'Word practice', 'points_reward' => 25]);
        $this->actingAs($this->student->user)->post(route('student.activities.submit', $activity->id), ['score' => 80])->assertRedirect();
        $log = ActivityLog::where('action', 'COMPLETE_ACTIVITY')->sole();
        $this->assertSame($this->student->user_id, $log->user_id);
        $this->assertStringContainsString('Word practice (ID ' . $activity->id . ')', $log->description);
        $this->assertStringContainsString('Score: 80% - Earned 25 points', $log->description);
    }
}
