<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = auth()->user()->role;

        // Admin can access teacher routes too
        if ($role === 'teacher' && $userRole === 'admin') {
            return $next($request);
        }

        if ($userRole !== $role) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}