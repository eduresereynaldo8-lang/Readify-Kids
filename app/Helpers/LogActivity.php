<?php
namespace App\Helpers;

use App\Models\ActivityLog;
use App\Models\Student;
use Illuminate\Support\Facades\Request;

class LogActivity
{
    // Rewards may be granted by a teacher; attribute these events to the student.
    public static function forStudent(Student $student, string $action, string $module, string $description): void
    {
        ActivityLog::create([
            'user_id' => $student->user_id,
            'role' => 'student',
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function log(
        string $action,
        string $module = '',
        string $description = ''
    ): void {
        if (!auth()->check()) return;

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'role'       => auth()->user()->role,
            'action'     => $action,
            'module'     => $module,
            'description'=> $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}