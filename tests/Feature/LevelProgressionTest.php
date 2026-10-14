<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\ActivityResult;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LevelProgressionTest extends TestCase
{
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        // Disposable in-memory tables only: no migrations or project database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $tables = [
            'users' => ['username', 'role', 'status'],
            'students' => ['user_id', 'teacher_id', 'current_level', 'total_points'],
            'activities' => ['teacher_id', 'activity_name', 'activity_type', 'level', 'points_reward',
                'is_published', 'battle_mode'],
            'activity_results' => ['student_id', 'activity_id', 'score', 'status', 'mistakes', 'time_spent', 'completed_at'],
            'activity_logs' => ['user_id', 'role', 'action', 'module', 'description', 'ip_address', 'user_agent'],
            'badges' => ['badge_name', 'criteria'],
            'student_badges' => ['student_id', 'badge_id', 'earned_at'],
            'activity_word_bank' => ['activity_id', 'word', 'order'],
        ];
        foreach ($tables as $table => $columns) {
            DB::connection()->getSchemaBuilder()->create($table, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $column) {
                    if ($column === 'score') {
                        $t->decimal($column, 5, 2)->nullable();
                    } elseif (str_ends_with($column, '_id') || in_array($column, [
                        'current_level', 'total_points', 'level', 'points_reward', 'mistakes', 'time_spent', 'order',
                    ])) {
                        $t->integer($column)->nullable();
                    } else {
                        $t->string($column)->nullable();
                    }
                }
                $t->timestamps();
            });
        }
        $user = User::create(['username' => 'progress_reader', 'role' => 'student', 'status' => 'active']);
        $this->student = Student::create(['user_id' => $user->id, 'teacher_id' => 1,
            'current_level' => 1, 'total_points' => 0]);
        $this->actingAs($user);
    }

    private function activity(int $level = 1, array $attributes = []): Activity
    {
        return Activity::create(array_replace(['teacher_id' => 1, 'activity_name' => 'Reading practice',
            'activity_type' => 'Phonics', 'level' => $level, 'points_reward' => 50,
            'is_published' => true, 'battle_mode' => false], $attributes));
    }

    private function readingResult(int $level, ?float $score, string $status = 'completed', ?int $studentId = null): void
    {
        ActivityResult::create(['student_id' => $studentId ?? $this->student->id,
            'activity_id' => $this->activity($level)->id, 'score' => $score, 'status' => $status]);
    }

    public static function eligibilityExamples(): array
    {
        return [
            'both pass' => [1, 500, [100, 100, 100], 2, 100.0],
            'one imperfect result' => [1, 500, [100, 100, 99], 1, 99.6666666667],
            'accuracy fails' => [1, 700, [70, 75, 78], 1, 74.3333333333],
            'former threshold is insufficient' => [1, 500, [80], 1, 80.0],
            'points fail' => [1, 450, [100, 100], 1, 100.0],
            'no scored activities' => [1, 500, [], 1, null],
            'exact accuracy threshold' => [1, 500, [100], 2, 100.0],
            '99.5 is not perfect' => [1, 700, [99.5], 1, 99.5],
            '99.9 is not perfect' => [1, 700, [99.9], 1, 99.9],
            '99.99 is not perfect' => [1, 700, [99.99], 1, 99.99],
            'do not round up eligibility' => [1, 500, [99.99, 100, 100], 1, 99.9966666667],
            'missing one point' => [1, 499, [100], 1, 100.0],
            'zero is a valid score' => [1, 500, [0, 100], 1, 50.0],
            'level 2 advances' => [2, 1050, [100, 100, 100], 3, 100.0],
            'supported level 4' => [3, 1500, [100], 4, 100.0],
            'supported level 5' => [4, 2000, [100], 5, 100.0],
            'maximum level' => [5, 9999, [100], 5, 100.0],
            'preserve legacy higher level' => [7, 9999, [100], 7, 100.0],
        ];
    }

    #[DataProvider('eligibilityExamples')]
    public function test_points_and_current_level_reading_are_both_required(
        int $level, int $points, array $scores, int $expectedLevel, ?float $average
    ): void {
        $this->student->update(['current_level' => $level, 'total_points' => $points]);
        foreach ($scores as $score) $this->readingResult($level, $score);
        $progress = $this->student->levelProgress();
        $this->assertSame(100.0, $progress['required_score']);
        if ($average === null) {
            $this->assertNull($progress['average_score']);
        } else {
            $this->assertEqualsWithDelta($average, $progress['average_score'], 0.000001);
        }
        $levels = $this->student->checkAndUpdateLevel();
        $this->assertSame($expectedLevel, (int) $this->student->fresh()->current_level);
        $this->assertSame($points, (int) $this->student->fresh()->total_points);
        $this->assertSame($expectedLevel > $level ? [$expectedLevel] : null, $levels);
        $this->assertNull($this->student->checkAndUpdateLevel());
        $this->assertSame($expectedLevel > $level ? 1 : 0, ActivityLog::where('action', 'LEVEL_UP')->count());
    }

    public function test_only_completed_nonnull_current_level_results_for_this_student_count(): void
    {
        $this->student->update(['total_points' => 700]);
        $this->readingResult(0, 100);
        $this->readingResult(2, 100);
        $this->readingResult(1, 100, 'pending');
        $this->readingResult(1, 100, 'completed', 999);
        $this->readingResult(1, null);
        $this->assertNull($this->student->levelProgress()['average_score']);
        $this->assertNull($this->student->checkAndUpdateLevel());

        $this->readingResult(1, 75);
        $this->assertSame(75.0, $this->student->levelProgress()['average_score']);
        $this->assertNull($this->student->checkAndUpdateLevel());
    }

    public function test_points_cannot_skip_a_level_without_its_own_reading_results(): void
    {
        $this->student->update(['total_points' => 1600]);
        $this->readingResult(1, 100);
        $this->readingResult(3, 100);
        $staleStudent = $this->student->fresh();
        $this->assertSame([2], $this->student->checkAndUpdateLevel());
        $this->assertNull($staleStudent->checkAndUpdateLevel());
        $this->assertSame(2, (int) $staleStudent->current_level);
        $this->assertSame(1, ActivityLog::where('action', 'LEVEL_UP')->count());

        $this->readingResult(2, 99.99);
        $this->assertNull($this->student->checkAndUpdateLevel());
        $this->assertSame(2, (int) $this->student->fresh()->current_level);
    }

    public function test_multiple_levels_require_each_level_and_stop_at_maximum(): void
    {
        $this->student->update(['total_points' => 4000]);
        foreach ([1, 2, 3, 4, 5] as $level) $this->readingResult($level, 100);
        $this->assertSame([2, 3, 4, 5], $this->student->checkAndUpdateLevel());
        $this->assertSame(5, (int) $this->student->fresh()->current_level);
        $this->assertSame(4000, (int) $this->student->fresh()->total_points);
        $this->assertNull($this->student->checkAndUpdateLevel());
        $this->assertSame(1, ActivityLog::where('action', 'LEVEL_UP')->count());
    }

    public function test_generic_activity_completion_awards_and_levels_up_only_once(): void
    {
        $this->student->update(['total_points' => 450]);
        $activity = $this->activity();
        $this->post(route('student.activities.submit', $activity->id), ['score' => 100])
            ->assertRedirect()->assertSessionHas('success', 'Level Up! You reached Level 2!');
        $this->post(route('student.activities.submit', $activity->id), ['score' => 100])->assertRedirect();
        $this->assertSame(500, (int) $this->student->fresh()->total_points);
        $this->assertSame(2, (int) $this->student->fresh()->current_level);
        $this->assertSame(1, ActivityResult::count());
        $this->assertSame(1, ActivityLog::where('action', 'COMPLETE_ACTIVITY')->count());
        $this->assertSame(1, ActivityLog::where('action', 'LEVEL_UP')->count());
    }

    public function test_higher_level_activity_show_and_submit_are_blocked(): void
    {
        $activity = $this->activity(2);
        $this->get(route('student.activities.show', $activity->id))->assertNotFound();
        $this->post(route('student.activities.submit', $activity->id), ['score' => 100])->assertNotFound();
        $this->assertSame(0, ActivityResult::count());
        $this->assertSame(0, (int) $this->student->fresh()->total_points);
    }

    public function test_generic_endpoint_cannot_bypass_battle_or_teacher_evaluation(): void
    {
        foreach ([
            ['battle_mode' => true],
            ['activity_type' => 'Read Aloud'],
        ] as $attributes) {
            $activity = $this->activity(1, $attributes);
            $this->post(route('student.activities.submit', $activity->id), ['score' => 100])->assertForbidden();
        }
        $this->assertSame(0, ActivityResult::count());
    }

    public function test_read_aloud_show_and_upload_keep_level_restrictions(): void
    {
        $activity = $this->activity(2, ['activity_type' => 'Read Aloud']);
        $this->get(route('student.readaloud.show', $activity->id))->assertNotFound();
        $this->postJson(route('student.readaloud.upload', $activity->id))->assertNotFound();
    }

    public function test_display_does_not_round_a_failing_average_up_to_a_passing_score(): void
    {
        $this->student->update(['total_points' => 500]);
        foreach ([99.99, 100, 100] as $score) $this->readingResult(1, $score);
        $html = view('student.partials.level-progress', ['levelProgress' => $this->student->levelProgress()])->render();
        $this->assertStringContainsString('&lt;100%', $html);
        $this->assertStringNotContainsString('100.00%', $html);
        $this->assertStringContainsString('Achieve a 100% reading average to advance.', $html);
    }

    public static function progressMessages(): array
    {
        return [
            [1, 550, 98, 'Points requirement reached. Achieve a 100% reading average to advance.'],
            [1, 350, 100, 'Reading requirement passed. Earn 150 more points to advance.'],
            [1, 900, null, 'N/A'],
            [5, 4000, 100, 'Maximum level reached!'],
        ];
    }

    #[DataProvider('progressMessages')]
    public function test_progress_display_explains_both_requirements(int $level, int $points, ?float $score, string $message): void
    {
        $this->student->update(['current_level' => $level, 'total_points' => $points]);
        if ($score !== null) $this->readingResult($level, $score);
        $html = view('student.partials.level-progress', ['levelProgress' => $this->student->levelProgress()])->render();
        $this->assertStringContainsString($message, $html);
        if ($level < Student::MAX_LEVEL) {
            $this->assertStringContainsString('/ 100%', $html);
            $this->assertStringNotContainsString('/ 80%', $html);
        }
        $this->assertStringNotContainsString('Next Level: 6', $html);
        $this->assertStringNotContainsString('-100 more points', $html);
        if ($score === null) $this->assertStringNotContainsString('0.00%', $html);
    }
}
