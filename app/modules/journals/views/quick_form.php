<?php declare(strict_types=1); ?>
<?php
$template = is_array($template ?? null) ? $template : journal_quick_template_data('cash_in');
$templateOptions = is_array($templateOptions ?? null) ? $templateOptions : journal_quick_template_options();
$favoriteTemplates = is_array($favoriteTemplates ?? null) ? $favoriteTemplates : [];
$formData = is_array($formData ?? null) ? $formData : [];
$periodOptions = is_array($periodOptions ?? null) ? $periodOptions : [];
$unitOptions = is_array($unitOptions ?? null) ? $unitOptions : [];
$accountOptions = is_array($accountOptions ?? null) ? $accountOptions : [];
$cashflowComponentOptions = is_array($cashflowComponentOptions ?? null) ? $cashflowComponentOptions : [];
$preview = is_array($preview ?? null) ? $preview : null;
$attachmentFeatureStatus = is_array($attachmentFeatureStatus ?? null) ? $attachmentFeatureStatus : ['enabled' => false];
$favoriteMap = [];
foreach ($favoriteTemplates as $item) {
    $favoriteMap[(string) ($item['template_key'] ?? '')] = true;
}
$selectedTemplateKey = (string) ($formData['template_key'] ?? ($template['template_key'] ?? 'cash_in'));
$suggestionText = match ($selectedTemplateKey) {
    'cash_in', 'revenue' => 'Pilih akun kas/bank di sisi debit dan akun pendapatan/piutang/modal di sisi kredit.',
    'cash_out', 'expense' => 'Pilih akun beban/aset di sisi debit dan akun kas/bank di sisi kredit.',
    'transfer' => 'Gunakan dua akun kas/bank berbeda untuk perpindahan internal.',
    'asset' => 'Gunakan akun aset/persediaan di debit dan kas/utang di kredit.',
    'opening' => 'Gunakan untuk penyesuaian atau saldo awal dengan memilih dua akun yang tepat.',
    default => 'Pilih akun debit dan kredit yang sesuai, lalu cek preview sebelum simpan.',
};
$optionalPanelOpen = trim((string) ($formData['party_name'] ?? '')) !== ''
    || trim((string) ($formData['reference_no'] ?? '')) !== ''
    || trim((string) ($formData['attachment_title'] ?? '')) !== ''
    || trim((string) ($formData['cashflow_component_id'] ?? '')) !== '';
$accountJs = array_map(static function (array $account): array {
    $label = trim((string) (($account['account_code'] ?? '') . ' - ' . ($account['account_name'] ?? '')));
    $type = trim((string) ($account['account_type'] ?? ''));
    $fullLabel = $type !== '' ? ($label . ' (' . $type . ')') : $label;

    return [
        'id' => (string) ($account['id'] ?? ''),
        'code' => (string) ($account['account_code'] ?? ''),
        'name' => (string) ($account['account_name'] ?? ''),
        'type' => $type,
        'label' => $fullLabel,
        'search' => function_exists('mb_strtolower')
            ? mb_strtolower($fullLabel)
            : strtolower($fullLabel),
        'is_suggested' => (int) ($account['is_suggested'] ?? 0),
    ];
}, $accountOptions);
?>

<style>
.qj-shell {
    max-width: 1080px;
    margin: 0 auto;
    display: grid;
    gap: 1rem;
    padding-bottom: .5rem;
}
.qj-card {
    background: var(--bg-panel, #fff);
    border: 1px solid var(--border-soft, #dbe5f2);
    border-radius: 16px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
    overflow: hidden;
}
.qj-card__head {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-soft, #e8eef7);
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}
.qj-card__body { padding: 1.1rem 1.25rem 1.25rem; }
.qj-page-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.qj-head-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.qj-template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: .7rem;
}
.qj-template-item {
    display: block;
    text-decoration: none;
    color: inherit;
    border: 1px solid var(--border-soft, #dbe5f2);
    border-radius: 12px;
    padding: .85rem .9rem;
    background: var(--bg-panel-soft, #fbfdff);
}
.qj-template-item:hover { border-color: #93c5fd; background: #f8fbff; }
.qj-template-item.is-active { border-color: #0ea5e9; background: #ecfeff; color: #075985; }
.qj-help {
    border-left: 4px solid #0ea5e9;
    background: #ecfeff;
    color: #155e75;
    border-radius: 10px;
    padding: .85rem 1rem;
}
.qj-preview-line {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: .75rem 0;
    border-bottom: 1px dashed var(--border-soft, #dbe5f2);
}
.qj-preview-line:last-child { border-bottom: 0; padding-bottom: 0; }
.qj-pill-list { display: flex; flex-wrap: wrap; gap: .5rem; }
.qj-pill {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .5rem .8rem;
    border: 1px solid var(--border-soft, #dbe5f2);
    border-radius: 999px;
    background: var(--bg-panel, #fff);
    text-decoration: none;
    color: #334155;
}
.qj-pill:hover { border-color: #93c5fd; background: #eff6ff; color: #1d4ed8; }
.qj-form-grid { display: grid; gap: 1rem; }
.qj-primary-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
}
.qj-span-3 { grid-column: span 3; }
.qj-span-4 { grid-column: span 4; }
.qj-span-6 { grid-column: span 6; }
.qj-span-8 { grid-column: span 8; }
.qj-span-12 { grid-column: 1 / -1; }
.qj-account-field { display: grid; gap: .35rem; }
.qj-account-trigger {
    display: none;
    width: 100%;
    min-height: 48px;
    padding: .72rem .85rem;
    border: 1px solid var(--border-soft, #dbe5f2);
    border-radius: 10px;
    background: var(--bg-panel, #fff);
    color: var(--text-main, #13233f);
    text-align: left;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
}
.qj-account-trigger__text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.qj-optional-toggle {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-height: 46px;
}
.qj-optional-panel {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
    padding-top: .25rem;
}
.qj-optional-panel[hidden] { display: none !important; }
.qj-action-card {
    position: sticky;
    bottom: 1rem;
    z-index: 5;
}
.qj-actions-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.qj-live-summary { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.qj-action-buttons .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
}
.qj-status-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem .7rem;
    border-radius: 999px;
    border: 1px solid rgba(14, 165, 233, .28);
    background: #ecfeff;
    color: #075985;
    font-weight: 700;
}
.qj-total-text { color: var(--text-muted, #697c9f); font-size: .9rem; }
.qj-sheet-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1060;
    background: rgba(15, 23, 42, .42);
    opacity: 0;
    pointer-events: none;
    transition: opacity .18s ease;
}
.qj-sheet {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1061;
    max-height: min(78vh, 680px);
    display: grid;
    grid-template-rows: auto auto minmax(0, 1fr);
    gap: .8rem;
    padding: .95rem .95rem calc(1rem + env(safe-area-inset-bottom, 0px));
    border-radius: 18px 18px 0 0;
    background: var(--bg-panel, #fff);
    box-shadow: 0 -24px 60px rgba(15, 23, 42, .22);
    transform: translateY(105%);
    transition: transform .22s ease;
}
.qj-sheet.is-open { transform: translateY(0); }
.qj-sheet-backdrop.is-open { opacity: 1; pointer-events: auto; }
.qj-sheet__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}
.qj-sheet__title { font-weight: 800; color: var(--text-main, #13233f); }
.qj-sheet__list {
    min-height: 0;
    overflow: auto;
    display: grid;
    gap: .45rem;
    padding-right: .1rem;
}
.qj-account-option {
    width: 100%;
    border: 1px solid var(--border-soft, #dbe5f2);
    border-radius: 10px;
    background: var(--bg-panel-soft, #f8fbff);
    padding: .75rem .85rem;
    text-align: left;
    color: var(--text-main, #13233f);
}
.qj-account-option:hover,
.qj-account-option:focus { border-color: #38bdf8; background: #ecfeff; }
.qj-account-option__name { display: block; font-weight: 750; }
.qj-account-option__meta { display: block; margin-top: .12rem; font-size: .78rem; color: var(--text-muted, #697c9f); }
.qj-sheet-empty {
    padding: 1rem;
    border: 1px dashed var(--border-soft, #dbe5f2);
    border-radius: 10px;
    color: var(--text-muted, #697c9f);
    text-align: center;
}
body.qj-sheet-open { overflow: hidden; }

@media (max-width: 991.98px) {
    body.route-journals-quick .app-frame {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    body.route-journals-quick .app-main {
        width: 100vw !important;
        max-width: 100vw !important;
        margin-left: 0 !important;
        scrollbar-gutter: auto !important;
    }
    body.route-journals-quick .app-topbar {
        width: 100% !important;
        padding: .45rem 8px .55rem !important;
    }
    body.route-journals-quick .app-topbar__inner {
        width: 100% !important;
        max-width: 100% !important;
        min-height: 52px !important;
        margin: 0 !important;
        padding: .45rem .55rem !important;
        border-radius: 16px !important;
    }
    body.route-journals-quick .app-content {
        width: 100% !important;
        max-width: 100% !important;
        padding: .45rem 8px 7rem !important;
    }
    body.route-journals-quick .content-wrap,
    body.route-journals-quick .content-wrap.container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .qj-shell {
        max-width: 100%;
        gap: .65rem;
        padding-bottom: 6rem;
    }
    .qj-page-head {
        padding: .1rem 2px 0;
        align-items: flex-start;
        gap: .7rem;
    }
    .qj-page-head h1 {
        font-size: 1.2rem;
        line-height: 1.15;
        margin-bottom: .25rem !important;
    }
    .qj-page-head p {
        max-width: 21rem;
        font-size: .82rem;
        line-height: 1.45;
    }
    .qj-head-actions .btn { min-height: 42px; }
    .qj-card {
        border-radius: 12px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .05);
    }
    .qj-card__head,
    .qj-card__body { padding: .78rem; }
    .qj-card__head h2 {
        font-size: 1rem;
        line-height: 1.25;
    }
    .qj-card__head .small,
    .qj-card__body .small,
    .qj-card__body .form-text {
        font-size: .78rem;
    }
    .qj-help {
        padding: .65rem .75rem;
        border-radius: 8px;
    }
    .qj-template-grid {
        display: flex;
        overflow-x: auto;
        padding-bottom: .1rem;
        scroll-snap-type: x proximity;
    }
    .qj-template-item {
        flex: 0 0 132px;
        scroll-snap-align: start;
        padding: .64rem .68rem;
        border-radius: 10px;
        font-size: .88rem;
        line-height: 1.3;
    }
    .qj-template-item .small { display: none; }
    .qj-primary-grid,
    .qj-optional-panel { grid-template-columns: 1fr; gap: .7rem; }
    .qj-span-3,
    .qj-span-4,
    .qj-span-6,
    .qj-span-8,
    .qj-span-12 { grid-column: 1 / -1; }
    .qj-form-grid { gap: .65rem; }
    .qj-form-grid .form-label {
        margin-bottom: .28rem;
        font-size: .82rem;
    }
    .qj-form-grid .form-control,
    .qj-form-grid .form-select {
        min-height: 42px;
        border-radius: 10px;
        font-size: .92rem;
        padding: .52rem .68rem;
    }
    .qj-account-trigger {
        display: flex;
        min-height: 42px;
        padding: .52rem .68rem;
        font-size: .92rem;
    }
    .qj-account-select {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: .01;
        pointer-events: none;
    }
    .qj-action-card {
        position: fixed;
        left: 8px;
        right: 8px;
        bottom: calc(86px + env(safe-area-inset-bottom, 0px));
        border-radius: 12px;
        z-index: 1034;
    }
    .qj-action-card .qj-card__body {
        padding: .7rem;
    }
    .qj-actions-row { align-items: stretch; }
    .qj-live-summary { width: 100%; justify-content: space-between; }
    .qj-status-pill {
        padding: .42rem .6rem;
        font-size: .86rem;
    }
    .qj-total-text { font-size: .84rem; }
    .qj-action-buttons {
        width: 100%;
        display: grid !important;
        grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
        gap: .5rem !important;
    }
    .qj-action-buttons .btn {
        width: 100%;
        min-height: 42px;
        border-radius: 10px;
        white-space: nowrap;
        font-size: .88rem;
        padding: .5rem .6rem;
    }
}

@media (max-width: 390px) {
    .qj-head-actions { width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
    .qj-head-actions .btn { width: 100%; }
    .qj-status-pill,
    .qj-total-text { font-size: .82rem; }
}
</style>

<div class="qj-shell">
    <div class="qj-page-head">
        <div>
            <h1 class="h3 mb-1">Transaksi Cepat</h1>
            <p class="text-secondary mb-0">Input transaksi, cek preview, lalu simpan sebagai jurnal.</p>
        </div>
        <div class="qj-head-actions">
            <a href="<?= e(base_url('/journals')) ?>" class="btn btn-outline-light">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">Jurnal</span>
            </a>
            <a href="<?= e(base_url('/journals/create?template=' . urlencode((string) ($template['template_key'] ?? 'revenue')))) ?>" class="btn btn-outline-info">
                <i class="bi bi-journal-plus" aria-hidden="true"></i>
                <span>Lengkap</span>
            </a>
        </div>
    </div>

    <section class="qj-card">
        <div class="qj-card__body">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                <div>
                    <div class="fw-semibold">Pilih template transaksi</div>
                    <div class="text-secondary small">Pilih alur transaksi yang paling dekat.</div>
                </div>
                <form method="post" action="<?= e(base_url('/journals/quick/favorite-template')) ?>" class="m-0">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="template_key" value="<?= e((string) ($template['template_key'] ?? 'cash_in')) ?>">
                    <button type="submit" class="btn btn-sm <?= isset($favoriteMap[(string) ($template['template_key'] ?? '')]) ? 'btn-warning' : 'btn-outline-warning' ?>">
                        <?= isset($favoriteMap[(string) ($template['template_key'] ?? '')]) ? 'Hapus Favorit' : 'Favorit' ?>
                    </button>
                </form>
            </div>

            <?php if ($favoriteTemplates !== []): ?>
                <div class="mb-3">
                    <div class="small text-secondary mb-2">Favorit Anda</div>
                    <div class="qj-pill-list">
                        <?php foreach ($favoriteTemplates as $item): ?>
                            <a class="qj-pill" href="<?= e(base_url('/journals/quick?template=' . urlencode((string) ($item['template_key'] ?? 'cash_in')))) ?>"><?= e((string) ($item['label'] ?? $item['template_key'] ?? 'Template')) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="qj-template-grid">
                <?php foreach ($templateOptions as $templateKey => $option): ?>
                    <a href="<?= e(base_url('/journals/quick?template=' . urlencode((string) $templateKey))) ?>" class="qj-template-item <?= $selectedTemplateKey === (string) $templateKey ? 'is-active' : '' ?>">
                        <div class="fw-semibold mb-1"><?= e((string) ($option['label'] ?? $templateKey)) ?></div>
                        <div class="small text-secondary"><?= e((string) ($option['description'] ?? '')) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <form method="post" action="<?= e(base_url('/journals/quick')) ?>" enctype="multipart/form-data" class="qj-form-grid" id="quick-journal-form">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="template_key" value="<?= e($selectedTemplateKey) ?>">

        <section class="qj-card">
            <div class="qj-card__head">
                <div>
                    <h2 class="h5 mb-1"><?= e((string) ($template['template_name'] ?? 'Transaksi Cepat')) ?></h2>
                    <div class="text-secondary small"><?= e((string) ($template['description'] ?? '')) ?></div>
                </div>
                <div class="badge text-bg-light border">Nomor otomatis</div>
            </div>
            <div class="qj-card__body">
                <div class="qj-help mb-3">
                    <div class="fw-semibold mb-1">Akun</div>
                    <div class="small"><?= e($suggestionText) ?></div>
                </div>

                <div class="qj-primary-grid">
                    <div class="qj-span-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="journal_date" class="form-control" value="<?= e((string) ($formData['journal_date'] ?? date('Y-m-d'))) ?>" required>
                    </div>
                    <div class="qj-span-3">
                        <label class="form-label">Periode</label>
                        <select name="period_id" class="form-select" required>
                            <option value="">Pilih periode</option>
                            <?php foreach ($periodOptions as $period): ?>
                                <option value="<?= e((string) ($period['id'] ?? '')) ?>" <?= (string) ($formData['period_id'] ?? '') === (string) ($period['id'] ?? '') ? 'selected' : '' ?>><?= e((string) (($period['period_code'] ?? '') . ' - ' . ($period['period_name'] ?? ''))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="qj-span-3">
                        <label class="form-label">Unit Usaha</label>
                        <select name="business_unit_id" class="form-select">
                            <option value="">Semua unit</option>
                            <?php foreach ($unitOptions as $unit): ?>
                                <option value="<?= e((string) ($unit['id'] ?? '')) ?>" <?= (string) ($formData['business_unit_id'] ?? '') === (string) ($unit['id'] ?? '') ? 'selected' : '' ?>><?= e((string) (($unit['unit_code'] ?? '') . ' - ' . ($unit['unit_name'] ?? ''))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="qj-span-3">
                        <label class="form-label">Nominal</label>
                        <input type="text" name="amount" inputmode="decimal" class="form-control" value="<?= e((string) ($formData['amount'] ?? '')) ?>" placeholder="Contoh: 1500000" required>
                    </div>
                    <div class="qj-span-12">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="description" class="form-control" value="<?= e((string) ($formData['description'] ?? '')) ?>" placeholder="Contoh: Pembayaran listrik kantor bulan April" required>
                        <div class="form-text">Dipakai sebagai deskripsi jurnal.</div>
                    </div>
                    <div class="qj-span-6 qj-account-field">
                        <label class="form-label">Akun Debit</label>
                        <button type="button" class="qj-account-trigger" data-qj-account-trigger="debit_account_id">
                            <span class="qj-account-trigger__text">Pilih akun debit</span>
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </button>
                        <select name="debit_account_id" class="form-select qj-account-select" required>
                            <option value="">Pilih akun debit</option>
                            <?php foreach ($accountOptions as $account): ?>
                                <option value="<?= e((string) ($account['id'] ?? '')) ?>" <?= (string) ($formData['debit_account_id'] ?? '') === (string) ($account['id'] ?? '') ? 'selected' : '' ?>><?= e((string) (($account['account_code'] ?? '') . ' - ' . ($account['account_name'] ?? '') . ' (' . ($account['account_type'] ?? '') . ')')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="qj-span-6 qj-account-field">
                        <label class="form-label">Akun Kredit</label>
                        <button type="button" class="qj-account-trigger" data-qj-account-trigger="credit_account_id">
                            <span class="qj-account-trigger__text">Pilih akun kredit</span>
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </button>
                        <select name="credit_account_id" class="form-select qj-account-select" required>
                            <option value="">Pilih akun kredit</option>
                            <?php foreach ($accountOptions as $account): ?>
                                <option value="<?= e((string) ($account['id'] ?? '')) ?>" <?= (string) ($formData['credit_account_id'] ?? '') === (string) ($account['id'] ?? '') ? 'selected' : '' ?>><?= e((string) (($account['account_code'] ?? '') . ' - ' . ($account['account_name'] ?? '') . ' (' . ($account['account_type'] ?? '') . ')')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <section class="qj-card">
            <div class="qj-card__body">
                <button type="button" class="btn btn-outline-secondary qj-optional-toggle" id="qj-optional-toggle" aria-expanded="<?= $optionalPanelOpen ? 'true' : 'false' ?>" aria-controls="qj-optional-panel">
                    <span><i class="bi bi-sliders me-1" aria-hidden="true"></i>Detail opsional</span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
                <div class="qj-optional-panel mt-3" id="qj-optional-panel" <?= $optionalPanelOpen ? '' : 'hidden' ?>>
                    <div class="qj-span-6">
                        <label class="form-label">Nama Pihak</label>
                        <input type="text" name="party_name" class="form-control" value="<?= e((string) ($formData['party_name'] ?? '')) ?>" placeholder="Opsional">
                    </div>
                    <div class="qj-span-6">
                        <label class="form-label">Nomor Referensi</label>
                        <input type="text" name="reference_no" class="form-control" value="<?= e((string) ($formData['reference_no'] ?? '')) ?>" placeholder="Opsional">
                    </div>
                    <div class="qj-span-6">
                        <label class="form-label">Komponen Laporan Arus Kas</label>
                        <select name="cashflow_component_id" class="form-select">
                            <option value="">Tidak masuk / klasifikasi otomatis</option>
                            <?php foreach ($cashflowComponentOptions as $component): ?>
                                <option value="<?= e((string) ($component['id'] ?? '')) ?>" <?= (string) ($formData['cashflow_component_id'] ?? '') === (string) ($component['id'] ?? '') ? 'selected' : '' ?>><?= e((string) (($component['code'] ?? '') . ' - ' . ($component['name'] ?? ''))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Opsional untuk klasifikasi arus kas.</div>
                    </div>
                    <div class="qj-span-6">
                        <label class="form-label">Judul Lampiran</label>
                        <input type="text" name="attachment_title" class="form-control" value="<?= e((string) ($formData['attachment_title'] ?? '')) ?>" placeholder="Opsional">
                    </div>
                    <div class="qj-span-12">
                        <label class="form-label">Lampiran Bukti</label>
                        <input type="file" name="attachment_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        <div class="form-text"><?= !empty($attachmentFeatureStatus['enabled']) ? 'File akan menempel ke jurnal.' : 'Lampiran belum aktif di database.' ?></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="qj-card qj-action-card">
            <div class="qj-card__body">
                <div class="qj-actions-row">
                    <div class="qj-live-summary">
                        <span class="qj-status-pill" id="qj-live-status"><i class="bi bi-check2-circle" aria-hidden="true"></i>Debit = Kredit</span>
                        <span class="qj-total-text">Nominal: <strong id="qj-live-amount">Rp 0</strong></span>
                    </div>
                    <div class="d-flex justify-content-end gap-2 qj-action-buttons">
                        <button type="submit" name="action" value="preview" class="btn btn-outline-primary">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                            <span>Preview</span>
                        </button>
                        <button type="submit" name="action" value="save" class="btn btn-primary">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            <span>Simpan Jurnal</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </form>

    <section class="qj-card">
        <div class="qj-card__head">
            <div>
                <h2 class="h5 mb-1">Preview Jurnal</h2>
                <div class="text-secondary small">Periksa pasangan debit dan kredit sebelum menyimpan.</div>
            </div>
        </div>
        <div class="qj-card__body">
            <?php if ($preview === null): ?>
                <div class="text-center text-secondary py-4">Isi form lalu klik <strong>Preview</strong>.</div>
            <?php elseif (($preview['errors'] ?? []) !== []): ?>
                <div class="alert alert-warning mb-0">
                    <div class="fw-semibold mb-2">Input belum valid.</div>
                    <ul class="mb-0 ps-3">
                        <?php foreach (($preview['errors'] ?? []) as $error): ?>
                            <li><?= e((string) $error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <div class="small text-secondary">Nominal transaksi</div>
                    <div class="fs-4 fw-bold"><?= e(number_format((float) ($preview['amount'] ?? 0), 2, ',', '.')) ?></div>
                </div>
                <?php foreach (($preview['lines'] ?? []) as $line): ?>
                    <div class="qj-preview-line">
                        <div>
                            <div class="fw-semibold"><?= e((string) (($line['line_description'] ?? '-'))) ?></div>
                            <div class="small text-secondary">COA ID: <?= e((string) ($line['coa_id'] ?? '')) ?></div>
                        </div>
                        <div class="text-end">
                            <div class="small text-secondary">Debit <?= e(number_format((float) ($line['debit'] ?? 0), 2, ',', '.')) ?></div>
                            <div class="small text-secondary">Kredit <?= e(number_format((float) ($line['credit'] ?? 0), 2, ',', '.')) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (is_array($preview['receipt'] ?? null)): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <div class="fw-semibold mb-1">Kwitansi aktif</div>
                        <div class="small"><?= e((string) (($preview['receipt']['party_title'] ?? '') . ' ' . ($preview['receipt']['party_name'] ?? '-'))) ?> &middot; <?= e((string) ($preview['receipt']['purpose'] ?? '-')) ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="qj-sheet-backdrop" id="qj-account-backdrop" hidden></div>
<div class="qj-sheet" id="qj-account-sheet" role="dialog" aria-modal="true" aria-labelledby="qj-account-sheet-title" hidden>
    <div class="qj-sheet__head">
        <div>
            <div class="qj-sheet__title" id="qj-account-sheet-title">Pilih Akun</div>
            <div class="small text-secondary" id="qj-account-sheet-subtitle">Cari kode atau nama akun.</div>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="qj-account-close" aria-label="Tutup pilihan akun">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>
    <input type="search" class="form-control" id="qj-account-search" placeholder="Cari kode / nama akun" autocomplete="off">
    <div class="qj-sheet__list" id="qj-account-results"></div>
</div>

<script>
(() => {
    const form = document.getElementById('quick-journal-form');
    if (!form) return;

    const accounts = <?= json_encode($accountJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || [];
    const amountInput = form.querySelector('[name="amount"]');
    const liveAmount = document.getElementById('qj-live-amount');
    const liveStatus = document.getElementById('qj-live-status');
    const optionalToggle = document.getElementById('qj-optional-toggle');
    const optionalPanel = document.getElementById('qj-optional-panel');
    const sheet = document.getElementById('qj-account-sheet');
    const backdrop = document.getElementById('qj-account-backdrop');
    const search = document.getElementById('qj-account-search');
    const results = document.getElementById('qj-account-results');
    const closeBtn = document.getElementById('qj-account-close');
    const sheetTitle = document.getElementById('qj-account-sheet-title');
    let activeSelect = null;

    function parseAmount(value) {
        const normalized = String(value || '')
            .replace(/Rp/gi, '')
            .replace(/\s+/g, '')
            .replace(/\.(?=.*[,])/g, '')
            .replace(',', '.');
        const parsed = parseFloat(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function accountById(id) {
        return accounts.find((account) => String(account.id) === String(id || '')) || null;
    }

    function updateAccountTriggers() {
        form.querySelectorAll('[data-qj-account-trigger]').forEach((trigger) => {
            const fieldName = trigger.getAttribute('data-qj-account-trigger') || '';
            const select = form.querySelector(`select[name="${fieldName}"]`);
            const text = trigger.querySelector('.qj-account-trigger__text');
            const selected = accountById(select ? select.value : '');
            if (text) {
                text.textContent = selected ? selected.label : (fieldName.includes('debit') ? 'Pilih akun debit' : 'Pilih akun kredit');
            }
            trigger.classList.toggle('is-selected', !!selected);
        });
    }

    function updateSummary() {
        const amount = parseAmount(amountInput?.value || '');
        if (liveAmount) liveAmount.textContent = formatRupiah(amount);
        const debit = form.querySelector('[name="debit_account_id"]')?.value || '';
        const credit = form.querySelector('[name="credit_account_id"]')?.value || '';
        const ready = amount > 0 && debit !== '' && credit !== '' && debit !== credit;
        if (liveStatus) {
            liveStatus.innerHTML = ready
                ? '<i class="bi bi-check2-circle" aria-hidden="true"></i>Siap disimpan'
                : '<i class="bi bi-info-circle" aria-hidden="true"></i>Debit = Kredit';
        }
        updateAccountTriggers();
    }

    function renderResults() {
        if (!results) return;
        const term = String(search?.value || '').trim().toLowerCase();
        const pool = term === ''
            ? accounts.slice().sort((a, b) => Number(b.is_suggested || 0) - Number(a.is_suggested || 0))
            : accounts.filter((account) => String(account.search || '').includes(term));
        const visible = pool.slice(0, 60);
        if (visible.length === 0) {
            results.innerHTML = '<div class="qj-sheet-empty">Akun tidak ditemukan.</div>';
            return;
        }
        results.innerHTML = visible.map((account) => `
            <button type="button" class="qj-account-option" data-account-id="${escapeHtml(account.id)}">
                <span class="qj-account-option__name">${escapeHtml(account.code)} - ${escapeHtml(account.name)}</span>
                <span class="qj-account-option__meta">${escapeHtml(account.type || 'Akun')}</span>
            </button>
        `).join('');
    }

    function openSheet(select) {
        activeSelect = select;
        if (!sheet || !backdrop) return;
        const isDebit = String(select?.name || '').includes('debit');
        if (sheetTitle) sheetTitle.textContent = isDebit ? 'Pilih Akun Debit' : 'Pilih Akun Kredit';
        sheet.hidden = false;
        backdrop.hidden = false;
        document.body.classList.add('qj-sheet-open');
        requestAnimationFrame(() => {
            sheet.classList.add('is-open');
            backdrop.classList.add('is-open');
            renderResults();
            search?.focus();
        });
    }

    function closeSheet() {
        if (!sheet || !backdrop) return;
        sheet.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        document.body.classList.remove('qj-sheet-open');
        setTimeout(() => {
            sheet.hidden = true;
            backdrop.hidden = true;
            if (search) search.value = '';
            activeSelect = null;
        }, 180);
    }

    optionalToggle?.addEventListener('click', () => {
        if (!optionalPanel) return;
        const open = optionalPanel.hidden;
        optionalPanel.hidden = !open;
        optionalToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        optionalToggle.querySelector('.bi-chevron-down')?.classList.toggle('rotate-180', open);
    });

    form.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-qj-account-trigger]');
        if (!trigger) return;
        const fieldName = trigger.getAttribute('data-qj-account-trigger') || '';
        const select = form.querySelector(`select[name="${fieldName}"]`);
        if (select) openSheet(select);
    });

    form.addEventListener('input', updateSummary);
    form.addEventListener('change', updateSummary);
    search?.addEventListener('input', renderResults);
    closeBtn?.addEventListener('click', closeSheet);
    backdrop?.addEventListener('click', closeSheet);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sheet && !sheet.hidden) closeSheet();
    });
    results?.addEventListener('click', (event) => {
        const option = event.target.closest('[data-account-id]');
        if (!option || !activeSelect) return;
        activeSelect.value = option.getAttribute('data-account-id') || '';
        activeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        closeSheet();
    });

    updateSummary();
})();
</script>
