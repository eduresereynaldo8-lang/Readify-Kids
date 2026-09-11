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

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    html,
    body {
        width: 100%;
        min-height: 100%;
    }

    body {
    font-family: 'Segoe UI', sans-serif;
    background: #F5F5F5;

    min-height: 100vh;

    overflow-x: hidden;
}

    /* =========================================================
       SIDEBAR
       ========================================================= */

    .sidebar {
        width: var(--sidebar-w);
        background: var(--sidebar-bg);

        display: flex;
        flex-direction: column;

        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;

        z-index: 1050;

        padding: 0 0 20px;

        transition: transform 0.25s ease;
    }

    .sidebar-brand {
        padding: 18px 16px 14px;

        border-bottom: 1px solid rgba(255,255,255,0.08);

        margin-bottom: 8px;
    }

    .sidebar-brand .logo {
        font-size: 15px;
        font-weight: 800;
        color: #fff;

        display: flex;
        align-items: center;
        gap: 8px;

        white-space: nowrap;
    }

    .sidebar-brand .admin-chip {
        font-size: 9px;
        background: var(--admin-primary);
        color: #fff;

        padding: 2px 7px;
        border-radius: 10px;

        font-weight: 700;
        letter-spacing: 0.05em;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 10px;

        padding: 9px 16px;

        color: rgba(255,255,255,0.6);

        font-size: 13px;
        font-weight: 600;

        text-decoration: none;

        border-radius: 8px;

        margin: 2px 10px;

        transition: all 0.2s;
    }

    .nav-item:hover,
    .nav-item.active {
        background: rgba(220,38,38,0.2);
        color: #fff;
    }

    .nav-item.active {
        color: #FCA5A5;
    }

    .nav-item i {
        font-size: 17px;
        flex-shrink: 0;
    }

    .nav-section {
        font-size: 9px;
        font-weight: 700;

        color: rgba(255,255,255,0.25);

        text-transform: uppercase;
        letter-spacing: 0.1em;

        padding: 12px 26px 4px;
    }

    .sidebar-footer {
        margin-top: auto;
        padding: 0 10px;
    }

    .logout-btn {
        display: flex;
        align-items: center;
        gap: 10px;

        padding: 9px 16px;

        color: #FCA5A5;

        font-size: 13px;
        font-weight: 600;

        text-decoration: none;

        border-radius: 8px;

        transition: all 0.2s;

        width: 100%;

        background: transparent;
        border: none;

        cursor: pointer;
    }

    .logout-btn:hover {
        background: rgba(220,38,38,0.2);
    }


    /* =========================================================
       SIDEBAR OVERLAY
       ========================================================= */

    .sidebar-overlay {
        display: none;

        position: fixed;
        inset: 0;

        background: rgba(0,0,0,0.45);

        z-index: 1040;
    }

    .sidebar-overlay.show {
        display: block;
    }


    /* =========================================================
       MOBILE MENU BUTTON
       ========================================================= */

    .mobile-menu-btn {
        display: none;

        width: 38px;
        height: 38px;

        border: 1px solid #E5E7EB;
        border-radius: 9px;

        background: #fff;

        color: #374151;

        align-items: center;
        justify-content: center;

        font-size: 21px;

        cursor: pointer;

        flex-shrink: 0;
    }

    .mobile-menu-btn:hover {
        background: #F9FAFB;
    }


    /* =========================================================
   MAIN CONTENT
   ========================================================= */

.main {
    margin-left: var(--sidebar-w);

    display: flex;
    flex-direction: column;

    min-height: 100vh;

    width: auto;
    min-width: 0;
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

        gap: 15px;

        position: sticky;
        top: 0;

        z-index: 50;

        min-height: 64px;
    }

    .topbar-left {
        display: flex;
        align-items: center;

        gap: 10px;

        min-width: 0;
    }

    .topbar-text {
        min-width: 0;
    }

    .topbar-title {
        font-size: 15px;
        font-weight: 700;

        color: #111827;

        line-height: 1.2;
    }

    .topbar-sub {
        font-size: 11px;

        color: #9CA3AF;

        margin-top: 2px;

        line-height: 1.2;
    }

    .admin-badge {
        background: #FEE2E2;
        color: #991B1B;

        font-size: 11px;
        font-weight: 700;

        padding: 4px 12px;

        border-radius: 20px;

        display: flex;
        align-items: center;

        gap: 6px;

        white-space: nowrap;

        flex-shrink: 0;
    }


    /* =========================================================
       PAGE CONTENT
       ========================================================= */

    .page-content {
        padding: 24px;

        flex: 1;

        width: 100%;

        min-width: 0;
    }


    /* =========================================================
       DASHBOARD CARDS
       ========================================================= */

    .dash-card {
        background: #fff;

        border-radius: 12px;

        border: 1px solid #E5E7EB;

        padding: 16px;

        width: 100%;
    }

    .dash-card-title {
        font-size: 13px;

        font-weight: 700;

        color: #111827;

        margin-bottom: 12px;
    }

    .metric-card {
        background: #fff;

        border-radius: 12px;

        border: 1px solid #E5E7EB;

        padding: 14px 16px;

        width: 100%;
    }

    .metric-icon {
        width: 40px;
        height: 40px;

        border-radius: 10px;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 18px;

        flex-shrink: 0;
    }

    .metric-label {
        font-size: 11px;
        color: #9CA3AF;
    }

    .metric-value {
        font-size: 22px;

        font-weight: 800;

        color: #111827;
    }

    .metric-sub {
        font-size: 10px;
        color: #9CA3AF;
    }


    /* =========================================================
       TABLES
       ========================================================= */

    .dash-table {
        width: 100%;

        border-collapse: collapse;

        font-size: 12px;
    }

    .dash-table th {
        text-align: left;

        padding: 8px 10px;

        color: #9CA3AF;

        font-weight: 600;

        border-bottom: 1px solid #F3F4F6;

        white-space: nowrap;
    }

    .dash-table td {
        padding: 9px 10px;

        border-bottom: 1px solid #F9FAFB;

        vertical-align: middle;
    }

    .dash-table tr:last-child td {
        border-bottom: none;
    }

    /*
       Use this around tables:

       <div class="table-responsive">
           <table class="dash-table">
               ...
           </table>
       </div>
    */

    .table-responsive {
        width: 100%;

        overflow-x: auto;

        -webkit-overflow-scrolling: touch;
    }

    .table-responsive .dash-table {
        min-width: 600px;
    }


    /* =========================================================
       STATUS BADGES
       ========================================================= */

    .status-badge {
        font-size: 10px;

        padding: 2px 8px;

        border-radius: 20px;

        font-weight: 600;

        white-space: nowrap;
    }

    .badge-red {
        background: #FEE2E2;
        color: #991B1B;
    }

    .badge-green {
        background: #DCFCE7;
        color: #166534;
    }

    .badge-blue {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .badge-amber {
        background: #FEF3C7;
        color: #92400E;
    }

    .prog-bg {
        background: #E5E7EB;

        border-radius: 4px;

        height: 6px;
    }

    .prog-fill {
        height: 6px;

        border-radius: 4px;
    }


    /* =========================================================
       FORMS
       ========================================================= */

    input,
    select,
    textarea,
    button {
        max-width: 100%;
    }

    input,
    select,
    textarea {
        font-size: 14px;
    }


    /* =========================================================
       TABLET
       ========================================================= */

   @media (max-width: 991.98px) {

    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.25s ease;

        box-shadow: 5px 0 20px rgba(0,0,0,0.2);
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .sidebar-overlay {
        display: none;

        position: fixed;
        inset: 0;

        background: rgba(0,0,0,0.45);

        z-index: 1040;
    }

    .sidebar-overlay.show {
        display: block;
    }

    .main {
        margin-left: 0;

        width: 100%;
        max-width: 100%;

        min-width: 0;
    }

    .mobile-menu-btn {
        display: flex;
    }

    .topbar {
        width: 100%;
    }

    .page-content {
        width: 100%;
        max-width: 100%;
    }
}

@media (min-width: 992px) {

    .mobile-menu-btn {
        display: none;
    }

    .sidebar-overlay {
        display: none !important;
    }

    .sidebar {
        transform: translateX(0);
    }

    .main {
        margin-left: 220px;

        width: auto;
        max-width: none;
    }
}
    /* =========================================================
       PHONE
       ========================================================= */

    @media (max-width: 767.98px) {

        body {
            min-height: 100dvh;
        }

        /* ---------------- TOPBAR ---------------- */

        .topbar {
            padding: 9px 11px;

            min-height: 56px;

            gap: 8px;
        }

        .topbar-left {
            gap: 8px;

            flex: 1;

            min-width: 0;
        }

        .mobile-menu-btn {
            width: 36px;
            height: 36px;

            font-size: 20px;
        }

        .topbar-text {
            min-width: 0;
        }

        .topbar-title {
            font-size: 13px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .topbar-sub {
            font-size: 9px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .admin-badge {
            padding: 4px 7px;

            font-size: 9px;

            gap: 3px;

            max-width: 125px;

            overflow: hidden;

            text-overflow: ellipsis;
        }

        .admin-badge i {
            font-size: 13px;
        }


        /* ---------------- PAGE ---------------- */

        .page-content {
            padding: 12px;
        }


        /* ---------------- CARDS ---------------- */

        .dash-card {
            padding: 12px;

            border-radius: 10px;
        }

        .dash-card-title {
            font-size: 12px;

            margin-bottom: 9px;
        }

        .metric-card {
            padding: 11px 12px;

            border-radius: 10px;
        }

        .metric-icon {
            width: 34px;
            height: 34px;

            font-size: 16px;

            border-radius: 8px;
        }

        .metric-label {
            font-size: 9px;
        }

        .metric-value {
            font-size: 19px;
        }

        .metric-sub {
            font-size: 9px;
        }


        /* ---------------- TABLE ---------------- */

        .table-responsive {
            margin-left: -1px;
            margin-right: -1px;
        }

        .dash-table {
            font-size: 11px;
        }

        .dash-table th {
            padding: 7px 8px;
        }

        .dash-table td {
            padding: 8px;
        }


        /* ---------------- BADGES ---------------- */

        .status-badge {
            font-size: 9px;

            padding: 2px 6px;
        }


        /* ---------------- BUTTONS ---------------- */

        .btn {
            font-size: 12px;
        }


        /* ---------------- FORMS ---------------- */

        input,
        select,
        textarea {
            font-size: 16px;
        }


        /* ---------------- BOOTSTRAP ROWS ---------------- */

        .row {
            --bs-gutter-x: 0.75rem;
            --bs-gutter-y: 0.75rem;
        }
    }


    /* =========================================================
       VERY SMALL PHONES
       320px - 400px
       ========================================================= */

    @media (max-width: 400px) {

        .topbar {
            padding: 8px;
        }

        .mobile-menu-btn {
            width: 34px;
            height: 34px;

            font-size: 18px;
        }

        .topbar-title {
            font-size: 12px;
        }

        .topbar-sub {
            display: none;
        }

        .admin-badge {
            max-width: 105px;

            font-size: 8px;

            padding: 4px 6px;
        }

        .page-content {
            padding: 9px;
        }

        .dash-card {
            padding: 10px;
        }

        .metric-card {
            padding: 10px;
        }

        .metric-value {
            font-size: 18px;
        }

        .metric-icon {
            width: 32px;
            height: 32px;

            font-size: 14px;
        }
    }


    /* =========================================================
       LANDSCAPE PHONE
       ========================================================= */

    @media (max-width: 900px)
           and (orientation: landscape) {

        .topbar {
            min-height: 50px;

            padding: 7px 12px;
        }

        .mobile-menu-btn {
            width: 34px;
            height: 34px;
        }

        .page-content {
            padding: 12px;
        }

        .dash-card {
            padding: 12px;
        }

        .metric-card {
            padding: 10px 12px;
        }

        .metric-icon {
            width: 34px;
            height: 34px;
        }

        .metric-value {
            font-size: 19px;
        }
    }
</style>
</head>
<body>

    {{-- SIDEBAR --}}
    <div class="sidebar" id="adminSidebar">

        <div class="sidebar-brand">
            <div class="logo">
                📚 Readify Kids
                <span class="admin-chip">ADMIN</span>
            </div>
        </div>

        <div class="nav-section">Overview</div>

        <a href="{{ route('admin.dashboard') }}"
           class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="ti ti-layout-dashboard"></i>
            Dashboard
        </a>

        <div class="nav-section">Management</div>

        <a href="{{ route('admin.teachers') }}"
           class="nav-item {{ request()->routeIs('admin.teachers') ? 'active' : '' }}">
            <i class="ti ti-school"></i>
            Teachers
        </a>

        <a href="{{ route('admin.students') }}"
           class="nav-item {{ request()->routeIs('admin.students') ? 'active' : '' }}">
            <i class="ti ti-users"></i>
            Students
        </a>

        <a href="{{ route('admin.activities') }}"
           class="nav-item {{ request()->routeIs('admin.activities') ? 'active' : '' }}">
            <i class="ti ti-book"></i>
            Activities
        </a>

        <a href="{{ route('admin.evaluations') }}"
           class="nav-item {{ request()->routeIs('admin.evaluations') ? 'active' : '' }}">
            <i class="ti ti-book"></i>
            Evaluations
        </a>

        <a href="{{ route('admin.reports') }}"
           class="nav-item {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
            <i class="ti ti-book"></i>
            Reports
        </a>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button class="logout-btn" type="submit">
                    <i class="ti ti-logout"></i>
                    Logout
                </button>
            </form>
        </div>

    </div>


    {{-- MOBILE OVERLAY --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>


    {{-- MAIN --}}
    <div class="main">

        <div class="topbar">

            <div class="topbar-left">

                <button
                    type="button"
                    class="mobile-menu-btn"
                    id="mobileMenuBtn">

                    <i class="ti ti-menu-2"></i>

                </button>

                <div class="topbar-text">

                    <div class="topbar-title">
                        @yield('page-title', 'Dashboard')
                    </div>

                    <div class="topbar-sub">
                        @yield('page-sub', '')
                    </div>

                </div>

            </div>


            <div class="admin-badge">

                <i class="ti ti-shield"></i>

                <span>
                    Administrator — {{ auth()->user()->firstname }}
                </span>

            </div>

        </div>


        <div class="page-content">

            @yield('content')

        </div>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script>
    document.addEventListener('DOMContentLoaded', function () {

        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuBtn = document.getElementById('mobileMenuBtn');

        if (!sidebar || !overlay || !menuBtn) {
            return;
        }


        function openSidebar() {

            sidebar.classList.add('open');
            overlay.classList.add('show');

            document.body.classList.add('sidebar-open');
        }


        function closeSidebar() {

            sidebar.classList.remove('open');
            overlay.classList.remove('show');

            document.body.classList.remove('sidebar-open');
        }


        menuBtn.addEventListener('click', function () {

            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }

        });


        overlay.addEventListener('click', function () {
            closeSidebar();
        });


        sidebar.querySelectorAll('.nav-item').forEach(function (link) {

            link.addEventListener('click', function () {

                if (window.innerWidth <= 991) {
                    closeSidebar();
                }

            });

        });


        document.addEventListener('keydown', function (event) {

            if (event.key === 'Escape') {
                closeSidebar();
            }

        });


        window.addEventListener('resize', function () {

            if (window.innerWidth > 991) {
                closeSidebar();
            }

        });

    });
    </script>

    @stack('scripts')

</body>
</html>