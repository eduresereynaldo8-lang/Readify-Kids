<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityWordBank;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BattleActivityManagementTest extends TestCase
{
    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        // Disposable in-memory tables only; no migrations or project database access.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'session.driver' => 'array']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $tables = [
            'users' => ['username', 'role', 'status'],
            'teachers' => ['user_id', 'firstname', 'lastname'],
            'activities' => ['teacher_id', 'activity_name', 'description', 'activity_type', 'level',
                'difficulty_level', 'duration_minutes', 'points_reward', 'is_published', 'allow_reattempt', 'battle_mode'],
            'activity_word_bank' => ['activity_id', 'word', 'order', 'type'],
            'activity_logs' => ['user_id', 'role', 'action', 'module', 'description', 'ip_address', 'user_agent'],
        ];
        foreach ($tables as $table => $columns) {
            DB::connection()->getSchemaBuilder()->create($table, function (Blueprint $t) use ($columns) {
                $t->id();
                foreach ($columns as $column) {
                    if (str_ends_with($column, '_id') || in_array($column, ['level', 'duration_minutes',
                        'points_reward', 'is_published', 'allow_reattempt', 'battle_mode', 'order'])) {
                        $t->integer($column)->nullable();
                    } else {
                        $t->text($column)->nullable();
                    }
                }
                $t->timestamps();
            });
        }
        $user = User::create(['username' => 'battle_teacher', 'role' => 'teacher', 'status' => 'active']);
        $this->teacher = Teacher::create(['user_id' => $user->id, 'firstname' => 'Battle', 'lastname' => 'Teacher']);
        $this->actingAs($user);
    }

    private function battleInput(mixed $items): array
    {
        return [
            'activity_name' => 'Teacher battle', 'description' => 'Reading practice', 'level' => 3,
            'difficulty_level' => 'Easy', 'duration_minutes' => 5, 'points_reward' => 25,
            'is_published' => 1, 'allow_reattempt' => 1, 'battle_words' => $items,
        ];
    }

    public static function readingContent(): array
    {
        return [
            'word' => ['cat', 'word'],
            'phrase' => ['extraordinarily bright stars', 'phrase'],
            'sentence or paragraph' => ['The little cat sleeps on a warm blanket.', 'paragraph'],
            'zero is legitimate text' => ['0', 'word'],
        ];
    }

    #[DataProvider('readingContent')]
    public function test_teacher_can_create_and_edit_battle_with_one_item(string $text, string $type): void
    {
        $this->post(route('teacher.activities.store.battle'), $this->battleInput([$text]))
            ->assertRedirect(route('teacher.activities.index'))->assertSessionHasNoErrors();
        $activity = Activity::sole();
        $this->assertSame($this->teacher->id, (int) $activity->teacher_id);
        $this->assertSame(1, (int) $activity->battle_mode);
        $this->assertSame($text, $activity->wordBank()->sole()->word);
        $this->assertSame($type, $activity->wordBank()->sole()->type);

        $this->put(route('teacher.activities.update.battle', $activity->id), $this->battleInput(['  dog  ']))
            ->assertRedirect(route('teacher.activities.index'))->assertSessionHasNoErrors();
        $this->assertSame('dog', $activity->wordBank()->sole()->word);
        $this->assertSame(1, ActivityWordBank::count());
    }

    public static function emptyContent(): array
    {
        return [
            'missing' => [null],
            'empty list' => [[]],
            'blank' => [['']],
            'whitespace' => [['   ']],
            'null item' => [[null]],
            'blank additional item' => [['cat', '']],
        ];
    }

    #[DataProvider('emptyContent')]
    public function test_create_and_edit_reject_empty_readings_without_losing_existing_items(mixed $items): void
    {
        $this->postJson(route('teacher.activities.store.battle'), $this->battleInput($items))
            ->assertUnprocessable();
        $this->assertSame(0, Activity::count());
        $activity = Activity::create([
            'teacher_id' => $this->teacher->id, 'activity_name' => 'Existing battle', 'battle_mode' => true,
        ]);
        $activity->wordBank()->create(['word' => 'Keep this reading', 'order' => 0, 'type' => 'phrase']);

        $this->putJson(route('teacher.activities.update.battle', $activity->id), $this->battleInput($items))
            ->assertUnprocessable();
        $this->assertSame('Existing battle', $activity->fresh()->activity_name);
        $this->assertSame('Keep this reading', $activity->wordBank()->sole()->word);
    }

    public function test_mixed_content_retains_teacher_order_and_existing_types(): void
    {
        $items = ['cat', 'extraordinarily bright stars', 'The little cat sleeps on a warm blanket.'];
        $this->post(route('teacher.activities.store.battle'), $this->battleInput($items))
            ->assertRedirect()->assertSessionHasNoErrors();
        $entries = Activity::sole()->wordBank()->orderBy('order')->get();
        $this->assertSame($items, $entries->pluck('word')->all());
        $this->assertSame(['word', 'phrase', 'paragraph'], $entries->pluck('type')->all());
        $this->assertSame([0, 1, 2], $entries->pluck('order')->all());
    }
}
