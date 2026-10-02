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

    private function submit(GameSession $session, string $text = 'the little cat sleeps')
    {
        return $this->postJson(route('student.game.submitRound', $session->id), [
            'recording' => UploadedFile::fake()->create('reading.wav', 1, 'audio/wav'),
            'word_or_passage' => $text,
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
            ->assertJsonPath('damage', 170)->assertJsonPath('miscues', 3)
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
            ->assertJsonPath('miscues', null)->assertJsonPath('damage', (int) round(200 * $score / 100));
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

    private function setReadingItems(int $count): array
    {
        $this->activity->wordBank()->delete();
        // Insert in reverse order to also check teacher-defined round ordering.
        for ($index = $count - 1; $index >= 0; $index--) {
            $this->activity->wordBank()->create([
                'word' => 'Reading item '.($index + 1),
                'order' => $index,
                'type' => ['word', 'phrase', 'paragraph'][$index % 3],
            ]);
        }

        return $this->activity->wordBank()->orderBy('order')->pluck('word')->all();
    }

    public static function perfectBattles(): array
    {
        return [
            'level 1 one item' => [1, 500, [500]],
            'level 1 two items' => [1, 500, [250, 250]],
            'level 1 remainder' => [1, 500, [167, 167, 166]],
            'level 1 four items' => [1, 500, [125, 125, 125, 125]],
            'level 1 five items' => [1, 500, array_fill(0, 5, 100)],
            'level 1 ten items' => [1, 500, array_fill(0, 10, 50)],
            'level 1 twelve items' => [1, 500, [42, 42, 42, 42, 42, 42, 42, 42, 41, 41, 41, 41]],
            'level 2 requested example' => [2, 1000, [250, 250, 250, 250]],
            'level 2 seeded HP' => [2, 750, [188, 188, 187, 187]],
            'level 3 requested example' => [3, 1500, [500, 500, 500]],
            'level 3 seeded HP' => [3, 1000, [334, 333, 333]],
            'future level' => [5, 2003, [668, 668, 667]],
        ];
    }

    #[DataProvider('perfectBattles')]
    public function test_perfect_readings_use_all_enemy_hp_and_win(int $level, int $hp, array $allocations): void
    {
        $items = $this->setReadingItems(count($allocations));
        $this->activity->update(['level' => $level]);
        $this->enemy->update(['level' => $level, 'max_hp' => $hp]);
        // Unrelated word-bank rows must never increase this battle's round count.
        Activity::create(['activity_name' => 'Other activity'])->wordBank()
            ->create(['word' => 'Unrelated reading', 'order' => 0, 'type' => 'word']);
        \App\Models\Badge::create([
            'badge_name' => 'First Victory', 'badge_icon' => 'star', 'criteria' => 'first_battle_win',
        ]);
        Http::fake(['127.0.0.1:5000/*' => Http::response(['score' => 100])]);

        $this->get(route('student.game.start', $this->activity->id))->assertRedirect();
        $session = GameSession::sole();
        $this->get(route('student.game.battle', $session->id))->assertOk()
            ->assertViewHas('totalWords', count($items))->assertViewHas('currentWord', $items[0]);
        $remainingHp = $hp;

        foreach ($allocations as $index => $allocation) {
            $remainingHp -= $allocation;
            $lastRound = $index === count($items) - 1;
            $response = $this->submit($session, $items[$index])->assertOk()
                ->assertJsonPath('damage', $allocation)
                ->assertJsonPath('enemy_hp', $remainingHp)
                ->assertJsonPath('rounds_left', count($items) - $index - 1)
                ->assertJsonPath('status', $lastRound ? 'won' : 'ongoing');
            if (!$lastRound) {
                $response->assertJsonPath('next_word', $items[$index + 1])
                    ->assertJsonPath('next_round', $index + 2)
                    ->assertJsonPath('next_round_index', $index + 1);
            }
            $state = $session->fresh();
            $this->assertSame($index + 1, (int) $state->rounds_played);
            $this->assertSame($hp - $remainingHp, (int) $state->total_damage);
            $this->assertGreaterThanOrEqual(0, $state->student_current_hp);
            $this->assertGreaterThanOrEqual((int) round($hp * 0.08), $response->json('enemy_damage'));
            $this->assertLessThanOrEqual((int) round($hp * 0.15), $response->json('enemy_damage'));
        }

        $response->assertJsonPath('new_badges.0.name', 'First Victory')->assertJsonPath('points', 25);
        $this->assertSame(100.0, (float) ActivityResult::sole()->score);
        $this->assertSame(25, (int) $this->student->fresh()->total_points);
        $this->assertSame($allocations, $session->rounds()->orderBy('id')->pluck('damage_dealt')->all());
        $this->assertSame(1, \App\Models\StudentBadge::count());
        $this->assertSame(1, \App\Models\ActivityLog::where('action', 'BATTLE_WON')->count());
        Http::assertSentCount(count($items));
        foreach ($session->rounds()->get() as $round) {
            Storage::disk('public')->assertExists($round->recording_path);
        }

        // Finished battles cannot consume another item or grant another reward.
        $this->submit($session)->assertNotFound();
        $this->assertSame(count($items), $session->rounds()->count());
        $this->assertSame(25, (int) $this->student->fresh()->total_points);
    }

    public function test_mixed_scores_lose_after_last_item_and_average_reading_scores(): void
    {
        $items = $this->setReadingItems(3);
        $session = $this->createGameSession(['enemy_current_hp' => 500, 'enemy_max_hp' => 500]);
        Http::fake(['127.0.0.1:5000/*' => Http::sequence()
            ->push(['score' => 100])->push(['score' => 80])->push(['score' => 90])]);

        foreach ([167, 134, 149] as $index => $damage) {
            $response = $this->submit($session, $items[$index])->assertOk()->assertJsonPath('damage', $damage)
                ->assertJsonPath('status', $index === 2 ? 'lost' : 'ongoing');
        }

        $response->assertJsonPath('enemy_hp', 50)->assertJsonPath('rounds_used', 3)
            ->assertJsonPath('student_hp', 0);
        $this->assertSame(450, (int) $session->fresh()->total_damage);
        $this->assertSame(90.0, (float) ActivityResult::sole()->score);
        $this->assertSame(0, (int) $this->student->fresh()->total_points);
        $this->submit($session)->assertNotFound();
        $this->assertSame(3, $session->rounds()->count());
        $this->assertSame(1, \App\Models\ActivityLog::where('action', 'BATTLE_LOST')->count());
    }

    public function test_empty_battle_cannot_start_or_accept_a_recording(): void
    {
        $this->setReadingItems(0);
        $this->enemy->update(['level' => 1]);
        $this->get(route('student.game.start', $this->activity->id))
            ->assertRedirect(route('student.game.index'))->assertSessionHas('error');
        $this->assertSame(0, GameSession::count());
        $this->assertSame(0, \App\Models\ActivityLog::count());

        // Also guard sessions whose teacher subsequently removed the content.
        $session = $this->createGameSession();
        $this->submit($session)->assertUnprocessable()->assertJsonValidationErrors('battle');
        $this->assertSame(0, $session->rounds()->count());
        $this->assertSame(0, (int) $session->fresh()->rounds_played);
        $this->assertSame([], Storage::disk('public')->allFiles('game_recordings'));
        Http::assertNothingSent();
    }

    public function test_exhausted_ongoing_session_does_not_wrap_to_first_reading(): void
    {
        $this->setReadingItems(2);
        $session = $this->createGameSession(['rounds_played' => 2]);
        $this->get(route('student.game.battle', $session->id))
            ->assertRedirect(route('student.game.index'))->assertSessionHas('error');
        $this->submit($session)->assertUnprocessable()->assertJsonValidationErrors('battle');
        $this->assertSame(0, $session->rounds()->count());
        Http::assertNothingSent();
    }

    public static function boundedScores(): array
    {
        return [
            'above 100' => [140, 100, 250],
            'negative' => [-20, 0, 0],
            'zero without minimum damage' => [0, 0, 0],
            'failed transcription' => [null, null, 0],
            'direct score scaling' => [60, 60, 150],
        ];
    }

    #[DataProvider('boundedScores')]
    public function test_damage_respects_score_bounds_and_null_analysis(?int $raw, ?int $expected, int $damage): void
    {
        $this->setReadingItems(2);
        $session = $this->createGameSession(['enemy_current_hp' => 500, 'enemy_max_hp' => 500]);
        Http::fake(['127.0.0.1:5000/*' => Http::response(['score' => $raw])]);
        $this->submit($session)->assertOk()->assertJsonPath('damage', $damage)
            ->assertJsonPath('ml_score', $expected)->assertJsonPath('enemy_hp', 500 - $damage);
        $round = $session->rounds()->sole();
        $this->assertEquals($expected, $round->final_score);
        $this->assertSame($expected === null ? 'pending' : 'ml_scored', $round->status);
        if ($expected === null) {
            $this->assertNull($round->final_score);
        }
    }

    public function test_resume_keeps_round_allocation_and_reattempt_starts_fresh(): void
    {
        $items = $this->setReadingItems(3);
        $this->enemy->update(['level' => 1, 'max_hp' => 500]);
        Http::fake(['127.0.0.1:5000/*' => Http::response(['score' => 100])]);
        $this->get(route('student.game.start', $this->activity->id))->assertRedirect();
        $session = GameSession::sole();
        $this->submit($session, $items[0])->assertOk()->assertJsonPath('damage', 167);
        $this->get(route('student.game.start', $this->activity->id))
            ->assertRedirect(route('student.game.battle', $session->id));
        $this->get(route('student.game.battle', $session->id))->assertOk()
            ->assertViewHas('currentWord', $items[1])->assertViewHas('roundIndex', 1)
            ->assertViewHas('totalWords', 3)->assertViewHas('roundsLeft', 2);
        $this->submit($session, $items[1])->assertOk()->assertJsonPath('damage', 167);
        $this->submit($session, $items[2])->assertOk()->assertJsonPath('damage', 166)->assertJsonPath('status', 'won');
        $this->get(route('student.game.start', $this->activity->id))->assertRedirect();
        $retry = GameSession::latest('id')->firstOrFail();
        $this->assertNotSame($session->id, $retry->id);
        $this->assertSame(0, (int) $retry->rounds_played);
        $this->assertSame(500, (int) $retry->enemy_current_hp);
        $this->submit($retry, $items[0])->assertOk()->assertJsonPath('damage', 167);
        $this->assertSame(2, \App\Models\ActivityLog::where('action', 'BATTLE_STARTED')->count());
    }

    private function createIsolatedTables(): void
    {
        $tables = [
            'users' => ['username', 'role', 'status'],
            'students' => ['user_id', 'total_points'],
            'activities' => ['activity_name', 'points_reward', 'level'],
            'enemies' => ['name', 'max_hp', 'level'],
            'activity_word_bank' => ['activity_id', 'word', 'order', 'type'],
            'game_sessions' => ['student_id', 'activity_id', 'enemy_id', 'enemy_current_hp', 'enemy_max_hp',
                'student_current_hp', 'student_max_hp', 'total_damage', 'rounds_played', 'points_earned', 'status'],
            'game_rounds' => ['game_session_id', 'student_id', 'word_or_passage', 'recording_path',
                'ml_score', 'final_score', 'damage_dealt', 'status'],
            'activity_results' => ['student_id', 'activity_id', 'score', 'status', 'completed_at'],
            'badges' => ['badge_name', 'badge_icon', 'criteria'],
            'student_badges' => ['student_id', 'badge_id', 'earned_at'],
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
