<?php

namespace App\Http\Controllers;

use App\Helpers\LogActivity;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentProgress;
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
        abort_unless($teacher, 403);
        $students = StudentProgress::withActivityProgress(Student::where('teacher_id', $teacher->id))
            ->latest()->get();

        $total = $students->count();
        $onTrack = $students->filter(fn ($s) => $s->reading_status['key'] === 'on_track')->count();
        $needAttention = $students->filter(fn ($s) => in_array($s->reading_status['key'], ['needs_help', 'struggling'], true))->count();

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
        abort_unless($teacher, 403);
        $student = StudentProgress::withActivityProgress(Student::where('teacher_id', $teacher->id))
            ->with(['activityResults' => fn ($q) => $q->with('activity')->orderByDesc('completed_at')->orderByDesc('id')])
            ->findOrFail($id);

        $avg = $student->activity_results_avg_score === null ? null : round($student->activity_results_avg_score, 2);
        $status = $student->reading_status['label'];
        $badgeClass = $student->reading_status['badge'];

        return view('teacher.students.show', compact('student', 'avg', 'status', 'badgeClass'));
    }

    public function exportClassPdf(Request $request)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);
        $filters = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(StudentProgress::EXPORT_FILTERS)],
            // Keep the page status as an intersection, never silently broaden its scope.
            'page_status' => ['sometimes', 'required', Rule::in(array_merge(['all'], array_keys(StudentProgress::STATUSES)))],
            'search' => ['nullable', 'string', 'max:200'],
            'section' => ['nullable', 'string', 'max:50'],
            'level' => ['nullable', 'integer', 'min:1'],
        ]);
        $filters = array_merge(['status' => 'all', 'page_status' => 'all', 'search' => null,
            'section' => null, 'level' => null], $filters);

        if (! app()->bound('dompdf.wrapper')) {
            return $this->pdfUnavailable();
        }

        $query = StudentProgress::forTeacher($teacher->id)
            ->when($filters['section'] !== null, fn ($q) => $q->where('section', $filters['section']))
            ->when($filters['level'] !== null, fn ($q) => $q->where('current_level', $filters['level']))
            ->orderBy('lastname')->orderBy('firstname')->orderBy('id');
        $search = mb_strtolower(trim($filters['search'] ?? ''));
        $rows = $query->get()
            ->filter(fn ($student) => $search === ''
                || str_contains(mb_strtolower($student->firstname.' '.$student->lastname), $search)
                || str_contains(mb_strtolower($student->lrn_no ?? ''), $search))
            ->map(fn ($student) => StudentProgress::report($student))
            ->filter(fn ($row) => ($filters['status'] === 'all' || $row['status']['key'] === $filters['status'])
                && ($filters['page_status'] === 'all' || $row['status']['key'] === $filters['page_status']))
            ->values();

        $summary = ['total' => $rows->count()];
        foreach (array_keys(StudentProgress::STATUSES) as $key) {
            $summary[$key] = $rows->where('status.key', $key)->count();
        }
        $reportFilter = $filters['status'] === 'all' ? 'All Students' : StudentProgress::STATUSES[$filters['status']]['label'];
        if ($filters['page_status'] !== 'all') {
            $reportFilter .= ' (Page status: '.StudentProgress::STATUSES[$filters['page_status']]['label'].')';
        }
        $generatedAt = now();
        $filename = match ($filters['status']) {
            'on_track' => 'ReadifyKids_On_Track_Students',
            'needs_help' => 'ReadifyKids_Needs_Help_Students',
            'struggling' => 'ReadifyKids_Struggling_Students',
            default => 'ReadifyKids_All_Students_Report',
        };

        return $this->downloadPdf('teacher.students.class-report-pdf',
            compact('teacher', 'rows', 'summary', 'filters', 'reportFilter', 'generatedAt'),
            $filename.'_'.$generatedAt->format('Y-m-d').'.pdf', 'landscape');
    }

    public function exportPdf(Request $request, $id)
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 403);
        // Resolve within the teacher's scope even when the PDF package is unavailable.
        $student = Student::where('teacher_id', $teacher->id)->findOrFail($id);
        if (! app()->bound('dompdf.wrapper')) {
            return $this->pdfUnavailable();
        }

        $student = StudentProgress::forTeacher($teacher->id)
            ->with([
                'activityResults' => fn ($q) => $q->with('activity')->orderByDesc('completed_at')->orderByDesc('id'),
                'evaluations' => fn ($q) => $q->where('evaluations.teacher_id', $teacher->id)
                    ->with(['voiceRecording.activity', 'teacher'])->orderByDesc('evaluations.created_at')->orderByDesc('evaluations.id'),
            ])->findOrFail($student->id);
        $progress = StudentProgress::report($student);
        $generatedAt = now();

        return $this->downloadPdf('teacher.students.student-report-pdf',
            compact('teacher', 'student', 'progress', 'generatedAt'),
            'ReadifyKids_Student_'.$student->id.'_'.$generatedAt->format('Y-m-d').'.pdf', 'portrait');
    }

    private function pdfUnavailable()
    {
        return redirect()->route('teacher.students.index')
            ->with('error', 'PDF export is not available yet. Please contact your administrator to enable it.');
    }

    private function downloadPdf(string $view, array $data, string $filename, string $orientation)
    {
        $response = app('dompdf.wrapper')->loadView($view, $data)
            ->setPaper('a4', $orientation)->download($filename);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
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
