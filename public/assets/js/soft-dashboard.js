(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
            return;
        }
        document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function () {
        var sidebar = document.getElementById('appSidebar');
        var openBtn = document.getElementById('sidebarToggleBtn');
        var closeBtn = document.getElementById('sidebarCloseBtn');
        var backdrop = document.getElementById('sidebarBackdrop');

        function openSidebar() {
            if (!sidebar) return;
            sidebar.classList.add('sidebar-mobile-open');
            document.body.classList.add('sidebar-is-open');
        }

        function closeSidebar() {
            if (!sidebar) return;
            sidebar.classList.remove('sidebar-mobile-open');
            document.body.classList.remove('sidebar-is-open');
        }

        if (openBtn) openBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });
    });
})();
