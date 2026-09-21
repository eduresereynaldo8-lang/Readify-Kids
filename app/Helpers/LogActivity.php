<?php
namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogActivity
{
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