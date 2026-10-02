<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudentProfilePhoto
{
    public function replace(Student $student, UploadedFile $photo): void
    {
        $path = $photo->store('profile_pictures/students/'.$student->id, 'public');
        if ($path === false) {
            throw ValidationException::withMessages([
                'profile_picture' => 'The picture could not be saved. Please try again.',
            ])->errorBag('photo');
        }

        try {
            $previous = DB::transaction(function () use ($student, $path) {
                $current = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
                $previous = $current->profile_picture;
                $current->profile_picture = $path;
                $current->save();

                return $previous;
            });
        } catch (Throwable $exception) {
            $this->deleteManaged($student, $path);
            throw $exception;
        }

        // Retain the old file until the new path has committed successfully.
        $this->deleteManaged($student, $previous);
    }

    public function remove(Student $student): void
    {
        $previous = DB::transaction(function () use ($student) {
            $current = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $previous = $current->profile_picture;
            $current->profile_picture = null;
            $current->save();

            return $previous;
        });

        $this->deleteManaged($student, $previous);
    }

    private function deleteManaged(Student $student, ?string $path): void
    {
        if (! $student->ownsProfilePicture($path)) {
            return;
        }

        try {
            if (! Storage::disk('public')->delete($path)) {
                Log::warning('Could not remove an old student profile picture.', ['student_id' => $student->id]);
            }
        } catch (Throwable $exception) {
            // The new path has committed; report cleanup failures without undoing it.
            report($exception);
        }
    }
}
