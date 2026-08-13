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
        $totalTeachers   = Teacher::count();
        $totalStudents   = Student::count();
        $totalActivities = Activity::count();
        $totalRecordings = VoiceRecording::count();
        $totalGames      = GameSession::count();
        $totalWins       = GameSession::where('status', 'won')->count();

        // Recent teachers
        $recentTeachers = Teacher::with('user')
                          ->latest()->take(5)->get();

        // System activity per day (last 7 days)
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $weeklyData[] = [
                'day'   => $date->format('D'),
                'games' => GameSession::whereDate('created_at', $date)->count(),
                'recs'  => VoiceRecording::whereDate('created_at', $date)->count(),
            ];
        }

        return view('admin.dashboard', compact(
            'totalTeachers', 'totalStudents', 'totalActivities',
            'totalRecordings', 'totalGames', 'totalWins',
            'recentTeachers', 'weeklyData'
        ));
    }

    // All teachers
    public function teachers()
    {
        $teachers = Teacher::with(['user', 'students', 'activities'])
                    ->latest()->get();

        return view('admin.teachers', compact('teachers'));
    }

    // All students
    public function students()
    {
        $students = Student::with(['teacher', 'activityResults'])
                    ->latest()->get();

        return view('admin.students', compact('students'));
    }

    // All activities
    public function activities()
    {
        $activities = Activity::with(['teacher'])
                      ->latest()->get();

        return view('admin.activities', compact('activities'));
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
}