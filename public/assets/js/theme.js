(function () {
    const themeKey = 'bumdes_theme';
    const sidebarKey = 'bumdes_sidebar_collapsed';
    const sidebarGroupsKey = 'bumdes_sidebar_open_groups';
    const sidebarScrollKey = 'bumdes_sidebar_scroll_state';
    const sidebarLastPathKey = 'bumdes_sidebar_last_path';
    const desktopShellMinWidth = 1366;
    const root = document.documentElement;

    const sunIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></svg>';
    const moonIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path></svg>';

    function getBody() {
        return document.body;
    }

    function isDesktop() {
        return window.innerWidth >= desktopShellMinWidth;
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

    function getSavedSidebarGroups() {
        try {
            const raw = localStorage.getItem(sidebarGroupsKey);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.map(String) : [];
        } catch (e) {
            return [];
        }
    }

    function saveSidebarGroups(groups) {
        try {
            localStorage.setItem(sidebarGroupsKey, JSON.stringify(Array.from(new Set(groups)).filter(Boolean)));
        } catch (e) {}
    }

    function getSidebarScrollState() {
        try {
            const raw = localStorage.getItem(sidebarScrollKey);
            const parsed = raw ? JSON.parse(raw) : {};
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function saveSidebarScrollState(sidebar) {
        if (!sidebar) {
            return;
        }

        const inner = sidebar.querySelector('.app-sidebar__inner');
        try {
            localStorage.setItem(sidebarScrollKey, JSON.stringify({
                sidebarTop: sidebar.scrollTop || 0,
                innerTop: inner ? (inner.scrollTop || 0) : 0,
                savedAt: Date.now()
            }));
        } catch (e) {}
    }

    function rememberSidebarPath(link) {
        if (!link) {
            return;
        }

        try {
            const href = link.getAttribute('href') || '';
            if (href && href.charAt(0) !== '#') {
                localStorage.setItem(sidebarLastPathKey, href);
            }
        } catch (e) {}
    }

    function restoreSidebarScrollState(sidebar) {
        if (!sidebar) {
            return;
        }

        const state = getSidebarScrollState();
        const inner = sidebar.querySelector('.app-sidebar__inner');
        const activeLink = sidebar.querySelector('.app-nav__link.is-active');
        const lastPath = (function () {
            try {
                return localStorage.getItem(sidebarLastPathKey) || '';
            } catch (e) {
                return '';
            }
        })();
        const lastLink = lastPath
            ? Array.from(sidebar.querySelectorAll('a[href]')).find(function (link) {
                return (link.getAttribute('href') || '') === lastPath;
            })
            : null;
        const preferredLink = lastLink || activeLink;
        const hasSavedScroll = (typeof state.sidebarTop === 'number' && state.sidebarTop > 0)
            || (typeof state.innerTop === 'number' && state.innerTop > 0);

        const restore = function (shouldCenterLink) {
            if (typeof state.sidebarTop === 'number') {
                sidebar.scrollTop = state.sidebarTop;
            }
            if (inner && typeof state.innerTop === 'number') {
                inner.scrollTop = state.innerTop;
            }

            if (shouldCenterLink && preferredLink && !hasSavedScroll) {
                preferredLink.scrollIntoView({ block: 'center', inline: 'nearest' });
            }

            saveSidebarScrollState(sidebar);
        };

        window.requestAnimationFrame(function () {
            restore(false);
        });
        [80, 240, 650].forEach(function (delay) {
            window.setTimeout(function () {
                restore(delay >= 240);
            }, delay);
        });
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
        const icons = document.querySelectorAll('[data-theme-icon]');
        const texts = document.querySelectorAll('[data-theme-text]');
        const toggles = document.querySelectorAll('[data-theme-toggle]');

        icons.forEach(function (icon) {
            icon.innerHTML = theme === 'light' ? sunIcon : moonIcon;
        });

        texts.forEach(function (text) {
            text.textContent = theme === 'light' ? 'Light' : 'Dark';
        });

        toggles.forEach(function (toggle) {
            toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            toggle.setAttribute('data-theme-current', theme);
        });
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

    function getSidebarGroups() {
        return Array.from(document.querySelectorAll('[data-sidebar-group]')).filter(function (group) {
            return group.hasAttribute('data-sidebar-group') && group.matches('.app-nav__group');
        });
    }

    function getSidebarGroup(groupName) {
        return document.querySelector('.app-nav__group[data-sidebar-group="' + groupName + '"]');
    }

    function setSidebarGroupExpanded(groupName, expanded) {
        const group = getSidebarGroup(groupName);
        if (!group) {
            return;
        }

        const trigger = group.querySelector('[data-sidebar-trigger]');
        const panel = group.querySelector('[data-sidebar-panel]');
        if (!trigger || !panel) {
            return;
        }

        group.classList.toggle('is-open', expanded);
        trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        panel.hidden = !expanded;
    }

    function setSidebarGroupExpandedPersisted(groupName, expanded) {
        setSidebarGroupExpanded(groupName, expanded);
        const groups = getSavedSidebarGroups().filter(function (name) {
            return name !== groupName;
        });
        if (expanded) {
            groups.push(groupName);
        }
        saveSidebarGroups(groups);
    }

    function closeAllSidebarGroups(exception) {
        getSidebarGroups().forEach(function (group) {
            if (exception && group === exception) {
                return;
            }
            const groupName = group.getAttribute('data-sidebar-group');
            if (groupName) {
                setSidebarGroupExpanded(groupName, false);
            }
        });
    }

    function toggleSidebarGroup(groupName) {
        const group = getSidebarGroup(groupName);
        if (!group) {
            return;
        }

        const shouldExpand = !group.classList.contains('is-open');
        setSidebarGroupExpandedPersisted(groupName, shouldExpand);
    }

    function restoreSidebarGroups() {
        const savedGroups = getSavedSidebarGroups();
        const activeGroups = getSidebarGroups()
            .filter(function (group) {
                return group.classList.contains('is-current') || !!group.querySelector('.app-nav__link.is-active');
            })
            .map(function (group) {
                return group.getAttribute('data-sidebar-group');
            })
            .filter(Boolean);
        const groupsToOpen = Array.from(new Set(savedGroups.concat(activeGroups)));

        getSidebarGroups().forEach(function (group) {
            const groupName = group.getAttribute('data-sidebar-group');
            if (groupName) {
                setSidebarGroupExpanded(groupName, groupsToOpen.includes(groupName));
            }
        });

        saveSidebarGroups(groupsToOpen);
    }

    function syncSidebarState() {
        const body = getBody();
        const sidebar = document.getElementById('appSidebar');
        if (!body || !sidebar) {
            return;
        }

        if (isDesktop()) {
            body.classList.remove('sidebar-open');
            body.style.overflow = '';
            body.classList.toggle('sidebar-collapsed', getSidebarCollapsed());
            sidebar.setAttribute('aria-hidden', 'false');
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
            body.style.overflow = '';
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

    function initShellUi() {
        resetSidebarStateOnce();
        applyTheme(getPreferredTheme());

        const themeToggles = document.querySelectorAll('[data-theme-toggle]');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const sidebar = document.getElementById('appSidebar');

        themeToggles.forEach(function (toggle) {
            toggle.removeEventListener('click', toggleTheme);
            toggle.addEventListener('click', toggleTheme);
        });

        if (sidebarToggle) {
            sidebarToggle.removeEventListener('click', toggleSidebar);
            sidebarToggle.addEventListener('click', toggleSidebar);
        }

        if (sidebarClose) {
            sidebarClose.removeEventListener('click', closeSidebarMobile);
            sidebarClose.addEventListener('click', closeSidebarMobile);
        }

        if (sidebarBackdrop) {
            sidebarBackdrop.removeEventListener('click', closeSidebarMobile);
            sidebarBackdrop.addEventListener('click', closeSidebarMobile);
        }

        if (sidebar) {
            restoreSidebarGroups();
            restoreSidebarScrollState(sidebar);

            if (!sidebar.dataset.sidebarBound) {
                let sidebarScrollTimer = 0;
                const sidebarInner = sidebar.querySelector('.app-sidebar__inner');
                const saveScrollSoon = function () {
                    window.clearTimeout(sidebarScrollTimer);
                    sidebarScrollTimer = window.setTimeout(function () {
                        saveSidebarScrollState(sidebar);
                    }, 120);
                };
                const saveScrollNow = function () {
                    window.clearTimeout(sidebarScrollTimer);
                    saveSidebarScrollState(sidebar);
                };

                sidebar.addEventListener('scroll', saveScrollSoon, { passive: true });
                if (sidebarInner) {
                    sidebarInner.addEventListener('scroll', saveScrollSoon, { passive: true });
                }
                window.addEventListener('beforeunload', saveScrollNow);
                document.addEventListener('visibilitychange', function () {
                    if (document.visibilityState === 'hidden') {
                        saveScrollNow();
                    }
                });

                sidebar.addEventListener('click', function (event) {
                    const target = event.target;
                    if (!(target instanceof Element)) {
                        return;
                    }

                    const trigger = target.closest('[data-sidebar-trigger]');
                    if (trigger) {
                        const groupName = trigger.getAttribute('data-sidebar-group');
                    if (groupName) {
                        toggleSidebarGroup(groupName);
                        window.setTimeout(function () {
                            saveSidebarScrollState(sidebar);
                        }, 180);
                    }
                    return;
                }

                    const link = target.closest('a');
                    if (!link) {
                        return;
                    }

                    rememberSidebarPath(link);
                    saveSidebarScrollState(sidebar);

                    if (!isDesktop()) {
                        closeSidebarMobile();
                    }
                });

                sidebar.dataset.sidebarBound = '1';
            }
        }

        if (!document.body.dataset.sidebarDocumentBound) {
            document.addEventListener('click', function (event) {
                const body = getBody();
                if (!body || !isDesktop() || !body.classList.contains('sidebar-collapsed')) {
                    return;
                }
                const target = event.target;
                if (target instanceof Element && target.closest('#appSidebar')) {
                    return;
                }
                closeAllSidebarGroups();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeAllSidebarGroups();
                    closeSidebarMobile();
                }
            });

            window.addEventListener('resize', syncSidebarState);
            document.body.dataset.sidebarDocumentBound = '1';
        }

        syncSidebarState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initShellUi, { once: true });
    } else {
        initShellUi();
    }

    window.addEventListener('pageshow', function () {
        initShellUi();
    });
})();
