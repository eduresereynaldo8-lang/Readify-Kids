<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VoiceRecording;
use App\Models\Activity;
use App\Helpers\LogActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VoiceRecordingController extends Controller
{
    // List all read aloud activities for the student
    public function index()
    {
        $student = auth()->user()->student;

        $activities = Activity::where('is_published', true)
            ->where('teacher_id', $student->teacher_id)
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
        $student = auth()->user()->student;

        $activity = Activity::where('is_published', true)
            ->where('teacher_id', $student->teacher_id)
            ->where('activity_type', 'Read Aloud')
            ->with(['readingMaterial', 'wordBank'])
            ->findOrFail($id);

        // Previous recordings for this student and activity
        $recordings = VoiceRecording::where('student_id', $student->id)
            ->where('activity_id', $id)
            ->with('evaluation')
            ->latest()
            ->get();

        $attemptNumber = $recordings->count() + 1;

        return view(
            'student.readaloud.show',
            compact('activity', 'recordings', 'attemptNumber')
        );
    }

    // Upload and submit voice recording for manual teacher evaluation.
    public function upload(Request $request, $id)
    {
        $student = auth()->user()->student;
        $activity = Activity::where('teacher_id', $student->teacher_id)
            ->where('is_published', true)
            ->where('activity_type', 'Read Aloud')
            ->findOrFail($id);

        $file = $request->file('recording');
        $isUploadedFile = $file instanceof \Illuminate\Http\UploadedFile;
        if (config('app.debug')) {
            Log::debug('Read aloud upload received', [
                'student_id' => $student->id,
                'activity_id' => $activity->id,
                'has_recording' => $request->hasFile('recording'),
                'mime' => $isUploadedFile && $file->isValid() ? $file->getMimeType() : null,
                'client_mime' => $isUploadedFile ? $file->getClientMimeType() : null,
                'extension' => $isUploadedFile ? $file->getClientOriginalExtension() : null,
                'size' => $isUploadedFile && $file->isValid() ? $file->getSize() : null,
            ]);
        }

        $request->validate([
            'recording' => 'required|file|mimes:mp3,wav,ogg,webm,weba,mp4,m4a|max:20480',
        ]);

        $attemptNumber = VoiceRecording::where('student_id', $student->id)
            ->where('activity_id', $activity->id)
            ->count() + 1;

        $path = null;
        try {
            $path = $file->store('recordings', 'public');
            if (!$path) {
                throw new \RuntimeException('Public disk did not store the recording.');
            }

            $recording = VoiceRecording::create([
                'student_id'     => $student->id,
                'activity_id'    => $activity->id,
                'recording_path' => $path,
                'attempt_number' => $attemptNumber,
                'status'         => 'pending',
            ]);
        } catch (\Throwable $error) {
            if ($path) {
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Throwable $cleanupError) {
                    report($cleanupError);
                }
            }
            Log::error('Read aloud upload could not be saved', [
                'student_id' => $student->id,
                'activity_id' => $activity->id,
                'exception' => $error,
            ]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your recording could not be saved. Please try again.'], 500);
            }
            return back()->withErrors(['recording' => 'Your recording could not be saved. Please try again.']);
        }

        // A badge/log failure must not turn a saved submission into a failed upload.
        try {
            \App\Services\BadgeService::checkAndAward($student);
        } catch (\Throwable $error) {
            report($error);
        }
        try {
            LogActivity::log('SUBMIT_RECORDING', 'Read Aloud',
                'Submitted Read Aloud recording for activity: ' . $activity->activity_name
                . ' (ID ' . $activity->id . ') - Attempt ' . $attemptNumber);
        } catch (\Throwable $error) {
            report($error);
        }

        $message = 'Recording submitted! Your teacher will listen and give you feedback soon. 🎉';
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'pending',
                'recording_id' => $recording->id,
                'message' => $message,
            ], 201);
        }

        return redirect()->route('student.readaloud.show', $id)->with('success', $message);
    }
}
