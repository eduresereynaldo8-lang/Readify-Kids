<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Readify Kids') — Teacher Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #F3F4F6; }

        /* Sidebar */
        .sidebar {
            width: 220px; height: 100vh; position: fixed; top: 0; left: 0;
            background: #fff; border-right: 1px solid #E5E7EB;
            display: flex; flex-direction: column; z-index: 100;
        }
        .sidebar-logo {
            padding: 16px; border-bottom: 1px solid #E5E7EB;
            display: flex; align-items: center; gap: 10px;
        }
        .logo-icon {
            width: 36px; height: 36px; background: #185FA5;
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
        }
        .logo-icon i { color: #fff; font-size: 18px; }
        .logo-name { font-size: 14px; font-weight: 700; color: #111827; }
        .logo-sub { font-size: 10px; color: #9CA3AF; }

        .nav-section { padding: 12px 10px; flex: 1; overflow-y: auto; }
        .nav-label {
            font-size: 10px; color: #9CA3AF; text-transform: uppercase;
            letter-spacing: 0.07em; padding: 0 8px; margin: 12px 0 5px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 9px;
            padding: 8px 10px; border-radius: 8px;
            font-size: 13px; color: #6B7280; text-decoration: none;
            margin-bottom: 2px; transition: background 0.15s;
        }
        .nav-item:hover { background: #F3F4F6; color: #111827; }
        .nav-item.active { background: #EFF6FF; color: #185FA5; font-weight: 600; }
        .nav-item i { font-size: 17px; }

        .sidebar-footer {
            padding: 12px 16px; border-top: 1px solid #E5E7EB;
        }
        .logout-btn {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: #EF4444; text-decoration: none;
        }

        /* Main content */
        .main-content { margin-left: 220px; min-height: 100vh; display: flex; flex-direction: column; }

        /* Topbar */
        .topbar {
            background: #fff; border-bottom: 1px solid #E5E7EB;
            padding: 12px 24px; display: flex; align-items: center;
            justify-content: space-between; position: sticky; top: 0; z-index: 99;
        }
        .topbar-title { font-size: 15px; font-weight: 600; color: #111827; }
        .topbar-sub { font-size: 11px; color: #6B7280; margin-top: 1px; }
        .teacher-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: #EFF6FF; display: flex; align-items: center;
            justify-content: center; font-size: 12px; font-weight: 700; color: #185FA5;
        }

        /* Content area */
        .content-area { padding: 20px 24px; flex: 1; }

        /* Metric cards */
        .metric-card {
            background: #fff; border: 1px solid #E5E7EB;
            border-radius: 12px; padding: 16px;
        }
        .metric-label { font-size: 11px; color: #6B7280; margin-bottom: 4px; }
        .metric-value { font-size: 26px; font-weight: 700; color: #111827; }
        .metric-sub { font-size: 11px; color: #9CA3AF; margin-top: 2px; }
        .metric-icon {
            width: 36px; height: 36px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }

        /* Cards */
        .dash-card {
            background: #fff; border: 1px solid #E5E7EB;
            border-radius: 12px; padding: 16px;
        }
        .dash-card-title {
            font-size: 13px; font-weight: 600; color: #111827;
            margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;
        }
        .dash-card-title a { font-size: 11px; color: #185FA5; font-weight: 400; text-decoration: none; }

        /* Table */
        .dash-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .dash-table th {
            text-align: left; color: #9CA3AF; font-weight: 500;
            padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 11px;
        }
        .dash-table td { padding: 9px 10px; border-bottom: 1px solid #F3F4F6; color: #374151; }
        .dash-table tr:last-child td { border-bottom: none; }
        .dash-table tr:hover td { background: #F9FAFB; }

        /* Badges */
        .status-badge {
            display: inline-block; font-size: 10px; padding: 2px 10px;
            border-radius: 20px; font-weight: 500;
        }
        .badge-green { background: #DCFCE7; color: #166534; }
        .badge-amber { background: #FEF3C7; color: #92400E; }
        .badge-red   { background: #FEE2E2; color: #991B1B; }
        .badge-blue  { background: #DBEAFE; color: #1E40AF; }

        /* Progress bar */
        .prog-bg { background: #E5E7EB; border-radius: 4px; height: 6px; width: 80px; display: inline-block; vertical-align: middle; }
        .prog-fill { height: 6px; border-radius: 4px; display: block; }

        /* Activity feed */
        .feed-item { display: flex; gap: 10px; padding: 8px 0; border-bottom: 1px solid #F3F4F6; }
        .feed-item:last-child { border-bottom: none; }
        .feed-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
        .feed-text { font-size: 12px; color: #374151; line-height: 1.4; }
        .feed-time { font-size: 10px; color: #9CA3AF; margin-top: 2px; }

        /* Btn */
        .btn-listen {
            font-size: 11px; padding: 3px 10px; border-radius: 6px;
            border: 1px solid #D1D5DB; background: #fff; color: #374151; cursor: pointer;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="ti ti-book-2"></i></div>
        <div>
            <div class="logo-name">Readify Kids</div>
            <div class="logo-sub">Teacher Panel</div>
        </div>
    </div>
    <div class="nav-section">
        <div class="nav-label">Main</div>
        <a href="{{ route('teacher.dashboard') }}" class="nav-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
            <i class="ti ti-layout-dashboard"></i> Dashboard
        </a>
        <a href="{{ route('teacher.students.index') }}" class="nav-item {{ request()->routeIs('teacher.students.*') ? 'active' : '' }}">
            <i class="ti ti-users"></i> Students
        </a>
<a href="{{ route('teacher.activities.index') }}" class="nav-item {{ request()->routeIs('teacher.activities.*') ? 'active' : '' }}">
            <i class="ti ti-book"></i> All Activities
        </a>
        <a href="{{ route('teacher.activities.create.readaloud') }}" class="nav-item" style="padding-left:34px;font-size:12px;">
            <i class="ti ti-microphone" style="font-size:15px;"></i> + Read Aloud
        </a>
        <a href="{{ route('teacher.activities.create.battle') }}" class="nav-item" style="padding-left:34px;font-size:12px;">
            <i class="ti ti-sword" style="font-size:15px;"></i> + Battle
        </a>
        <a href="{{ route('teacher.evaluations.index') }}" class="nav-item {{ request()->routeIs('teacher.evaluations.*') ? 'active' : '' }}">
            <i class="ti ti-microphone"></i> Evaluations
        </a>
        <div class="nav-label">Reports</div>
        <a href="{{ route('teacher.progress') }}" class="nav-item {{ request()->routeIs('teacher.progress') ? 'active' : '' }}">
            <i class="ti ti-chart-bar"></i> Progress
        </a>
        <a href="{{ route('teacher.leaderboard') }}" class="nav-item {{ request()->routeIs('teacher.leaderboard') ? 'active' : '' }}">
            <i class="ti ti-trophy"></i> Leaderboard
        </a>
        <div class="nav-label">Account</div>
        <a href="#" class="nav-item">
            <i class="ti ti-settings"></i> Settings
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
    <!-- Topbar -->
    <div class="topbar">
        <div>
            <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
            <div class="topbar-sub">@yield('page-sub', 'Welcome back!')</div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <i class="ti ti-bell" style="font-size:20px; color:#9CA3AF;"></i>
            <div class="teacher-avatar">
                {{ strtoupper(substr(auth()->user()->teacher->firstname ?? 'T', 0, 1)) }}{{ strtoupper(substr(auth()->user()->teacher->lastname ?? 'C', 0, 1)) }}
            </div>
            <span style="font-size:13px; color:#6B7280;">
                {{ auth()->user()->teacher->firstname ?? 'Teacher' }}
            </span>
        </div>
    </div>

    <!-- Page content -->
    <div class="content-area">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>