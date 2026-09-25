<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
   ->withMiddleware(function (Middleware $middleware) {
    // The local ngrok agent forwards HTTPS requests over a loopback connection.
    $middleware->trustProxies(
        at: ['127.0.0.1', '::1'],
        headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
    );

    // Wrap web responses, including redirects and authentication errors.
    $middleware->web(prepend: [
        \App\Http\Middleware\PreventBackHistory::class,
    ]);

    $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => match ($request->user()->role) {
        'admin' => route('admin.dashboard'),
        'teacher' => route('teacher.dashboard'),
        default => route('student.dashboard'),
    });

    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
