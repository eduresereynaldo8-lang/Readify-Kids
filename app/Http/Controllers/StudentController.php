<?php

namespace App\Http\Controllers;

use App\Helpers\LogActivity;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    // List all students
    public function index()
    {
        $teacher = auth()->user()->teacher;
        $students = Student::where('teacher_id', $teacher->id)
            ->with('activityResults')
            ->latest()->get();

        $total = $students->count();
        $onTrack = $students->filter(fn ($s) => ($s->activityResults->avg('score') ?? 0) >= 75)->count();
        $needAttention = $total - $onTrack;

        // Get unique sections for filter dropdown
        $sections = $students->pluck('section')
            ->unique()->filter()->values();

        $levelOptions = $students->pluck('current_level')->merge(range(1, 5))->unique()->sort()->values();

        return view('teacher.students.index', compact(
            'students', 'total', 'onTrack', 'needAttention', 'sections', 'levelOptions'
        ));
    }

    // Show add student form.
    public function create()
    {
        $teacher = auth()->user()->teacher;
        abort_unless($teacher, 403);

        return view('teacher.students.create', $this->formOptions($teacher->id));
    }

    public function store(Request $request)
    {
        $teacher = auth()->user()->teacher;
        abort_unless($teacher, 403);
        $data = $request->validate(array_merge($this->profileRules(), [
            'username' => 'required|string|max:100|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
        ]));
        // Never trust the read-only preview or a submitted age.
        $data['age'] = (int) Carbon::parse($data['birthday'])->age;

        DB::transaction(function () use ($data, $teacher) {
            $user = User::create([
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'role' => 'student',
                'status' => 'active',
            ]);

            unset($data['username'], $data['password']);
            Student::create(array_merge($data, [
                'user_id' => $user->id,
                'teacher_id' => $teacher->id,
                'total_points' => 0,
            ]));

            LogActivity::log('ADD_STUDENT', 'Students',
                'Added student: '.$data['firstname'].' '.$data['lastname']);
        });

        return redirect()->route('teacher.students.index')
            ->with('success', 'Student added successfully!');
    }

    // View student profile
    public function show($id)
    {
        $teacher = auth()->user()->teacher;
        $student = Student::where('teacher_id', $teacher->id)
            ->with('activityResults.activity')
            ->findOrFail($id);

        $avg = round($student->activityResults->avg('score') ?? 0, 1);

        if ($avg >= 75) {
            $status = 'On Track';
            $badgeClass = 'badge-green';
        } elseif ($avg >= 50) {
            $status = 'Needs Help';
            $badgeClass = 'badge-amber';
        } else {
            $status = 'Struggling';
            $badgeClass = 'badge-red';
        }

        return view('teacher.students.show', compact('student', 'avg', 'status', 'badgeClass'));
    }

    public function edit($id)
    {
        $teacher = auth()->user()->teacher;
        abort_unless($teacher, 403);
        $student = Student::where('teacher_id', $teacher->id)->with('user')->findOrFail($id);

        return view('teacher.students.edit', array_merge(
            ['student' => $student], $this->formOptions($teacher->id, $student)
        ));
    }

    public function update(Request $request, $id)
    {
        $teacher = auth()->user()->teacher;
        abort_unless($teacher, 403);
        $student = Student::where('teacher_id', $teacher->id)->findOrFail($id);
        $data = $request->validate($this->profileRules($student));
        $data['age'] = (int) Carbon::parse($data['birthday'])->age;
        $student->update($data);

        return redirect()->route('teacher.students.index')
            ->with('success', 'Student updated successfully!');
    }

    private function profileRules(?Student $student = null): array
    {
        $uniqueLrn = Rule::unique('students', 'lrn_no');
        if ($student !== null) {
            // Ignore only the authorized route record, never a submitted student ID.
            $uniqueLrn->ignore($student->id);
        }

        return [
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'lrn_no' => ['required', 'string', 'digits:12', $uniqueLrn],
            'birthday' => 'required|date_format:Y-m-d|before_or_equal:today',
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'section' => 'required|string|max:50',
            // Preserve an existing level above five that was earned through points.
            'current_level' => ['required', 'integer', Rule::in($this->availableLevels($student))],
        ];
    }

    private function availableLevels(?Student $student = null): array
    {
        return array_values(array_unique(array_merge(range(1, 5),
            $student === null ? [] : [(int) $student->current_level])));
    }

    private function formOptions(int $teacherId, ?Student $student = null): array
    {
        $sections = Student::where('teacher_id', $teacherId)->whereNotNull('section')
            ->distinct()->orderBy('section')->pluck('section')
            ->merge(['Section A', 'Section B'])->filter()->unique()->sort()->values();

        return ['sections' => $sections, 'levels' => $this->availableLevels($student)];
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
