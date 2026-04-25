<?php declare(strict_types=1); ?>
<div class="bank-reconciliation-page module-page">
<section class="module-hero mb-4">
    <div class="module-hero__content">
        <div>
            <div class="module-hero__eyebrow">Rekonsiliasi</div>
            <h1 class="module-hero__title">Rekonsiliasi Bank</h1>
            <p class="module-hero__text">Import CSV mutasi bank, cocokkan ke jurnal bank, lalu review selisih agar proses closing lebih aman dan lebih mudah dipahami.</p>
        </div>
        <div class="module-hero__actions">
            <?php if ($selected): ?>
                <a href="<?= e(base_url('/bank-reconciliations/print?id=' . (int) $selected['id'])) ?>" target="_blank" class="btn btn-outline-secondary">Cetak Rekonsiliasi</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="alert alert-info mb-4">
    <div class="fw-semibold mb-1">Panduan singkat rekonsiliasi</div>
    <div class="small">Urutan yang paling aman adalah import CSV mutasi, cek hasil auto match, pastikan selisih jurnal vs bank mendekati nol, lalu review checklist tutup buku sebelum periode ditutup.</div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-5">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Import Mutasi Bank</h2>
                <form method="post" action="<?= e(base_url('/bank-reconciliations/store')) ?>" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                    <div class="col-12">
                        <label class="form-label">Judul Sesi</label>
                        <input type="text" name="title" class="form-control" placeholder="Contoh: Rekonsiliasi Bank BRI Maret 2026">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Akun Bank / Kas</label>
                        <select name="bank_account_coa_id" class="form-select" required>
                            <option value="">Pilih akun</option>
                            <?php foreach ($bankAccounts as $account): ?>
                                <option value="<?= e((string) $account['id']) ?>"><?= e((string) $account['account_code'] . ' - ' . (string) $account['account_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Periode</label>
                        <select name="period_id" class="form-select">
                            <option value="">Tanpa filter periode</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?= e((string) $period['id']) ?>" <?= (string) ($defaults['period_id'] ?? '') === (string) $period['id'] ? 'selected' : '' ?>><?= e((string) $period['period_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Unit Usaha</label>
                        <select name="business_unit_id" class="form-select">
                            <option value="">Semua unit / gabungan</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?= e((string) $unit['id']) ?>"><?= e((string) $unit['unit_code'] . ' - ' . (string) $unit['unit_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. Mutasi / Rekening Koran</label>
                        <input type="text" name="statement_no" class="form-control" placeholder="Opsional">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Awal Statement</label>
                        <input type="date" name="statement_start_date" class="form-control" value="<?= e((string) ($defaults['statement_start_date'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Akhir Statement</label>
                        <input type="date" name="statement_end_date" class="form-control" value="<?= e((string) ($defaults['statement_end_date'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Saldo Awal Statement</label>
                        <input type="text" name="opening_balance" class="form-control" value="<?= e((string) ($defaults['opening_balance'] ?? '0')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Saldo Akhir Statement</label>
                        <input type="text" name="closing_balance" class="form-control" value="<?= e((string) ($defaults['closing_balance'] ?? '0')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Toleransi Auto Match (hari)</label>
                        <input type="number" min="0" max="14" name="auto_match_tolerance_days" class="form-control" value="<?= e((string) ($defaults['auto_match_tolerance_days'] ?? '3')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">File CSV Mutasi</label>
                        <input type="file" name="statement_file" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" rows="3" class="form-control" placeholder="Opsional: sumber file, nama bank, info rekonsiliasi."></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Import &amp; Buat Rekonsiliasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Riwayat Sesi Rekonsiliasi</h2>
                    <span class="badge text-bg-dark"><?= e((string) count($reconciliations)) ?> sesi</span>
                </div>
                <?php if ($reconciliations === []): ?>
                    <div class="text-center text-secondary py-5">Belum ada sesi rekonsiliasi. Import CSV mutasi bank pertama Anda dari panel kiri.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Judul</th>
                                <th style="width:16%">Mutasi</th>
                                <th style="width:12%">Baris</th>
                                <th style="width:12%">Match</th>
                                <th style="width:16%" class="text-end">Aksi</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($reconciliations as $row): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e((string) $row['title']) ?></div>
                                        <div class="small text-secondary"><?= e((string) $row['account_code'] . ' - ' . (string) $row['account_name']) ?></div>
                                        <div class="small text-secondary"><?= e(bank_reconciliation_statement_label($row)) ?></div>
                                    </td>
                                    <td>
                                        <div class="small">Masuk <?= e(bank_reconciliation_currency((float) $row['total_statement_in'])) ?></div>
                                        <div class="small">Keluar <?= e(bank_reconciliation_currency((float) $row['total_statement_out'])) ?></div>
                                    </td>
                                    <td class="fw-semibold"><?= e((string) ($row['total_statement_rows'] ?? 0)) ?></td>
                                    <td>
                                        <div class="small text-success">Cocok <?= e((string) ($row['total_matched_rows'] ?? 0)) ?></div>
                                        <div class="small text-warning">Belum <?= e((string) ($row['total_unmatched_rows'] ?? 0)) ?></div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <a href="<?= e(base_url('/bank-reconciliations?id=' . (int) $row['id'])) ?>" class="btn btn-sm <?= ($selected && (int) $selected['id'] === (int) $row['id']) ? 'btn-primary' : 'btn-outline-light' ?>">Buka</a>
                                            <form method="post" action="<?= e(base_url('/bank-reconciliations/delete')) ?>" class="m-0" onsubmit="return confirm('Hapus sesi rekonsiliasi ini?');">
                                                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($selected): ?>
    <?php $netGap = (float) ($selected['total_statement_net'] ?? 0) - (float) ($journalSummary['journal_net'] ?? 0); ?>
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1"><?= e((string) $selected['title']) ?></h2>
            <div class="text-secondary small"><?= e((string) $selected['account_code'] . ' - ' . (string) $selected['account_name']) ?> &middot; <?= e(bank_reconciliation_statement_label($selected)) ?></div>
            <div class="text-secondary small"><?= e(bank_reconciliation_filters_label($selected)) ?></div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="post" action="<?= e(base_url('/bank-reconciliations/auto-match')) ?>" class="m-0">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= e((string) $selected['id']) ?>">
                <button type="submit" class="btn btn-primary">Jalankan Auto Match Ulang</button>
            </form>
            <form method="post" action="<?= e(base_url('/bank-reconciliations/reset-all')) ?>" class="m-0" onsubmit="return confirm('Reset semua hasil match pada sesi ini?');">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= e((string) $selected['id']) ?>">
                <button type="submit" class="btn btn-outline-warning">Reset Semua Match</button>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Baris Statement</div><div class="display-6 fw-bold mb-0"><?= e((string) ($selected['total_statement_rows'] ?? 0)) ?></div></div></div></div>
        <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Mutasi Bank Bersih</div><div class="fs-5 fw-semibold <?= (float) ($selected['total_statement_net'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(bank_reconciliation_currency((float) ($selected['total_statement_net'] ?? 0))) ?></div><div class="small text-secondary">Masuk <?= e(bank_reconciliation_currency((float) ($selected['total_statement_in'] ?? 0))) ?> &middot; Keluar <?= e(bank_reconciliation_currency((float) ($selected['total_statement_out'] ?? 0))) ?></div></div></div></div>
        <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Status Match</div><div class="fs-5 fw-semibold text-success"><?= e((string) ($selected['total_matched_rows'] ?? 0)) ?> cocok</div><div class="small text-secondary"><?= e((string) ($selected['total_unmatched_rows'] ?? 0)) ?> belum cocok</div></div></div></div>
        <div class="col-md-3"><div class="card dashboard-card h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Selisih Jurnal vs Bank</div><div class="fs-5 fw-semibold <?= abs($netGap) < 0.01 ? 'text-success' : 'text-warning' ?>"><?= e(bank_reconciliation_currency($netGap)) ?></div><div class="small text-secondary">Net bank <?= e(bank_reconciliation_currency((float) ($journalSummary['journal_net'] ?? 0))) ?></div></div></div></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Saldo Awal Statement</div><div class="fs-5 fw-semibold"><?= e(bank_reconciliation_currency((float) ($selected['opening_balance'] ?? 0))) ?></div><div class="text-secondary small mt-3 mb-1">Saldo Akhir Statement</div><div class="fs-5 fw-semibold"><?= e(bank_reconciliation_currency((float) ($selected['closing_balance'] ?? 0))) ?></div><div class="small mt-3 <?= bank_reconciliation_statement_balance_ok($selected) ? 'text-success' : 'text-warning' ?>"><?= bank_reconciliation_statement_balance_ok($selected) ? 'Saldo awal + mutasi bank = saldo akhir.' : 'Periksa kembali saldo statement. Gap ' . e(bank_reconciliation_currency(bank_reconciliation_balance_gap($selected))) ?></div></div></div></div>
        <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Jurnal Bank Masuk</div><div class="fs-5 fw-semibold text-success"><?= e(bank_reconciliation_currency((float) ($journalSummary['journal_in'] ?? 0))) ?></div><div class="text-secondary small mt-3 mb-1">Jurnal Bank Keluar</div><div class="fs-5 fw-semibold text-danger"><?= e(bank_reconciliation_currency((float) ($journalSummary['journal_out'] ?? 0))) ?></div><div class="small text-secondary mt-3"><?= e((string) ($journalSummary['journal_count'] ?? 0)) ?> jurnal bank ada di rentang ini.</div></div></div></div>
        <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">File CSV</div><div class="fw-semibold mb-2"><?= e((string) ($selected['imported_file_name'] ?? '-')) ?></div><div class="small text-secondary">Tolerance auto match: <?= e((string) ($selected['auto_match_tolerance_days'] ?? 0)) ?> hari</div><?php if ((string) ($selected['notes'] ?? '') !== ''): ?><div class="small text-secondary mt-3">Catatan: <?= e((string) $selected['notes']) ?></div><?php endif; ?></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:7%">No</th>
                        <th style="width:11%">Tanggal</th>
                        <th>Keterangan</th>
                        <th style="width:10%">Arah</th>
                        <th style="width:12%" class="text-end">Masuk</th>
                        <th style="width:12%" class="text-end">Keluar</th>
                        <th style="width:12%">Status</th>
                        <th style="width:24%">Jurnal / Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($lines as $line): ?>
                        <?php $lineSuggestions = $suggestions[(int) $line['id']] ?? []; ?>
                        <tr>
                            <td><?= e((string) ($line['line_no'] ?? 0)) ?></td>
                            <td><?= e(format_id_date((string) ($line['transaction_date'] ?? ''))) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($line['description'] ?? '-')) ?></div>
                                <div class="small text-secondary">Ref: <?= e((string) (($line['reference_no'] ?? '') !== '' ? $line['reference_no'] : '-')) ?></div>
                                <?php if ($line['running_balance'] !== null): ?><div class="small text-secondary">Saldo: <?= e(bank_reconciliation_currency((float) $line['running_balance'])) ?></div><?php endif; ?>
                            </td>
                            <td><?= e(bank_reconciliation_direction_label((float) ($line['amount_in'] ?? 0), (float) ($line['amount_out'] ?? 0))) ?></td>
                            <td class="text-end text-success"><?= e(bank_reconciliation_currency((float) ($line['amount_in'] ?? 0))) ?></td>
                            <td class="text-end text-danger"><?= e(bank_reconciliation_currency((float) ($line['amount_out'] ?? 0))) ?></td>
                            <td>
                                <span class="badge <?= e(bank_reconciliation_status_badge_class((string) ($line['match_status'] ?? 'UNMATCHED'))) ?>"><?= e(bank_reconciliation_status_label((string) ($line['match_status'] ?? 'UNMATCHED'))) ?></span>
                                <?php if ((string) ($line['matched_reason'] ?? '') !== ''): ?><div class="small text-secondary mt-2"><?= e((string) $line['matched_reason']) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($line['matched_journal_id'])): ?>
                                    <div class="fw-semibold"><?= e((string) ($line['journal_no'] ?? '-')) ?></div>
                                    <div class="small text-secondary"><?= e(format_id_date((string) ($line['journal_date'] ?? ''))) ?> &middot; <?= e((string) ($line['journal_description'] ?? '')) ?></div>
                                    <div class="d-flex gap-2 flex-wrap mt-2">
                                        <a href="<?= e(base_url('/journals/detail?id=' . (int) $line['matched_journal_id'])) ?>" class="btn btn-sm btn-outline-light" target="_blank">Detail Jurnal</a>
                                        <form method="post" action="<?= e(base_url('/bank-reconciliations/reset-line')) ?>" class="m-0">
                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="line_id" value="<?= e((string) $line['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Reset</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <?php if ($lineSuggestions !== []): ?>
                                        <form method="post" action="<?= e(base_url('/bank-reconciliations/manual-match')) ?>" class="d-grid gap-2 mb-2">
                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="line_id" value="<?= e((string) $line['id']) ?>">
                                            <select name="journal_id" class="form-select form-select-sm" required>
                                                <option value="">Pilih saran jurnal</option>
                                                <?php foreach ($lineSuggestions as $suggestion): ?>
                                                    <option value="<?= e((string) $suggestion['journal_id']) ?>"><?= e((string) $suggestion['journal_no'] . ' | ' . format_id_date((string) $suggestion['journal_date']) . ' | score ' . number_format((float) $suggestion['score'], 0, ',', '.')) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Simpan Manual Match</button>
                                        </form>
                                        <details class="small text-secondary">
                                            <summary>Lihat alasan saran</summary>
                                            <ul class="mb-0 mt-2 ps-3">
                                                <?php foreach ($lineSuggestions as $suggestion): ?>
                                                    <li><strong><?= e((string) $suggestion['journal_no']) ?></strong> - <?= e((string) $suggestion['reason']) ?> (<?= e((string) $suggestion['score_label']) ?>)</li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php else: ?>
                                        <div class="small text-secondary mb-2">Belum ada saran jurnal yang cocok otomatis untuk baris ini.</div>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(base_url('/bank-reconciliations/ignore-line')) ?>" class="m-0">
                                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="line_id" value="<?= e((string) $line['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Abaikan Baris</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>
