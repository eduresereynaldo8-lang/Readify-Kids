<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Enemy;
use App\Models\GameSession;
use App\Models\GameRound;

class GameController extends Controller
{
    // Game lobby
    public function index()
    {
        $student    = auth()->user()->student;
        $activities = Activity::where('is_published', true)
                      ->where('teacher_id', $student->teacher_id)
                      ->where('level', '<=', $student->current_level)
                      ->where('battle_mode', true)
                      ->whereIn('activity_type', [
                          'Phonics','Word Game','Vocabulary',
                          'Word Recognition','Sound Blending'
                      ])
                      ->get();

        $enemies = Enemy::all()->keyBy('level');

        // Get latest session status per activity
        $sessionStatuses = GameSession::where('student_id', $student->id)
                           ->whereIn('activity_id', $activities->pluck('id'))
                           ->orderByDesc('created_at')
                           ->get()
                           ->groupBy('activity_id')
                           ->map->first();

        return view('student.game.index', compact(
            'activities', 'enemies', 'sessionStatuses'
        ));
    }

    // Start or resume a game session
    public function start($activityId)
    {
        $student  = auth()->user()->student;
        $activity = Activity::where('teacher_id', $student->teacher_id)->findOrFail($activityId);
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

        // Create new session (also handles retry after win/loss)
        $session = GameSession::create([
            'student_id'       => $student->id,
            'activity_id'      => $activityId,
            'enemy_id'         => $enemy->id,
            'enemy_current_hp' => $enemy->max_hp,
            'enemy_max_hp'     => $enemy->max_hp,
            'total_damage'     => 0,
            'rounds_played'    => 0,
            'status'           => 'ongoing',
            'points_earned'    => 0,
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

        // Block if already ended
        if ($session->status === 'won') {
            return redirect()->route('student.game.index')
                   ->with('info', '🏆 You already won this battle! Start a new one.');
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
                   ->with('error', 'This activity has no battle words set up yet!');
        }

        $roundIndex  = $session->rounds_played % $totalWords;
        $currentWord = $allWords[$roundIndex];
        $roundsLeft  = $totalWords - $session->rounds_played;
        $hpPercent   = max(0, round(($session->enemy_current_hp / $session->enemy_max_hp) * 100));

        return view('student.game.battle', compact(
            'session', 'currentWord', 'hpPercent',
            'allWords', 'totalWords', 'roundIndex', 'roundsLeft'
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
                   ->with(['enemy', 'activity.wordBank'])
                   ->findOrFail($sessionId);

        $totalWords   = $session->activity->wordBank->count();
        $roundsPlayed = $session->rounds_played;

        // Store recording
        $path = $request->file('recording')->store('game_recordings', 'public');

        // Call ML scoring
        $mlResult   = $this->callMLScoring(
            storage_path('app/public/' . $path),
            $request->word_or_passage
        );
        $mlScore    = $mlResult['score']      ?? null;
        $transcript = $mlResult['transcript'] ?? null;

        // Calculate damage
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
        $newHp           = max(0, $session->enemy_current_hp - $damage);
        $newRoundsPlayed = $roundsPlayed + 1;

        $session->update([
            'enemy_current_hp' => $newHp,
            'rounds_played'    => $newRoundsPlayed,
            'total_damage'     => $session->total_damage + $damage,
        ]);

        $hpPercent  = round(($newHp / $session->enemy_max_hp) * 100);
        $roundsLeft = $totalWords - $newRoundsPlayed;

        // ── WIN ───────────────────────────────────────────────
        if ($newHp <= 0) {
            $pointsEarned = $session->activity->points_reward;
            $session->update([
                'status'        => 'won',
                'points_earned' => $pointsEarned,
            ]);
            $student->increment('total_points', $pointsEarned);

            \App\Models\ActivityResult::updateOrCreate(
                [
                    'student_id'  => $student->id,
                    'activity_id' => $session->activity_id,
                ],
                [
                    'score'        => min(100, round(($session->total_damage / $session->enemy_max_hp) * 100, 1)),
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

        // ── LOSE — no rounds left and enemy still alive ───────
        if ($newRoundsPlayed >= $totalWords) {
            $session->update(['status' => 'lost']);

            \App\Models\ActivityResult::updateOrCreate(
                [
                    'student_id'  => $student->id,
                    'activity_id' => $session->activity_id,
                ],
                [
                    'score'        => min(100, round(($session->total_damage / $session->enemy_max_hp) * 100, 1)),
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]
            );

            return response()->json([
                'status'      => 'lost',
                'ml_score'    => $mlScore,
                'transcript'  => $transcript,
                'damage'      => $damage,
                'enemy_hp'    => $newHp,
                'hp_percent'  => $hpPercent,
                'enemy_name'  => $session->enemy->name,
                'rounds_used' => $newRoundsPlayed,
                'total_words' => $totalWords,
                'message'     => '💀 You lost to ' . $session->enemy->name . '!',
            ]);
        }

        // ── ONGOING ───────────────────────────────────────────
        if ($mlScore === null) {
            $message = '⏳ Could not analyze recording. Try again!';
        } elseif ($mlScore >= 90) {
            $message = "🔥 Excellent! -{$damage} HP! {$roundsLeft} round(s) left.";
        } elseif ($mlScore >= 70) {
            $message = "⚔️ Good job! -{$damage} HP! {$roundsLeft} round(s) left.";
        } elseif ($mlScore >= 50) {
            $message = "👍 Keep going! -{$damage} HP! {$roundsLeft} round(s) left.";
        } else {
            $message = "💪 Read more clearly! -{$damage} HP! {$roundsLeft} round(s) left.";
        }

        return response()->json([
            'status'      => 'ongoing',
            'ml_score'    => $mlScore,
            'transcript'  => $transcript,
            'damage'      => $damage,
            'enemy_hp'    => $newHp,
            'hp_percent'  => $hpPercent,
            'round_id'    => $round->id,
            'rounds_left' => $roundsLeft,
            'rounds_used' => $newRoundsPlayed,
            'total_words' => $totalWords,
            'message'     => $message,
        ]);
    }

    // Calculate damage from score
    private function calculateDamage(float $score, int $maxHp): int
    {
        $maxDamagePerRound = $maxHp * 0.20;
        return (int) round(($score / 100) * $maxDamagePerRound);
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
}