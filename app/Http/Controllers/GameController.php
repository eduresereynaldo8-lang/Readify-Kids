<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Enemy;
use App\Models\GameSession;
use App\Models\GameRound;

class GameController extends Controller
{
    // Show game lobby — pick an activity to battle
    public function index()
    {
        $student    = auth()->user()->student;
        $activities = Activity::where('is_published', true)
                      ->where('level', '<=', $student->current_level)
                      ->where('battle_mode', true)
                      ->whereIn('activity_type', ['Phonics','Word Game','Vocabulary','Word Recognition','Sound Blending'])
                      ->get();

        $enemies = Enemy::all()->keyBy('level');

        return view('student.game.index', compact('activities', 'enemies'));
    }

    // Start a new game session
    public function start($activityId)
    {
        $student  = auth()->user()->student;
        $activity = Activity::findOrFail($activityId);
        $enemy    = Enemy::where('level', $activity->level)->first();

        if (!$enemy) {
            return redirect()->route('student.game.index')
                   ->with('error', 'No enemy found for this level!');
        }

        // Check for existing ongoing session
        $existing = GameSession::where('student_id', $student->id)
                    ->where('activity_id', $activityId)
                    ->where('status', 'ongoing')
                    ->first();

        if ($existing) {
            return redirect()->route('student.game.battle', $existing->id);
        }

        // Create new game session
        $session = GameSession::create([
            'student_id'       => $student->id,
            'activity_id'      => $activityId,
            'enemy_id'         => $enemy->id,
            'enemy_current_hp' => $enemy->max_hp,
            'enemy_max_hp'     => $enemy->max_hp,
            'status'           => 'ongoing',
        ]);

        return redirect()->route('student.game.battle', $session->id);
    }

    // Battle screen
   public function battle($sessionId)
{
    $student = auth()->user()->student;
    $session = GameSession::where('student_id', $student->id)
               ->with(['enemy', 'activity.wordBank', 'rounds'])
               ->findOrFail($sessionId);

    if ($session->status !== 'ongoing') {
        return redirect()->route('student.game.index')
               ->with('info', 'This battle has already ended!');
    }

    // Get all words ordered
    $allWords = $session->activity->wordBank
                ->sortBy('order')
                ->pluck('word')
                ->values()
                ->toArray();

    if (empty($allWords)) {
        return redirect()->route('student.game.index')
               ->with('error', 'This activity has no battle words set up yet!');
    }

    // Pick next word based on rounds played (cycle through)
    $roundIndex  = $session->rounds_played % count($allWords);
    $currentWord = $allWords[$roundIndex];
    $totalWords  = count($allWords);

    $hpPercent = max(0, round(($session->enemy_current_hp / $session->enemy_max_hp) * 100));

    return view('student.game.battle', compact(
        'session', 'currentWord', 'hpPercent', 'allWords', 'totalWords', 'roundIndex'
    ));
}
    // Submit a round recording
   public function submitRound(Request $request, $sessionId)
{
    $request->validate([
        'recording'       => 'required|file|mimes:webm,mp3,wav,ogg,mp4|max:20480',
        'word_or_passage' => 'required|string',
    ]);

    $student = auth()->user()->student;
    $session = GameSession::where('student_id', $student->id)
               ->where('status', 'ongoing')
               ->findOrFail($sessionId);

    // Store recording
    $path = $request->file('recording')->store('game_recordings', 'public');

    // Call ML scoring
    $mlResult = $this->callMLScoring(
        storage_path('app/public/' . $path),
        $request->word_or_passage
    );

    $mlScore    = $mlResult['score']      ?? null;
    $transcript = $mlResult['transcript'] ?? null;

    // Calculate damage — if ML fails use 0 damage
    $damage = $mlScore !== null
        ? $this->calculateDamage($mlScore, $session->enemy_max_hp)
        : 0;

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

    // Update session
    $newHp = max(0, $session->enemy_current_hp - $damage);
    $session->increment('rounds_played');
    $session->increment('total_damage', $damage);
    $session->update(['enemy_current_hp' => $newHp]);

    // Check if enemy defeated
    if ($newHp <= 0) {
        $pointsEarned = $session->activity->points_reward;
        $session->update([
            'status'        => 'won',
            'points_earned' => $pointsEarned,
        ]);
        $student->increment('total_points', $pointsEarned);

        // Save activity result
        \App\Models\ActivityResult::updateOrCreate(
            [
                'student_id'  => $student->id,
                'activity_id' => $session->activity_id,
            ],
            [
                'score'        => round($session->total_damage / $session->rounds_played / $session->enemy_max_hp * 100, 1),
                'status'       => 'completed',
                'completed_at' => now(),
            ]
        );

        return response()->json([
            'status'     => 'won',
            'ml_score'   => $mlScore,
            'transcript' => $transcript,
            'damage'     => $damage,
            'enemy_hp'   => 0,
            'hp_percent' => 0,
            'points'     => $pointsEarned,
            'message'    => '🎉 You defeated the ' . $session->enemy->name . '!',
        ]);
    }

    $hpPercent = round(($newHp / $session->enemy_max_hp) * 100);

    // Build response message based on score
    if ($mlScore === null) {
        $message = '⏳ Could not analyze recording. Try again!';
    } elseif ($mlScore >= 90) {
        $message = "🔥 Excellent reading! -{$damage} HP damage!";
    } elseif ($mlScore >= 70) {
        $message = "⚔️ Good job! -{$damage} HP damage!";
    } elseif ($mlScore >= 50) {
        $message = "👍 Keep practicing! -{$damage} HP damage!";
    } else {
        $message = "💪 Try to read more clearly! -{$damage} HP damage!";
    }

    return response()->json([
        'status'     => 'ongoing',
        'ml_score'   => $mlScore,
        'transcript' => $transcript,
        'damage'     => $damage,
        'enemy_hp'   => $newHp,
        'hp_percent' => $hpPercent,
        'round_id'   => $round->id,
        'message'    => $message,
    ]);
}

    // Calculate damage from score
    private function calculateDamage(float $score, int $maxHp): int
    {
        // Max damage per round = 20% of enemy max HP
        // Score 100 = full 20%, Score 50 = 10%, etc.
        $maxDamagePerRound = $maxHp * 0.20;
        return (int) round(($score / 100) * $maxDamagePerRound);
    }

    // Call Python ML API for voice scoring
    private function callMLScoring(string $recordingPath, string $expectedText): array
{
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(30)->post('http://127.0.0.1:5000/score', [
            'recording_path' => $recordingPath,
            'expected_text'  => $expectedText,
        ]);

        if ($response->successful()) {
            return [
                'score'          => (float) $response->json('score'),
                'transcript'     => $response->json('transcript'),
                'word_breakdown' => $response->json('word_breakdown'),
            ];
        }
    } catch (\Exception $e) {
        \Log::warning('ML API unavailable: ' . $e->getMessage());
    }

    return ['score' => null, 'transcript' => null, 'word_breakdown' => null];
}

    // Teacher override score
    public function overrideScore(Request $request, $roundId)
    {
        $request->validate(['teacher_score' => 'required|numeric|min:0|max:100']);

        $round   = GameRound::findOrFail($roundId);
        $session = $round->session;

        $oldDamage = $round->damage_dealt;
        $newDamage = $this->calculateDamage($request->teacher_score, $session->enemy_max_hp);

        // Adjust HP difference
        $hpDiff = $newDamage - $oldDamage;
        $newHp  = max(0, $session->enemy_current_hp - $hpDiff);

        $round->update([
            'teacher_score' => $request->teacher_score,
            'final_score'   => $request->teacher_score,
            'damage_dealt'  => $newDamage,
            'status'        => 'teacher_reviewed',
        ]);

        $session->update([
            'enemy_current_hp' => $newHp,
            'total_damage'     => $session->total_damage + $hpDiff,
        ]);

        if ($newHp <= 0 && $session->status === 'ongoing') {
            $session->update(['status' => 'won', 'points_earned' => $session->activity->points_reward]);
            $session->student->increment('total_points', $session->activity->points_reward);
        }

        return back()->with('success', 'Score updated successfully!');
    }
}