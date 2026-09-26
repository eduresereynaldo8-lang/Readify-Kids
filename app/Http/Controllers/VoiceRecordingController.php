<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VoiceRecording;
use App\Models\Activity;
use App\Helpers\LogActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            ->where('battle_mode', false)
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
            ->where('battle_mode', false)
            ->where('level', '<=', $student->current_level)
            ->with(['readingMaterial', 'wordBank'])
            ->findOrFail($id);

        // Previous recordings for this student and activity
        $recordings = VoiceRecording::where('student_id', $student->id)
            ->where('activity_id', $id)
            ->with('evaluation')
            ->latest()
            ->get();

        $attemptNumber = $recordings->count() + 1;
        $durationSeconds = $activity->readAloudDurationSeconds();
        $canRecord = $durationSeconds !== null && ($activity->allow_reattempt || $recordings->isEmpty());

        return view(
            'student.readaloud.show',
            compact('activity', 'recordings', 'attemptNumber', 'durationSeconds', 'canRecord')
        );
    }

    // Upload and submit voice recording for manual teacher evaluation.
    public function upload(Request $request, $id)
    {
        $student = auth()->user()->student;
        $activity = Activity::where('teacher_id', $student->teacher_id)
            ->where('is_published', true)
            ->where('activity_type', 'Read Aloud')
            ->where('battle_mode', false)
            ->where('level', '<=', $student->current_level)
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
            'recording' => 'required|file|mimes:mp3,wav,ogg,webm,weba,mp4,m4a|min:1|max:20480',
            'recording_token' => 'nullable|uuid',
        ]);

        // Reuse a token for retries of this same audio. Its path is also the durable
        // duplicate check, so a lost HTTP response does not create a second attempt.
        $token = strtolower($request->input('recording_token') ?: (string) Str::uuid());
        $directory = 'recordings/'.$student->id.'/'.$activity->id;
        $filename = $token.'.'.$file->extension();
        $recordingPath = $directory.'/'.$filename;

        try {
            [$recording, $replayed] = DB::transaction(function () use ($student, $activity, $file, $directory, $filename, $recordingPath) {
                // Serialize attempts across tabs before checking the reattempt rule.
                $student->newQuery()->whereKey($student->id)->lockForUpdate()->firstOrFail();
                $recordings = VoiceRecording::where('student_id', $student->id)
                    ->where('activity_id', $activity->id);
                if ($existing = (clone $recordings)->where('recording_path', $recordingPath)->first()) {
                    return [$existing, true];
                }
                if ($activity->readAloudDurationSeconds() === null) {
                    throw ValidationException::withMessages([
                        'recording' => 'This activity does not have a valid reading duration. Please contact your teacher.',
                    ]);
                }
                if (!$activity->allow_reattempt && (clone $recordings)->exists()) {
                    abort(409, 'You have already submitted this activity. Another attempt is not allowed.');
                }

                $path = null;
                try {
                    $path = $file->storeAs($directory, $filename, 'public');
                    if (!$path) {
                        throw new \RuntimeException('Public disk did not store the recording.');
                    }
                    $recording = VoiceRecording::create([
                        'student_id' => $student->id,
                        'activity_id' => $activity->id,
                        'recording_path' => $path,
                        'attempt_number' => ((int) (clone $recordings)->max('attempt_number')) + 1,
                        'status' => 'pending',
                    ]);

                    return [$recording, false];
                } catch (\Throwable $error) {
                    // Clean up while the student lock is still held.
                    if ($path) {
                        try {
                            Storage::disk('public')->delete($path);
                        } catch (\Throwable $cleanupError) {
                            report($cleanupError);
                        }
                    }
                    throw $error;
                }
            });
        } catch (ValidationException | \Symfony\Component\HttpKernel\Exception\HttpException $error) {
            throw $error;
        } catch (\Throwable $error) {
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

        if (!$replayed) {
            // A badge/log failure must not turn a saved submission into a failed upload.
            try {
                \App\Services\BadgeService::checkAndAward($student);
            } catch (\Throwable $error) {
                report($error);
            }
            try {
                LogActivity::log('SUBMIT_RECORDING', 'Read Aloud',
                    'Submitted Read Aloud recording for activity: ' . $activity->activity_name
                    . ' (ID ' . $activity->id . ') - Attempt ' . $recording->attempt_number);
            } catch (\Throwable $error) {
                report($error);
            }

        }

        $message = 'Recording submitted! Your teacher will listen and give you feedback soon. 🎉';
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'pending',
                'recording_id' => $recording->id,
                'message' => $message,
            ], $replayed ? 200 : 201);
        }

        return redirect()->route('student.readaloud.show', $id)->with('success', $message);
    }
}

