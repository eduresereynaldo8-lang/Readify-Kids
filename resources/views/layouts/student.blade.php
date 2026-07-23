<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Readify Kids') — Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #F0F6FF; }

        .sidebar {
            width: 220px; height: 100vh; position: fixed; top: 0; left: 0;
            background: #fff; border-right: 1px solid #E5E7EB;
            display: flex; flex-direction: column; z-index: 100;
        }
        .sidebar-logo {
            padding: 14px 16px; border-bottom: 1px solid #E5E7EB;
            display: flex; align-items: center; gap: 10px;
        }
        .logo-icon {
            width: 36px; height: 36px; background: #185FA5;
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
        }
        .logo-icon i { color: #fff; font-size: 18px; }
        .logo-name { font-size: 14px; font-weight: 700; color: #111827; }
        .logo-sub { font-size: 10px; color: #9CA3AF; }

        /* Student profile in sidebar */
        .stu-profile {
            padding: 14px 16px; border-bottom: 1px solid #E5E7EB;
            display: flex; flex-direction: column; align-items: center; gap: 5px;
        }
        .stu-big-av {
            width: 52px; height: 52px; border-radius: 50%;
            background: #DBEAFE; color: #1E40AF;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 700;
            border: 3px solid #93C5FD;
        }
        .stu-full-name { font-size: 13px; font-weight: 700; color: #111827; }
        .stu-level { font-size: 11px; color: #6B7280; }
        .stu-points { font-size: 12px; font-weight: 700; color: #F59E0B; }

        .nav-section { padding: 10px 8px; flex: 1; overflow-y: auto; }
        .nav-item {
            display: flex; align-items: center; gap: 9px;
            padding: 8px 10px; border-radius: 8px;
            font-size: 13px; color: #6B7280; text-decoration: none;
            margin-bottom: 2px; transition: background 0.15s;
        }
        .nav-item:hover { background: #F3F4F6; color: #111827; }
        .nav-item.active { background: #EFF6FF; color: #185FA5; font-weight: 700; }
        .nav-item i { font-size: 17px; }

        .sidebar-footer { padding: 12px 16px; border-top: 1px solid #E5E7EB; }
        .logout-btn {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: #EF4444; text-decoration: none;
        }

        .main-content { margin-left: 220px; min-height: 100vh; display: flex; flex-direction: column; }

        .topbar {
            background: #fff; border-bottom: 1px solid #E5E7EB;
            padding: 12px 24px; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 99;
        }
        .topbar-greet { font-size: 15px; font-weight: 700; color: #111827; }
        .topbar-sub { font-size: 11px; color: #6B7280; margin-top: 1px; }

        .content-area { padding: 20px 24px; flex: 1; }

        /* Cards */
        .dash-card {
            background: #fff; border: 1px solid #E5E7EB;
            border-radius: 14px; padding: 16px;
        }
        .dash-card-title {
            font-size: 13px; font-weight: 700; color: #111827;
            margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;
        }
        .dash-card-title a { font-size: 11px; color: #185FA5; font-weight: 400; text-decoration: none; }

        /* Stat cards */
        .stat-card {
            background: #fff; border: 1px solid #E5E7EB;
            border-radius: 12px; padding: 14px; text-align: center;
        }
        .stat-emoji { font-size: 24px; margin-bottom: 4px; }
        .stat-value { font-size: 22px; font-weight: 700; color: #111827; }
        .stat-label { font-size: 11px; color: #6B7280; margin-top: 2px; }

        /* Hero */
        .hero-banner {
            background: linear-gradient(135deg, #185FA5, #2563EB);
            border-radius: 14px; padding: 18px 22px;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px;
        }
        .hero-title { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .hero-sub { font-size: 12px; color: rgba(255,255,255,0.85); margin-bottom: 10px; }
        .hero-btn {
            font-size: 12px; padding: 7px 18px; border-radius: 8px;
            border: none; background: #fff; color: #185FA5; font-weight: 700; cursor: pointer;
            text-decoration: none;
        }
        .hero-emoji { font-size: 52px; }

        /* XP bar */
        .xp-row {
            background: #fff; border: 1px solid #E5E7EB; border-radius: 10px;
            padding: 10px 14px; display: flex; align-items: center; gap: 10px;
            margin-bottom: 16px;
        }
        .xp-label { font-size: 11px; color: #6B7280; white-space: nowrap; }
        .xp-bar-bg { flex: 1; background: #E5E7EB; border-radius: 6px; height: 10px; }
        .xp-bar-fill {
            height: 10px; border-radius: 6px;
            background: linear-gradient(90deg, #185FA5, #60A5FA);
        }
        .xp-val { font-size: 11px; color: #6B7280; white-space: nowrap; }
        .level-pill {
            background: #185FA5; color: #fff;
            font-size: 11px; font-weight: 700;
            padding: 3px 10px; border-radius: 20px; white-space: nowrap;
        }
        .level-pill-gray {
            background: #E5E7EB; color: #9CA3AF;
            font-size: 11px; font-weight: 700;
            padding: 3px 10px; border-radius: 20px; white-space: nowrap;
        }

        /* Activity items */
        .act-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 10px;
            border: 1px solid #E5E7EB; background: #F9FAFB;
            margin-bottom: 8px; text-decoration: none; color: inherit;
            transition: border-color 0.15s;
        }
        .act-item:hover { border-color: #185FA5; background: #EFF6FF; }
        .act-icon {
            width: 34px; height: 34px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .act-title { font-size: 12px; font-weight: 600; color: #111827; }
        .act-sub { font-size: 10px; color: #9CA3AF; margin-top: 2px; }
        .act-badge {
            font-size: 10px; padding: 2px 9px; border-radius: 20px;
            font-weight: 600; white-space: nowrap; margin-left: auto;
        }
        .b-new      { background: #DBEAFE; color: #1E40AF; }
        .b-progress { background: #FEF3C7; color: #92400E; }
        .b-done     { background: #DCFCE7; color: #166534; }

        /* Badge items */
        .badge-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 8px; }
        .badge-item { display: flex; flex-direction: column; align-items: center; gap: 3px; }
        .badge-icon {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .badge-name { font-size: 10px; color: #6B7280; text-align: center; }
        .badge-locked { opacity: 0.35; }

        /* Leaderboard */
        .lb-item {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 8px; border-radius: 8px; font-size: 12px; margin-bottom: 4px;
        }
        .lb-item.me { background: #EFF6FF; border: 1px solid #BFDBFE; }
        .lb-rank { width: 22px; text-align: center; font-size: 14px; flex-shrink: 0; }
        .lb-av {
            width: 26px; height: 26px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; flex-shrink: 0;
        }
        .lb-name { flex: 1; font-weight: 600; color: #111827; }
        .lb-pts { font-size: 11px; font-weight: 700; color: #F59E0B; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="ti ti-book-2"></i></div>
        <div>
            <div class="logo-name">Readify Kids</div>
            <div class="logo-sub">Student Portal</div>
        </div>
    </div>

    @php $student = auth()->user()->student; @endphp
    <div class="stu-profile">
        <div class="stu-big-av">
            {{ strtoupper(substr($student->firstname ?? 'S', 0, 1)) }}{{ strtoupper(substr($student->lastname ?? 'T', 0, 1)) }}
        </div>
        <div class="stu-full-name">{{ $student->firstname }} {{ $student->lastname }}</div>
        <div class="stu-level">Level {{ $student->current_level }} · {{ $student->section }}</div>
        <div class="stu-points">⭐ {{ number_format($student->total_points) }} pts</div>
    </div>

    <div class="nav-section">
        <a href="{{ route('student.dashboard') }}" class="nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
            <i class="ti ti-layout-dashboard"></i> Dashboard
        </a>
        <a href="{{ route('student.game.index') }}" class="nav-item {{ request()->routeIs('student.game.*') ? 'active' : '' }}"
   style="{{ request()->routeIs('student.game.*') ? 'background:#F5F3FF;color:#7C3AED;' : '' }}">
    <i class="ti ti-sword" style="{{ request()->routeIs('student.game.*') ? 'color:#7C3AED;' : '' }}"></i> Battle Arena
</a>
        <a href="{{ route('student.activities.index') }}" class="nav-item {{ request()->routeIs('student.activities.*') ? 'active' : '' }}">
            <i class="ti ti-book"></i> My Activities
        </a>
        <a href="{{ route('student.readaloud.index') }}" class="nav-item {{ request()->routeIs('student.readaloud.*') ? 'active' : '' }}">
            <i class="ti ti-microphone"></i> Read Aloud
        </a>
        <a href="{{ route('student.leaderboard') }}" class="nav-item {{ request()->routeIs('student.leaderboard') ? 'active' : '' }}">
            <i class="ti ti-award"></i> Leaderboard
        </a>
        <a href="{{ route('student.progress') }}" class="nav-item {{ request()->routeIs('student.progress') ? 'active' : '' }}">
            <i class="ti ti-chart-bar"></i> My Progress
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="{{ route('logout') }}" class="logout-btn">
            <i class="ti ti-logout"></i> Log out
        </a>
    </div>
</div>

<!-- Main -->
<div class="main-content">
    <div class="topbar">
        <div>
            <div class="topbar-greet">@yield('page-greet', 'Welcome!')</div>
            <div class="topbar-sub">@yield('page-sub', '')</div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <i class="ti ti-bell" style="font-size:20px;color:#9CA3AF;"></i>
            <span class="level-pill">Level {{ $student->current_level }}</span>
            <span class="stu-points">⭐ {{ number_format($student->total_points) }} pts</span>
        </div>
    </div>

    <div class="content-area">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>