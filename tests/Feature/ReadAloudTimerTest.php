<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ReadingMaterial;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\VoiceRecording;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReadAloudTimerTest extends TestCase
{
    private Activity $activity;
    private Student $student;
    private User $teacherUser;

    protected function setUp(): void
    {
        parent::setUp();
        // Disposable tables only: never migrate or connect to the project's DB.
        config([
            'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array',
        ]);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $tables = [
            'users' => ['username', 'password', 'role', 'status'],
            'teachers' => ['user_id', 'firstname', 'lastname', 'school_name'],
            'students' => ['user_id', 'teacher_id', 'firstname', 'lastname', 'current_level', 'total_points'],
            'reading_materials' => ['teacher_id', 'title', 'content', 'difficulty_level', 'level'],
            'activities' => ['teacher_id', 'reading_material_id', 'activity_name', 'description',
                'activity_type', 'duration_minutes', 'level', 'difficulty_level', 'points_reward',
                'is_published', 'allow_reattempt', 'battle_mode'],
            'activity_word_bank' => ['activity_id', 'word'],
            'voice_recordings' => ['student_id', 'activity_id', 'recording_path', 'attempt_number', 'status'],
            'evaluations' => ['recording_id'],
            'badges' => ['badge_name', 'criteria'],
            'activity_logs' => ['user_id', 'role', 'action', 'module', 'description', 'ip_address', 'user_agent'],
        ];
        foreach ($tables as $table => $columns) {
            DB::connection()->getSchemaBuilder()->create($table, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $column) {
                    if (in_array($column, ['attempt_number', 'duration_minutes', 'level', 'current_level'])) {
                        $t->integer($column)->nullable();
                    } else {
                        $t->text($column)->nullable();
                    }
                }
                $t->timestamps();
            });
        }
        Storage::fake('public');
        $this->teacherUser = User::create(['username' => 'timer_teacher', 'role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $this->teacherUser->id,
            'firstname' => 'Test', 'lastname' => 'Teacher']);
        $user = User::create(['username' => 'timer_student', 'role' => 'student']);
        $this->student = Student::create(['user_id' => $user->id, 'teacher_id' => $teacher->id,
            'firstname' => 'Test', 'lastname' => 'Reader', 'current_level' => 1, 'total_points' => 0]);
        $material = ReadingMaterial::create(['teacher_id' => $teacher->id,
            'title' => 'Reading', 'content' => 'The little cat can run.']);
        $this->activity = Activity::create([
            'teacher_id' => $teacher->id, 'reading_material_id' => $material->id,
            'activity_name' => 'Timed Reading', 'activity_type' => 'Read Aloud',
            'duration_minutes' => 1, 'level' => 1, 'difficulty_level' => 'Easy', 'points_reward' => 10,
            'is_published' => true, 'allow_reattempt' => false, 'battle_mode' => false,
        ]);
        $this->actingAs($user);
    }

    private function audio(): UploadedFile
    {
        $samples = str_repeat("\0", 1600);
        $wav = 'RIFF'.pack('V', 36 + strlen($samples)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16)
            .'data'.pack('V', strlen($samples)).$samples;

        return UploadedFile::fake()->createWithContent('reading.wav', $wav);
    }

    private function upload(?string $token = null)
    {
        return $this->postJson(route('student.readaloud.upload', $this->activity->id), [
            'recording' => $this->audio(), 'recording_token' => $token ?? (string) Str::uuid(),
        ]);
    }

    private function teacherInput(int $minutes = 1): array
    {
        return [
            'activity_name' => 'Teacher Timer', 'description' => '', 'level' => 1,
            'difficulty_level' => 'Easy', 'duration_minutes' => $minutes,
            'points_reward' => 10, 'passage' => 'Read this short passage.',
            'is_published' => '1', 'allow_reattempt' => '1',
        ];
    }

    public function test_teacher_creation_and_edit_supply_the_saved_duration_to_the_student(): void
    {
        $this->actingAs($this->teacherUser)->post(route('teacher.activities.store.readaloud'), $this->teacherInput())
            ->assertRedirect();
        $created = Activity::where('activity_name', 'Teacher Timer')->sole();
        $this->actingAs($this->student->user)->get(route('student.readaloud.show', $created->id))
            ->assertOk()->assertSee('data-duration-seconds="60"', false)->assertSee('01:00')
            ->assertSee('Time Limit: 1 minute');
        $this->actingAs($this->teacherUser)
            ->put(route('teacher.activities.update.readaloud', $created->id), $this->teacherInput(2))->assertRedirect();
        $this->actingAs($this->student->user)->get(route('student.readaloud.show', $created->id))
            ->assertOk()->assertSee('data-duration-seconds="120"', false)->assertSee('02:00')
            ->assertSee('Time Limit: 2 minutes');
        $this->assertSame(2, $created->fresh()->duration_minutes);
    }

    public static function invalidDurations(): array
    {
        return [[null], [0], [-1], ['invalid'], [1.5]];
    }

    #[DataProvider('invalidDurations')]
    public function test_invalid_legacy_duration_blocks_recording_and_upload(mixed $duration): void
    {
        $this->activity->update(['duration_minutes' => $duration]);
        $this->get(route('student.readaloud.show', $this->activity->id))->assertOk()
            ->assertSee('data-can-record="false"', false)
            ->assertSee('does not have a valid reading duration');
        $this->upload()->assertUnprocessable()->assertJsonValidationErrors('recording');
        $this->assertSame(0, VoiceRecording::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    #[DataProvider('invalidDurations')]
    public function test_teacher_create_and_update_require_positive_whole_minutes(mixed $duration): void
    {
        $input = array_replace($this->teacherInput(), ['duration_minutes' => $duration]);
        $this->actingAs($this->teacherUser)
            ->postJson(route('teacher.activities.store.readaloud'), $input)
            ->assertUnprocessable()->assertJsonValidationErrors('duration_minutes');
        $this->putJson(route('teacher.activities.update.readaloud', $this->activity->id), $input)
            ->assertUnprocessable()->assertJsonValidationErrors('duration_minutes');
        $this->assertSame(1, $this->activity->fresh()->duration_minutes);
        $this->assertSame(1, Activity::count());
    }

    public function test_success_uses_existing_pending_teacher_evaluation_workflow(): void
    {
        $response = $this->upload()->assertCreated()->assertJsonPath('status', 'pending');
        $recording = VoiceRecording::sole();
        $response->assertJsonPath('recording_id', $recording->id);
        $this->assertSame(1, $recording->attempt_number);
        $this->assertSame('pending', $recording->status);
        Storage::disk('public')->assertExists($recording->recording_path);
        $this->assertSame(0, DB::table('evaluations')->count());
    }

    public function test_retry_after_lost_response_returns_same_recording_and_does_not_duplicate_log_or_file(): void
    {
        $token = (string) Str::uuid();
        $first = $this->upload($token)->assertCreated();
        $this->upload($token)->assertOk()->assertJsonPath('recording_id', $first->json('recording_id'));
        $this->assertSame(1, VoiceRecording::count());
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'SUBMIT_RECORDING')->count());
    }

    public function test_disallowed_reattempt_is_blocked_in_page_and_upload(): void
    {
        $this->upload()->assertCreated();
        $this->get(route('student.readaloud.show', $this->activity->id))->assertOk()
            ->assertSee('data-can-record="false"', false)->assertSee('has not allowed another attempt');
        $this->upload()->assertStatus(409);
        $this->assertSame(1, VoiceRecording::count());
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_allowed_reattempt_returns_full_timer_and_creates_next_attempt(): void
    {
        $this->activity->update(['duration_minutes' => 2, 'allow_reattempt' => true]);
        $this->upload()->assertCreated();
        $this->get(route('student.readaloud.show', $this->activity->id))->assertOk()
            ->assertSee('data-can-record="true"', false)->assertSee('data-duration-seconds="120"', false)
            ->assertSee('02:00');
        $this->upload()->assertCreated();
        $this->assertSame([1, 2], VoiceRecording::orderBy('id')->pluck('attempt_number')->all());
    }

    public static function inaccessibleActivities(): array
    {
        return [['teacher_id', 999], ['is_published', false], ['level', 2],
            ['activity_type', 'Word Game'], ['battle_mode', true]];
    }

    #[DataProvider('inaccessibleActivities')]
    public function test_activity_access_cannot_be_bypassed_on_show_or_upload(string $field, mixed $value): void
    {
        $this->activity->update([$field => $value]);
        $this->get(route('student.readaloud.show', $this->activity->id))->assertNotFound();
        $this->upload()->assertNotFound();
        $this->assertSame(0, VoiceRecording::count());
    }

    public function test_audio_and_retry_token_are_validated(): void
    {
        $url = route('student.readaloud.upload', $this->activity->id);
        $this->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('recording');
        $this->postJson($url, ['recording' => UploadedFile::fake()->create('empty.wav', 0, 'audio/wav')])
            ->assertUnprocessable()->assertJsonValidationErrors('recording');
        $this->postJson($url, ['recording' => UploadedFile::fake()->createWithContent('bad.txt', 'not audio')])
            ->assertUnprocessable()->assertJsonValidationErrors('recording');
        $this->postJson($url, ['recording' => $this->audio(), 'recording_token' => '../bad'])
            ->assertUnprocessable()->assertJsonValidationErrors('recording_token');
        $this->assertSame(0, VoiceRecording::count());
    }

    public function test_guest_and_teacher_cannot_upload_as_a_student(): void
    {
        $this->actingAs($this->teacherUser);
        $this->upload()->assertForbidden();
        auth()->logout();
        $this->post(route('student.readaloud.upload', $this->activity->id), [])
            ->assertRedirect(route('login'));
        $this->assertSame(0, VoiceRecording::count());
    }

    public function test_failed_save_does_not_leave_audio_and_same_attempt_can_retry(): void
    {
        $token = (string) Str::uuid();
        VoiceRecording::creating(fn () => throw new \RuntimeException('Simulated write failure'));
        try {
            $this->upload($token)->assertStatus(500)->assertJsonPath('message', 'Your recording could not be saved. Please try again.');
        } finally {
            VoiceRecording::flushEventListeners();
        }
        $this->assertSame(0, VoiceRecording::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->upload($token)->assertCreated();
        $this->assertSame(1, VoiceRecording::count());
    }
}
