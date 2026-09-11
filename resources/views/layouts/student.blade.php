<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Readify Kids') — Student</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #F0F6FF;
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
            padding: 14px 16px;
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

        /* Student profile */

        .stu-profile {
            padding: 14px 16px;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
        }

        .stu-big-av {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #DBEAFE;
            color: #1E40AF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            border: 3px solid #93C5FD;
        }

        .stu-full-name {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            text-align: center;
            word-break: break-word;
        }

        .stu-level {
            font-size: 11px;
            color: #6B7280;
            text-align: center;
        }

        .stu-points {
            font-size: 12px;
            font-weight: 700;
            color: #F59E0B;
            white-space: nowrap;
        }

        /* Navigation */

        .nav-section {
            padding: 10px 8px;
            flex: 1;
            overflow-y: auto;
            min-height: 0;
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
            font-weight: 700;
        }

        .nav-item i {
            font-size: 17px;
            flex-shrink: 0;
        }

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
           MOBILE SIDEBAR OVERLAY
        ========================================================= */

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
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
            display: flex;
            flex-direction: column;
            width: calc(100% - 220px);
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

        .topbar-greet {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }

        .topbar-sub {
            font-size: 11px;
            color: #6B7280;
            margin-top: 1px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .topbar-bell {
            font-size: 20px;
            color: #9CA3AF;
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
           CARDS
        ========================================================= */

        .dash-card {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 14px;
            padding: 16px;
            max-width: 100%;
        }

        .dash-card-title {
            font-size: 13px;
            font-weight: 700;
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
           STAT CARDS
        ========================================================= */

        .stat-card {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            height: 100%;
        }

        .stat-emoji {
            font-size: 24px;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
        }

        .stat-label {
            font-size: 11px;
            color: #6B7280;
            margin-top: 2px;
        }

        /* =========================================================
           HERO
        ========================================================= */

        .hero-banner {
            background: linear-gradient(135deg, #185FA5, #2563EB);
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 16px;
        }

        .hero-title {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }

        .hero-sub {
            font-size: 12px;
            color: rgba(255,255,255,0.85);
            margin-bottom: 10px;
        }

        .hero-btn {
            font-size: 12px;
            padding: 7px 18px;
            border-radius: 8px;
            border: none;
            background: #fff;
            color: #185FA5;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .hero-emoji {
            font-size: 52px;
            flex-shrink: 0;
        }

        /* =========================================================
           XP BAR
        ========================================================= */

        .xp-row {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            min-width: 0;
        }

        .xp-label {
            font-size: 11px;
            color: #6B7280;
            white-space: nowrap;
        }

        .xp-bar-bg {
            flex: 1;
            background: #E5E7EB;
            border-radius: 6px;
            height: 10px;
            min-width: 30px;
            overflow: hidden;
        }

        .xp-bar-fill {
            height: 10px;
            border-radius: 6px;
            background: linear-gradient(90deg, #185FA5, #60A5FA);
        }

        .xp-val {
            font-size: 11px;
            color: #6B7280;
            white-space: nowrap;
        }

        .level-pill {
            background: #185FA5;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .level-pill-gray {
            background: #E5E7EB;
            color: #9CA3AF;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }

        /* =========================================================
           ACTIVITY ITEMS
        ========================================================= */

        .act-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 10px;
            border: 1px solid #E5E7EB;
            background: #F9FAFB;
            margin-bottom: 8px;
            text-decoration: none;
            color: inherit;
            transition: border-color 0.15s, background 0.15s;
            min-width: 0;
        }

        .act-item:hover {
            border-color: #185FA5;
            background: #EFF6FF;
        }

        .act-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .act-content {
            min-width: 0;
            flex: 1;
        }

        .act-title {
            font-size: 12px;
            font-weight: 600;
            color: #111827;
            overflow-wrap: anywhere;
        }

        .act-sub {
            font-size: 10px;
            color: #9CA3AF;
            margin-top: 2px;
        }

        .act-badge {
            font-size: 10px;
            padding: 2px 9px;
            border-radius: 20px;
            font-weight: 600;
            white-space: nowrap;
            margin-left: auto;
            flex-shrink: 0;
        }

        .b-new {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .b-progress {
            background: #FEF3C7;
            color: #92400E;
        }

        .b-done {
            background: #DCFCE7;
            color: #166534;
        }

        /* =========================================================
           BADGES
        ========================================================= */

        .badge-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .badge-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            min-width: 0;
        }

        .badge-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .badge-name {
            font-size: 10px;
            color: #6B7280;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .badge-locked {
            opacity: 0.35;
        }

        /* =========================================================
           LEADERBOARD
        ========================================================= */

        .lb-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 8px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 4px;
            min-width: 0;
        }

        .lb-item.me {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
        }

        .lb-rank {
            width: 22px;
            text-align: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .lb-av {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .lb-name {
            flex: 1;
            font-weight: 600;
            color: #111827;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .lb-pts {
            font-size: 11px;
            font-weight: 700;
            color: #F59E0B;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* =========================================================
           TABLE RESPONSIVENESS
        ========================================================= */

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* =========================================================
           TABLET
        ========================================================= */

        @media (max-width: 991.98px) {

            .sidebar {
                transform: translateX(-100%);
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
            }

            .sidebar.show {
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

            .topbar-greet {
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

            .topbar-right {
                gap: 7px;
            }

            .topbar-bell {
                font-size: 18px;
            }

            .topbar-right .stu-points {
                display: none;
            }

            .level-pill {
                font-size: 9px;
                padding: 3px 7px;
            }

            .content-area {
                padding: 12px;
            }

            .dash-card {
                border-radius: 12px;
                padding: 13px;
            }

            .hero-banner {
                padding: 16px;
                border-radius: 12px;
            }

            .hero-title {
                font-size: 14px;
            }

            .hero-sub {
                font-size: 10px;
            }

            .hero-btn {
                font-size: 11px;
                padding: 6px 13px;
            }

            .hero-emoji {
                font-size: 38px;
            }

            /* XP */

            .xp-row {
                padding: 9px 10px;
                gap: 7px;
            }

            .xp-label {
                font-size: 10px;
            }

            .xp-val {
                font-size: 9px;
            }

            .level-pill,
            .level-pill-gray {
                font-size: 9px;
                padding: 3px 7px;
            }

            /* Stats */

            .stat-card {
                padding: 12px 8px;
            }

            .stat-emoji {
                font-size: 21px;
            }

            .stat-value {
                font-size: 18px;
            }

            .stat-label {
                font-size: 9px;
            }

            /* Activity */

            .act-item {
                padding: 8px 9px;
                gap: 8px;
            }

            .act-icon {
                width: 31px;
                height: 31px;
                font-size: 16px;
            }

            .act-title {
                font-size: 11px;
            }

            .act-sub {
                font-size: 9px;
            }

            .act-badge {
                font-size: 8px;
                padding: 2px 6px;
            }

            /* Badges */

            .badge-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 6px;
            }

            .badge-icon {
                width: 34px;
                height: 34px;
                font-size: 17px;
            }

            .badge-name {
                font-size: 8px;
            }

            /* Leaderboard */

            .lb-item {
                padding: 6px;
                gap: 6px;
            }

            .lb-rank {
                width: 19px;
                font-size: 12px;
            }

            .lb-av {
                width: 24px;
                height: 24px;
                font-size: 9px;
            }

            .lb-name {
                font-size: 11px;
            }

            .lb-pts {
                font-size: 10px;
            }
        }

        /* =========================================================
           VERY SMALL PHONES
        ========================================================= */

        @media (max-width: 400px) {

            .topbar-right .level-pill {
                display: none;
            }

            .content-area {
                padding: 10px;
            }

            .hero-banner {
                padding: 13px;
            }

            .hero-emoji {
                font-size: 32px;
            }

            .hero-title {
                font-size: 13px;
            }

            .hero-sub {
                font-size: 9px;
            }

            .xp-label {
                display: none;
            }

            .badge-grid {
                gap: 4px;
            }

            .badge-icon {
                width: 31px;
                height: 31px;
                font-size: 15px;
            }
        }
    </style>
</head>

<body>

@php
    $student = auth()->user()->student;
@endphp

<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- =========================================================
     SIDEBAR
========================================================= -->

<div class="sidebar" id="studentSidebar">

    <div class="sidebar-logo">
        <div class="logo-icon">
            <i class="ti ti-book-2"></i>
        </div>

        <div>
            <div class="logo-name">Readify Kids</div>
            <div class="logo-sub">Student Portal</div>
        </div>
    </div>

    <!-- Student Profile -->

    <div class="stu-profile">

        <div class="stu-big-av">
            {{ strtoupper(substr($student->firstname ?? 'S', 0, 1)) }}{{ strtoupper(substr($student->lastname ?? 'T', 0, 1)) }}
        </div>

        <div class="stu-full-name">
            {{ $student->firstname }} {{ $student->lastname }}
        </div>

        <div class="stu-level">
            Level {{ $student->current_level }} · {{ $student->section }}
        </div>

        <div class="stu-points">
            ⭐ {{ number_format($student->total_points) }} pts
        </div>

    </div>

    <!-- Navigation -->

    <div class="nav-section">

        <a href="{{ route('student.dashboard') }}"
           class="nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">

            <i class="ti ti-layout-dashboard"></i>
            <span>Dashboard</span>

        </a>

        <a href="{{ route('student.game.index') }}"
           class="nav-item {{ request()->routeIs('student.game.*') ? 'active' : '' }}"
           style="{{ request()->routeIs('student.game.*') ? 'background:#F5F3FF;color:#7C3AED;' : '' }}">

            <i class="ti ti-sword"
               style="{{ request()->routeIs('student.game.*') ? 'color:#7C3AED;' : '' }}"></i>

            <span>Battle Arena</span>

        </a>

        <a href="{{ route('student.activities.index') }}"
           class="nav-item {{ request()->routeIs('student.activities.*') ? 'active' : '' }}">

            <i class="ti ti-book"></i>
            <span>My Activities</span>

        </a>

        <a href="{{ route('student.readaloud.index') }}"
           class="nav-item {{ request()->routeIs('student.readaloud.*') ? 'active' : '' }}">

            <i class="ti ti-microphone"></i>
            <span>Read Aloud</span>

        </a>

        <a href="{{ route('student.leaderboard') }}"
           class="nav-item {{ request()->routeIs('student.leaderboard') ? 'active' : '' }}">

            <i class="ti ti-award"></i>
            <span>Leaderboard</span>

        </a>

        <a href="{{ route('student.progress') }}"
           class="nav-item {{ request()->routeIs('student.progress') ? 'active' : '' }}">

            <i class="ti ti-chart-bar"></i>
            <span>My Progress</span>

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
     MAIN
========================================================= -->

<div class="main-content">

    <!-- TOPBAR -->

    <div class="topbar">

        <div class="topbar-left">

            <!-- Mobile Menu Button -->

            <button
                type="button"
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                aria-label="Open navigation menu"
                aria-controls="studentSidebar"
                aria-expanded="false">

                <i class="ti ti-menu-2"></i>

            </button>

            <div style="min-width:0;">

                <div class="topbar-greet">
                    @yield('page-greet', 'Welcome!')
                </div>

                <div class="topbar-sub">
                    @yield('page-sub', '')
                </div>

            </div>

        </div>

        <!-- TOPBAR RIGHT -->

        <div class="topbar-right">

            <i class="ti ti-bell topbar-bell"></i>

            <span class="level-pill">
                Level {{ $student->current_level }}
            </span>

            <span class="stu-points">
                ⭐ {{ number_format($student->total_points) }} pts
            </span>

        </div>

    </div>

    <!-- CONTENT -->

    <div class="content-area">

        @yield('content')

    </div>

</div>

<!-- Bootstrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Mobile Sidebar Script -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('studentSidebar');
    const menuBtn = document.getElementById('mobileMenuBtn');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !menuBtn || !overlay) {
        return;
    }

    function openSidebar() {
        sidebar.classList.add('show');
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
        sidebar.classList.remove('show');
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

        if (sidebar.classList.contains('show')) {
            closeSidebar();
        } else {
            openSidebar();
        }

    }

    menuBtn.addEventListener('click', toggleSidebar);

    overlay.addEventListener('click', closeSidebar);

    /* Close sidebar when navigation link is clicked */

    document.querySelectorAll('.sidebar .nav-item').forEach(function (link) {

        link.addEventListener('click', function () {

            if (window.innerWidth <= 991) {
                closeSidebar();
            }

        });

    });

    /* Close sidebar when screen becomes desktop */

    window.addEventListener('resize', function () {

        if (window.innerWidth > 991) {
            closeSidebar();
        }

    });

    /* Close with ESC */

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