document.addEventListener('DOMContentLoaded', function () {
    var toggleButton = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('appSidebar');

    if (toggleButton && sidebar) {
        toggleButton.addEventListener('click', function () {
            sidebar.classList.toggle('is-open');
        });
    }
});
