(function () {
    const themeKey = 'bumdes_theme';
    const sidebarKey = 'bumdes_sidebar_collapsed';
    const root = document.documentElement;

    function getBody() {
        return document.body;
    }

    function isDesktop() {
        return window.innerWidth >= 992;
    }

    function getPreferredTheme() {
        try {
            const saved = localStorage.getItem(themeKey);
            if (saved === 'light' || saved === 'dark') {
                return saved;
            }
        } catch (e) {}
        return 'light';
    }

    function getSidebarCollapsed() {
        try {
            return localStorage.getItem(sidebarKey) === '1';
        } catch (e) {
            return false;
        }
    }

    function setSidebarCollapsed(value) {
        try {
            localStorage.setItem(sidebarKey, value ? '1' : '0');
        } catch (e) {}
    }

    function resetSidebarStateOnce() {
        try {
            if (localStorage.getItem('bumdes_sidebar_force_reset_done') === '1') {
                return;
            }
            localStorage.removeItem(sidebarKey);
            localStorage.removeItem('bumdes_sidebar_collapsed_v2');
            localStorage.setItem('bumdes_sidebar_force_reset_done', '1');
        } catch (e) {}
    }

    function setThemeUi(theme) {
        const icon = document.getElementById('themeToggleIcon');
        const text = document.getElementById('themeToggleText');
        const toggle = document.getElementById('themeToggle');
        if (!icon || !text) {
            return;
        }

        if (theme === 'light') {
            icon.textContent = '☀️';
            text.textContent = 'Light';
        } else {
            icon.textContent = '🌙';
            text.textContent = 'Dark';
        }

        if (toggle) {
            toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        }
    }

    function applyTheme(theme) {
        const body = getBody();
        root.setAttribute('data-theme', theme);
        if (body) {
            body.classList.remove('theme-light', 'theme-dark');
            body.classList.add(theme === 'light' ? 'theme-light' : 'theme-dark');
        }
        setThemeUi(theme);
    }

    function persistTheme(theme) {
        try {
            localStorage.setItem(themeKey, theme);
        } catch (e) {}
    }

    function toggleTheme() {
        const current = root.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        persistTheme(next);
        applyTheme(next);
    }

    function updateSidebarToggleUi() {
        const body = getBody();
        const toggle = document.getElementById('sidebarToggle');
        if (!body || !toggle) {
            return;
        }

        const expanded = isDesktop() ? !body.classList.contains('sidebar-collapsed') : body.classList.contains('sidebar-open');
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        toggle.classList.toggle('is-active', expanded);
    }

    function syncSidebarState() {
        const body = getBody();
        const sidebar = document.getElementById('appSidebar');
        if (!body || !sidebar) {
            return;
        }

        if (isDesktop()) {
            body.classList.remove('sidebar-open');
            body.style.overflow = 'hidden';
            body.classList.toggle('sidebar-collapsed', getSidebarCollapsed());
            sidebar.setAttribute('aria-hidden', body.classList.contains('sidebar-collapsed') ? 'true' : 'false');
        } else {
            body.classList.remove('sidebar-collapsed');
            body.style.overflow = body.classList.contains('sidebar-open') ? 'hidden' : '';
            sidebar.setAttribute('aria-hidden', body.classList.contains('sidebar-open') ? 'false' : 'true');
        }

        updateSidebarToggleUi();
    }

    function toggleSidebar() {
        const body = getBody();
        if (!body) {
            return;
        }

        if (isDesktop()) {
            const next = !body.classList.contains('sidebar-collapsed');
            body.classList.toggle('sidebar-collapsed', next);
            setSidebarCollapsed(next);
            body.classList.remove('sidebar-open');
            body.style.overflow = 'hidden';
        } else {
            body.classList.toggle('sidebar-open');
            body.style.overflow = body.classList.contains('sidebar-open') ? 'hidden' : '';
        }

        updateSidebarToggleUi();
    }

    function closeSidebarMobile() {
        const body = getBody();
        if (!body || isDesktop()) {
            return;
        }
        body.classList.remove('sidebar-open');
        body.style.overflow = '';
        updateSidebarToggleUi();
    }

    document.addEventListener('DOMContentLoaded', function () {
        resetSidebarStateOnce();
        applyTheme(getPreferredTheme());

        const themeToggle = document.getElementById('themeToggle');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const sidebar = document.getElementById('appSidebar');

        if (themeToggle) {
            themeToggle.addEventListener('click', toggleTheme);
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }

        if (sidebarClose) {
            sidebarClose.addEventListener('click', closeSidebarMobile);
        }

        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', closeSidebarMobile);
        }

        if (sidebar) {
            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (!isDesktop()) {
                        closeSidebarMobile();
                    }
                });
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeSidebarMobile();
            }
        });

        syncSidebarState();
        window.addEventListener('resize', syncSidebarState);
    });
})();
