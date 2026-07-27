<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VoiceRecording;
use App\Models\Evaluation;

class EvaluationController extends Controller
{
    // List all pending recordings
    public function index()
    {
        $teacher = auth()->user()->teacher;

        $pending = VoiceRecording::where('status', 'pending')
                   ->whereHas('student', fn($q) => $q->where('teacher_id', $teacher->id))
                   ->with(['student', 'activity'])
                   ->latest()->get();

        $evaluated = VoiceRecording::where('status', 'evaluated')
                     ->whereHas('student', fn($q) => $q->where('teacher_id', $teacher->id))
                     ->with(['student', 'activity', 'evaluation'])
                     ->latest()->take(10)->get();

        return view('teacher.evaluations.index', compact('pending', 'evaluated'));
    }

    // Show evaluation form for a recording
    public function show($id)
    {
        $teacher   = auth()->user()->teacher;
        $recording = VoiceRecording::whereHas('student', fn($q) => $q->where('teacher_id', $teacher->id))
                     ->with(['student', 'activity', 'evaluation'])
                     ->findOrFail($id);

        return view('teacher.evaluations.show', compact('recording'));
    }

    // Save evaluation
    public function store(Request $request)
    {
        $request->validate([
            'recording_id'        => 'required|exists:voice_recordings,id',
            'pronunciation_score' => 'required|integer|min:1|max:5',
            'fluency_score'       => 'required|integer|min:1|max:5',
            'accuracy_score'      => 'required|integer|min:1|max:5',
            'comprehension_score' => 'required|integer|min:1|max:5',
            'proficiency_level'   => 'required|string',
            'feedback'            => 'nullable|string|max:1000',
        ]);

        $teacher   = auth()->user()->teacher;
        $recording = VoiceRecording::findOrFail($request->recording_id);

        // Save or update evaluation
        Evaluation::updateOrCreate(
            ['recording_id' => $recording->id],
            [
                'teacher_id'          => $teacher->id,
                'pronunciation_score' => $request->pronunciation_score,
                'fluency_score'       => $request->fluency_score,
                'accuracy_score'      => $request->accuracy_score,
                'comprehension_score' => $request->comprehension_score,
                'proficiency_level'   => $request->proficiency_level,
                'feedback'            => $request->feedback,
            ]
        );

        // Mark recording as evaluated
        $recording->update(['status' => 'evaluated']);

        // Update student's activity result score
        // Average of all 4 evaluation scores * 20 = percentage
        $avgScore = (
            $request->pronunciation_score +
            $request->fluency_score +
            $request->accuracy_score +
            $request->comprehension_score
        ) / 4 * 20;

        \App\Models\ActivityResult::updateOrCreate(
            [
                'student_id'  => $recording->student_id,
                'activity_id' => $recording->activity_id,
            ],
            [
                'score'        => $avgScore,
                'status'       => 'completed',
                'completed_at' => now(),
            ]
        );

        // Award points to student
        $student = $recording->student;
        $student->increment('total_points', $recording->activity->points_reward);
        $student->checkAndUpdateLevel();

        return redirect()->route('teacher.evaluations.index')
               ->with('success', 'Evaluation saved successfully! Student has been notified.');
    }
}