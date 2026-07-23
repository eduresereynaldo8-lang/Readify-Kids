<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VoiceRecording;
use App\Models\Activity;

class VoiceRecordingController extends Controller
{
    // List all read aloud activities for the student
    public function index()
{
    $student = auth()->user()->student;

    $activities = Activity::where('is_published', true)
        ->where('activity_type', 'Read Aloud')
        ->where('level', '<=', $student->current_level)
        ->with([
            'results' => fn ($q) => $q->where('student_id', $student->id)
        ])
        ->get();

    // Group by activity type
    $grouped = $activities->groupBy('activity_type');

    return view('student.readaloud.index', compact('activities', 'grouped'));
}

    // Show a specific read aloud activity with recording form
    public function show($id)
    {
        $student  = auth()->user()->student;
        $activity = Activity::where('is_published', true)
                    ->where('activity_type', 'Read Aloud')
                    ->with(['readingMaterial', 'wordBank'])
                    ->findOrFail($id);

        // Previous recordings for this student and activity
        $recordings = VoiceRecording::where('student_id', $student->id)
                      ->where('activity_id', $id)
                      ->with('evaluation')
                      ->latest()->get();

        $attemptNumber = $recordings->count() + 1;

        return view('student.readaloud.show', compact('activity', 'recordings', 'attemptNumber'));
    }

    // Upload and submit voice recording
    public function upload(Request $request, $id)
    {
        $request->validate([
            'recording' => 'required|file|mimes:mp3,wav,ogg,webm,mp4|max:20480',
        ]);

        $student  = auth()->user()->student;
        $activity = Activity::findOrFail($id);

        // Count previous attempts
        $attemptNumber = VoiceRecording::where('student_id', $student->id)
                         ->where('activity_id', $id)->count() + 1;

        // Store the recording file
        $path = $request->file('recording')->store('recordings', 'public');

        VoiceRecording::create([
            'student_id'    => $student->id,
            'activity_id'   => $id,
            'recording_path'=> $path,
            'attempt_number'=> $attemptNumber,
            'status'        => 'pending',
        ]);

        return redirect()->route('student.readaloud.show', $id)
               ->with('success', 'Recording submitted! Your teacher will listen and give you feedback soon. 🎉');
    }
}