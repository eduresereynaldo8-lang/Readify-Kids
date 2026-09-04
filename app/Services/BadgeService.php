<?php
namespace App\Services;

use App\Models\Student;
use App\Models\Badge;
use App\Models\StudentBadge;
use App\Models\ActivityResult;
use App\Models\VoiceRecording;
use App\Models\GameSession;

class BadgeService
{
    /**
     * Check all badge conditions for a student and award any that are met.
     * Returns array of newly awarded Badge models.
     * Call this after: recording upload, activity completion, battle win, points update.
     */
    public static function checkAndAward(Student $student): array
    {
        $newBadges = [];

        // Refresh student data to get latest points/streak
        $student->refresh();

        $allBadges = Badge::all();

        foreach ($allBadges as $badge) {
            // Skip already earned badges
            $alreadyEarned = StudentBadge::where('student_id', $student->id)
                             ->where('badge_id', $badge->id)
                             ->exists();

            if ($alreadyEarned) {
                continue;
            }

            // Check if student now meets the condition
            if (self::meetsCondition($student, $badge->criteria)) {
                StudentBadge::create([
                    'student_id' => $student->id,
                    'badge_id'   => $badge->id,
                    'earned_at'  => now(),
                ]);
                $newBadges[] = $badge;
            }
        }

        return $newBadges;
    }

    /**
     * Check one specific badge criteria for a student.
     */
    private static function meetsCondition(Student $student, ?string $criteria): bool
    {
        if (!$criteria) return false;

        switch ($criteria) {

            // ── Read Aloud ──────────────────────────────────────
            case 'first_recording':
                return VoiceRecording::where('student_id', $student->id)
                       ->exists();

            // ── Activity completions ─────────────────────────────
            case 'activities_5':
                return ActivityResult::where('student_id', $student->id)
                       ->where('status', 'completed')
                       ->count() >= 5;

            case 'activities_10':
                return ActivityResult::where('student_id', $student->id)
                       ->where('status', 'completed')
                       ->count() >= 10;

            // ── Battle wins ──────────────────────────────────────
            case 'first_battle_win':
                return GameSession::where('student_id', $student->id)
                       ->where('status', 'won')
                       ->exists();

            case 'battles_won_5':
                return GameSession::where('student_id', $student->id)
                       ->where('status', 'won')
                       ->count() >= 5;

            // ── Scores ───────────────────────────────────────────
            case 'score_90':
                return ActivityResult::where('student_id', $student->id)
                       ->where('status', 'completed')
                       ->where('score', '>=', 90)
                       ->exists();

            case 'score_100':
                return ActivityResult::where('student_id', $student->id)
                       ->where('status', 'completed')
                       ->where('score', '>=', 100)
                       ->exists();

            // ── Points ───────────────────────────────────────────
            case 'points_100':
                return $student->total_points >= 100;

            case 'points_500':
                return $student->total_points >= 500;

            // ── Re-attempts ──────────────────────────────────────
            case 'reattempt_3':
                return VoiceRecording::where('student_id', $student->id)
                       ->selectRaw('activity_id, COUNT(*) as cnt')
                       ->groupBy('activity_id')
                       ->having('cnt', '>=', 3)
                       ->exists();

            // ── Leaderboard ──────────────────────────────────────
            case 'rank_1':
                $classCount = Student::where('teacher_id', $student->teacher_id)->count();
                if ($classCount < 2) return false;
                $topStudent = Student::where('teacher_id', $student->teacher_id)
                              ->orderByDesc('total_points')
                              ->first();
                return $topStudent && $topStudent->id === $student->id;

            // ── Activity type completions ─────────────────────────
            case 'phonics_3':
                return ActivityResult::where('student_id', $student->id)
                       ->where('status', 'completed')
                       ->whereHas('activity', fn($q) =>
                           $q->where('activity_type', 'Phonics'))
                       ->count() >= 3;

            case 'wordgame_3':
                return ActivityResult::where('student_id', $student->id)
                       ->where('status', 'completed')
                       ->whereHas('activity', fn($q) =>
                           $q->where('activity_type', 'Word Game'))
                       ->count() >= 3;

            case 'speed_reader':
                return ActivityResult::where('student_id', $student->id)
                       ->where('status', 'completed')
                       ->whereHas('activity', fn($q) =>
                           $q->where('duration_minutes', '<=', 2))
                       ->exists();

            // ── Streak ───────────────────────────────────────────
            case 'streak_5':
                return $student->day_streak >= 5;

            default:
                return false;
        }
    }
}