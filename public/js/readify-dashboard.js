(() => {
    const sidebar = document.getElementById('dashboardSidebar');
    const menu = document.getElementById('dashboardMenu');
    const overlay = document.getElementById('dashboardOverlay');
    const close = sidebar?.querySelector('.rk-sidebar-close');
    const main = document.querySelector('.rk-main');
    const mobile = window.matchMedia('(max-width: 991.98px)');
    if (!sidebar || !menu || !overlay) return;
    function setOpen(open, restoreFocus = true) {
        const isOpen = open && mobile.matches;
        sidebar.classList.toggle('open', isOpen);
        document.body.classList.toggle('sidebar-open', isOpen);
        overlay.hidden = !isOpen;
        menu.setAttribute('aria-expanded', String(isOpen));
        sidebar.inert = mobile.matches && !isOpen;
        main.inert = isOpen;
        if (isOpen) close.focus();
        else if (restoreFocus && mobile.matches) menu.focus();
    }
    menu.addEventListener('click', () => setOpen(!sidebar.classList.contains('open')));
    close.addEventListener('click', () => setOpen(false));
    overlay.addEventListener('click', () => setOpen(false));
    sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
        if (mobile.matches) setOpen(false, false);
    }));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            setOpen(false);
            document.querySelectorAll('.rk-profile-menu[open]').forEach(details => details.open = false);
        }
        if (event.key === 'Tab' && sidebar.classList.contains('open')) {
            const focusable = [...sidebar.querySelectorAll('a, button')].filter(el => el.getClientRects().length);
            const first = focusable[0], last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    document.addEventListener('click', event => {
        document.querySelectorAll('.rk-profile-menu[open]').forEach(details => {
            if (!details.contains(event.target)) details.open = false;
        });
    });
    document.querySelectorAll('a[href="#all-badges"]').forEach(link => link.addEventListener('click', () => {
        const badges = document.getElementById('all-badges');
        if (badges) badges.open = true;
    }));
    mobile.addEventListener('change', () => setOpen(false, false));
    setOpen(false, false);
})();
