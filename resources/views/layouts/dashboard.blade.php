@php
    $role = auth()->user()->role;
    $profile = $role === 'student' ? auth()->user()->student : ($role === 'teacher' ? auth()->user()->teacher : auth()->user());
    $displayName = $role === 'admin' ? (auth()->user()->username ?: 'Administrator') : $profile->firstname;
    $initials = $role === 'admin' ? 'A' : mb_strtoupper(mb_substr($profile->firstname ?? '', 0, 1) . mb_substr($profile->lastname ?? '', 0, 1));
    $currentRoute = request()->route()?->getName() ?? '';
    $activeRoute = $currentRoute;
    $groups = [
        'admin.teachers' => 'admin.teachers', 'admin.students' => 'admin.students',
        'admin.activities' => 'admin.activities', 'admin.evaluations' => 'admin.evaluations',
        'teacher.students' => 'teacher.students.index', 'teacher.activities' => 'teacher.activities.index',
        'teacher.evaluations' => 'teacher.evaluations.index',
        'student.activities' => 'student.activities.index', 'student.readaloud' => 'student.readaloud.index',
        'student.game' => 'student.game.index',
    ];
    foreach ($groups as $prefix => $destination) {
        if ($currentRoute === $prefix || str_starts_with($currentRoute, $prefix . '.')) {
            $activeRoute = $destination;
            break;
        }
    }
    if (in_array($currentRoute, ['teacher.activities.create.readaloud', 'teacher.activities.create.battle'])) {
        $activeRoute = $currentRoute;
    }
    $isDashboard = request()->routeIs($role . '.dashboard');
    $navigation = match($role) {
        'student' => [
            ['Learn','student.dashboard','home','Dashboard'],
            ['Learn','student.game.index','swords','Battle Arena'],
            ['Learn','student.activities.index','book','My Activities'],
            ['Learn','student.readaloud.index','microphone','Read Aloud'],
            ['Explore','student.leaderboard','trophy','Leaderboard'],
            ['Explore','student.progress','chart-bar','My Progress'],
        ],
        'teacher' => [
            ['Classroom','teacher.dashboard','home','Dashboard'],
            ['Classroom','teacher.students.index','users','Students'],
            ['Classroom','teacher.activities.index','book','All Activities'],
            ['Classroom','teacher.activities.create.readaloud','microphone','+ Read Aloud'],
            ['Classroom','teacher.activities.create.battle','swords','+ Battle'],
            ['Classroom','teacher.evaluations.index','clipboard-check','Evaluations'],
            ['Reports','teacher.progress','chart-bar','Progress'],
            ['Reports','teacher.leaderboard','trophy','Leaderboard'],
            ['Reports','teacher.logs','history','Activity logs'],
        ],
        default => [
            ['Overview','admin.dashboard','home','Dashboard'],
            ['Management','admin.teachers','school','Teachers'],
            ['Management','admin.students','users','Students'],
            ['Management','admin.reports','chart-bar','Reports'],
            ['Management','admin.logs','history','Activity Logs'],
        ],
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.session-history')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if($role === 'student')
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Readify Kids">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Readify Kids</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/readify-dashboard.css') }}" rel="stylesheet">
    <link href="{{ asset('css/readify-pages.css') }}" rel="stylesheet">
    @stack('styles')
    <script src="{{ asset('js/readify-dashboard.js') }}" defer></script>
</head>
<body class="readify-dashboard" data-role="{{ $role }}">
<a class="rk-skip" href="#dashboard-content">Skip to dashboard</a>
<aside class="rk-sidebar sidebar" id="dashboardSidebar" aria-label="{{ ucfirst($role) }} navigation">
    <div class="rk-brand-row">
        <a class="rk-brand" href="{{ route($role . '.dashboard') }}" aria-label="Readify Kids dashboard">
            <span class="rk-brand-icon"><i class="ti ti-book-2" aria-hidden="true"></i></span>
            <span>Readify <strong><b>K</b><b>i</b><b>d</b><b>s</b></strong></span>
        </a>
        <button class="rk-sidebar-close rk-icon-button" type="button" aria-label="Close navigation"><i class="ti ti-x" aria-hidden="true"></i></button>
    </div>
    @if($role !== 'student')<span class="rk-role">{{ strtoupper($role) }}</span>@endif
    <div class="rk-sidebar-profile">
        <span class="rk-avatar rk-avatar-lg">{{ $initials }}</span>
        <div><strong>{{ $role === 'admin' ? 'Administrator' : $profile->firstname . ' ' . $profile->lastname }}</strong>
            <small>{{ $role === 'student' ? 'Level ' . $student->current_level . ' · ' . $student->section : ($role === 'teacher' ? ($teacher->school_name ?: 'Your classroom') : 'System overview') }}</small>
            @if($role === 'student')<span class="rk-sidebar-points">★ {{ number_format($student->total_points) }} pts</span>@endif
        </div>
    </div>
    <nav class="rk-nav">
        @php $lastSection = null; @endphp
        @foreach($navigation as [$section,$routeName,$icon,$label])
            @if($section !== $lastSection)<div class="rk-nav-label">{{ $section }}</div>@php $lastSection = $section; @endphp @endif
            <a href="{{ route($routeName) }}" class="rk-nav-link {{ $activeRoute === $routeName ? 'active' : '' }}" @if($activeRoute === $routeName) aria-current="page" @endif>
                <i class="ti ti-{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
    <div class="rk-sidebar-art" aria-hidden="true"><span class="rk-art-star star-a">✦</span><span class="rk-art-star star-b">★</span><span class="rk-art-star star-c">✧</span><div class="rk-art-book"><i class="ti ti-book-2"></i><span>Aa</span></div><p>A little reading.<br>A world of possibilities.</p></div>
    <form method="POST" action="{{ route('logout') }}" class="rk-logout">@csrf<button type="submit"><i class="ti ti-logout" aria-hidden="true"></i> Log out</button></form>
</aside>
<button class="rk-sidebar-overlay" id="dashboardOverlay" type="button" aria-label="Close navigation" hidden></button>
<div class="rk-main main-content">
    <header class="rk-topbar">
        <div class="rk-greeting">
            <button class="rk-menu-button rk-icon-button" id="dashboardMenu" type="button" aria-label="Open navigation" aria-controls="dashboardSidebar" aria-expanded="false"><i class="ti ti-menu-2" aria-hidden="true"></i></button>
            <span class="rk-sun" aria-hidden="true">☀</span><div><h1>@yield('greeting', $__env->yieldContent('page-title', $__env->yieldContent('page-greet', 'Welcome!')))</h1><p>@yield('subtitle', $__env->yieldContent('page-sub'))</p></div>
        </div>
        <div class="rk-topbar-actions">
            <time datetime="{{ now()->toDateString() }}"><i class="ti ti-calendar-event" aria-hidden="true"></i>{{ now()->format('M j, Y') }}</time>
            <details class="rk-profile-menu">
                <summary><span class="rk-avatar">{{ $initials }}</span><span>{{ $displayName }}</span><i class="ti ti-chevron-down" aria-hidden="true"></i></summary>
                <div class="rk-profile-panel"><strong>{{ ucfirst($role) }} account</strong>
                    @if($role === 'student')<a href="{{ route('student.progress') }}">My progress</a>@elseif($role === 'teacher')<a href="{{ route('teacher.students.index') }}">My classroom</a>@else<a href="{{ route('admin.reports') }}">System reports</a>@endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form>
                </div>
            </details>
        </div>
    </header>
    <main id="dashboard-content" class="rk-content {{ $isDashboard ? '' : 'rk-standard-content' }}" tabindex="-1">
        @if($isDashboard && session('success'))<div class="rk-notice" role="status">{{ session('success') }}</div>@endif
        @yield('content')
    </main>
    <footer class="rk-page-footer">Readify Kids <span>Little steps. Bright futures.</span></footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
