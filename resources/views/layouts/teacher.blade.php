<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Readify Kids') — Teacher Panel</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">

    <style>
        /* =========================================================
           GLOBAL
        ========================================================= */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #F3F4F6;
            min-height: 100%;
        }

        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            width: 220px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #fff;
            border-right: 1px solid #E5E7EB;
            display: flex;
            flex-direction: column;
            z-index: 1050;
            transition: transform 0.25s ease;
        }

        .sidebar-logo {
            padding: 16px;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: #185FA5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .logo-icon i {
            color: #fff;
            font-size: 18px;
        }

        .logo-name {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .logo-sub {
            font-size: 10px;
            color: #9CA3AF;
        }

        /* =========================================================
           NAVIGATION
        ========================================================= */

        .nav-section {
            padding: 12px 10px;
            flex: 1;
            overflow-y: auto;
            min-height: 0;
        }

        .nav-label {
            font-size: 10px;
            color: #9CA3AF;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            padding: 0 8px;
            margin: 12px 0 5px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px;
            color: #6B7280;
            text-decoration: none;
            margin-bottom: 2px;
            transition: background 0.15s, color 0.15s;
        }

        .nav-item:hover {
            background: #F3F4F6;
            color: #111827;
        }

        .nav-item.active {
            background: #EFF6FF;
            color: #185FA5;
            font-weight: 600;
        }

        .nav-item i {
            font-size: 17px;
            flex-shrink: 0;
        }

        /* =========================================================
           SIDEBAR FOOTER
        ========================================================= */

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid #E5E7EB;
            flex-shrink: 0;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #EF4444;
            text-decoration: none;
        }

        /* =========================================================
           SIDEBAR OVERLAY
        ========================================================= */

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1040;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /* =========================================================
           MAIN CONTENT
        ========================================================= */

        .main-content {
            margin-left: 220px;
            min-height: 100vh;
            width: calc(100% - 220px);
            display: flex;
            flex-direction: column;
        }

        /* =========================================================
           TOPBAR
        ========================================================= */

        .topbar {
            background: #fff;
            border-bottom: 1px solid #E5E7EB;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 99;
            min-height: 64px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .topbar-title {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .topbar-sub {
            font-size: 11px;
            color: #6B7280;
            margin-top: 1px;
        }

        .teacher-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #EFF6FF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #185FA5;
        }

        /* Mobile menu button */

        .mobile-menu-btn {
            display: none;
            width: 38px;
            height: 38px;
            border: 1px solid #E5E7EB;
            background: #fff;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #374151;
            flex-shrink: 0;
        }

        .mobile-menu-btn i {
            font-size: 22px;
        }

        /* =========================================================
           CONTENT
        ========================================================= */

        .content-area {
            padding: 20px 24px;
            flex: 1;
            width: 100%;
            overflow-x: hidden;
        }

        /* =========================================================
           METRIC CARDS
        ========================================================= */

        .metric-card {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 16px;
            height: 100%;
        }

        .metric-label {
            font-size: 11px;
            color: #6B7280;
            margin-bottom: 4px;
        }

        .metric-value {
            font-size: 26px;
            font-weight: 700;
            color: #111827;
        }

        .metric-sub {
            font-size: 11px;
            color: #9CA3AF;
            margin-top: 2px;
        }

        .metric-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        /* =========================================================
           CARDS
        ========================================================= */

        .dash-card {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 16px;
            max-width: 100%;
        }

        .dash-card-title {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .dash-card-title a {
            font-size: 11px;
            color: #185FA5;
            font-weight: 400;
            text-decoration: none;
            white-space: nowrap;
        }

        /* =========================================================
           TABLE
        ========================================================= */

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }

        .dash-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .dash-table th {
            text-align: left;
            color: #9CA3AF;
            font-weight: 500;
            padding: 8px 10px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 11px;
            white-space: nowrap;
        }

        .dash-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #F3F4F6;
            color: #374151;
        }

        .dash-table tr:last-child td {
            border-bottom: none;
        }

        .dash-table tr:hover td {
            background: #F9FAFB;
        }

        /* =========================================================
           STATUS BADGES
        ========================================================= */

        .status-badge {
            display: inline-block;
            font-size: 10px;
            padding: 2px 10px;
            border-radius: 20px;
            font-weight: 500;
            white-space: nowrap;
        }

        .badge-green {
            background: #DCFCE7;
            color: #166534;
        }

        .badge-amber {
            background: #FEF3C7;
            color: #92400E;
        }

        .badge-red {
            background: #FEE2E2;
            color: #991B1B;
        }

        .badge-blue {
            background: #DBEAFE;
            color: #1E40AF;
        }

        /* =========================================================
           PROGRESS BAR
        ========================================================= */

        .prog-bg {
            background: #E5E7EB;
            border-radius: 4px;
            height: 6px;
            width: 80px;
            display: inline-block;
            vertical-align: middle;
        }

        .prog-fill {
            height: 6px;
            border-radius: 4px;
            display: block;
        }

        /* =========================================================
           ACTIVITY FEED
        ========================================================= */

        .feed-item {
            display: flex;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #F3F4F6;
        }

        .feed-item:last-child {
            border-bottom: none;
        }

        .feed-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-top: 5px;
            flex-shrink: 0;
        }

        .feed-text {
            font-size: 12px;
            color: #374151;
            line-height: 1.4;
        }

        .feed-time {
            font-size: 10px;
            color: #9CA3AF;
            margin-top: 2px;
        }

        /* =========================================================
           BUTTON
        ========================================================= */

        .btn-listen {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 6px;
            border: 1px solid #D1D5DB;
            background: #fff;
            color: #374151;
            cursor: pointer;
            white-space: nowrap;
        }

        /* =========================================================
           TOOLBAR
        ========================================================= */

        .toolbar-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        /* =========================================================
           TABLET
        ========================================================= */

        @media (max-width: 991.98px) {

            .sidebar {
                transform: translateX(-100%);
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.12);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .topbar {
                padding: 10px 16px;
            }

            .content-area {
                padding: 16px;
            }

            /* Make Bootstrap columns stack more naturally */

            .row > [class*="col-lg-"] {
                margin-bottom: 12px;
            }

            /* Tables can scroll horizontally */

            .dash-card {
                overflow-x: hidden;
            }

            .dash-table {
                min-width: 600px;
            }

            /* Toolbar */

            .toolbar-wrap {
                flex-wrap: wrap;
            }
        }

        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 767.98px) {

            .topbar {
                min-height: 60px;
                padding: 9px 12px;
            }

            .topbar-left {
                gap: 8px;
                flex: 1;
                min-width: 0;
            }

            .mobile-menu-btn {
                width: 36px;
                height: 36px;
            }

            .topbar-title {
                font-size: 13px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .topbar-sub {
                font-size: 10px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .content-area {
                padding: 12px;
            }

            /* Cards */

            .dash-card {
                padding: 13px;
                border-radius: 10px;
            }

            /* Metrics */

            .metric-card {
                padding: 12px;
            }

            .metric-label {
                font-size: 10px;
            }

            .metric-value {
                font-size: 20px;
            }

            .metric-sub {
                font-size: 10px;
            }

            .metric-icon {
                width: 32px;
                height: 32px;
                font-size: 16px;
            }

            /* Card titles */

            .dash-card-title {
                font-size: 12px;
            }

            .dash-card-title a {
                font-size: 10px;
            }

            /* Tables */

            .dash-table {
                min-width: 600px;
                font-size: 11px;
            }

            .dash-table th {
                font-size: 10px;
                padding: 7px 8px;
            }

            .dash-table td {
                font-size: 11px;
                padding: 8px;
            }

            /* Progress */

            .prog-bg {
                width: 60px;
            }

            /* Status */

            .status-badge {
                font-size: 9px;
                padding: 2px 7px;
            }

            /* Toolbar */

            .toolbar-wrap {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .toolbar-wrap .d-flex {
                flex-wrap: wrap;
            }

            /* Forms */

            .form-control,
            .form-select {
                font-size: 16px !important;
            }

            /* Buttons */

            .btn {
                max-width: 100%;
            }

            /* Feed */

            .feed-text {
                font-size: 11px;
            }

            .feed-time {
                font-size: 9px;
            }

            /* Prevent long text from breaking layout */

            .content-area h1,
            .content-area h2,
            .content-area h3,
            .content-area h4,
            .content-area h5,
            .content-area h6 {
                overflow-wrap: anywhere;
            }
        }

        /* =========================================================
           VERY SMALL PHONES
        ========================================================= */

        @media (max-width: 400px) {

            .content-area {
                padding: 10px;
            }

            .topbar {
                padding: 8px 10px;
            }

            .mobile-menu-btn {
                width: 34px;
                height: 34px;
            }

            .topbar-title {
                font-size: 12px;
            }

            .topbar-sub {
                font-size: 9px;
            }

            .dash-card {
                padding: 11px;
            }

            .metric-card {
                padding: 10px;
            }

            .metric-value {
                font-size: 18px;
            }
        }

        /* =========================================================
           DESKTOP
        ========================================================= */

        @media (min-width: 992px) {

            .mobile-menu-btn {
                display: none !important;
            }

            .sidebar-overlay {
                display: none !important;
            }
        }
    </style>
</head>

<body>

<!-- =========================================================
     SIDEBAR OVERLAY
========================================================= -->

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- =========================================================
     SIDEBAR
========================================================= -->

<div class="sidebar" id="teacherSidebar">

    <div class="sidebar-logo">

        <div class="logo-icon">
            <i class="ti ti-book-2"></i>
        </div>

        <div>
            <div class="logo-name">Readify Kids</div>
            <div class="logo-sub">Teacher Panel</div>
        </div>

    </div>

    <!-- Navigation -->

    <div class="nav-section">

        <div class="nav-label">Main</div>

        <a href="{{ route('teacher.dashboard') }}"
           class="nav-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">

            <i class="ti ti-layout-dashboard"></i>
            <span>Dashboard</span>

        </a>

        <a href="{{ route('teacher.students.index') }}"
           class="nav-item {{ request()->routeIs('teacher.students.*') ? 'active' : '' }}">

            <i class="ti ti-users"></i>
            <span>Students</span>

        </a>

        <a href="{{ route('teacher.activities.index') }}"
           class="nav-item {{ request()->routeIs('teacher.activities.*') ? 'active' : '' }}">

            <i class="ti ti-book"></i>
            <span>All Activities</span>

        </a>

        <a href="{{ route('teacher.activities.create.readaloud') }}"
           class="nav-item"
           style="padding-left:34px;font-size:12px;">

            <i class="ti ti-microphone" style="font-size:15px;"></i>
            <span>+ Read Aloud</span>

        </a>

        <a href="{{ route('teacher.activities.create.battle') }}"
           class="nav-item"
           style="padding-left:34px;font-size:12px;">

            <i class="ti ti-sword" style="font-size:15px;"></i>
            <span>+ Battle</span>

        </a>

        <a href="{{ route('teacher.evaluations.index') }}"
           class="nav-item {{ request()->routeIs('teacher.evaluations.*') ? 'active' : '' }}">

            <i class="ti ti-microphone"></i>
            <span>Evaluations</span>

        </a>

        <a href="{{ route('teacher.logs') }}"
   class="nav-item {{ request()->routeIs('teacher.logs') ? 'active' : '' }}">
    <i class="ti ti-clipboard-list"></i> My Activity Logs
</a>

        <div class="nav-label">Reports</div>

        <a href="{{ route('teacher.progress') }}"
           class="nav-item {{ request()->routeIs('teacher.progress') ? 'active' : '' }}">

            <i class="ti ti-chart-bar"></i>
            <span>Progress</span>

        </a>

        <a href="{{ route('teacher.leaderboard') }}"
           class="nav-item {{ request()->routeIs('teacher.leaderboard') ? 'active' : '' }}">

            <i class="ti ti-trophy"></i>
            <span>Leaderboard</span>

        </a>

        <div class="nav-label">Account</div>

        <a href="#" class="nav-item">

            <i class="ti ti-settings"></i>
            <span>Settings</span>

        </a>

    </div>

    <!-- Footer -->

    <div class="sidebar-footer">

        <a href="{{ route('logout') }}" class="logout-btn">

            <i class="ti ti-logout"></i>
            <span>Log out</span>

        </a>

    </div>

</div>

<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="main-content">

    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <div class="topbar">

        <div class="topbar-left">

            <!-- Mobile Menu -->

            <button
                type="button"
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                aria-label="Open navigation menu"
                aria-controls="teacherSidebar"
                aria-expanded="false">

                <i class="ti ti-menu-2"></i>

            </button>

            <div style="min-width:0;">

                <div class="topbar-title">
                    @yield('page-title', 'Dashboard')
                </div>

                <div class="topbar-sub">
                    @yield('page-sub', '')
                </div>

            </div>

        </div>

        <!-- Topbar right -->

        <div class="d-flex align-items-center gap-2">

            @yield('topbar-right')

        </div>

    </div>

    <!-- =====================================================
         PAGE CONTENT
    ====================================================== -->

    <div class="content-area">

        @yield('content')

    </div>

</div>

<!-- Bootstrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- =========================================================
     RESPONSIVE SIDEBAR SCRIPT
========================================================= -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('teacherSidebar');
    const menuBtn = document.getElementById('mobileMenuBtn');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !menuBtn || !overlay) {
        return;
    }

    function openSidebar() {

        sidebar.classList.add('open');
        overlay.classList.add('show');

        menuBtn.setAttribute('aria-expanded', 'true');

        const icon = menuBtn.querySelector('i');

        if (icon) {
            icon.classList.remove('ti-menu-2');
            icon.classList.add('ti-x');
        }

        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {

        sidebar.classList.remove('open');
        overlay.classList.remove('show');

        menuBtn.setAttribute('aria-expanded', 'false');

        const icon = menuBtn.querySelector('i');

        if (icon) {
            icon.classList.remove('ti-x');
            icon.classList.add('ti-menu-2');
        }

        document.body.style.overflow = '';
    }

    function toggleSidebar() {

        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }

    }

    /* Menu button */

    menuBtn.addEventListener('click', toggleSidebar);

    /* Click outside sidebar */

    overlay.addEventListener('click', closeSidebar);

    /* Close sidebar after navigation */

    document.querySelectorAll('.sidebar .nav-item').forEach(function (link) {

        link.addEventListener('click', function () {

            if (window.innerWidth <= 991) {
                closeSidebar();
            }

        });

    });

    /* Close sidebar when resizing to desktop */

    window.addEventListener('resize', function () {

        if (window.innerWidth > 991) {
            closeSidebar();
        }

    });

    /* ESC closes sidebar */

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            closeSidebar();
        }

    });

});
</script>

@stack('scripts')

</body>
</html>