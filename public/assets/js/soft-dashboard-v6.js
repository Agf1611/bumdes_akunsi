
(function () {
    var raw = window.location.pathname || '/';
    var clean = raw.replace(/^\/+|\/+$/g, '');
    var first = clean.split('/')[0] || 'dashboard';
    document.body.classList.add('route-' + first.replace(/[^a-z0-9\-]/gi, '-').toLowerCase());

    if (clean.indexOf('/print') !== -1 || clean.endsWith('print')) {
        document.body.classList.add('route-print');
    }

    var prefersDark = document.documentElement.getAttribute('data-theme') === 'dark'
        || document.body.getAttribute('data-theme') === 'dark'
        || document.body.classList.contains('theme-dark');

    if (prefersDark) {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
})();
