<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Enemy;
use App\Models\GameSession;
use App\Models\GameRound;
use App\Helpers\LogActivity;

class GameController extends Controller
{
    // Shared with Blade/JavaScript so all Battle feedback uses the same bands.
    public const SCORE_BANDS = [
        ['min' => 90, 'label' => 'Excellent', 'color' => '#3FA86A', 'sound' => 'excellent', 'attack' => 4, 'aura' => 'auraExcellent 0.6s ease 3'],
        ['min' => 75, 'label' => 'Great', 'color' => '#3D82D9', 'sound' => 'great', 'attack' => 2, 'aura' => 'auraGood 0.6s ease 2'],
        ['min' => 60, 'label' => 'Good', 'color' => '#E0A11B', 'sound' => 'good', 'attack' => 1, 'aura' => 'auraGood 0.6s ease 2'],
        ['min' => 40, 'label' => 'Keep Practicing', 'color' => '#E07A2C', 'sound' => 'ok', 'attack' => 3, 'aura' => 'auraWeak 0.6s ease 2'],
        ['min' => 0, 'label' => 'Try Again', 'color' => '#E05B3C', 'sound' => 'weak', 'attack' => 0, 'aura' => 'auraWeak 0.6s ease 2'],
    ];

    // Game lobby
  public function index(Request $request)
{
    $student = auth()->user()->student;

    $search = $request->input('search');
    $level = $request->input('level');
    $statusFilter = $request->input('status');


    /*
    |--------------------------------------------------------------------------
    | Get the latest battle session for each activity
    |--------------------------------------------------------------------------
    */

    $sessions = GameSession::where('student_id', $student->id)
        ->latest('created_at')
        ->get()
        ->groupBy('activity_id')
        ->map(function ($activitySessions) {
            return $activitySessions->first();
        });


    /*
    |--------------------------------------------------------------------------
    | Get enemies
    |--------------------------------------------------------------------------
    */

    $enemies = Enemy::all();


    /*
    |--------------------------------------------------------------------------
    | Get available Battle Mode activities
    |--------------------------------------------------------------------------
    */

    $query = Activity::where('teacher_id', $student->teacher_id)
        ->where('is_published', true)
        ->where('battle_mode', true)
        ->where('level', '<=', $student->current_level)
        ->with('wordBank');


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    if ($search) {

        $query->where(function ($q) use ($search) {

            $q->where('activity_name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Level filter
    |--------------------------------------------------------------------------
    */

    if ($level) {
        $query->where('level', $level);
    }


    /*
    |--------------------------------------------------------------------------
    | Get activities
    |--------------------------------------------------------------------------
    */

    $activities = $query
        ->latest()
        ->get();


    /*
    |--------------------------------------------------------------------------
    | Status filter
    |--------------------------------------------------------------------------
    */

    if ($statusFilter) {

        $activities = $activities->filter(function ($activity) use (
            $sessions,
            $statusFilter
        ) {

            $session = $sessions->get($activity->id);

            $currentStatus = $session
                ? $session->status
                : 'new';

            return $currentStatus === $statusFilter;

        })->values();

    }


    /*
    |--------------------------------------------------------------------------
    | Return view
    |--------------------------------------------------------------------------
    */

    return view('student.game.index', compact(
        'activities',
        'sessions',
        'enemies',
        'search',
        'level',
        'statusFilter'
    ));
}

    // Start or resume a game session
  public function start($activityId)
{
    $student  = auth()->user()->student;
    $activity = Activity::findOrFail($activityId);
    $enemy    = Enemy::where('level', $activity->level)->first();

    if (!$enemy) {
        return redirect()->route('student.game.index')
               ->with('error', 'No enemy found for this level!');
    }

    // Resume ongoing session
    $existing = GameSession::where('student_id', $student->id)
                ->where('activity_id', $activityId)
                ->where('status', 'ongoing')
                ->first();

    if ($existing) {
        return redirect()->route('student.game.battle', $existing->id);
    }

    // Student max HP = same as enemy max HP for balance
    $studentMaxHp = $enemy->max_hp;

    $session = GameSession::create([
        'student_id'        => $student->id,
        'activity_id'       => $activityId,
        'enemy_id'          => $enemy->id,
        'enemy_current_hp'  => $enemy->max_hp,
        'enemy_max_hp'      => $enemy->max_hp,
        'student_current_hp'=> $studentMaxHp,
        'student_max_hp'    => $studentMaxHp,
        'total_damage'      => 0,
        'rounds_played'     => 0,
        'status'            => 'ongoing',
        'points_earned'     => 0,
    ]);

    LogActivity::forStudent($student, 'BATTLE_STARTED', 'Battle Arena',
        'Started battle: ' . $activity->activity_name . ' (ID ' . $activity->id . ')'
        . ' - Enemy: ' . $enemy->name . ' - Session #' . $session->id);

    return redirect()->route('student.game.battle', $session->id);
}

    // Battle screen
  public function battle($sessionId)
{
    $student = auth()->user()->student;
    $session = GameSession::where('student_id', $student->id)
               ->with(['enemy', 'activity.wordBank', 'rounds'])
               ->findOrFail($sessionId);

    if ($session->status === 'won') {
        return redirect()->route('student.game.index')
               ->with('info', '🏆 You already won this battle!');
    }
    if ($session->status === 'lost') {
        return redirect()->route('student.game.index')
               ->with('info', '💀 You lost this battle. Try again!');
    }

    $allWords   = $session->activity->wordBank
                  ->sortBy('order')->pluck('word')
                  ->values()->toArray();
    $totalWords = count($allWords);

    if (empty($allWords)) {
        return redirect()->route('student.game.index')
               ->with('error', 'No battle words set up yet!');
    }

    $roundIndex  = $session->rounds_played % $totalWords;
    $currentWord = $allWords[$roundIndex];
    $roundsLeft  = $totalWords - $session->rounds_played;
    $hpPercent   = max(0, round(($session->enemy_current_hp / $session->enemy_max_hp) * 100));

    // Student HP — use saved value from DB
    $studentMaxHp     = $session->student_max_hp ?: $session->enemy_max_hp;
    $studentCurrentHp = $session->student_current_hp ?? $studentMaxHp;
    $studentHpPct     = max(0, round(($studentCurrentHp / $studentMaxHp) * 100));

    $scoreBands = self::SCORE_BANDS;

    return view('student.game.battle', compact(
        'session', 'currentWord', 'hpPercent',
        'allWords', 'totalWords', 'roundIndex', 'roundsLeft',
        'studentMaxHp', 'studentCurrentHp', 'studentHpPct', 'scoreBands'
    ));
}

    // Submit a round recording
   public function submitRound(Request $request, $sessionId)
{
    $request->validate([
        'recording'         => 'required|file|mimes:webm,mp3,wav,ogg,mp4|max:20480',
        'word_or_passage'   => 'required|string',
        'student_current_hp'=> 'nullable|integer|min:0',
    ]);

    $student = auth()->user()->student;
    $session = GameSession::where('student_id', $student->id)
               ->where('status', 'ongoing')
               ->with(['enemy', 'activity.wordBank'])
               ->findOrFail($sessionId);

    $totalWords   = $session->activity->wordBank->count();
    $roundsPlayed = $session->rounds_played;

    // Store recording
    $path = $request->file('recording')->store('game_recordings', 'public');

    // Automated oral reading scoring (Whisper transcription + word alignment).
    $mlResult   = $this->callMLScoring(
        storage_path('app/public/' . $path),
        $request->word_or_passage
    );
    $mlScore    = $mlResult['score']      ?? null;
    $transcript = $mlResult['transcript'] ?? null;
    $miscues = $mlResult['miscues'] ?? null;
    $substitutions = $mlResult['substitutions'] ?? null;
    $deletions = $mlResult['deletions'] ?? null;
    $insertions = $mlResult['insertions'] ?? null;
    // Breakdown is response-only: game_rounds has no columns for these counts.
    $readingDetails = [
        'oral_reading_score' => $mlScore,
        'expected_word_count' => $mlResult['expected_word_count'] ?? null,
        'miscues' => $miscues,
        'substitutions' => $substitutions,
        'deletions' => $deletions,
        'insertions' => $insertions,
    ];

    // Calculate damage to enemy
    $damage = $mlScore !== null
        ? $this->calculateDamage($mlScore, $session->enemy_max_hp)
        : 0;

    // Calculate enemy damage to student (8–15% of student max HP)
    $studentMaxHp     = $session->student_max_hp ?: $session->enemy_max_hp;
    $enemyDamage      = (int) round($studentMaxHp * (0.08 + (mt_rand(0, 7) / 100)));
    $studentCurrentHp = $session->student_current_hp ?? $studentMaxHp;
    $newStudentHp     = max(0, $studentCurrentHp - $enemyDamage);

    // Save game round
    $round = GameRound::create([
        'game_session_id' => $session->id,
        'student_id'      => $student->id,
        'word_or_passage' => $request->word_or_passage,
        'recording_path'  => $path,
        'ml_score'        => $mlScore,
        'final_score'     => $mlScore,
        'damage_dealt'    => $damage,
        'status'          => $mlScore !== null ? 'ml_scored' : 'pending',
    ]);

    // Update enemy HP
    $newEnemyHp      = max(0, $session->enemy_current_hp - $damage);
    $newRoundsPlayed = $roundsPlayed + 1;
    $allWords = $session->activity->wordBank
        ->sortBy('order')
        ->pluck('word')
        ->values()
        ->toArray();
    $hpPercent       = round(($newEnemyHp / $session->enemy_max_hp) * 100);
    $roundsLeft      = $totalWords - $newRoundsPlayed;

    // Update session — save BOTH HPs
    $session->update([
        'enemy_current_hp'   => $newEnemyHp,
        'student_current_hp' => $newStudentHp,
        'rounds_played'      => $newRoundsPlayed,
        'total_damage'       => $session->total_damage + $damage,
    ]);

    // ── WIN ───────────────────────────────────────────────────
    if ($newEnemyHp <= 0) {
        $pointsEarned = $session->activity->points_reward;
        $session->update([
            'status'        => 'won',
            'points_earned' => $pointsEarned,
        ]);
        $student->increment('total_points', $pointsEarned);

        \App\Models\ActivityResult::updateOrCreate(
            ['student_id' => $student->id, 'activity_id' => $session->activity_id],
            ['score' => $this->calculateBattlePerformance($session),
             'status' => 'completed', 'completed_at' => now()]
        );

        $newBadges = \App\Services\BadgeService::checkAndAward($student);

        LogActivity::forStudent($student, 'BATTLE_WON', 'Battle Arena',
            'Won battle: ' . $session->activity->activity_name . ' (ID ' . $session->activity_id . ')'
            . ' - Enemy: ' . $session->enemy->name . ' - Session #' . $session->id
            . ' - Score: ' . $this->calculateBattlePerformance($session) . '%'
            . ' - Earned ' . $pointsEarned . ' points.');

        return response()->json([
            ...$readingDetails,
            'status'          => 'won',
            'ml_score'        => $mlScore,
            'transcript'      => $transcript,
            'damage'          => $damage,
            'enemy_hp'        => 0,
            'hp_percent'      => 0,
            'student_hp'      => $newStudentHp,
            'student_hp_pct'  => round(($newStudentHp / $studentMaxHp) * 100),
            'enemy_damage'    => $enemyDamage,
            'round_id'        => $round->id,
            'rounds_left'     => $roundsLeft,
            'points'          => $pointsEarned,
            'new_badges'      => collect($newBadges)->map(fn($b) => [
                'name' => $b->badge_name,
                'icon' => $b->badge_icon,
            ])->values(),
            'message'         => '🎉 You defeated the ' . $session->enemy->name . '!',
        ]);
    }

    // ── LOSE ──────────────────────────────────────────────────
    if ($newRoundsPlayed >= $totalWords) {
        $session->update(['status' => 'lost', 'student_current_hp' => 0]);

        \App\Models\ActivityResult::updateOrCreate(
            ['student_id' => $student->id, 'activity_id' => $session->activity_id],
            ['score' => $this->calculateBattlePerformance($session),
             'status' => 'completed', 'completed_at' => now()]
        );

        LogActivity::forStudent($student, 'BATTLE_LOST', 'Battle Arena',
            'Lost battle: ' . $session->activity->activity_name . ' (ID ' . $session->activity_id . ')'
            . ' - Enemy: ' . $session->enemy->name . ' - Session #' . $session->id
            . ' - Score: ' . $this->calculateBattlePerformance($session) . '%');

        return response()->json([
            ...$readingDetails,
            'status'         => 'lost',
            'ml_score'       => $mlScore,
            'transcript'     => $transcript,
            'damage'         => $damage,
            'enemy_hp'       => $newEnemyHp,
            'hp_percent'     => $hpPercent,
            'student_hp'     => 0,
            'student_hp_pct' => 0,
            'enemy_damage'   => $enemyDamage,
            'enemy_name'     => $session->enemy->name,
            'rounds_used'    => $newRoundsPlayed,
            'total_words'    => $totalWords,
            'message'        => '💀 You lost to ' . $session->enemy->name . '!',
        ]);
    }

    // ── ONGOING ───────────────────────────────────────────────
    if ($mlScore === null) {
        $message = '⏳ Could not analyze. Try again!';
    } else {
        $band = collect(self::SCORE_BANDS)->first(fn ($band) => $mlScore >= $band['min']);
        $message = "{$band['label']}! -{$damage} HP! {$roundsLeft} round(s) left.";
    }

    $nextRoundIndex = $newRoundsPlayed;
    $nextWord = $allWords[$nextRoundIndex] ?? null;

    return response()->json([
        ...$readingDetails,
        'status'           => 'ongoing',
        'ml_score'         => $mlScore,
        'transcript'       => $transcript,
        'damage'           => $damage,
        'enemy_hp'         => $newEnemyHp,
        'hp_percent'       => $hpPercent,
        'student_hp'       => $newStudentHp,
        'student_hp_pct'   => round(($newStudentHp / $studentMaxHp) * 100),
        'enemy_damage'     => $enemyDamage,
        'round_id'         => $round->id,
        'rounds_left'      => $roundsLeft,
        'rounds_used'      => $newRoundsPlayed,
        'total_words'     => $totalWords,
        'next_word'        => $nextWord,
        'next_round'       => $newRoundsPlayed + 1,
        'next_round_index' => $nextRoundIndex,
        'message'          => $message,
    ]);
}

    private function calculateBattlePerformance(GameSession $session): float
    {
        $average = $session->rounds()->whereNotNull('final_score')->avg('final_score');

        return round((float) ($average ?? 0), 2);
    }

    // Calculate damage from the oral reading score
    private function calculateDamage(float $score, int $maxHp): int
    {
        $baseDamage = $maxHp * 0.05;
        $bonusDamage = ($score / 100) * ($maxHp * 0.15);

        return (int) round($baseDamage + $bonusDamage);
    }

    // Call Python ML API
    private function callMLScoring(string $recordingPath, string $expectedText): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                        ->post('http://127.0.0.1:5000/score', [
                            'recording_path' => $recordingPath,
                            'expected_text'  => $expectedText,
                        ]);

            if ($response->successful()) {
                $score = $response->json('score');
                // A missing/null/invalid score is failed analysis, never a fake zero.
                if (!is_numeric($score) || !is_finite((float) $score) || $response->json('error')) {
                    return ['score' => null, 'transcript' => $response->json('transcript')];
                }

                return [
                    'score'          => round(max(0, min(100, (float) $score)), 2),
                    'expected_word_count' => $response->json('expected_word_count'),
                    'miscues'        => $response->json('miscues'),
                    'substitutions'  => $response->json('substitutions'),
                    'deletions'      => $response->json('deletions'),
                    'insertions'     => $response->json('insertions'),
                    'transcript'     => $response->json('transcript'),
                    'word_breakdown' => $response->json('word_breakdown'),
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('ML API unavailable: ' . $e->getMessage());
        }

        return ['score' => null, 'transcript' => null, 'word_breakdown' => null];
    }
}