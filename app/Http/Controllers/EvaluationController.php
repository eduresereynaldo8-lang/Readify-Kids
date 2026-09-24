<?php

namespace App\Http\Controllers;

use App\Helpers\LogActivity;
use App\Models\ActivityResult;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\VoiceRecording;
use App\Services\BadgeService;
use App\Services\ReadingAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    private function recordingsForTeacher()
    {
        $teacher = auth()->user()->teacher;
        abort_unless($teacher, 403);

        return VoiceRecording::whereHas('student', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->whereHas('activity', fn ($q) => $q->where('teacher_id', $teacher->id)
                ->where('activity_type', 'Read Aloud')->where('battle_mode', false));
    }

    public function index()
    {
        $pending = $this->recordingsForTeacher()->where('status', 'pending')
            ->with(['student', 'activity'])->latest()->get();
        $evaluated = $this->recordingsForTeacher()->where('status', 'evaluated')
            ->with(['student', 'activity', 'evaluation'])->latest()->take(10)->get();

        return view('teacher.evaluations.index', compact('pending', 'evaluated'));
    }

    public function show($id)
    {
        $recording = $this->recordingsForTeacher()
            ->with(['student', 'activity.readingMaterial', 'evaluation'])->findOrFail($id);
        $content = $recording->activity->readingMaterial?->content;
        $totalWords = ReadingAssessment::wordCount($content);
        $passageText = ReadingAssessment::passageText($content);
        $observations = ReadingAssessment::OBSERVATIONS;

        return view('teacher.evaluations.show', compact('recording', 'totalWords', 'passageText', 'observations'));
    }

    public function store(Request $request)
    {
        $request->validate(['recording_id' => 'required|integer']);
        $teacher = auth()->user()->teacher;

        DB::transaction(function () use ($request, $teacher) {
            // Lock the recording so duplicate submissions cannot award twice.
            $recording = $this->recordingsForTeacher()->lockForUpdate()
                ->with('activity.readingMaterial')->findOrFail($request->integer('recording_id'));
            // Serialize results/rewards across separate attempts for the same student.
            $student = Student::where('teacher_id', $teacher->id)->lockForUpdate()
                ->findOrFail($recording->student_id);
            $evaluation = Evaluation::where('recording_id', $recording->id)->first();
            $firstEvaluation = $recording->status !== 'evaluated' && $evaluation === null;
            $data = ReadingAssessment::calculate(
                $request->all(), $recording->activity->readingMaterial?->content
            );

            Evaluation::updateOrCreate(['recording_id' => $recording->id],
                array_merge($data, ['teacher_id' => $teacher->id]));
            $recording->update(['status' => 'evaluated']);

            $result = ActivityResult::where('student_id', $student->id)
                ->where('activity_id', $recording->activity_id)->lockForUpdate()->first();
            ActivityResult::updateOrCreate([
                'student_id' => $student->id, 'activity_id' => $recording->activity_id,
            ], [
                'score' => ReadingAssessment::finalScore($data),
                'status' => 'completed',
                'completed_at' => $result?->completed_at ?? now(),
            ]);

            // Preserve points per first evaluated recording/attempt, but never per edit.
            if ($firstEvaluation) {
                $student->increment('total_points', $recording->activity->points_reward);
                $student->checkAndUpdateLevel();
            }

            // BadgeService only reads/writes this database, with no external side effects.
            // Keep badge and log writes atomic with evaluation/results/rewards.
            BadgeService::checkAndAward($student);
            LogActivity::log('EVALUATE', 'Evaluation',
                sprintf('Evaluated recording ID %d — Oral Reading %.2f%%, Comprehension %s, Observation Level %d',
                    $recording->id, $data['oral_reading_score'],
                    ($data['comprehension_percentage'] === null ? 'N/A (no questions)'
                        : number_format($data['comprehension_percentage'], 2) . '%'), $data['observation_level']));
        }, 3);

        return redirect()->route('teacher.evaluations.index')
            ->with('success', 'Evaluation saved successfully! Student has been notified.');
    }
}
