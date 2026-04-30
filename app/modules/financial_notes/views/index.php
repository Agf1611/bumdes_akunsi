<?php declare(strict_types=1); ?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Catatan atas Laporan Keuangan</h1>
        <p class="text-secondary mb-0">CaLK mengikuti struktur KepmenDesa PDTT Nomor 136 Tahun 2022: pernyataan acuan, dasar penyusunan, kebijakan akuntansi, rincian pos, dan pengungkapan lain.</p>
    </div>
    <?php if (($filters['date_to'] ?? '') !== ''): ?>
        <?php $query = report_filters_query($filters); ?>
        <div class="d-flex gap-2">
            <a href="<?= e(base_url('/financial-notes/print?' . $query)) ?>" target="_blank" class="btn btn-outline-light">Cetak</a>
            <a href="<?= e(base_url('/financial-notes/pdf?' . $query)) ?>" class="btn btn-primary">Export PDF</a>
        </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-4"><div class="card-body p-4">
    <form method="get" action="<?= e(base_url('/financial-notes')) ?>" class="row g-3 align-items-end">
        <input type="hidden" name="filter_scope" value="<?= e(report_filter_scope($filters)) ?>">
        <div class="col-lg-2"><label for="period_id" class="form-label">Periode Awal</label><select name="period_id" id="period_id" class="form-select"><?= report_period_select_options($periods, (int) ($filters['period_id'] ?? 0), 'Manual tanggal') ?></select></div>
        <div class="col-lg-2"><label for="period_to_id" class="form-label">Sampai Periode</label><select name="period_to_id" id="period_to_id" class="form-select"><?= report_period_select_options($periods, (int) ($filters['period_to_id'] ?? 0), 'Sama dengan periode awal') ?></select></div>
        <div class="col-lg-3"><label for="unit_id" class="form-label">Unit Usaha</label><select name="unit_id" id="unit_id" class="form-select"><option value="">Semua Unit</option><?php foreach ($units as $unit): ?><option value="<?= e((string) $unit['id']) ?>" <?= (string) $filters['unit_id'] === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['unit_code'] . ' - ' . $unit['unit_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-2"><label for="date_from" class="form-label">Tanggal Mulai</label><input type="date" name="date_from" id="date_from" class="form-control" value="<?= e((string) $filters['date_from']) ?>"></div>
        <div class="col-lg-2"><label for="date_to" class="form-label">Tanggal Akhir</label><input type="date" name="date_to" id="date_to" class="form-control" value="<?= e((string) $filters['date_to']) ?>"></div>
        <div class="col-lg-1 d-grid"><button type="submit" class="btn btn-primary">Tampil</button></div>
    </form>
</div></div>

<?php if (($filters['date_to'] ?? '') === ''): ?>
    <div class="card shadow-sm"><div class="card-body p-5 text-center text-secondary">Pilih periode atau isi tanggal filter, lalu klik <strong class="text-light">Tampil</strong> untuk melihat Catatan atas Laporan Keuangan.</div></div>
<?php else: ?>
    <div class="alert alert-primary border mb-4">
        <div class="fw-semibold mb-1">Format resmi CaLK BUM Desa</div>
        <div class="small mb-0"><?= e(report_kepmendes_136_reference()) ?>. Bagian di bawah disusun agar mudah dibaca pemeriksa dan tetap terhubung ke saldo akun aplikasi.</div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Periode Laporan</div><div class="fw-semibold"><?= e(report_period_label($filters, $selectedPeriod)) ?></div></div></div></div>
        <div class="col-md-4"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Unit Usaha</div><div class="fw-semibold"><?= e($selectedUnitLabel) ?></div></div></div></div>
        <div class="col-md-4"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Identitas Legal</div><div class="fw-semibold"><?= e(financial_notes_profile_legal($profile) !== '' ? financial_notes_profile_legal($profile) : 'Belum dilengkapi') ?></div></div></div></div>
    </div>

    <?php foreach ($notes as $note): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3"><?= e((string) ($note['title'] ?? '')) ?></h2>
                <?php foreach (($note['paragraphs'] ?? []) as $paragraph): ?>
                    <p class="mb-2 text-secondary"><?= e((string) $paragraph) ?></p>
                <?php endforeach; ?>
                <?php if (financial_notes_has_rows((array) ($note['rows'] ?? []))): ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead><tr><th style="width: 16%;">Kode Akun</th><th>Nama Akun</th><th style="width: 20%;" class="text-end">Nilai</th></tr></thead>
                            <tbody>
                                <?php foreach (($note['rows'] ?? []) as $row): ?>
                                    <?php if (abs((float) ($row['amount'] ?? 0)) <= 0.004) { continue; } ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e((string) ($row['account_code'] ?? '-')) ?></td>
                                        <td><?= e((string) ($row['account_name'] ?? '-')) ?></td>
                                        <td class="text-end"><?= e(financial_notes_currency((float) ($row['amount'] ?? 0))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot><tr><th colspan="2" class="text-end">Total</th><th class="text-end"><?= e(financial_notes_currency(financial_notes_table_total((array) ($note['rows'] ?? [])))) ?></th></tr></tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary border mt-3 mb-0">Belum ada saldo akun yang relevan untuk bagian ini pada filter yang dipilih.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
