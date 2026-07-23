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

        $totalStudents   = Student::where('teacher_id', $teacher->id)->count();
        $activeToday     = Student::where('teacher_id', $teacher->id)
                            ->whereDate('updated_at', today())->count();
        $activitiesDone  = ActivityResult::whereHas('student', function($q) use ($teacher) {
                            $q->where('teacher_id', $teacher->id);
                           })->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $pendingReviews  = VoiceRecording::where('status', 'pending')
                            ->whereHas('student', function($q) use ($teacher) {
                                $q->where('teacher_id', $teacher->id);
                            })->count();

        $students = Student::where('teacher_id', $teacher->id)
                    ->with('activityResults')
                    ->latest()->take(5)->get();

        $recentActivity = ActivityResult::with(['student', 'activity'])
                    ->whereHas('student', function($q) use ($teacher) {
                        $q->where('teacher_id', $teacher->id);
                    })->latest('completed_at')->take(5)->get();

        $pendingRecordings = VoiceRecording::with(['student', 'activity'])
                    ->where('status', 'pending')
                    ->whereHas('student', function($q) use ($teacher) {
                        $q->where('teacher_id', $teacher->id);
                    })->latest()->take(5)->get();

        return view('teacher.dashboard', compact(
            'totalStudents', 'activeToday', 'activitiesDone',
            'pendingReviews', 'students', 'recentActivity', 'pendingRecordings'
        ));
    }

    public function studentDashboard()
{
    $student = auth()->user()->student;

    // Activities assigned to student's level that are published
    $activities = \App\Models\Activity::where('is_published', true)
                  ->where('level', '<=', $student->current_level)
                  ->latest()->take(4)->get();

    // Student's completed activity results
    $results = \App\Models\ActivityResult::where('student_id', $student->id)
               ->with('activity')->latest()->take(5)->get();

    // Stats
    $activitiesDone = \App\Models\ActivityResult::where('student_id', $student->id)
                      ->where('status', 'completed')->count();

    $badgesEarned = \App\Models\StudentBadge::where('student_id', $student->id)->count();

    // Leaderboard — classmates ranked by points
    $leaderboard = \App\Models\Student::where('teacher_id', $student->teacher_id)
                   ->orderByDesc('total_points')->take(5)->get();

    // Streak (days in a row with a completed activity)
    $streak = 0;
    $date = now()->startOfDay();
    for ($i = 0; $i < 30; $i++) {
        $hasActivity = \App\Models\ActivityResult::where('student_id', $student->id)
            ->whereDate('completed_at', $date->copy()->subDays($i))->exists();
        if ($hasActivity) $streak++;
        else break;
    }

    // Points needed for next level
    $nextLevelPoints = ($student->current_level) * 500;
    $xpPercent = min(100, round(($student->total_points / $nextLevelPoints) * 100));

    return view('student.dashboard', compact(
        'student', 'activities', 'results',
        'activitiesDone', 'badgesEarned', 'leaderboard',
        'streak', 'nextLevelPoints', 'xpPercent'
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
}