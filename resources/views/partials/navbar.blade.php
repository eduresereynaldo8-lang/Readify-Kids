<nav class="top-navbar">

    <h3>Dashboard</h3>

    <div class="user-area">

        <i class="bi bi-bell"></i>

        <div class="user">

            <i class="bi bi-person-circle"></i>

            <span>
                {{ auth()->user()->teacher->firstname ?? 'Teacher' }}
            </span>

        </div>

    </div>

</nav>