(function () {
  const palette = document.getElementById('workspacePalette');
  const toggle = document.getElementById('commandPaletteToggle');
  if (!palette || !toggle) {
    return;
  }

  const input = document.getElementById('workspacePaletteInput');
  const results = document.getElementById('workspacePaletteResults');
  const saveFilterButton = document.getElementById('saveCurrentFilterButton');
  const favoriteForm = document.getElementById('workspaceFavoriteForm');
  const favoriteButton = document.getElementById('workspaceFavoriteButton');
  const bootstrapPayload = JSON.parse(palette.dataset.bootstrap || '{}');
  const appBaseUrl = (palette.dataset.appBaseUrl || '').replace(/\/+$/, '');
  const searchUrl = palette.dataset.searchUrl || '';
  const favoriteUrl = bootstrapPayload.toggle_favorite_url || '';
  const saveFilterUrl = palette.dataset.saveFilterUrl || '';
  const csrf = palette.dataset.csrf || '';
  const pageTitle = palette.dataset.pageTitle || 'Halaman';
  const pagePath = palette.dataset.pagePath || '/';
  const pageLabel = palette.dataset.pageLabel || pageTitle;
  const canSaveFilter = palette.dataset.canSaveFilter === '1';

  const sectionLabels = {
    favorites: 'Favorit',
    recent: 'Item Terakhir',
    saved_filters: 'Filter Tersimpan',
    menus: 'Menu',
    journals: 'Jurnal',
    accounts: 'Akun COA',
    periods: 'Periode',
    units: 'Unit Usaha',
    users: 'Pengguna'
  };

  function openPalette() {
    palette.hidden = false;
    document.body.style.overflow = 'hidden';
    setTimeout(function () {
      if (input) input.focus();
    }, 0);
    renderBootstrapSections();
  }

  function closePalette() {
    palette.hidden = true;
    document.body.style.overflow = '';
  }

  function resolvePalettePath(path) {
    const value = String(path || '#').trim();
    if (value === '' || value === '#') {
      return '#';
    }

    if (/^[a-z][a-z0-9+.-]*:/i.test(value) && !/^https?:\/\//i.test(value)) {
      return '#';
    }

    if (/^(?:[a-z]+:)?\/\//i.test(value) || value.startsWith('#')) {
      return value;
    }

    if (!value.startsWith('/')) {
      return value;
    }

    if (!appBaseUrl) {
      return value;
    }

    return appBaseUrl + value;
  }

  function createEmpty(message) {
    const node = document.createElement('div');
    node.className = 'workspace-palette__empty';
    node.textContent = message;
    return node;
  }

  function appendText(parent, className, value) {
    const node = document.createElement('div');
    node.className = className;
    node.textContent = String(value || '');
    parent.appendChild(node);
    return node;
  }

  function renderSection(label, items) {
    if (!Array.isArray(items) || items.length === 0) {
      return null;
    }
    const section = document.createElement('section');
    section.className = 'workspace-palette__section';
    appendText(section, 'workspace-palette__section-head', label);

    const list = document.createElement('div');
    list.className = 'workspace-palette__items';
    items.forEach(function (item) {
      const link = document.createElement('a');
      link.href = resolvePalettePath(item.path || '#');
      link.className = 'workspace-palette__item';

      const textWrap = document.createElement('div');
      appendText(textWrap, 'workspace-palette__item-title', item.title || item.label || '-');
      if (item.subtitle) {
        appendText(textWrap, 'workspace-palette__item-subtitle', item.subtitle);
      }
      link.appendChild(textWrap);

      if (item.meta) {
        appendText(link, 'workspace-palette__item-meta', item.meta);
      }
      list.appendChild(link);
    });
    section.appendChild(list);
    return section;
  }

  function renderSections(sectionSpecs, emptyMessage) {
    const nodes = sectionSpecs
      .map(function (spec) {
        return renderSection(spec.label, spec.items);
      })
      .filter(Boolean);
    results.replaceChildren.apply(results, nodes.length ? nodes : [createEmpty(emptyMessage)]);
  }

  function renderBootstrapSections() {
    renderSections([
      { label: sectionLabels.favorites, items: bootstrapPayload.favorites || [] },
      { label: sectionLabels.recent, items: bootstrapPayload.recent || [] },
      { label: sectionLabels.saved_filters, items: bootstrapPayload.saved_filters || [] }
    ], 'Belum ada favorit, item terakhir, atau filter tersimpan.');
  }

  async function fetchSearch(query) {
    const url = new URL(searchUrl, window.location.origin);
    url.searchParams.set('q', query);
    const response = await fetch(url.toString(), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    return response.json();
  }

  function renderSearchSections(payload) {
    const sections = payload && payload.results ? payload.results : {};
    const orderedKeys = ['menus', 'journals', 'accounts', 'periods', 'units', 'users'];
    renderSections(orderedKeys.map(function (key) {
      return { label: sectionLabels[key], items: sections[key] || [] };
    }), 'Tidak ada hasil yang cocok.');
  }

  let typingTimer = null;
  input.addEventListener('input', function () {
    const query = String(input.value || '').trim();
    window.clearTimeout(typingTimer);
    if (query === '') {
      renderBootstrapSections();
      return;
    }
    typingTimer = window.setTimeout(async function () {
      try {
        const payload = await fetchSearch(query);
        renderSearchSections(payload);
      } catch (error) {
        results.replaceChildren(createEmpty('Pencarian belum bisa dijalankan sekarang.'));
      }
    }, 180);
  });

  toggle.addEventListener('click', openPalette);

  palette.addEventListener('click', function (event) {
    if (event.target.closest('[data-close-palette]')) {
      closePalette();
    }
  });

  document.addEventListener('keydown', function (event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      if (palette.hidden) {
        openPalette();
      } else {
        closePalette();
      }
    }
    if (event.key === 'Escape' && !palette.hidden) {
      closePalette();
    }
  });

  if (saveFilterButton && canSaveFilter && saveFilterUrl) {
    saveFilterButton.addEventListener('click', async function () {
      const name = window.prompt('Nama filter yang ingin disimpan?', pageLabel + ' - ' + new Date().toLocaleDateString('id-ID'));
      if (!name) {
        return;
      }

      const formData = new FormData();
      formData.append('_token', csrf);
      formData.append('name', name);
      formData.append('label', pageLabel);
      formData.append('path', pagePath);

      try {
        const response = await fetch(saveFilterUrl, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json();
        if (!payload.ok) {
          window.alert(payload.message || 'Filter gagal disimpan.');
          return;
        }
        bootstrapPayload.saved_filters = payload.items || [];
        window.alert('Filter berhasil disimpan.');
        renderBootstrapSections();
      } catch (error) {
        window.alert('Filter belum bisa disimpan sekarang.');
      }
    });
  }

  if (favoriteForm && favoriteButton && favoriteUrl) {
    favoriteForm.addEventListener('submit', async function (event) {
      event.preventDefault();

      const formData = new FormData(favoriteForm);
      try {
        const response = await fetch(favoriteUrl, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const payload = await response.json();
        if (!payload.ok) {
          window.alert(payload.message || 'Favorit belum bisa diperbarui.');
          return;
        }

        bootstrapPayload.favorites = payload.items || [];
        favoriteButton.classList.toggle('is-favorited', !!payload.favorited);
        const valueNode = favoriteButton.querySelector('.topbar-pill__value');
        if (valueNode) {
          valueNode.textContent = payload.favorited ? 'Tersimpan' : 'Simpan';
        }
        renderBootstrapSections();
      } catch (error) {
        favoriteForm.submit();
      }
    });
  }
})();
