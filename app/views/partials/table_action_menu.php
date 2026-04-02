<?php declare(strict_types=1); ?>
<style>
.table-responsive.coa-table-wrapper {
    overflow-x: auto !important;
    overflow-y: visible !important;
    -webkit-overflow-scrolling: touch;
    padding-bottom: .35rem;
}
.coa-table {
    width: max-content;
    min-width: 100%;
}
.coa-table tbody tr.table-action-row-open {
    position: relative;
    z-index: 40;
}
.table-action-col {
    width: 92px;
    min-width: 92px;
    position: sticky;
    right: 0;
    z-index: 2;
    background: #ffffff;
    box-shadow: -10px 0 18px rgba(15, 23, 42, .08);
}
.coa-table thead .table-action-col {
    z-index: 3;
    background: #f8fafc;
}
.coa-table tbody .table-action-col.is-open {
    z-index: 95;
    overflow: visible !important;
}
.table-action-menu {
    position: relative;
    display: inline-block;
    isolation: isolate;
}
.table-action-menu.is-open {
    z-index: 96;
}
.table-action-trigger {
    min-width: 78px;
    justify-content: center;
    position: relative;
    z-index: 1;
    background: #ffffff !important;
    border-color: rgba(37, 99, 235, .28) !important;
    color: #1e3a8a !important;
    box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
    opacity: 1 !important;
}
.table-action-trigger:hover,
.table-action-trigger:focus,
.table-action-menu.is-open .table-action-trigger {
    background: #eef4ff !important;
    border-color: rgba(37, 99, 235, .4) !important;
    color: #1d4ed8 !important;
}
.table-action-panel {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    z-index: 120;
    width: 220px;
    display: none;
    background: #fff;
    border: 1px solid rgba(16, 24, 40, .08);
    border-radius: 14px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, .16);
    padding: .5rem;
    pointer-events: auto;
}
.table-action-menu.open-up .table-action-panel {
    top: auto;
    bottom: calc(100% + 8px);
}
.table-action-menu.is-open .table-action-panel {
    display: block;
}
.table-action-panel a,
.table-action-panel button,
.table-action-note {
    display: flex;
    width: 100%;
    align-items: center;
    gap: .5rem;
    border: 0;
    background: transparent;
    color: #24324b;
    border-radius: 10px;
    padding: .7rem .8rem;
    text-decoration: none;
    text-align: left;
    font-weight: 500;
}
.table-action-panel a:hover,
.table-action-panel button:hover {
    background: rgba(59, 130, 246, .08);
    color: #1d4ed8;
}
.table-action-panel button[disabled] {
    color: #94a3b8;
    cursor: not-allowed;
}
.table-action-panel button[disabled]:hover {
    background: transparent;
    color: #94a3b8;
}
.table-action-note {
    color: #64748b;
    cursor: default;
}
.table-action-danger {
    color: #dc2626;
}
.table-action-danger:hover {
    background: rgba(220, 38, 38, .08);
    color: #b91c1c;
}
@media (max-width: 991.98px) {
    .table-action-panel,
    .table-action-menu.open-up .table-action-panel {
        position: fixed;
        left: 1rem;
        right: 1rem;
        top: auto;
        bottom: 1rem;
        width: auto;
        max-width: none;
    }
}
</style>
<script>
(function () {
    if (window.__tableActionMenuInit) {
        return;
    }
    window.__tableActionMenuInit = true;

    function getMenus() {
        return Array.from(document.querySelectorAll('.table-action-menu'));
    }

    function getPanel(menu) {
        return menu ? menu.querySelector('.table-action-panel') : null;
    }

    function measurePanelHeight(panel) {
        if (!panel) {
            return 0;
        }

        var previousDisplay = panel.style.display;
        var previousVisibility = panel.style.visibility;

        panel.style.visibility = 'hidden';
        panel.style.display = 'block';
        var height = panel.offsetHeight;
        panel.style.display = previousDisplay;
        panel.style.visibility = previousVisibility;

        return height;
    }

    function updateMenuDirection(menu) {
        if (!menu || window.innerWidth <= 991.98) {
            menu.classList.remove('open-up');
            return;
        }

        var panel = getPanel(menu);
        if (!panel) {
            menu.classList.remove('open-up');
            return;
        }

        var panelHeight = Math.max(measurePanelHeight(panel), 120);
        var trigger = menu.querySelector('.table-action-trigger');
        var reference = trigger ? trigger.getBoundingClientRect() : menu.getBoundingClientRect();
        var spaceBelow = window.innerHeight - reference.bottom;
        var spaceAbove = reference.top;
        var shouldOpenUp = spaceBelow < (panelHeight + 20) && spaceAbove > spaceBelow;

        menu.classList.toggle('open-up', shouldOpenUp);
    }

    function setMenuState(menu, isOpen) {
        if (!menu) {
            return;
        }

        menu.classList.toggle('is-open', isOpen);
        if (isOpen) {
            updateMenuDirection(menu);
        } else {
            menu.classList.remove('open-up');
        }

        var trigger = menu.querySelector('.table-action-trigger');
        if (trigger) {
            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }

        var cell = menu.closest('.table-action-col');
        if (cell) {
            cell.classList.toggle('is-open', isOpen);
        }

        var row = menu.closest('tr');
        if (row) {
            row.classList.toggle('table-action-row-open', isOpen);
        }
    }

    function closeMenus(exceptMenu) {
        getMenus().forEach(function (menu) {
            if (menu !== exceptMenu) {
                setMenuState(menu, false);
            }
        });
    }

    function refreshOpenMenus() {
        getMenus().forEach(function (menu) {
            if (menu.classList.contains('is-open')) {
                updateMenuDirection(menu);
            }
        });
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('.table-action-trigger');
        if (trigger) {
            var menu = trigger.closest('.table-action-menu');
            if (!menu) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            var willOpen = !menu.classList.contains('is-open');
            closeMenus(menu);
            setMenuState(menu, willOpen);
            return;
        }

        if (!event.target.closest('.table-action-menu')) {
            closeMenus(null);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenus(null);
        }
    });

    window.addEventListener('resize', refreshOpenMenus, { passive: true });
    window.addEventListener('scroll', refreshOpenMenus, { passive: true });
})();
</script>
