<?php declare(strict_types=1); ?>
<?php
$header = $header ?? null;
$formData = is_array($formData ?? null) ? $formData : [];
$receiptData = is_array($receiptData ?? null) ? $receiptData : [];
$detailRows = is_array($detailRows ?? null) ? array_values($detailRows) : [];
if ($detailRows === []) {
    $detailRows = [
        ['coa_id' => '', 'line_description' => '', 'debit_raw' => '', 'credit_raw' => '', 'partner_id' => '', 'inventory_item_id' => '', 'raw_material_id' => '', 'asset_id' => '', 'saving_account_id' => '', 'cashflow_component_id' => '', 'entry_tag' => ''],
        ['coa_id' => '', 'line_description' => '', 'debit_raw' => '', 'credit_raw' => '', 'partner_id' => '', 'inventory_item_id' => '', 'raw_material_id' => '', 'asset_id' => '', 'saving_account_id' => '', 'cashflow_component_id' => '', 'entry_tag' => ''],
    ];
}
$receiptFeatureStatus = is_array($receiptFeatureStatus ?? null) ? $receiptFeatureStatus : ['enabled' => false];
$periodOptions = is_array($periodOptions ?? null) ? $periodOptions : [];
$unitOptions = is_array($unitOptions ?? null) ? $unitOptions : [];
$accountOptions = is_array($accountOptions ?? null) ? $accountOptions : [];
$referenceOptions = is_array($referenceOptions ?? null) ? $referenceOptions : [];
$quickTemplateOptions = is_array($quickTemplateOptions ?? null) ? $quickTemplateOptions : [];
$activeQuickTemplate = is_array($activeQuickTemplate ?? null) ? $activeQuickTemplate : null;
$journalNoPreviewMap = is_array($journalNoPreviewMap ?? null) ? $journalNoPreviewMap : [];
$journalNoPreviewCurrent = (string) ($journalNoPreviewCurrent ?? 'Otomatis saat disimpan');
$draftStorageKey = 'journal-form-draft-' . ($header ? ('edit-' . (int) ($header['id'] ?? 0)) : 'create');
$actionUrl = $header ? base_url('/journals/update?id=' . (int) $header['id']) : base_url('/journals/store');
$backUrl = base_url('/journals');
$duplicateUrl = $header ? base_url('/journals/create?duplicate_id=' . (int) $header['id']) : '';
$entryTags = is_array($referenceOptions['entry_tags'] ?? null) ? $referenceOptions['entry_tags'] : ['' => 'Tidak Spesifik'];
$receiptPartyTitleOptions = is_array($receiptPartyTitleOptions ?? null) ? $receiptPartyTitleOptions : journal_receipt_party_title_options();
$normalizeReferenceItems = static function (array $items, array $labelKeys = [], array $codeKeys = []): array {
    $normalized = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = (string) ($item['id'] ?? '');
        $label = '';
        foreach ($labelKeys as $key) {
            if (isset($item[$key]) && trim((string) $item[$key]) !== '') {
                $label = trim((string) $item[$key]);
                break;
            }
        }
        if ($label === '') {
            foreach (['label', 'name', 'account_name', 'component_name', 'item_name', 'material_name', 'asset_name', 'partner_name'] as $fallbackKey) {
                if (isset($item[$fallbackKey]) && trim((string) $item[$fallbackKey]) !== '') {
                    $label = trim((string) $item[$fallbackKey]);
                    break;
                }
            }
        }
        $code = '';
        foreach ($codeKeys as $key) {
            if (isset($item[$key]) && trim((string) $item[$key]) !== '') {
                $code = trim((string) $item[$key]);
                break;
            }
        }
        if ($code === '') {
            foreach (['code', 'account_code', 'component_code', 'item_code', 'material_code', 'asset_code', 'partner_code', 'account_no'] as $fallbackKey) {
                if (isset($item[$fallbackKey]) && trim((string) $item[$fallbackKey]) !== '') {
                    $code = trim((string) $item[$fallbackKey]);
                    break;
                }
            }
        }
        if ($label === '') {
            $label = $code !== '' ? $code : ('Referensi #' . ($id !== '' ? $id : count($normalized) + 1));
        }
        $item['id'] = $id;
        $item['label'] = $code !== '' && stripos($label, $code) !== 0 ? ($code . ' - ' . $label) : $label;
        $normalized[] = $item;
    }
    return $normalized;
};
$referenceOptions['partners'] = $normalizeReferenceItems(array_values($referenceOptions['partners'] ?? []), ['partner_name', 'name'], ['partner_code', 'code']);
$referenceOptions['inventory'] = $normalizeReferenceItems(array_values($referenceOptions['inventory'] ?? []), ['item_name', 'name'], ['item_code', 'code']);
$referenceOptions['raw_materials'] = $normalizeReferenceItems(array_values($referenceOptions['raw_materials'] ?? []), ['material_name', 'name'], ['material_code', 'code']);
$referenceOptions['assets'] = $normalizeReferenceItems(array_values($referenceOptions['assets'] ?? []), ['asset_name', 'name'], ['asset_code', 'code']);
$referenceOptions['savings'] = $normalizeReferenceItems(array_values($referenceOptions['savings'] ?? []), ['account_name', 'name'], ['account_no', 'account_code', 'code']);
$referenceOptions['cashflow_components'] = $normalizeReferenceItems(array_values($referenceOptions['cashflow_components'] ?? []), ['component_name', 'name'], ['component_code', 'code']);
$accountJs = array_map(static function (array $account): array {
    $label = trim((string) (($account['account_code'] ?? '') . ' - ' . ($account['account_name'] ?? '')));
    return [
        'id' => (string) ($account['id'] ?? ''),
        'code' => (string) ($account['account_code'] ?? ''),
        'name' => (string) ($account['account_name'] ?? ''),
        'label' => $label,
        'type' => (string) ($account['account_type'] ?? ''),
        'search' => function_exists('mb_strtolower')
            ? mb_strtolower($label . ' ' . (string) ($account['account_type'] ?? ''))
            : strtolower($label . ' ' . (string) ($account['account_type'] ?? '')),
        'is_suggested' => (int) ($account['is_suggested'] ?? 0),
    ];
}, $accountOptions);
$referenceJs = [
    'partners' => array_values($referenceOptions['partners'] ?? []),
    'inventory' => array_values($referenceOptions['inventory'] ?? []),
    'raw_materials' => array_values($referenceOptions['raw_materials'] ?? []),
    'assets' => array_values($referenceOptions['assets'] ?? []),
    'savings' => array_values($referenceOptions['savings'] ?? []),
    'cashflow_components' => array_values($referenceOptions['cashflow_components'] ?? []),
    'entry_tags' => $entryTags,
];
?>
<style>
.jf-shell { max-width: 1240px; margin: 0 auto; display: grid; gap: 1rem; }
.jf-page-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
.jf-toolbar { display: flex; flex-wrap: wrap; gap: .65rem; }
.jf-card {
  background: #fff;
  border: 1px solid #dbe5f2;
  border-radius: 22px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
  overflow: hidden;
}
.jf-card-head {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e8eef7;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: .75rem;
  flex-wrap: wrap;
}
.jf-card-body { padding: 1.1rem 1.25rem 1.25rem; }
.jf-muted { color: #64748b !important; }
.jf-inline { font-size: .85rem; color: #64748b; }
.jf-preview { font-weight: 700; color: #1d4ed8; }
.jf-template-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .75rem; }
.jf-template-item {
  display: block;
  padding: .95rem 1rem;
  border: 1px solid #dbe5f2;
  border-radius: 18px;
  background: #fbfdff;
  text-decoration: none;
  color: inherit;
}
.jf-template-item:hover { border-color: #93c5fd; background: #f8fbff; }
.jf-template-item.is-active { border-color: #60a5fa; background: #eff6ff; }
.jf-template-switch { display:flex; flex-wrap:wrap; gap:.65rem; }
.jf-template-pill {
  display:inline-flex; align-items:center; gap:.45rem; padding:.7rem .95rem; border:1px solid #dbe5f2;
  border-radius:999px; background:#fbfdff; color:#334155; text-decoration:none; font-weight:600;
}
.jf-template-pill:hover { border-color:#93c5fd; background:#eff6ff; color:#1d4ed8; }
.jf-template-pill.is-active { border-color:#60a5fa; background:#dbeafe; color:#1d4ed8; }
.jf-pill-dot { width:.55rem; height:.55rem; border-radius:999px; background:currentColor; opacity:.75; }
.jf-form-alignment-note { border-left:4px solid #60a5fa; background:#eff6ff; padding:.85rem 1rem; border-radius:14px; color:#1e3a8a; }
.jf-main-grid { display: grid; gap: 1rem; }
.jf-side-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
.jf-summary-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
.jf-stat {
  border: 1px solid #dbe5f2;
  border-radius: 16px;
  background: #fbfdff;
  padding: .9rem 1rem;
}
.jf-stat .label {
  display: block;
  font-size: .76rem;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: #64748b;
  margin-bottom: .15rem;
}
.jf-stat .value { font-size: 1.08rem; font-weight: 700; color: #0f172a; }
.jf-soft-note {
  border: 1px dashed #cbd5e1;
  background: #f8fafc;
  border-radius: 16px;
  padding: .85rem 1rem;
}
.jf-list { margin: 0; padding-left: 1rem; display: grid; gap: .35rem; }
.jf-line-list { display: grid; gap: .85rem; }
.jf-line-item {
  border: 1px solid #dbe5f2;
  border-radius: 18px;
  background: #fbfdff;
  padding: 1rem;
}
.jf-line-top { display: flex; justify-content: space-between; align-items: center; gap: .75rem; margin-bottom: .8rem; flex-wrap: wrap; }
.jf-line-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr) 130px 130px; gap: .75rem; }
.jf-account-stack { display: grid; gap: .45rem; }
.jf-account-search { min-height: 40px; }
.jf-account-hint { font-size: .76rem; color: #64748b; }
.jf-meta-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .75rem;
  margin-top: .85rem;
  padding-top: .85rem;
  border-top: 1px dashed #dbe5f2;
}
.jf-meta-grid.is-hidden { display: none; }
.jf-actions-row { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; }

.jf-card .form-label,
.jf-card .small,
.jf-card .fw-semibold,
.jf-card h2,
.jf-card h5,
.jf-card p,
.jf-card div,
.jf-card span,
.jf-card li {
  color: inherit;
}
.jf-card .form-control,
.jf-card .form-select,
.jf-card textarea,
.jf-card input[type="date"],
.jf-card input[type="text"],
.jf-card input[type="number"] {
  background: #fff !important;
  color: #0f172a !important;
  border: 1px solid #cbd5e1 !important;
  box-shadow: none !important;
}
.jf-card .form-control:focus,
.jf-card .form-select:focus,
.jf-card textarea:focus {
  border-color: #60a5fa !important;
  box-shadow: 0 0 0 .2rem rgba(96,165,250,.18) !important;
}
.jf-card .form-control::placeholder,
.jf-card textarea::placeholder { color: #94a3b8 !important; }
.jf-card .btn-outline-light {
  color: #334155 !important;
  border-color: #cbd5e1 !important;
  background: #fff !important;
}
.jf-card .btn-outline-light:hover,
.jf-card .btn-outline-secondary:hover {
  background: #eff6ff !important;
  border-color: #93c5fd !important;
  color: #1d4ed8 !important;
}
.jf-card .btn-outline-info:hover { color: #075985 !important; }
.jf-card .btn-outline-danger:hover { color: #991b1b !important; }

@media (max-width: 1199px) {
  .jf-side-grid { grid-template-columns: 1fr; }
}
@media (max-width: 899px) {
  .jf-line-grid { grid-template-columns: 1fr 1fr; }
  .jf-meta-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 767px) {
  .jf-line-grid,
  .jf-meta-grid,
  .jf-summary-grid { grid-template-columns: 1fr; }
  .jf-actions-row { flex-direction: column; align-items: stretch; }
  .jf-card-head, .jf-page-head { align-items: stretch; }
}
</style>

<div class="jf-shell">
  <div class="jf-page-head module-hero mb-2">
    <div class="module-hero__content">
      <div>
        <div class="module-hero__eyebrow">Jurnal Umum</div>
        <h1 class="module-hero__title"><?= e($title ?? 'Form Jurnal Umum') ?></h1>
        <p class="module-hero__text">Form jurnal lengkap untuk transaksi yang memerlukan kontrol detail penuh, dengan validasi inti yang tetap aman dipakai lintas jenis usaha.</p>
      </div>
      <div class="module-hero__actions jf-toolbar">
        <?php if ($duplicateUrl !== ''): ?>
          <a href="<?= e($duplicateUrl) ?>" class="btn btn-outline-secondary">Duplikat Jurnal</a>
        <?php endif; ?>
        <a href="<?= e(base_url('/journals/quick')) ?>" class="btn btn-outline-info">Transaksi Cepat</a>
        <a href="<?= e($backUrl) ?>" class="btn btn-outline-secondary">Kembali ke Daftar</a>
      </div>
    </div>
  </div>

  <div class="alert alert-info mb-4">
    <div class="fw-semibold mb-1">Bantuan pengisian</div>
    <div class="small">Gunakan form ini jika Anda perlu kontrol detail penuh atas baris jurnal. Untuk pemasukan, pengeluaran, setoran bank, atau koreksi sederhana, pakai <a href="<?= e(base_url('/journals/quick')) ?>" class="alert-link">Transaksi Cepat</a> agar input lebih sedikit tetapi hasil jurnal tetap sama validnya.</div>
  </div>

  <?php if (!$header): ?>
  <section class="jf-card">
    <div class="jf-card-body d-grid gap-3">
      <?php if (is_array($activeQuickTemplate)): ?>
        <div class="alert alert-info border-0 shadow-sm mb-0">Mode cepat aktif: <strong><?= e((string) ($activeQuickTemplate['template_name'] ?? 'Template jurnal')) ?></strong></div>
      <?php endif; ?>
      <div>
        <div class="fw-semibold mb-2">Template transaksi cepat</div>
        <div class="jf-template-switch">
          <a href="<?= e(base_url('/journals/create')) ?>" class="jf-template-pill <?= !is_array($activeQuickTemplate) ? 'is-active' : '' ?>"><span class="jf-pill-dot"></span>Form kosong</a>
          <?php foreach ($quickTemplateOptions as $templateKey => $template): ?>
            <?php $isActiveTemplate = is_array($activeQuickTemplate) && (string) ($activeQuickTemplate['template_key'] ?? '') === (string) $templateKey; ?>
            <a class="jf-template-pill <?= $isActiveTemplate ? 'is-active' : '' ?>" href="<?= e(base_url('/journals/create?template=' . urlencode((string) $templateKey))) ?>"><span class="jf-pill-dot"></span><?= e((string) ($template['label'] ?? $templateKey)) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="jf-form-alignment-note small">
        Kolom inti form ini sama dengan template import jurnal: <strong>tanggal jurnal, periode, keterangan, akun, uraian baris, debit, dan kredit</strong>. Kolom lain seperti unit usaha, template cetak, dan referensi adalah tambahan opsional.
      </div>
    </div>
  </section>
  <?php endif; ?>

  <form method="post" action="<?= e($actionUrl) ?>" id="journal-form" novalidate>
    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">

    <section class="jf-card">
      <div class="jf-card-head">
        <div>
          <h2 class="h5 mb-1">Informasi Jurnal</h2>
          <div class="jf-inline">Nomor jurnal final akan dibuat otomatis saat disimpan.</div>
        </div>
        <span class="badge text-bg-light border">Preview nomor: <span id="journal-number-preview" class="jf-preview"><?= e($journalNoPreviewCurrent) ?></span></span>
      </div>
      <div class="jf-card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Tanggal Jurnal</label>
            <input type="date" name="journal_date" id="journal_date" class="form-control" value="<?= e((string) ($formData['journal_date'] ?? '')) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Periode Akuntansi</label>
            <select name="period_id" id="period_id" class="form-select" required>
              <option value="">Pilih Periode</option>
              <?php foreach ($periodOptions as $period): ?>
                <option value="<?= e((string) $period['id']) ?>" <?= (string) ($formData['period_id'] ?? '') === (string) $period['id'] ? 'selected' : '' ?>><?= e((string) $period['period_name'] . ' (' . (string) $period['period_code'] . ')') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Unit Usaha</label>
            <select name="business_unit_id" id="business_unit_id" class="form-select">
              <option value="">Semua / Tidak Spesifik</option>
              <?php foreach ($unitOptions as $unit): ?>
                <option value="<?= e((string) $unit['id']) ?>" <?= (string) ($formData['business_unit_id'] ?? '') === (string) $unit['id'] ? 'selected' : '' ?>><?= e((string) $unit['unit_code'] . ' - ' . (string) $unit['unit_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Template Cetak</label>
            <select name="print_template" id="print_template" class="form-select">
              <?php foreach (journal_print_template_options() as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= (string) ($formData['print_template'] ?? 'standard') === (string) $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label">Keterangan Jurnal</label>
            <textarea name="description" id="description" rows="2" class="form-control" maxlength="255" required><?= e((string) ($formData['description'] ?? '')) ?></textarea>
          </div>
        </div>
      </div>
    </section>

    <section class="jf-card" id="receipt-card" <?= (string) ($formData['print_template'] ?? 'standard') === 'receipt' ? '' : 'style="display:none"' ?>>
      <div class="jf-card-head">
        <div>
          <h2 class="h5 mb-1">Bukti Transaksi / Kwitansi</h2>
          <div class="jf-inline">Isi hanya bila jurnal ini akan dicetak sebagai bukti transaksi.</div>
        </div>
        <?php if (!($receiptFeatureStatus['enabled'] ?? false)): ?>
          <span class="badge text-bg-warning">Fitur database belum lengkap</span>
        <?php endif; ?>
      </div>
      <div class="jf-card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Label Pihak</label>
            <select name="party_title" class="form-select">
              <?php foreach ($receiptPartyTitleOptions as $value => $label): ?>
                <option value="<?= e((string) $value) ?>" <?= (string) ($receiptData['party_title'] ?? '') === (string) $value ? 'selected' : '' ?>><?= e((string) $label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label">Nama Pihak</label>
            <input type="text" name="party_name" class="form-control" maxlength="150" value="<?= e((string) ($receiptData['party_name'] ?? '')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Tujuan Transaksi</label>
            <input type="text" name="purpose" class="form-control" maxlength="255" value="<?= e((string) ($receiptData['purpose'] ?? '')) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Nominal Terbilang</label>
            <input type="text" name="amount_in_words" class="form-control" maxlength="255" value="<?= e((string) ($receiptData['amount_in_words'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Metode Pembayaran</label>
            <input type="text" name="payment_method" class="form-control" maxlength="50" value="<?= e((string) ($receiptData['payment_method'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Nomor Referensi</label>
            <input type="text" name="reference_no" class="form-control" maxlength="100" value="<?= e((string) ($receiptData['reference_no'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Catatan</label>
            <input type="text" name="notes" class="form-control" maxlength="1000" value="<?= e((string) ($receiptData['notes'] ?? '')) ?>">
          </div>
        </div>
      </div>
    </section>

    <section class="jf-card">
      <div class="jf-card-head">
        <div>
          <h2 class="h5 mb-1">Baris Jurnal</h2>
          <div class="jf-inline">Minimal 2 baris. Setiap baris cukup isi debit atau kredit. Klik <strong>Referensi</strong> bila jurnal perlu dihubungkan ke persediaan, aset, simpanan, atau komponen arus kas.</div>
        </div>
        <div class="jf-toolbar">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="add-line-btn">Tambah Baris</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="duplicate-last-line-btn">Duplikat Baris Terakhir</button>
        </div>
      </div>
      <div class="jf-card-body">
        <div class="jf-line-list" id="journal-line-list">
          <?php foreach ($detailRows as $index => $row): ?>
            <div class="jf-line-item" data-line-item>
              <div class="jf-line-top">
                <div class="fw-semibold">Baris <span class="line-number"><?= $index + 1 ?></span></div>
                <div class="jf-toolbar">
                  <button type="button" class="btn btn-sm btn-outline-info toggle-meta-btn">Referensi</button>
                  <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn">Hapus</button>
                </div>
              </div>
              <div class="jf-line-grid">
                <div>
                  <label class="form-label">Akun</label>
                  <div class="jf-account-stack">
                    <input type="search" class="form-control jf-account-search account-search-input" placeholder="Cari kode / nama akun" autocomplete="off">
                    <select name="coa_id[]" class="form-select account-select" required>
                      <option value="">Pilih Akun</option>
                      <?php foreach ($accountOptions as $account): ?>
                        <?php $accId = (string) ($account['id'] ?? ''); ?>
                        <option value="<?= e($accId) ?>" <?= (string) ($row['coa_id'] ?? '') === $accId ? 'selected' : '' ?>><?= e(trim((string) (($account['account_code'] ?? '') . ' - ' . ($account['account_name'] ?? '')))) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div class="jf-account-hint">Ketik untuk memfilter daftar akun.</div>
                  </div>
                </div>
                <div>
                  <label class="form-label">Uraian Baris</label>
                  <input type="text" name="line_description[]" class="form-control" maxlength="255" value="<?= e((string) ($row['line_description'] ?? '')) ?>">
                </div>
                <div>
                  <label class="form-label">Debit</label>
                  <input type="number" step="0.01" min="0" inputmode="decimal" name="debit[]" class="form-control text-end amount-input debit-input" value="<?= e((string) ($row['debit_raw'] ?? '')) ?>">
                </div>
                <div>
                  <label class="form-label">Kredit</label>
                  <input type="number" step="0.01" min="0" inputmode="decimal" name="credit[]" class="form-control text-end amount-input credit-input" value="<?= e((string) ($row['credit_raw'] ?? '')) ?>">
                </div>
              </div>
              <div class="jf-meta-grid is-hidden journal-meta-grid">
                <div>
                  <label class="form-label small jf-muted">Mitra</label>
                  <select name="partner_id[]" class="form-select form-select-sm partner-select">
                    <option value="">Tidak dipilih</option>
                    <?php foreach (($referenceOptions['partners'] ?? []) as $item): ?>
                      <option value="<?= e((string) $item['id']) ?>" <?= (string) ($row['partner_id'] ?? '') === (string) $item['id'] ? 'selected' : '' ?>><?= e((string) $item['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="form-label small jf-muted">Persediaan</label>
                  <select name="inventory_item_id[]" class="form-select form-select-sm inventory-select">
                    <option value="">Tidak dipilih</option>
                    <?php foreach (($referenceOptions['inventory'] ?? []) as $item): ?>
                      <option value="<?= e((string) $item['id']) ?>" <?= (string) ($row['inventory_item_id'] ?? '') === (string) $item['id'] ? 'selected' : '' ?>><?= e((string) $item['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="form-label small jf-muted">Bahan Baku</label>
                  <select name="raw_material_id[]" class="form-select form-select-sm raw-material-select">
                    <option value="">Tidak dipilih</option>
                    <?php foreach (($referenceOptions['raw_materials'] ?? []) as $item): ?>
                      <option value="<?= e((string) $item['id']) ?>" <?= (string) ($row['raw_material_id'] ?? '') === (string) $item['id'] ? 'selected' : '' ?>><?= e((string) $item['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="form-label small jf-muted">Aset</label>
                  <select name="asset_id[]" class="form-select form-select-sm asset-select">
                    <option value="">Tidak dipilih</option>
                    <?php foreach (($referenceOptions['assets'] ?? []) as $item): ?>
                      <option value="<?= e((string) $item['id']) ?>" <?= (string) ($row['asset_id'] ?? '') === (string) $item['id'] ? 'selected' : '' ?>><?= e((string) $item['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="form-label small jf-muted">Simpanan</label>
                  <select name="saving_account_id[]" class="form-select form-select-sm saving-select">
                    <option value="">Tidak dipilih</option>
                    <?php foreach (($referenceOptions['savings'] ?? []) as $item): ?>
                      <option value="<?= e((string) $item['id']) ?>" <?= (string) ($row['saving_account_id'] ?? '') === (string) $item['id'] ? 'selected' : '' ?>><?= e((string) $item['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="form-label small jf-muted">Komponen Arus Kas</label>
                  <select name="cashflow_component_id[]" class="form-select form-select-sm cashflow-select">
                    <option value="">Tidak dipilih</option>
                    <?php foreach (($referenceOptions['cashflow_components'] ?? []) as $item): ?>
                      <option value="<?= e((string) $item['id']) ?>" <?= (string) ($row['cashflow_component_id'] ?? '') === (string) $item['id'] ? 'selected' : '' ?>><?= e((string) $item['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="form-label small jf-muted">Tag Entri</label>
                  <select name="entry_tag[]" class="form-select form-select-sm entry-tag-select">
                    <?php foreach ($entryTags as $tagValue => $tagLabel): ?>
                      <option value="<?= e((string) $tagValue) ?>" <?= (string) ($row['entry_tag'] ?? '') === (string) $tagValue ? 'selected' : '' ?>><?= e((string) $tagLabel) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="jf-side-grid">
      <div class="jf-card">
        <div class="jf-card-head"><h2 class="h5 mb-0">Ringkasan &amp; Validasi</h2></div>
        <div class="jf-card-body d-grid gap-3">
          <div class="jf-summary-grid">
            <div class="jf-stat"><span class="label">Jumlah Baris</span><span class="value" id="summary-line-count">0</span></div>
            <div class="jf-stat"><span class="label">Status</span><span class="value" id="summary-balance-status">Belum seimbang</span></div>
            <div class="jf-stat"><span class="label">Total Debit</span><span class="value" id="summary-total-debit">Rp 0</span></div>
            <div class="jf-stat"><span class="label">Total Kredit</span><span class="value" id="summary-total-credit">Rp 0</span></div>
            <div class="jf-stat" style="grid-column:1/-1;"><span class="label">Selisih</span><span class="value" id="summary-difference">Rp 0</span></div>
          </div>
          <div class="jf-soft-note">
            <div class="fw-semibold mb-2">Checklist sebelum simpan</div>
            <ul class="jf-list" id="journal-validation-list"></ul>
          </div>
        </div>
      </div>

      <div class="jf-card">
        <div class="jf-card-head"><h2 class="h5 mb-0">Dampak ke Modul Lain</h2></div>
        <div class="jf-card-body">
          <ul class="jf-list" id="journal-impact-list"></ul>
        </div>
      </div>

      <div class="jf-card">
        <div class="jf-card-head"><h2 class="h5 mb-0">Draft Browser</h2></div>
        <div class="jf-card-body d-grid gap-3">
          <div class="jf-inline">Draft hanya tersimpan di browser perangkat ini. Cocok untuk input yang belum selesai.</div>
          <div class="jf-toolbar">
            <button type="button" class="btn btn-outline-secondary" id="save-draft-btn">Simpan Draft</button>
            <button type="button" class="btn btn-outline-secondary" id="load-draft-btn">Pulihkan Draft</button>
            <button type="button" class="btn btn-outline-danger" id="clear-draft-btn">Hapus Draft</button>
          </div>
          <div class="small jf-muted" id="draft-status">Belum ada status draft.</div>
        </div>
      </div>
    </section>

    <section class="jf-card">
      <div class="jf-card-body">
        <div class="jf-actions-row">
          <div class="jf-inline">Saat disimpan, jurnal akan memengaruhi modul sesuai akun dan referensi yang dipilih.</div>
          <div class="jf-toolbar">
            <button type="submit" class="btn btn-primary" id="submit-btn">Simpan Jurnal</button>
            <a href="<?= e($backUrl) ?>" class="btn btn-outline-secondary">Batal</a>
          </div>
        </div>
      </div>
    </section>
  </form>
</div>

<template id="journal-line-template">
  <div class="jf-line-item" data-line-item>
    <div class="jf-line-top">
      <div class="fw-semibold">Baris <span class="line-number">0</span></div>
      <div class="jf-toolbar">
        <button type="button" class="btn btn-sm btn-outline-info toggle-meta-btn">Referensi</button>
        <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn">Hapus</button>
      </div>
    </div>
    <div class="jf-line-grid">
      <div>
        <label class="form-label">Akun</label>
        <div class="jf-account-stack">
          <input type="search" class="form-control jf-account-search account-search-input" placeholder="Cari kode / nama akun" autocomplete="off">
          <select name="coa_id[]" class="form-select account-select" required></select>
          <div class="jf-account-hint">Ketik untuk memfilter daftar akun.</div>
        </div>
      </div>
      <div>
        <label class="form-label">Uraian Baris</label>
        <input type="text" name="line_description[]" class="form-control" maxlength="255">
      </div>
      <div>
        <label class="form-label">Debit</label>
        <input type="number" step="0.01" min="0" inputmode="decimal" name="debit[]" class="form-control text-end amount-input debit-input">
      </div>
      <div>
        <label class="form-label">Kredit</label>
        <input type="number" step="0.01" min="0" inputmode="decimal" name="credit[]" class="form-control text-end amount-input credit-input">
      </div>
    </div>
    <div class="jf-meta-grid is-hidden journal-meta-grid">
      <div><label class="form-label small jf-muted">Mitra</label><select name="partner_id[]" class="form-select form-select-sm partner-select"></select></div>
      <div><label class="form-label small jf-muted">Persediaan</label><select name="inventory_item_id[]" class="form-select form-select-sm inventory-select"></select></div>
      <div><label class="form-label small jf-muted">Bahan Baku</label><select name="raw_material_id[]" class="form-select form-select-sm raw-material-select"></select></div>
      <div><label class="form-label small jf-muted">Aset</label><select name="asset_id[]" class="form-select form-select-sm asset-select"></select></div>
      <div><label class="form-label small jf-muted">Simpanan</label><select name="saving_account_id[]" class="form-select form-select-sm saving-select"></select></div>
      <div><label class="form-label small jf-muted">Komponen Arus Kas</label><select name="cashflow_component_id[]" class="form-select form-select-sm cashflow-select"></select></div>
      <div><label class="form-label small jf-muted">Tag Entri</label><select name="entry_tag[]" class="form-select form-select-sm entry-tag-select"></select></div>
    </div>
  </div>
</template>

<script>
(() => {
  const form = document.getElementById('journal-form');
  if (!form) return;

  const accountOptions = <?= json_encode($accountJs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?> || [];
  const referenceOptions = <?= json_encode($referenceJs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?> || {};
  const journalNoPreviewMap = <?= json_encode($journalNoPreviewMap, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?> || {};
  const draftKey = <?= json_encode($draftStorageKey, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

  const lineList = document.getElementById('journal-line-list');
  const lineTemplate = document.getElementById('journal-line-template');
  const periodSelect = document.getElementById('period_id');
  const templateSelect = document.getElementById('print_template');
  const receiptCard = document.getElementById('receipt-card');
  const addLineBtn = document.getElementById('add-line-btn');
  const duplicateLastLineBtn = document.getElementById('duplicate-last-line-btn');
  const previewEl = document.getElementById('journal-number-preview');
  const validationList = document.getElementById('journal-validation-list');
  const impactList = document.getElementById('journal-impact-list');
  const draftStatus = document.getElementById('draft-status');

  function formatRupiah(num) {
    return 'Rp ' + Number(num || 0).toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2});
  }

  function setOptions(select, items, includeBlank = true) {
    if (!select) return;
    const current = String(select.value || '');
    const parts = [];
    if (includeBlank) parts.push('<option value="">Tidak dipilih</option>');
    (items || []).forEach((item) => {
      const value = String(item.id || '');
      const selected = current === value ? ' selected' : '';
      parts.push(`<option value="${value}"${selected}>${String(item.label || '')}</option>`);
    });
    select.innerHTML = parts.join('');
    if (current) select.value = current;
  }

  function setTagOptions(select) {
    if (!select) return;
    const current = String(select.value || '');
    const opts = referenceOptions.entry_tags || {'':'Tidak Spesifik'};
    select.innerHTML = Object.keys(opts).map((key) => `<option value="${key}"${current === String(key) ? ' selected' : ''}>${String(opts[key])}</option>`).join('');
    if (current) select.value = current;
  }

  function syncAccountSearchInput(item) {
    if (!item) return;
    const searchInput = item.querySelector('.account-search-input');
    const select = item.querySelector('.account-select');
    if (!searchInput || !select) return;
    const selectedOption = select.options[select.selectedIndex];
    if (document.activeElement === searchInput && searchInput.value.trim() !== '') return;
    searchInput.value = selectedOption && select.value ? selectedOption.textContent.trim() : '';
  }

  function setAccountOptions(select, searchTerm = '') {
    if (!select) return;
    const current = String(select.value || '');
    const term = String(searchTerm || '').trim().toLowerCase();
    const filteredAccounts = term === ''
      ? accountOptions.slice()
      : accountOptions.filter((item) => String(item.search || '').includes(term));
    const preferred = filteredAccounts.filter((item) => Number(item.is_suggested || 0) === 1).slice(0, 12);
    const other = filteredAccounts.filter((item) => Number(item.is_suggested || 0) !== 1);
    const chunks = ['<option value="">Pilih Akun</option>'];
    const pushGroup = (label, items) => {
      if (!items.length) return;
      chunks.push(`<optgroup label="${label}">`);
      items.forEach((item) => {
        const value = String(item.id || '');
        const selected = value === current ? ' selected' : '';
        chunks.push(`<option value="${value}"${selected}>${String(item.label || '')}</option>`);
      });
      chunks.push('</optgroup>');
    };
    pushGroup('Sering dipakai', preferred);
    pushGroup('Akun lain', other);
    select.innerHTML = chunks.join('');
    if (current) select.value = current;
  }

  function hydrateLineItem(item) {
    setAccountOptions(item.querySelector('.account-select'));
    syncAccountSearchInput(item);
    setOptions(item.querySelector('.partner-select'), referenceOptions.partners || []);
    setOptions(item.querySelector('.inventory-select'), referenceOptions.inventory || []);
    setOptions(item.querySelector('.raw-material-select'), referenceOptions.raw_materials || []);
    setOptions(item.querySelector('.asset-select'), referenceOptions.assets || []);
    setOptions(item.querySelector('.saving-select'), referenceOptions.savings || []);
    setOptions(item.querySelector('.cashflow-select'), referenceOptions.cashflow_components || []);
    setTagOptions(item.querySelector('.entry-tag-select'));
  }

  function refreshLineNumbers() {
    Array.from(lineList.querySelectorAll('[data-line-item]')).forEach((item, index) => {
      const label = item.querySelector('.line-number');
      if (label) label.textContent = String(index + 1);
    });
  }

  function parseAmount(value) {
    const normalized = String(value || '').replace(/,/g, '.').trim();
    const parsed = parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function selectedAccount(item) {
    const select = item.querySelector('.account-select');
    return accountOptions.find((acc) => String(acc.id) === String(select ? select.value : '')) || null;
  }

  function computeSummary() {
    const items = Array.from(lineList.querySelectorAll('[data-line-item]'));
    let debit = 0;
    let credit = 0;
    items.forEach((item) => {
      debit += parseAmount(item.querySelector('.debit-input')?.value);
      credit += parseAmount(item.querySelector('.credit-input')?.value);
    });
    const diff = debit - credit;
    document.getElementById('summary-line-count').textContent = String(items.length);
    document.getElementById('summary-total-debit').textContent = formatRupiah(debit);
    document.getElementById('summary-total-credit').textContent = formatRupiah(credit);
    document.getElementById('summary-difference').textContent = formatRupiah(Math.abs(diff));
    document.getElementById('summary-balance-status').textContent = Math.abs(diff) < 0.005 && debit > 0 && credit > 0 ? 'Seimbang' : 'Belum seimbang';
  }

  function computeValidation() {
    const items = Array.from(lineList.querySelectorAll('[data-line-item]'));
    const issues = [];
    const dateValue = document.getElementById('journal_date')?.value || '';
    const periodValue = periodSelect?.value || '';
    const descriptionValue = document.getElementById('description')?.value.trim() || '';
    if (!dateValue) issues.push('Tanggal jurnal wajib diisi.');
    if (!periodValue) issues.push('Periode akuntansi wajib dipilih.');
    if (descriptionValue.length < 3) issues.push('Keterangan jurnal minimal 3 karakter.');
    if (items.length < 2) issues.push('Minimal harus ada 2 baris jurnal.');

    let debit = 0; let credit = 0; let debitLines = 0; let creditLines = 0;
    items.forEach((item, index) => {
      const account = item.querySelector('.account-select')?.value || '';
      const d = parseAmount(item.querySelector('.debit-input')?.value);
      const c = parseAmount(item.querySelector('.credit-input')?.value);
      if (!account) issues.push(`Baris ${index + 1}: akun belum dipilih.`);
      if ((d > 0 && c > 0) || (d <= 0 && c <= 0)) issues.push(`Baris ${index + 1}: isi salah satu, debit atau kredit.`);
      if (d > 0) debitLines += 1;
      if (c > 0) creditLines += 1;
      debit += d; credit += c;
    });
    if (debitLines === 0 || creditLines === 0) issues.push('Harus ada minimal satu baris debit dan satu baris kredit.');
    if (Math.abs(debit - credit) >= 0.005) issues.push('Total debit harus sama dengan total kredit.');

    validationList.innerHTML = issues.length
      ? issues.map((msg) => `<li>${msg}</li>`).join('')
      : '<li>Form sudah siap disimpan. Pastikan akun, nominal, dan referensi sudah benar.</li>';
    return issues;
  }

  function computeImpact() {
    const items = Array.from(lineList.querySelectorAll('[data-line-item]'));
    const effects = new Set();
    let hasCash = false;
    items.forEach((item) => {
      const acc = selectedAccount(item);
      const accountName = String(acc?.name || '').toLowerCase();
      const accountType = String(acc?.type || '').toLowerCase();
      const partner = item.querySelector('.partner-select')?.value || '';
      const inventory = item.querySelector('.inventory-select')?.value || '';
      const rawMaterial = item.querySelector('.raw-material-select')?.value || '';
      const asset = item.querySelector('.asset-select')?.value || '';
      const saving = item.querySelector('.saving-select')?.value || '';
      const cashflow = item.querySelector('.cashflow-select')?.value || '';
      const entryTag = item.querySelector('.entry-tag-select')?.value || '';

      if (/kas|bank/.test(accountName)) { hasCash = true; effects.add('Akan memengaruhi saldo kas / bank dan kemungkinan masuk Arus Kas.'); }
      if (/piutang/.test(accountName) || partner) effects.add('Dapat memengaruhi Buku Pembantu Piutang / mitra bila akun terkait piutang dipakai.');
      if (/utang/.test(accountName) || partner) effects.add('Dapat memengaruhi Buku Pembantu Utang / kreditur bila akun terkait utang dipakai.');
      if (inventory || rawMaterial || /persediaan|hpp|bahan baku/.test(accountName)) effects.add('Dapat memengaruhi modul persediaan / bahan baku bila referensinya digunakan.');
      if (asset || /aset|penyusutan/.test(accountName)) effects.add('Dapat memengaruhi modul aset tetap bila akun atau referensi aset dipakai.');
      if (saving || /simpanan/.test(accountName)) effects.add('Dapat memengaruhi modul simpanan bila referensi simpanan dipakai.');
      if (cashflow) effects.add('Komponen arus kas dipilih manual, sehingga laporan arus kas dapat membaca jurnal ini lebih presisi.');
      if (entryTag === 'SALDO_AWAL' || entryTag === 'PEMBUKAAN') effects.add('Jurnal ditandai sebagai saldo awal / pembukaan. Pastikan tidak diperlakukan sebagai transaksi operasional biasa.');
      if (entryTag === 'PENYESUAIAN' || entryTag === 'PENUTUPAN') effects.add('Jurnal penyesuaian / penutupan dapat memengaruhi laporan akhir periode dan perubahan ekuitas.');
      if (/pendapatan|penjualan/.test(accountName) || /expense|beban|biaya/.test(accountType + ' ' + accountName)) effects.add('Jurnal ini berpotensi memengaruhi Laba Rugi.');
      if (/modal|ekuitas|laba ditahan/.test(accountName)) effects.add('Jurnal ini berpotensi memengaruhi Perubahan Ekuitas.');
    });
    if (!items.length) effects.add('Belum ada baris jurnal untuk dianalisis.');
    if (!hasCash) effects.add('Jika tidak ada akun kas / bank, jurnal ini umumnya tidak akan masuk ke laporan Arus Kas.');
    impactList.innerHTML = Array.from(effects).map((msg) => `<li>${msg}</li>`).join('');
  }

  function updatePreviewNumber() {
    const preview = journalNoPreviewMap[String(periodSelect?.value || '')] || 'Otomatis saat disimpan';
    if (previewEl) previewEl.textContent = preview;
  }

  function updateReceiptPanel() {
    if (!templateSelect || !receiptCard) return;
    receiptCard.style.display = templateSelect.value === 'receipt' ? '' : 'none';
  }

  function updateAll() {
    refreshLineNumbers();
    computeSummary();
    computeValidation();
    computeImpact();
    updatePreviewNumber();
    updateReceiptPanel();
  }

  function makeLineItemFromData(data = {}) {
    const node = lineTemplate.content.firstElementChild.cloneNode(true);
    hydrateLineItem(node);
    node.querySelector('.account-select').value = String(data.coa_id || '');
    node.querySelector('input[name="line_description[]"]').value = String(data.line_description || '');
    node.querySelector('input[name="debit[]"]').value = String(data.debit_raw || '');
    node.querySelector('input[name="credit[]"]').value = String(data.credit_raw || '');
    node.querySelector('.partner-select').value = String(data.partner_id || '');
    node.querySelector('.inventory-select').value = String(data.inventory_item_id || '');
    node.querySelector('.raw-material-select').value = String(data.raw_material_id || '');
    node.querySelector('.asset-select').value = String(data.asset_id || '');
    node.querySelector('.saving-select').value = String(data.saving_account_id || '');
    node.querySelector('.cashflow-select').value = String(data.cashflow_component_id || '');
    node.querySelector('.entry-tag-select').value = String(data.entry_tag || '');
    syncAccountSearchInput(node);
    return node;
  }

  function collectFormData() {
    return {
      journal_date: document.getElementById('journal_date')?.value || '',
      period_id: periodSelect?.value || '',
      business_unit_id: document.getElementById('business_unit_id')?.value || '',
      print_template: templateSelect?.value || 'standard',
      description: document.getElementById('description')?.value || '',
      receipt: {
        party_title: form.querySelector('[name="party_title"]')?.value || '',
        party_name: form.querySelector('[name="party_name"]')?.value || '',
        purpose: form.querySelector('[name="purpose"]')?.value || '',
        amount_in_words: form.querySelector('[name="amount_in_words"]')?.value || '',
        payment_method: form.querySelector('[name="payment_method"]')?.value || '',
        reference_no: form.querySelector('[name="reference_no"]')?.value || '',
        notes: form.querySelector('[name="notes"]')?.value || ''
      },
      detail_rows: Array.from(lineList.querySelectorAll('[data-line-item]')).map((item) => ({
        coa_id: item.querySelector('.account-select')?.value || '',
        line_description: item.querySelector('input[name="line_description[]"]')?.value || '',
        debit_raw: item.querySelector('input[name="debit[]"]')?.value || '',
        credit_raw: item.querySelector('input[name="credit[]"]')?.value || '',
        partner_id: item.querySelector('.partner-select')?.value || '',
        inventory_item_id: item.querySelector('.inventory-select')?.value || '',
        raw_material_id: item.querySelector('.raw-material-select')?.value || '',
        asset_id: item.querySelector('.asset-select')?.value || '',
        saving_account_id: item.querySelector('.saving-select')?.value || '',
        cashflow_component_id: item.querySelector('.cashflow-select')?.value || '',
        entry_tag: item.querySelector('.entry-tag-select')?.value || '',
      }))
    };
  }

  function applyDraft(data) {
    if (!data || typeof data !== 'object') return;
    const dateField = document.getElementById('journal_date');
    const unitField = document.getElementById('business_unit_id');
    const descField = document.getElementById('description');
    if (dateField) dateField.value = data.journal_date || '';
    if (periodSelect) periodSelect.value = data.period_id || '';
    if (unitField) unitField.value = data.business_unit_id || '';
    if (templateSelect) templateSelect.value = data.print_template || 'standard';
    if (descField) descField.value = data.description || '';
    Object.entries(data.receipt || {}).forEach(([key, value]) => {
      const field = form.querySelector(`[name="${key}"]`);
      if (field) field.value = value || '';
    });
    lineList.innerHTML = '';
    (Array.isArray(data.detail_rows) && data.detail_rows.length ? data.detail_rows : [{}, {}]).forEach((row) => lineList.appendChild(makeLineItemFromData(row)));
    updateAll();
  }

  function saveDraft() {
    localStorage.setItem(draftKey, JSON.stringify({ saved_at: new Date().toISOString(), data: collectFormData() }));
    draftStatus.textContent = 'Draft tersimpan di browser pada ' + new Date().toLocaleString('id-ID');
  }

  function loadDraft() {
    const raw = localStorage.getItem(draftKey);
    if (!raw) {
      draftStatus.textContent = 'Draft belum tersedia untuk form ini.';
      return;
    }
    try {
      const parsed = JSON.parse(raw);
      applyDraft(parsed.data || {});
      draftStatus.textContent = 'Draft berhasil dipulihkan.';
    } catch (error) {
      draftStatus.textContent = 'Draft tidak dapat dibaca.';
    }
  }

  function clearDraft() {
    localStorage.removeItem(draftKey);
    draftStatus.textContent = 'Draft browser dihapus.';
  }

  lineList.querySelectorAll('[data-line-item]').forEach(hydrateLineItem);
  updateAll();

  addLineBtn?.addEventListener('click', () => {
    lineList.appendChild(makeLineItemFromData({}));
    updateAll();
  });

  duplicateLastLineBtn?.addEventListener('click', () => {
    const items = Array.from(lineList.querySelectorAll('[data-line-item]'));
    const last = items[items.length - 1];
    if (!last) return;
    lineList.appendChild(makeLineItemFromData({
      coa_id: last.querySelector('.account-select')?.value || '',
      line_description: last.querySelector('input[name="line_description[]"]')?.value || '',
      debit_raw: '',
      credit_raw: '',
      partner_id: last.querySelector('.partner-select')?.value || '',
      inventory_item_id: last.querySelector('.inventory-select')?.value || '',
      raw_material_id: last.querySelector('.raw-material-select')?.value || '',
      asset_id: last.querySelector('.asset-select')?.value || '',
      saving_account_id: last.querySelector('.saving-select')?.value || '',
      cashflow_component_id: last.querySelector('.cashflow-select')?.value || '',
      entry_tag: last.querySelector('.entry-tag-select')?.value || ''
    }));
    updateAll();
  });

  lineList.addEventListener('click', (event) => {
    const item = event.target.closest('[data-line-item]');
    if (!item) return;
    if (event.target.closest('.toggle-meta-btn')) {
      item.querySelector('.journal-meta-grid')?.classList.toggle('is-hidden');
    }
    if (event.target.closest('.remove-line-btn')) {
      if (lineList.querySelectorAll('[data-line-item]').length <= 2) return;
      item.remove();
      updateAll();
    }
  });

  lineList.addEventListener('input', (event) => {
    const item = event.target.closest('[data-line-item]');
    if (!item) return;
    if (event.target.classList.contains('account-search-input')) {
      const select = item.querySelector('.account-select');
      const current = select ? select.value : '';
      setAccountOptions(select, event.target.value || '');
      if (select && current) select.value = current;
      return;
    }
    if (event.target.classList.contains('debit-input') && parseAmount(event.target.value) > 0) {
      const credit = item.querySelector('.credit-input');
      if (credit) credit.value = '';
    }
    if (event.target.classList.contains('credit-input') && parseAmount(event.target.value) > 0) {
      const debit = item.querySelector('.debit-input');
      if (debit) debit.value = '';
    }
    updateAll();
  });

  lineList.addEventListener('change', (event) => {
    const item = event.target.closest('[data-line-item]');
    if (item && event.target.classList.contains('account-select')) {
      syncAccountSearchInput(item);
    }
    updateAll();
  });
  periodSelect?.addEventListener('change', updateAll);
  templateSelect?.addEventListener('change', updateAll);
  document.getElementById('journal_date')?.addEventListener('change', updateAll);
  document.getElementById('description')?.addEventListener('input', updateAll);

  document.getElementById('save-draft-btn')?.addEventListener('click', saveDraft);
  document.getElementById('load-draft-btn')?.addEventListener('click', loadDraft);
  document.getElementById('clear-draft-btn')?.addEventListener('click', clearDraft);

  form.addEventListener('submit', (event) => {
    const issues = computeValidation();
    if (issues.length > 0) {
      event.preventDefault();
      validationList.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

  const existingDraft = localStorage.getItem(draftKey);
  if (existingDraft) {
    try {
      const parsed = JSON.parse(existingDraft);
      if (parsed?.saved_at) {
        draftStatus.textContent = 'Draft tersedia dari ' + new Date(parsed.saved_at).toLocaleString('id-ID');
      }
    } catch (error) {
      draftStatus.textContent = 'Draft browser tersedia.';
    }
  }
})();
</script>
