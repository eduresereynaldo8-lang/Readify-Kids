<?php

namespace Tests\Feature;

use App\Http\Controllers\GameController;
use App\Models\Activity;
use App\Models\ActivityResult;
use App\Models\Enemy;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BattleOralReadingTest extends TestCase
{
    private Student $student;
    private Activity $activity;
    private Enemy $enemy;

    protected function setUp(): void
    {
        parent::setUp();
        // Isolated in-memory tables only. Never run migrations or use the project DB.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->createIsolatedTables();
        Storage::fake('public');
        Http::preventStrayRequests();
        $user = User::create(['username' => 'battle_test', 'role' => 'student', 'status' => 'active']);
        $this->student = Student::create(['user_id' => $user->id, 'total_points' => 0]);
        $this->activity = Activity::create(['activity_name' => 'Reading', 'points_reward' => 25, 'level' => 1]);
        $this->enemy = Enemy::create(['name' => 'Test Enemy', 'max_hp' => 1000]);
        for ($i = 0; $i < 5; $i++) {
            $this->activity->wordBank()->create(['word' => 'the little cat sleeps', 'order' => $i]);
        }
        $this->actingAs($user);
    }

    private function createGameSession(array $attributes = []): GameSession
    {
        return GameSession::create(array_replace([
            'student_id' => $this->student->id, 'activity_id' => $this->activity->id,
            'enemy_id' => $this->enemy->id, 'enemy_current_hp' => 1000, 'enemy_max_hp' => 1000,
            'student_current_hp' => 1000, 'student_max_hp' => 1000, 'total_damage' => 0,
            'rounds_played' => 0, 'points_earned' => 0, 'status' => 'ongoing',
        ], $attributes));
    }

    private function round(GameSession $session, ?float $score): void
    {
        $session->rounds()->create(['student_id' => $this->student->id, 'word_or_passage' => 'cat',
            'ml_score' => $score, 'final_score' => $score, 'damage_dealt' => 0,
            'status' => $score === null ? 'pending' : 'ml_scored']);
    }

    private function submit(GameSession $session)
    {
        return $this->postJson(route('student.game.submitRound', $session->id), [
            'recording' => UploadedFile::fake()->create('reading.wav', 1, 'audio/wav'),
            'word_or_passage' => 'the little cat sleeps',
        ]);
    }

    public function test_start_logs_activity_once_and_resume_does_not_duplicate_event(): void
    {
        $this->enemy->update(['level' => 1]);
        $this->get(route('student.game.start', $this->activity->id))->assertRedirect();
        $session = GameSession::sole();
        $this->get(route('student.game.start', $this->activity->id))
            ->assertRedirect(route('student.game.battle', $session->id));
        $log = \App\Models\ActivityLog::where('action', 'BATTLE_STARTED')->sole();
        $this->assertSame($this->student->user_id, $log->user_id);
        $this->assertStringContainsString('Reading (ID ' . $this->activity->id . ')', $log->description);
        $this->assertSame(1, GameSession::count());
    }

    public static function outcomes(): array
    {
        return [['won', 100], ['lost', 1000]];
    }

    #[DataProvider('outcomes')]
    public function test_both_outcomes_store_current_session_average(string $outcome, int $enemyHp): void
    {
        $session = $this->createGameSession(['rounds_played' => 4, 'enemy_current_hp' => $enemyHp, 'total_damage' => 777]);
        foreach ([80, 72, 91, 68, null] as $score) $this->round($session, $score);
        $oldSession = $this->createGameSession(['status' => 'lost']);
        $this->round($oldSession, 0);
        $this->round($oldSession, 100);
        Http::fake(['127.0.0.1:5000/*' => Http::response(['score' => 85, 'transcript' => 'cat',
            'expected_word_count' => 20, 'miscues' => 3, 'substitutions' => 1, 'deletions' => 1, 'insertions' => 1])]);
        $this->submit($session)->assertOk()->assertJsonPath('status', $outcome)
            ->assertJsonPath('ml_score', 85)->assertJsonPath('oral_reading_score', 85)
            ->assertJsonPath('damage', 178)->assertJsonPath('miscues', 3)
            ->assertJsonPath('substitutions', 1)->assertJsonPath('deletions', 1)->assertJsonPath('insertions', 1);
        $this->assertSame(79.2, (float) ActivityResult::firstOrFail()->score);
        $this->assertSame($outcome, $session->fresh()->status);
        $event = \App\Models\ActivityLog::where('action', $outcome === 'won' ? 'BATTLE_WON' : 'BATTLE_LOST')->sole();
        $this->assertSame($this->student->user_id, $event->user_id);
        $this->assertSame('student', $event->role);
        $this->assertStringContainsString('Reading (ID ' . $this->activity->id . ')', $event->description);
        $this->assertStringContainsString('Session #' . $session->id, $event->description);
        $this->submit($session)->assertNotFound();
        $this->assertSame(1, \App\Models\ActivityLog::where('action', $event->action)->count());
        $last = $session->rounds()->latest('id')->first();
        $this->assertSame(85.0, (float) $last->ml_score);
        $this->assertSame(85.0, (float) $last->final_score);
        $this->assertSame($outcome === 'won' ? 25 : 0, (int) $this->student->fresh()->total_points);
    }

    public static function failedResponses(): array
    {
        return [[['score' => null], 200], [[], 200], [['score' => 'invalid'], 200],
            [['score' => 100, 'error' => 'failed'], 200], [['score' => null], 500]];
    }

    #[DataProvider('failedResponses')]
    public function test_failed_analysis_stays_null_and_does_zero_damage(array $body, int $status): void
    {
        $session = $this->createGameSession(['rounds_played' => 4]);
        $this->round($session, 80);
        Http::fake(['127.0.0.1:5000/*' => Http::response($body, $status)]);
        $this->submit($session)->assertOk()->assertJsonPath('status', 'lost')
            ->assertJsonPath('ml_score', null)->assertJsonPath('damage', 0)->assertJsonPath('miscues', null);
        $last = $session->rounds()->latest('id')->first();
        $this->assertNull($last->ml_score);
        $this->assertNull($last->final_score);
        $this->assertSame('pending', $last->status);
        $this->assertSame(80.0, (float) ActivityResult::firstOrFail()->score);
    }

    public function test_connection_failure_is_not_a_zero_score(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('test unavailable'));
        $session = $this->createGameSession();
        $this->submit($session)->assertOk()->assertJsonPath('ml_score', null)->assertJsonPath('damage', 0);
        $this->assertNull($session->rounds()->first()->final_score);
    }

    public function test_average_includes_valid_zero_excludes_null_and_returns_zero_when_empty(): void
    {
        $method = new \ReflectionMethod(GameController::class, 'calculateBattlePerformance');
        $controller = new GameController;
        $session = $this->createGameSession();
        $this->assertSame(0.0, $method->invoke($controller, $session));
        $this->round($session, null);
        $this->assertSame(0.0, $method->invoke($controller, $session));
        foreach ([0, 66.67, 100] as $score) $this->round($session, $score);
        $this->assertSame(55.56, $method->invoke($controller, $session));
    }

    public static function bands(): array
    {
        return [[100, 'Excellent'], [90, 'Excellent'], [89.99, 'Great'], [75, 'Great'],
            [74.99, 'Good'], [60, 'Good'], [59.99, 'Keep Practicing'], [40, 'Keep Practicing'],
            [39.99, 'Try Again'], [0, 'Try Again']];
    }

    #[DataProvider('bands')]
    public function test_score_bands_and_legacy_response_compatibility(float $score, string $label): void
    {
        $session = $this->createGameSession();
        Http::fake(['127.0.0.1:5000/*' => Http::response(['score' => $score])]);
        $response = $this->submit($session)->assertOk()->assertJsonPath('status', 'ongoing')
            ->assertJsonPath('miscues', null)->assertJsonPath('damage', (int) round(50 + $score * 1.5));
        $this->assertStringStartsWith($label . '!', $response->json('message'));
        $this->assertSame($score, (float) $session->rounds()->first()->final_score);
    }

    public function test_battle_view_renders_shared_bands_and_nullable_history(): void
    {
        $session = $this->createGameSession();
        $this->round($session, 75);
        $this->round($session, null);
        $response = $this->get(route('student.game.battle', $session->id))->assertOk()
            ->assertSee('Oral Reading Score')->assertDontSee('AI Score')
            ->assertSee('75.00%')->assertSee('style="color:#3D82D9"', false)
            ->assertSee('style="color:#9CA3AF"', false);
        $this->assertStringContainsString('const SCORE_BANDS = ', $response->getContent());
        $this->assertStringNotContainsString('@json($scoreBands)', $response->getContent());
    }

    private function createIsolatedTables(): void
    {
        $tables = [
            'users' => ['username', 'role', 'status'],
            'students' => ['user_id', 'total_points'],
            'activities' => ['activity_name', 'points_reward', 'level'],
            'enemies' => ['name', 'max_hp', 'level'],
            'activity_word_bank' => ['activity_id', 'word', 'order'],
            'game_sessions' => ['student_id', 'activity_id', 'enemy_id', 'enemy_current_hp', 'enemy_max_hp',
                'student_current_hp', 'student_max_hp', 'total_damage', 'rounds_played', 'points_earned', 'status'],
            'game_rounds' => ['game_session_id', 'student_id', 'word_or_passage', 'recording_path',
                'ml_score', 'final_score', 'damage_dealt', 'status'],
            'activity_results' => ['student_id', 'activity_id', 'score', 'status', 'completed_at'],
            'badges' => ['name', 'criteria'],
            'activity_logs' => ['user_id', 'role', 'action', 'module', 'description', 'ip_address', 'user_agent'],
        ];
        foreach ($tables as $table => $columns) {
            DB::connection()->getSchemaBuilder()->create($table, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $column) {
                    if (in_array($column, ['score', 'ml_score', 'final_score'])) {
                        $t->decimal($column, 5, 2)->nullable();
                    } elseif (str_ends_with($column, '_id') || in_array($column, ['total_points', 'points_reward',
                        'level', 'max_hp', 'order', 'enemy_current_hp', 'enemy_max_hp', 'student_current_hp',
                        'student_max_hp', 'total_damage', 'rounds_played', 'points_earned', 'damage_dealt'])) {
                        $t->integer($column)->nullable();
                    } else {
                        $t->string($column)->nullable();
                    }
                }
                $t->timestamps();
            });
        }
    }
}
