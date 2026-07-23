<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;
use App\Models\User;

class StudentController extends Controller
{
    // List all students
  public function index()
{
    $teacher  = auth()->user()->teacher;
    $students = Student::where('teacher_id', $teacher->id)
                ->with('activityResults')
                ->latest()->get();

    $total         = $students->count();
    $onTrack       = $students->filter(fn($s) =>
                        ($s->activityResults->avg('score') ?? 0) >= 75)->count();
    $needAttention = $total - $onTrack;

    // Get unique sections for filter dropdown
    $sections = $students->pluck('section')
                ->unique()->filter()->values();

    return view('teacher.students.index', compact(
        'students', 'total', 'onTrack', 'needAttention', 'sections'
    ));
}

    // Show add student form
    public function create()
    {
        return view('teacher.students.create');
    }

    // Store new student
    public function store(Request $request)
    {
        $request->validate([
            'firstname'      => 'required|string|max:100',
            'lastname'       => 'required|string|max:100',
            'student_number' => 'required|string|unique:students,student_number',
            'section'        => 'required|string|max:50',
            'current_level'  => 'required|integer|min:1|max:5',
            'username'       => 'required|string|unique:users,username',
            'password'       => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role'     => 'student',
            'status'   => 'active',
        ]);

        Student::create([
            'user_id'        => $user->id,
            'teacher_id'     => auth()->user()->teacher->id,
            'firstname'      => $request->firstname,
            'lastname'       => $request->lastname,
            'student_number' => $request->student_number,
            'section'        => $request->section,
            'current_level'  => $request->current_level,
            'total_points'   => 0,
        ]);

        return redirect()->route('teacher.students.index')
               ->with('success', 'Student added successfully!');
    }

    // View student profile
    public function show($id)
    {
        $teacher = auth()->user()->teacher;
        $student = Student::where('teacher_id', $teacher->id)
                   ->with(['activityResults.activity', 'badges.badge'])
                   ->findOrFail($id);

        $avg = round($student->activityResults->avg('score') ?? 0, 1);

        if ($avg >= 75)      { $status = 'On Track';    $badgeClass = 'badge-green'; }
        elseif ($avg >= 50)  { $status = 'Needs Help';  $badgeClass = 'badge-amber'; }
        else                 { $status = 'Struggling';  $badgeClass = 'badge-red'; }

        return view('teacher.students.show', compact('student', 'avg', 'status', 'badgeClass'));
    }

    // Show edit form
    public function edit($id)
    {
        $teacher = auth()->user()->teacher;
        $student = Student::where('teacher_id', $teacher->id)->findOrFail($id);
        return view('teacher.students.edit', compact('student'));
    }

    // Update student
    public function update(Request $request, $id)
    {
        $teacher = auth()->user()->teacher;
        $student = Student::where('teacher_id', $teacher->id)->findOrFail($id);

        $request->validate([
            'firstname'     => 'required|string|max:100',
            'lastname'      => 'required|string|max:100',
            'section'       => 'required|string|max:50',
            'current_level' => 'required|integer|min:1|max:5',
        ]);

        $student->update($request->only('firstname', 'lastname', 'section', 'current_level'));

        return redirect()->route('teacher.students.index')
               ->with('success', 'Student updated successfully!');
    }

    // Delete student
    public function destroy($id)
    {
        $teacher = auth()->user()->teacher;
        $student = Student::where('teacher_id', $teacher->id)->findOrFail($id);
        $student->user->delete();
        return redirect()->route('teacher.students.index')
               ->with('success', 'Student deleted successfully!');
    }
}