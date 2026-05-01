(function () {
    function normalizeText(text) {
        return String(text || '')
            .replace(/\s+/g, ' ')
            .replace(/[^\S\r\n]+/g, ' ')
            .trim()
            .toLowerCase();
    }

    function resolveIconClass(label) {
        const text = normalizeText(label);
        const matchers = [
            [/^(tambah|buat|baru)\b/, 'bi-plus-circle'],
            [/\b(edit|ubah|perbarui)\b/, 'bi-pencil-square'],
            [/\b(hapus|delete)\b/, 'bi-trash'],
            [/\b(simpan|save)\b/, 'bi-check2-circle'],
            [/\b(batal|tutup|close)\b/, 'bi-x-circle'],
            [/\b(kembali|back)\b/, 'bi-arrow-left'],
            [/\b(cetak|print)\b/, 'bi-printer'],
            [/\b(unduh|download)\b/, 'bi-download'],
            [/\b(upload)\b/, 'bi-upload'],
            [/\b(import)\b/, 'bi-box-arrow-in-down'],
            [/\b(export)\b/, 'bi-box-arrow-up-right'],
            [/\b(filter|terapkan)\b/, 'bi-funnel'],
            [/\b(reset|bersihkan|clear)\b/, 'bi-arrow-counterclockwise'],
            [/\b(preview|lihat)\b/, 'bi-eye'],
            [/\b(detail)\b/, 'bi-eye'],
            [/\b(duplikat|salin|copy)\b/, 'bi-copy'],
            [/\b(proses|submit)\b/, 'bi-gear-wide-connected'],
            [/\b(quick|cepat)\b/, 'bi-lightning-charge'],
            [/\b(aktifkan)\b/, 'bi-play-circle'],
            [/\b(nonaktifkan)\b/, 'bi-pause-circle'],
            [/\b(reset password|password)\b/, 'bi-key'],
            [/\b(referensi|opsi|pengaturan)\b/, 'bi-sliders'],
            [/\b(audit)\b/, 'bi-file-earmark-text'],
            [/\b(template)\b/, 'bi-file-earmark-arrow-down'],
            [/\b(menu|aksi|lainnya)\b/, 'bi-three-dots'],
        ];

        for (const [pattern, iconClass] of matchers) {
            if (pattern.test(text)) {
                return iconClass;
            }
        }

        return '';
    }

    function extractLabel(element) {
        if (!(element instanceof HTMLElement)) {
            return '';
        }

        if (element.dataset.uiActionLabel) {
            return String(element.dataset.uiActionLabel).trim();
        }

        const text = Array.from(element.childNodes)
            .filter(function (node) {
                return node.nodeType === Node.TEXT_NODE;
            })
            .map(function (node) {
                return node.textContent || '';
            })
            .join(' ')
            .trim();

        return text || element.textContent || '';
    }

    function shouldSkip(element) {
        if (!(element instanceof HTMLElement)) {
            return true;
        }

        if (element.dataset.uiNoIconify === '1' || element.closest('[data-ui-no-iconify="1"]')) {
            return true;
        }

        if (element.matches('.table-action-trigger, .journal-action-trigger') || element.closest('.table-action-trigger, .journal-action-trigger')) {
            return true;
        }

        if (element.closest('#appSidebar, .app-topbar, .workspace-palette, .mobile-bottom-nav')) {
            return true;
        }

        if (element.classList.contains('ui-action-btn') || element.querySelector('.ui-action-btn__icon, .bi, svg, img')) {
            return true;
        }

        if (element.children.length > 0) {
            return true;
        }

        return false;
    }

    function shouldCompact(element, label) {
        const text = normalizeText(label);
        if (label.length >= 10) {
            return true;
        }

        if (/\b(tambah|simpan|batal|kembali|cetak|unduh|upload|import|export|preview|filter|reset|hapus|duplikat|referensi|proses|cepat)\b/.test(text)) {
            return true;
        }

        return !!element.closest('.module-hero__actions, .card-header, .card-body, .listing-controls-shell, .journal-action-panel, .table-action-panel, .journal-card__actions, .jf-toolbar, .user-action-group, .report-filter-card');
    }

    function enhanceActionTrigger(element) {
        if (!(element instanceof HTMLElement) || element.dataset.uiActionTriggerReady === '1') {
            return;
        }

        const label = extractLabel(element).trim() || 'Aksi';
        element.dataset.uiActionTriggerReady = '1';
        element.dataset.uiActionLabel = label;
        element.classList.add('ui-action-btn', 'ui-action-btn--compact');
        element.setAttribute('title', label);
        element.setAttribute('aria-label', label);

        if (!element.querySelector('.ui-action-btn__icon, .bi')) {
            element.textContent = '';
            const icon = document.createElement('span');
            icon.className = 'ui-action-btn__icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = '<i class="bi bi-three-dots"></i>';

            const text = document.createElement('span');
            text.className = 'ui-action-btn__label';
            text.textContent = label;

            element.appendChild(icon);
            element.appendChild(text);
        }
    }

    function applyIconify(element) {
        if (shouldSkip(element)) {
            return;
        }

        const label = extractLabel(element).trim();
        const iconClass = resolveIconClass(label);
        if (!label || !iconClass) {
            return;
        }

        element.dataset.uiActionLabel = label;
        element.textContent = '';
        element.classList.add('ui-action-btn');
        if (shouldCompact(element, label)) {
            element.classList.add('ui-action-btn--compact');
        }
        if (!element.getAttribute('title')) {
            element.setAttribute('title', label);
        }
        if (!element.getAttribute('aria-label')) {
            element.setAttribute('aria-label', label);
        }

        const icon = document.createElement('span');
        icon.className = 'ui-action-btn__icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = '<i class="bi ' + iconClass + '"></i>';

        const text = document.createElement('span');
        text.className = 'ui-action-btn__label';
        text.textContent = label;

        element.appendChild(icon);
        element.appendChild(text);
    }

    function enhanceActionButtons(root) {
        const scope = root instanceof HTMLElement || root instanceof Document ? root : document;
        const selector = [
            '.content-wrap .btn',
            '.content-wrap .dropdown-item',
            '.content-wrap .journal-action-panel a',
            '.content-wrap .journal-action-panel button',
            '.content-wrap .table-action-panel a',
            '.content-wrap .table-action-panel button'
        ].join(', ');

        scope.querySelectorAll(selector).forEach(applyIconify);
        scope.querySelectorAll('.content-wrap .table-action-trigger').forEach(enhanceActionTrigger);
    }

    function initGlobalActionUi() {
        enhanceActionButtons(document);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGlobalActionUi, { once: true });
    } else {
        initGlobalActionUi();
    }

    window.addEventListener('pageshow', initGlobalActionUi);

    const observer = new MutationObserver(function (mutations) {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach(function (node) {
                if (!(node instanceof HTMLElement)) {
                    return;
                }
                if (node.matches('.table-action-trigger')) {
                    enhanceActionTrigger(node);
                }
                if (node.matches('.btn, .dropdown-item, .journal-action-panel a, .journal-action-panel button, .table-action-panel a, .table-action-panel button')) {
                    applyIconify(node);
                }
                enhanceActionButtons(node);
            });
        }
    });

    if (document.documentElement) {
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
})();
