<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Activity;
use App\Models\VoiceRecording;
use App\Models\GameSession;

class AdminController extends Controller
{
    // Admin dashboard
    public function dashboard()
    {
        $totalTeachers = Teacher::count();
        $totalStudents = Student::count();
        $totalActivities = Activity::count();
        $totalRecordings = VoiceRecording::count();
        $battleCounts = GameSession::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $totalGames = $battleCounts->sum();
        $totalWins = (int) ($battleCounts['won'] ?? 0);
        $battleLost = (int) ($battleCounts['lost'] ?? 0);
        $battleOngoing = (int) ($battleCounts['ongoing'] ?? 0);
        $totalPoints = Student::sum('total_points');
        $averageScore = \App\Models\ActivityResult::where('status', 'completed')->avg('score');
        $averageScore = $averageScore === null ? null : round($averageScore, 1);
        $recentTeachers = Teacher::with('user')->latest()->take(5)->get();
        $topTeachers = Teacher::withCount(['students', 'activities'])
            ->orderByDesc('students_count')->orderBy('id')->take(5)->get();
        $topStudents = Student::withAvg('activityResults', 'score')
            ->orderByDesc('total_points')->orderBy('id')->take(10)->get();
        $skills = \App\Services\DashboardMetrics::readingSkills(\App\Models\Evaluation::query());
        $activityTypes = \App\Services\DashboardMetrics::activityTypes(Activity::query());
        $attention = [
            'inactive' => Student::whereDoesntHave('activityResults', fn ($q) => $q
                ->where('status', 'completed')
                ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()]))->count(),
            'unattempted' => \App\Services\DashboardMetrics::unattemptedActivities(Activity::query()),
            'pending' => VoiceRecording::where('status', 'pending')->count(),
        ];

        // One grouped query per series, rather than one query per day.
        $since = now()->subDays(6)->startOfDay();
        $games = GameSession::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day');
        $recordings = VoiceRecording::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day');
        $completed = \App\Models\ActivityResult::where('status', 'completed')->where('completed_at', '>=', $since)
            ->selectRaw('DATE(completed_at) as day, COUNT(*) as total')->groupBy('day')->pluck('total', 'day');
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->toDateString();
            $weeklyData[] = [
                'day' => $date->format('D'), 'date' => $key,
                'games' => (int) ($games[$key] ?? 0),
                'recs' => (int) ($recordings[$key] ?? 0),
                'completed' => (int) ($completed[$key] ?? 0),
            ];
        }
        return view('admin.dashboard', compact(
            'totalTeachers', 'totalStudents', 'totalActivities', 'totalRecordings',
            'totalGames', 'totalWins', 'recentTeachers', 'weeklyData', 'totalPoints',
            'averageScore', 'battleLost', 'battleOngoing', 'topTeachers', 'topStudents',
            'skills', 'activityTypes', 'attention'
        ));
    }

   // All teachers
public function teachers(Request $request)
{
    $search   = $request->input('search');
    $status   = $request->input('status');

    $teachers = Teacher::with(['user', 'students', 'activities'])
                ->when($search, fn($q) => $q->where(function($q) use ($search) {
                    $q->where('firstname',   'like', "%{$search}%")
                      ->orWhere('lastname',  'like', "%{$search}%")
                      ->orWhere('school_name','like', "%{$search}%")
                      ->orWhereHas('user',   fn($q) => $q->where('email','like',"%{$search}%"));
                }))
                ->when($status === 'active',   fn($q) => $q->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at')))
                ->when($status === 'inactive', fn($q) => $q->whereHas('user', fn($q) => $q->whereNull('email_verified_at')))
                ->latest()
                ->paginate(10)
                ->withQueryString();

    return view('admin.teachers', compact('teachers', 'search', 'status'));
}

// All students
public function students(Request $request)
{
    $search  = $request->input('search');
    $level   = $request->input('level');

    $students = Student::with(['teacher', 'activityResults'])
                ->when($search, fn($q) => $q->where(function($q) use ($search) {
                    $q->where('firstname',      'like', "%{$search}%")
                      ->orWhere('lastname',     'like', "%{$search}%")
                      ->orWhere('student_number','like', "%{$search}%")
                      ->orWhere('section',      'like', "%{$search}%");
                }))
                ->when($level, fn($q) => $q->where('current_level', $level))
                ->latest()
                ->paginate(10)
                ->withQueryString();

    return view('admin.students', compact('students', 'search', 'level'));
}

// All activities
public function activities(Request $request)
{
    $search = $request->input('search');
    $type   = $request->input('type');
    $status = $request->input('status');

    $activities = Activity::with(['teacher'])
                  ->when($search, fn($q) => $q->where('activity_name', 'like', "%{$search}%")
                                              ->orWhere('description',  'like', "%{$search}%"))
                  ->when($type,   fn($q) => $q->where('activity_type', $type))
                  ->when($status === 'published', fn($q) => $q->where('is_published', true))
                  ->when($status === 'draft',     fn($q) => $q->where('is_published', false))
                  ->when($status === 'battle',    fn($q) => $q->where('battle_mode',  true))
                  ->latest()
                  ->paginate(10)
                  ->withQueryString();

    // Get all unique types for filter dropdown
    $types = Activity::select('activity_type')->distinct()->pluck('activity_type');

    return view('admin.activities', compact('activities', 'search', 'type', 'status', 'types'));
}
    // Toggle teacher active/inactive
    public function toggleTeacher($id)
    {
        $teacher = Teacher::findOrFail($id);
        $user    = $teacher->user;
        // We use email_verified_at as active flag — null = inactive
        $user->email_verified_at = $user->email_verified_at ? null : now();
        $user->save();

        return back()->with('success',
            'Teacher ' . $teacher->firstname . ' has been ' .
            ($user->email_verified_at ? 'activated' : 'deactivated') . '.');
    }

    // Delete teacher
    public function deleteTeacher($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->user()->delete(); // cascades
        return back()->with('success', 'Teacher deleted successfully.');
    }

    // Delete student
    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        $student->user()->delete();
        return back()->with('success', 'Student deleted successfully.');
    }

    // Delete activity
    public function deleteActivity($id)
    {
        Activity::findOrFail($id)->delete();
        return back()->with('success', 'Activity deleted successfully.');
    }

    // View all evaluations across all teachers
public function evaluations()
{
    $evaluations = \App\Models\Evaluation::with([
                       'voiceRecording.student',
                       'voiceRecording.activity',
                   ])
                   ->latest()
                   ->get();

    $pending   = \App\Models\VoiceRecording::where('status', 'pending')->count();
    $evaluated = \App\Models\VoiceRecording::where('status', 'evaluated')->count();

    return view('admin.evaluations', compact('evaluations', 'pending', 'evaluated'));
}

// System-wide reports
public function reports()
{
    // Overall counts
    $totalTeachers   = \App\Models\Teacher::count();
    $totalStudents   = \App\Models\Student::count();
    $totalActivities = \App\Models\Activity::count();
    $totalRecordings = \App\Models\VoiceRecording::count();
    $totalGames      = \App\Models\GameSession::count();
    $totalWins       = \App\Models\GameSession::where('status', 'won')->count();
    $totalLosses     = \App\Models\GameSession::where('status', 'lost')->count();
    $totalPoints     = \App\Models\Student::sum('total_points');

    // Activity type breakdown
    $byType = \App\Models\Activity::selectRaw('activity_type, count(*) as count')
              ->groupBy('activity_type')->get();

    // Top 10 students system-wide
    $topStudents = \App\Models\Student::with(['teacher', 'activityResults'])
                   ->orderByDesc('total_points')->take(10)->get();

    // Top 5 teachers by student count
    $topTeachers = \App\Models\Teacher::withCount('students')
                   ->orderByDesc('students_count')->take(5)->get();

    // Game sessions per level
    $gamesByLevel = \App\Models\GameSession::with('activity')
                    ->get()
                    ->groupBy('activity.level')
                    ->map->count();

    // Monthly activity completions (last 6 months)
    $monthlyData = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $monthlyData[] = [
            'month' => $date->format('M'),
            'count' => \App\Models\ActivityResult::whereYear('completed_at', $date->year)
                       ->whereMonth('completed_at', $date->month)->count(),
        ];
    }

    // Skill averages across all evaluations
    $evaluations = \App\Models\Evaluation::all();
    $skills = [
        'Pronunciation' => round($evaluations->avg('pronunciation_score') * 20 ?? 0, 1),
        'Fluency'       => round($evaluations->avg('fluency_score') * 20 ?? 0, 1),
        'Accuracy'      => round($evaluations->avg('accuracy_score') * 20 ?? 0, 1),
        'Comprehension' => round($evaluations->avg('comprehension_score') * 20 ?? 0, 1),
    ];

    return view('admin.reports', compact(
        'totalTeachers', 'totalStudents', 'totalActivities',
        'totalRecordings', 'totalGames', 'totalWins', 'totalLosses',
        'totalPoints', 'byType', 'topStudents', 'topTeachers',
        'gamesByLevel', 'monthlyData', 'skills'
    ));
}
// Show create teacher form
public function createTeacher()
{
    return view('admin.teachers_create');
}

// Store new teacher
public function storeTeacher(Request $request)
{
    $request->validate([
        'firstname'   => 'required|string|max:100',
        'lastname'    => 'required|string|max:100',
        'email'       => 'required|email|unique:users,email',
        'username'    => 'required|string|max:100|unique:users,username',
        'school_name' => 'required|string|max:255',
        'password'    => 'required|string|min:6|confirmed',
    ]);

    // Create user
    $user = \App\Models\User::create([
        'email'             => $request->email,
        'username'          => $request->username,
        'password'          => \Illuminate\Support\Facades\Hash::make($request->password),
        'role'              => 'teacher',
        'email_verified_at' => now(), // active by default
    ]);

    // Create teacher profile
    \App\Models\Teacher::create([
        'user_id'     => $user->id,
        'firstname'   => $request->firstname,
        'lastname'    => $request->lastname,
        'school_name' => $request->school_name,
    ]);

    return redirect()->route('admin.teachers')
           ->with('success', "Teacher {$request->firstname} {$request->lastname} created successfully!");
}

public function logs(Request $request)
{
    $search = $request->input('search');
    $role   = $request->input('role');
    $action = $request->input('action');
    $date   = $request->input('date');

    $logs = \App\Models\ActivityLog::with('user')
            ->when($search, fn($q) => $q->whereHas('user',
                fn($q2) => $q2->where('username', 'like', "%{$search}%"))
                ->orWhere('description', 'like', "%{$search}%"))
            ->when($role,   fn($q) => $q->where('role',   $role))
            ->when($action, fn($q) => $q->where('action', $action))
            ->when($date,   fn($q) => $q->whereDate('created_at', $date))
            ->latest()
            ->paginate(20)
            ->withQueryString();

    $actions = \App\Models\ActivityLog::select('action')
               ->distinct()->pluck('action');

    return view('admin.logs', compact('logs', 'search', 'role', 'action', 'date', 'actions'));
}

public function editTeacher($id)
{
    $teacher = Teacher::with('user')->findOrFail($id);
    return view('admin.teachers_edit', compact('teacher'));
}

public function updateTeacher(Request $request, $id)
{
    $teacher = Teacher::with('user')->findOrFail($id);

    $request->validate([
        'firstname'   => 'required|string|max:100',
        'lastname'    => 'required|string|max:100',
        'school_name' => 'required|string|max:255',
        'email'       => 'required|email|unique:users,email,' . $teacher->user_id,
        'username'    => 'required|string|max:100|unique:users,username,' . $teacher->user_id,
        'password'    => 'nullable|string|min:6|confirmed',
    ]);

    $teacher->update([
        'firstname'   => $request->firstname,
        'lastname'    => $request->lastname,
        'school_name' => $request->school_name,
    ]);

    $teacher->user->update([
        'email'    => $request->email,
        'username' => $request->username,
    ]);

    if ($request->filled('password')) {
        $teacher->user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);
    }

    LogActivity::log('EDIT_TEACHER', 'Teachers',
        'Updated teacher: ' . $teacher->firstname . ' ' . $teacher->lastname);

    return redirect()->route('admin.teachers')
           ->with('success', 'Teacher updated successfully!');
}

public function viewStudent($id)
{
    $student = Student::with([
        'teacher', 'activityResults.activity',
        'studentBadges.badge', 'user'
    ])->findOrFail($id);

    $gameSessions  = \App\Models\GameSession::where('student_id', $id)
                     ->with('enemy', 'activity')->latest()->get();
    $recordings    = \App\Models\VoiceRecording::where('student_id', $id)
                     ->with('activity', 'evaluation')->latest()->get();
    $totalCompleted = $student->activityResults->where('status','completed')->count();
    $avgScore       = round($student->activityResults->avg('score') ?? 0, 1);
    $battlesWon     = $gameSessions->where('status','won')->count();

    return view('admin.students_view', compact(
        'student', 'gameSessions', 'recordings',
        'totalCompleted', 'avgScore', 'battlesWon'
    ));
}

}