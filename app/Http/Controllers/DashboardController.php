<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\ActivityResult;
use App\Models\VoiceRecording;

class DashboardController extends Controller
{
    public function teacherDashboard()
    {
        $teacher = auth()->user()->teacher;
        $students = Student::where('teacher_id', $teacher->id)
            ->withAvg(['activityResults' => fn ($q) => $q->where('status', 'completed')], 'score')
            ->get();
        $total = $students->count();
        $query = \App\Services\ReadingAssessmentMetrics::forTeacher($teacher->id);
        $assessment = \App\Services\ReadingAssessmentMetrics::report($query);
        $evaluationsCompleted = (clone $query)
            ->whereBetween('created_at', [now()->startOfWeek(), now()])->count();
        $pendingReviews = VoiceRecording::whereHas('student', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->whereHas('activity', fn ($q) => $q->where('teacher_id', $teacher->id)
                ->where('activity_type', 'Read Aloud')->where('battle_mode', false))
            ->where('status', 'pending')->count();
        $averageFinalScore = ActivityResult::whereHas('student', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->where('status', 'completed')->avg('score');

        $scoredStudents = $students->filter(fn ($s) => $s->activity_results_avg_score !== null);
        $topStudents = $scoredStudents->sortByDesc('activity_results_avg_score')->take(5)->values();
        $needHelp = $scoredStudents->filter(fn ($s) => $s->activity_results_avg_score < 75)
            ->sortBy('activity_results_avg_score')->take(5)->values();

        return view('teacher.dashboard', compact(
            'teacher', 'students', 'total', 'assessment', 'evaluationsCompleted',
            'pendingReviews', 'averageFinalScore', 'topStudents', 'needHelp'
        ));
    }

   public function studentDashboard()
{
    $student = auth()->user()->student;

    // Activities assigned to student's level that are published
    $activities = \App\Models\Activity::where('is_published', true)
                  ->where('teacher_id', $student->teacher_id)
                  ->where('level', '<=', $student->current_level)
                  ->latest()->take(4)->get();

    // Student's completed activity results
    $results = \App\Models\ActivityResult::where('student_id', $student->id)
               ->with('activity')->latest()->take(5)->get();

    // Stats
    $activitiesDone = \App\Models\ActivityResult::where('student_id', $student->id)
                      ->where('status', 'completed')->count();

    $badgesEarned = \App\Models\StudentBadge::where('student_id', $student->id)->count();

    // Leaderboard
    $leaderboard = \App\Models\Student::where('teacher_id', $student->teacher_id)
                   ->orderByDesc('total_points')->take(5)->get();

    // Streak
    $streak = 0;
    for ($i = 0; $i < 30; $i++) {
        $hasActivity = \App\Models\ActivityResult::where('student_id', $student->id)
            ->whereDate('completed_at', now()->startOfDay()->subDays($i))->exists();
        if ($hasActivity) $streak++;
        else break;
    }

    // XP / level
    $nextLevelPoints = $student->current_level * 500;
    $xpPercent       = min(100, round(($student->total_points / max(1, $nextLevelPoints)) * 100));

    // ── Badges ────────────────────────────────────────────────
    $allBadges    = \App\Models\Badge::all();
    $earnedBadges = \App\Models\StudentBadge::where('student_id', $student->id)
                    ->with('badge')->latest('earned_at')->get();
    $earnedIds    = $earnedBadges->pluck('badge_id')->toArray();

    // ── Trophy (leaderboard rank) ─────────────────────────────
    $allClassmates = \App\Models\Student::where('teacher_id', $student->teacher_id)
                     ->orderByDesc('total_points')->get();
    $myRank        = $allClassmates->search(fn($s) => $s->id === $student->id) + 1;
    $trophy        = match(true) {
        $myRank === 1 && $allClassmates->count() > 1
            => ['icon' => '🥇', 'label' => 'Gold Trophy',   'color' => '#F59E0B'],
        $myRank === 2
            => ['icon' => '🥈', 'label' => 'Silver Trophy', 'color' => '#9CA3AF'],
        $myRank === 3
            => ['icon' => '🥉', 'label' => 'Bronze Trophy', 'color' => '#CD7C0A'],
        default => null,
    };

    // ── Day streak update + badge check ───────────────────────
    $today     = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();
    if ($student->last_active !== $today) {
        if ($student->last_active === $yesterday) {
            $student->increment('day_streak');
        } else {
            $student->update(['day_streak' => 1]);
        }
        $student->update(['last_active' => $today]);
        $student->refresh();
        \App\Services\BadgeService::checkAndAward($student);
        // Refresh earned badges after potential new awards
        $earnedBadges = \App\Models\StudentBadge::where('student_id', $student->id)
                        ->with('badge')->latest('earned_at')->get();
        $earnedIds    = $earnedBadges->pluck('badge_id')->toArray();
        $badgesEarned = $earnedBadges->count();
    }

    $skills = \App\Services\ReadingAssessmentMetrics::skills(
        \App\Models\Evaluation::whereHas('voiceRecording', fn ($q) => $q->where('student_id', $student->id))
    );
    $completedActivityIds = ActivityResult::where('student_id', $student->id)
        ->where('status', 'completed')->pluck('activity_id')->all();

    return view('student.dashboard', compact(
        'student', 'activities', 'results',
        'activitiesDone', 'badgesEarned', 'leaderboard',
        'streak', 'nextLevelPoints', 'xpPercent',
        'allBadges', 'earnedBadges', 'earnedIds',
        'myRank', 'trophy', 'skills', 'completedActivityIds'
    ));
}
    public function progress()
    {
        $teacher = auth()->user()->teacher;
        $query = \App\Services\ReadingAssessmentMetrics::forTeacher($teacher->id);
        $assessment = \App\Services\ReadingAssessmentMetrics::report($query);
        $history = (clone $query)->with(['voiceRecording.student', 'voiceRecording.activity'])
            ->latest('created_at')->orderByDesc('id')->paginate(20);
        $history->getCollection()->each(function ($evaluation) {
            $evaluation->reading_time_label = \App\Services\ReadingAssessment::formatTime($evaluation->total_reading_seconds);
        });

        return view('teacher.progress', compact('assessment', 'history'));
    }

public function leaderboard()
{
    $teacher  = auth()->user()->teacher;
    $students = Student::where('teacher_id', $teacher->id)
                ->with('activityResults')
                ->orderByDesc('total_points')
                ->get();

    $sections = $students->pluck('section')->unique()->filter()->values();

    return view('teacher.leaderboard', compact('students', 'sections'));
}

   public function studentProgress()
{
    $student = auth()->user()->student;

    $results = \App\Models\ActivityResult::where('student_id', $student->id)
               ->where('status', 'completed')
               ->with('activity')
               ->latest('completed_at')
               ->get();

    $totalDone    = $results->count();
    $avgScore     = round($results->avg('score') ?? 0, 1);
    $totalPoints  = $student->total_points;
    $recordings   = \App\Models\VoiceRecording::where('student_id', $student->id)
                    ->with('evaluation')->latest()->get();

    $skills = \App\Services\ReadingAssessmentMetrics::skills(
        \App\Models\Evaluation::whereHas('voiceRecording', fn ($q) => $q->where('student_id', $student->id))
    );

    // Activity type breakdown
    $byType = $results->groupBy('activity.activity_type')->map->count();

    // Next level info
    $nextLevelPoints = $student->current_level * 500;
    $xpPercent       = min(100, round(($student->total_points / $nextLevelPoints) * 100));

    return view('student.progress', compact(
        'student', 'results', 'totalDone', 'avgScore',
        'totalPoints', 'recordings', 'skills', 'byType',
        'nextLevelPoints', 'xpPercent'
    ));
}

public function teacherLogs(Request $request)
{
    $user = auth()->user();
    $teacher = $user->teacher;
    abort_unless($teacher, 403);

    $filters = $request->validate([
        'scope' => 'nullable|in:all,students,mine',
        'student_id' => 'nullable|integer|min:1',
        'action' => 'nullable|string|max:100',
        'date' => 'nullable|date_format:Y-m-d',
    ]);
    $scope = $filters['scope'] ?? 'all';
    $studentId = $filters['student_id'] ?? null;
    $action = $filters['action'] ?? null;
    $date = $filters['date'] ?? null;
    $students = Student::where('teacher_id', $teacher->id)
        ->orderBy('lastname')->orderBy('firstname')->get();
    if ($studentId) {
        abort_unless($students->contains('id', $studentId), 403);
    }

    // Group the ownership conditions so every filter stays inside this classroom.
    $query = \App\Models\ActivityLog::where(function ($q) use ($user, $teacher) {
        $q->where('user_id', $user->id)
            ->orWhere(function ($q) use ($teacher) {
                $q->where('role', 'student')->whereHas('user.student',
                    fn ($q) => $q->where('teacher_id', $teacher->id));
            });
    });
    $query->when($scope === 'mine', fn ($q) => $q->where('user_id', $user->id))
        ->when($scope === 'students', fn ($q) => $q->where('role', 'student'))
        ->when($studentId, fn ($q) => $q->whereHas('user.student',
            fn ($q) => $q->whereKey($studentId)->where('teacher_id', $teacher->id)));

    $actions = (clone $query)->select('action')->distinct()->orderBy('action')->pluck('action');
    $logs = $query->with('user.student')
        ->when($action, fn ($q) => $q->where('action', $action))
        ->when($date, fn ($q) => $q->whereDate('created_at', $date))
        ->latest()->orderByDesc('id')->paginate(20)->withQueryString();

    return view('teacher.logs', compact(
        'logs', 'action', 'date', 'actions', 'scope', 'studentId', 'students'
    ));
}
}
