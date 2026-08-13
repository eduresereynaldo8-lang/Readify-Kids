<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Readify Kids</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #DC2626;
            --admin-dark:    #991B1B;
            --sidebar-bg:    #1C0A0A;
            --sidebar-w:     220px;
        }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:#F5F5F5; display:flex; min-height:100vh; }

        /* ── Sidebar ─────────────────────────── */
        .sidebar {
            width: var(--sidebar-w); background: var(--sidebar-bg);
            display: flex; flex-direction: column;
            position: fixed; top:0; left:0; bottom:0; z-index:100;
            padding: 0 0 20px;
        }
        .sidebar-brand {
            padding: 18px 16px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 8px;
        }
        .sidebar-brand .logo {
            font-size:15px; font-weight:800; color:#fff;
            display:flex; align-items:center; gap:8px;
        }
        .sidebar-brand .admin-chip {
            font-size:9px; background:var(--admin-primary);
            color:#fff; padding:2px 7px; border-radius:10px;
            font-weight:700; letter-spacing:0.05em;
        }
        .nav-item {
            display:flex; align-items:center; gap:10px;
            padding:9px 16px; color:rgba(255,255,255,0.6);
            font-size:13px; font-weight:600; text-decoration:none;
            border-radius:8px; margin:2px 10px;
            transition:all 0.2s;
        }
        .nav-item:hover, .nav-item.active {
            background:rgba(220,38,38,0.2);
            color:#fff;
        }
        .nav-item.active { color:#FCA5A5; }
        .nav-item i { font-size:17px; flex-shrink:0; }
        .nav-section {
            font-size:9px; font-weight:700; color:rgba(255,255,255,0.25);
            text-transform:uppercase; letter-spacing:0.1em;
            padding:12px 26px 4px;
        }
        .sidebar-footer {
            margin-top:auto; padding:0 10px;
        }
        .logout-btn {
            display:flex; align-items:center; gap:10px;
            padding:9px 16px; color:#FCA5A5; font-size:13px;
            font-weight:600; text-decoration:none; border-radius:8px;
            transition:all 0.2s; width:100%;
            background:transparent; border:none; cursor:pointer;
        }
        .logout-btn:hover { background:rgba(220,38,38,0.2); }

        /* ── Main ────────────────────────────── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1; display:flex; flex-direction:column; min-height:100vh;
        }
        .topbar {
            background:#fff; border-bottom:1px solid #E5E7EB;
            padding:12px 24px; display:flex;
            align-items:center; justify-content:space-between;
            position:sticky; top:0; z-index:50;
        }
        .topbar-title {
            font-size:15px; font-weight:700; color:#111827;
        }
        .topbar-sub {
            font-size:11px; color:#9CA3AF; margin-top:1px;
        }
        .admin-badge {
            background:#FEE2E2; color:#991B1B;
            font-size:11px; font-weight:700;
            padding:4px 12px; border-radius:20px;
            display:flex; align-items:center; gap:6px;
        }
        .page-content { padding:24px; flex:1; }

        /* ── Dash cards ──────────────────────── */
        .dash-card {
            background:#fff; border-radius:12px;
            border:1px solid #E5E7EB; padding:16px;
        }
        .dash-card-title {
            font-size:13px; font-weight:700; color:#111827; margin-bottom:12px;
        }
        .metric-card {
            background:#fff; border-radius:12px;
            border:1px solid #E5E7EB; padding:14px 16px;
        }
        .metric-icon {
            width:40px; height:40px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:18px; flex-shrink:0;
        }
        .metric-label { font-size:11px; color:#9CA3AF; }
        .metric-value { font-size:22px; font-weight:800; color:#111827; }
        .metric-sub   { font-size:10px; color:#9CA3AF; }
        .dash-table   { width:100%; border-collapse:collapse; font-size:12px; }
        .dash-table th {
            text-align:left; padding:8px 10px;
            color:#9CA3AF; font-weight:600;
            border-bottom:1px solid #F3F4F6; white-space:nowrap;
        }
        .dash-table td {
            padding:9px 10px; border-bottom:1px solid #F9FAFB;
            vertical-align:middle;
        }
        .dash-table tr:last-child td { border-bottom:none; }
        .status-badge {
            font-size:10px; padding:2px 8px;
            border-radius:20px; font-weight:600;
        }
        .badge-red    { background:#FEE2E2; color:#991B1B; }
        .badge-green  { background:#DCFCE7; color:#166534; }
        .badge-blue   { background:#DBEAFE; color:#1E40AF; }
        .badge-amber  { background:#FEF3C7; color:#92400E; }
        .prog-bg { background:#E5E7EB; border-radius:4px; height:6px; }
        .prog-fill { height:6px; border-radius:4px; }
    </style>
</head>
<body>

{{-- ── Sidebar ──────────────────────────────────────────────── --}}
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="logo">
            📚 Readify Kids
            <span class="admin-chip">ADMIN</span>
        </div>
    </div>

    <div class="nav-section">Overview</div>
    <a href="{{ route('admin.dashboard') }}"
       class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="ti ti-layout-dashboard"></i> Dashboard
    </a>

    <div class="nav-section">Management</div>
    <a href="{{ route('admin.teachers') }}"
       class="nav-item {{ request()->routeIs('admin.teachers') ? 'active' : '' }}">
        <i class="ti ti-school"></i> Teachers
    </a>
    <a href="{{ route('admin.students') }}"
       class="nav-item {{ request()->routeIs('admin.students') ? 'active' : '' }}">
        <i class="ti ti-users"></i> Students
    </a>
    <a href="{{ route('admin.activities') }}"
       class="nav-item {{ request()->routeIs('admin.activities') ? 'active' : '' }}">
        <i class="ti ti-book"></i> Activities
    </a>
    <a href="{{ route('admin.evaluations') }}"
       class="nav-item {{ request()->routeIs('admin.evaluations') ? 'active' : '' }}">
        <i class="ti ti-book"></i> Evaluations
    </a>
     <a href="{{ route('admin.reports') }}"
       class="nav-item {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
        <i class="ti ti-book"></i> Reports
    </a>


    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout-btn" type="submit">
                <i class="ti ti-logout"></i> Logout
            </button>
        </form>
    </div>
</div>

{{-- ── Main ─────────────────────────────────────────────────── --}}
<div class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
            <div class="topbar-sub">@yield('page-sub', '')</div>
        </div>
        <div class="admin-badge">
            <i class="ti ti-shield"></i>
            Administrator — {{ auth()->user()->firstname }}
        </div>
    </div>

    <div class="page-content">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>