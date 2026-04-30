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
    'cash_in', 'revenue' => 'Pilih akun kas/bank di sisi debit dan akun pendapatan/piutang/modaI di sisi kredit.',
    'cash_out', 'expense' => 'Pilih akun beban/aset di sisi debit dan akun kas/bank di sisi kredit.',
    'transfer' => 'Gunakan dua akun kas/bank berbeda untuk perpindahan internal.',
    'asset' => 'Gunakan akun aset/persediaan di debit dan kas/utang di kredit.',
    'opening' => 'Gunakan untuk penyesuaian atau saldo awal dengan memilih dua akun yang tepat.',
    default => 'Pilih akun debit dan kredit yang sesuai, lalu cek preview sebelum simpan.',
};
?>

<style>
.qj-shell { max-width: 1080px; margin: 0 auto; display: grid; gap: 1rem; }
.qj-card { background:#fff; border:1px solid #dbe5f2; border-radius:22px; box-shadow:0 10px 24px rgba(15,23,42,.06); overflow:hidden; }
.qj-card__head { padding:1rem 1.25rem; border-bottom:1px solid #e8eef7; display:flex; justify-content:space-between; gap:1rem; align-items:center; flex-wrap:wrap; }
.qj-card__body { padding:1.1rem 1.25rem 1.25rem; }
.qj-template-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:.75rem; }
.qj-template-item { display:block; text-decoration:none; color:inherit; border:1px solid #dbe5f2; border-radius:18px; padding:.95rem 1rem; background:#fbfdff; }
.qj-template-item:hover { border-color:#93c5fd; background:#f8fbff; }
.qj-template-item.is-active { border-color:#60a5fa; background:#eff6ff; }
.qj-help { border-left:4px solid #60a5fa; background:#eff6ff; color:#1e3a8a; border-radius:14px; padding:.9rem 1rem; }
.qj-preview-line { display:flex; justify-content:space-between; gap:1rem; padding:.75rem 0; border-bottom:1px dashed #dbe5f2; }
.qj-preview-line:last-child { border-bottom:0; padding-bottom:0; }
.qj-pill-list { display:flex; flex-wrap:wrap; gap:.5rem; }
.qj-pill { display:inline-flex; align-items:center; gap:.45rem; padding:.5rem .8rem; border:1px solid #dbe5f2; border-radius:999px; background:#fff; text-decoration:none; color:#334155; }
.qj-pill:hover { border-color:#93c5fd; background:#eff6ff; color:#1d4ed8; }
</style>

<div class="qj-shell">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="h3 mb-1">Transaksi Cepat</h1>
            <p class="text-secondary mb-0">Isi ringkas, lihat preview debit/kredit, lalu simpan sebagai jurnal tanpa membuka form jurnal lengkap.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= e(base_url('/journals')) ?>" class="btn btn-outline-light">Kembali ke Jurnal</a>
            <a href="<?= e(base_url('/journals/create?template=' . urlencode((string) ($template['template_key'] ?? 'revenue')))) ?>" class="btn btn-outline-info">Buka Form Jurnal Lengkap</a>
        </div>
    </div>

    <section class="qj-card">
        <div class="qj-card__body">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                <div>
                    <div class="fw-semibold">Pilih template transaksi</div>
                    <div class="text-secondary small">Template hanya memandu alur dan instruksi akun. Engine jurnal yang dipakai tetap sama.</div>
                </div>
                <form method="post" action="<?= e(base_url('/journals/quick/favorite-template')) ?>" class="m-0">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="template_key" value="<?= e((string) ($template['template_key'] ?? 'cash_in')) ?>">
                    <button type="submit" class="btn btn-sm <?= isset($favoriteMap[(string) ($template['template_key'] ?? '')]) ? 'btn-warning' : 'btn-outline-warning' ?>">
                        <?= isset($favoriteMap[(string) ($template['template_key'] ?? '')]) ? 'Hapus dari Favorit' : 'Simpan Template Favorit' ?>
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

    <form method="post" action="<?= e(base_url('/journals/quick')) ?>" enctype="multipart/form-data" class="qj-card">
        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="template_key" value="<?= e($selectedTemplateKey) ?>">
        <div class="qj-card__head">
            <div>
                <h2 class="h5 mb-1"><?= e((string) ($template['template_name'] ?? 'Transaksi Cepat')) ?></h2>
                <div class="text-secondary small"><?= e((string) ($template['description'] ?? '')) ?></div>
            </div>
            <div class="badge text-bg-light border">Preview nomor jurnal dibuat otomatis saat simpan</div>
        </div>
        <div class="qj-card__body">
            <div class="qj-help mb-4">
                <div class="fw-semibold mb-1">Panduan cepat</div>
                <div class="small"><?= e($suggestionText) ?></div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="journal_date" class="form-control" value="<?= e((string) ($formData['journal_date'] ?? date('Y-m-d'))) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Periode</label>
                    <select name="period_id" class="form-select" required>
                        <option value="">Pilih periode</option>
                        <?php foreach ($periodOptions as $period): ?>
                            <option value="<?= e((string) ($period['id'] ?? '')) ?>" <?= (string) ($formData['period_id'] ?? '') === (string) ($period['id'] ?? '') ? 'selected' : '' ?>><?= e((string) (($period['period_code'] ?? '') . ' - ' . ($period['period_name'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit Usaha</label>
                    <select name="business_unit_id" class="form-select">
                        <option value="">Semua unit</option>
                        <?php foreach ($unitOptions as $unit): ?>
                            <option value="<?= e((string) ($unit['id'] ?? '')) ?>" <?= (string) ($formData['business_unit_id'] ?? '') === (string) ($unit['id'] ?? '') ? 'selected' : '' ?>><?= e((string) (($unit['unit_code'] ?? '') . ' - ' . ($unit['unit_name'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nominal</label>
                    <input type="text" name="amount" class="form-control" value="<?= e((string) ($formData['amount'] ?? '')) ?>" placeholder="Contoh: 1500000" required>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Tujuan / Keterangan</label>
                    <input type="text" name="description" class="form-control" value="<?= e((string) ($formData['description'] ?? '')) ?>" placeholder="Contoh: Pembayaran listrik kantor bulan April" required>
                    <div class="form-text">Tulis keterangan singkat yang nanti dipakai sebagai deskripsi jurnal dan tujuan transaksi.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nama Pihak</label>
                    <input type="text" name="party_name" class="form-control" value="<?= e((string) ($formData['party_name'] ?? '')) ?>" placeholder="Opsional">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Akun Debit</label>
                    <select name="debit_account_id" class="form-select" required>
                        <option value="">Pilih akun debit</option>
                        <?php foreach ($accountOptions as $account): ?>
                            <option value="<?= e((string) ($account['id'] ?? '')) ?>" <?= (string) ($formData['debit_account_id'] ?? '') === (string) ($account['id'] ?? '') ? 'selected' : '' ?>><?= e((string) (($account['account_code'] ?? '') . ' - ' . ($account['account_name'] ?? '') . ' (' . ($account['account_type'] ?? '') . ')')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Akun Kredit</label>
                    <select name="credit_account_id" class="form-select" required>
                        <option value="">Pilih akun kredit</option>
                        <?php foreach ($accountOptions as $account): ?>
                            <option value="<?= e((string) ($account['id'] ?? '')) ?>" <?= (string) ($formData['credit_account_id'] ?? '') === (string) ($account['id'] ?? '') ? 'selected' : '' ?>><?= e((string) (($account['account_code'] ?? '') . ' - ' . ($account['account_name'] ?? '') . ' (' . ($account['account_type'] ?? '') . ')')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nomor Referensi</label>
                    <input type="text" name="reference_no" class="form-control" value="<?= e((string) ($formData['reference_no'] ?? '')) ?>" placeholder="Opsional">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Komponen Laporan Arus Kas</label>
                    <select name="cashflow_component_id" class="form-select">
                        <option value="">Tidak masuk / klasifikasi otomatis</option>
                        <?php foreach ($cashflowComponentOptions as $component): ?>
                            <option value="<?= e((string) ($component['id'] ?? '')) ?>" <?= (string) ($formData['cashflow_component_id'] ?? '') === (string) ($component['id'] ?? '') ? 'selected' : '' ?>><?= e((string) (($component['code'] ?? '') . ' - ' . ($component['name'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Pilih ini agar Laporan Arus Kas mengikuti komponen Kemendesa, bukan hanya tebakan dari akun lawan.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Judul Lampiran</label>
                    <input type="text" name="attachment_title" class="form-control" value="<?= e((string) ($formData['attachment_title'] ?? '')) ?>" placeholder="Opsional">
                </div>

                <div class="col-12">
                    <label class="form-label">Lampiran Bukti</label>
                    <input type="file" name="attachment_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    <div class="form-text"><?= !empty($attachmentFeatureStatus['enabled']) ? 'Jika file diisi, lampiran akan langsung menempel ke jurnal yang dibuat.' : 'Fitur lampiran database belum aktif, jadi file saat ini bersifat opsional dan akan diabaikan.' ?></div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" name="action" value="preview" class="btn btn-outline-primary">Preview Jurnal</button>
                <button type="submit" name="action" value="save" class="btn btn-primary">Simpan sebagai Jurnal</button>
            </div>
        </div>
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
                <div class="text-center text-secondary py-4">Isi form transaksi cepat lalu klik <strong>Preview Jurnal</strong> untuk melihat hasil pembentukan jurnal otomatis.</div>
            <?php elseif (($preview['errors'] ?? []) !== []): ?>
                <div class="alert alert-warning mb-0">
                    <div class="fw-semibold mb-2">Preview belum bisa dibuat penuh karena masih ada input yang perlu diperbaiki.</div>
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
                        <div class="fw-semibold mb-1">Kwitansi / bukti transaksi akan ikut dibuat</div>
                        <div class="small"><?= e((string) (($preview['receipt']['party_title'] ?? '') . ' ' . ($preview['receipt']['party_name'] ?? '-'))) ?> · <?= e((string) ($preview['receipt']['purpose'] ?? '-')) ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
