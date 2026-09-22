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
            ->withAvg('activityResults', 'score')
            ->withCount(['activityResults as completed_this_week' => fn ($q) => $q
                ->where('status', 'completed')
                ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])])
            ->get();
        $total = $students->count();
        $activeToday = \Illuminate\Support\Facades\DB::table('sessions')
            ->whereIn('user_id', Student::where('teacher_id', $teacher->id)->select('user_id'))
            ->where('last_activity', '>=', now()->startOfDay()->timestamp)
            ->distinct()->count('user_id');
        $activitiesDone = ActivityResult::whereHas('student', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->where('status', 'completed')
            ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $pendingReviews = VoiceRecording::whereHas('student', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->where('status', 'pending')->count();

        // Preserve the existing activity-average status thresholds.
        $onTrack = $students->filter(fn ($s) => ($s->activity_results_avg_score ?? 0) >= 75)->count();
        $needsHelp = $students->filter(fn ($s) => ($s->activity_results_avg_score ?? 0) >= 50 && ($s->activity_results_avg_score ?? 0) < 75)->count();
        $struggling = $students->filter(fn ($s) => ($s->activity_results_avg_score ?? 0) < 50)->count();
        $skills = \App\Services\DashboardMetrics::readingSkills(
            \App\Models\Evaluation::whereHas('voiceRecording.student', fn ($q) => $q->where('teacher_id', $teacher->id))
        );
        $topStudents = $students->sortByDesc('total_points')->take(5)->values();
        $activityTypes = \App\Services\DashboardMetrics::activityTypes(
            \App\Models\Activity::where('teacher_id', $teacher->id)
        );
        $attention = [
            'inactive' => $students->where('completed_this_week', 0)->count(),
            'unattempted' => \App\Services\DashboardMetrics::unattemptedActivities(
                \App\Models\Activity::where('teacher_id', $teacher->id)
            ),
            'pending' => $pendingReviews,
        ];
        return view('teacher.dashboard', compact(
            'teacher', 'students', 'total', 'activeToday', 'activitiesDone', 'pendingReviews',
            'onTrack', 'needsHelp', 'struggling', 'skills', 'topStudents', 'activityTypes', 'attention'
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

    $skills = \App\Services\DashboardMetrics::readingSkills(
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
    $teacher  = auth()->user()->teacher;
    $students = Student::where('teacher_id', $teacher->id)
                ->with(['activityResults.activity'])
                ->get();

    // Class averages
    $classAvg     = round($students->flatMap->activityResults->avg('score') ?? 0, 1);
    $totalDone    = $students->flatMap->activityResults->count();
    $struggling   = $students->filter(fn($s) => ($s->activityResults->avg('score') ?? 0) < 50)->count();
    $onTrack      = $students->filter(fn($s) => ($s->activityResults->avg('score') ?? 0) >= 75)->count();

    // Skill breakdown from evaluations
    $evaluations  = \App\Models\Evaluation::whereHas('voiceRecording.student',
                    fn($q) => $q->where('teacher_id', $teacher->id))->get();

    $skills = [
        'Pronunciation' => round($evaluations->avg('pronunciation_score') * 20 ?? 0, 1),
        'Fluency'       => round($evaluations->avg('fluency_score') * 20 ?? 0, 1),
        'Accuracy'      => round($evaluations->avg('accuracy_score') * 20 ?? 0, 1),
        'Comprehension' => round($evaluations->avg('comprehension_score') * 20 ?? 0, 1),
    ];

    // Activity completions per day this week
    $weeklyData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date  = now()->subDays($i);
        $count = \App\Models\ActivityResult::whereHas('student',
                 fn($q) => $q->where('teacher_id', $teacher->id))
                 ->whereDate('completed_at', $date)->count();
        $weeklyData[] = [
            'day'   => $date->format('D'),
            'count' => $count,
        ];
    }

    // Top performers
    $topStudents = $students->sortByDesc(fn($s) => $s->activityResults->avg('score') ?? 0)->take(5);

    // Students needing intervention
    $needHelp = $students->filter(fn($s) => ($s->activityResults->avg('score') ?? 0) < 50)
                ->sortBy(fn($s) => $s->activityResults->avg('score') ?? 0)->take(5);

    // Activity type breakdown
    $byType = \App\Models\ActivityResult::whereHas('student',
              fn($q) => $q->where('teacher_id', $teacher->id))
              ->with('activity')
              ->get()
              ->groupBy('activity.activity_type')
              ->map->count();

    return view('teacher.progress', compact(
        'students', 'classAvg', 'totalDone', 'struggling', 'onTrack',
        'skills', 'weeklyData', 'topStudents', 'needHelp', 'byType'
    ));
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

    // Skill breakdown from evaluations
    $evaluations = \App\Models\Evaluation::whereHas('voiceRecording', fn($q) => $q->where('student_id', $student->id))->get();
    $skills = [
        'Pronunciation' => round($evaluations->avg('pronunciation_score') * 20 ?? 0, 1),
        'Fluency'       => round($evaluations->avg('fluency_score') * 20 ?? 0, 1),
        'Accuracy'      => round($evaluations->avg('accuracy_score') * 20 ?? 0, 1),
        'Comprehension' => round($evaluations->avg('comprehension_score') * 20 ?? 0, 1),
    ];

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
    $teacher = auth()->user();
    $action  = $request->input('action');
    $date    = $request->input('date');

    $logs = \App\Models\ActivityLog::where('user_id', $teacher->id)
            ->when($action, fn($q) => $q->where('action', $action))
            ->when($date,   fn($q) => $q->whereDate('created_at', $date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

    $actions = \App\Models\ActivityLog::where('user_id', $teacher->id)
               ->select('action')->distinct()->pluck('action');

    return view('teacher.logs', compact('logs', 'action', 'date', 'actions'));
}
}