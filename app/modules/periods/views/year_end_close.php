<?php declare(strict_types=1); ?>
<?php
$period = $preview['period'];
$nextPeriod = $preview['next_period'];
$proposal = $preview['proposal'];
$retained = $preview['retained_earnings'];
$totals = $preview['totals'];
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Tutup Buku Tahunan Otomatis</h1>
        <p class="text-secondary mb-0">Sistem akan menutup periode tahun berjalan, membuat atau memakai periode tahun baru, lalu membentuk jurnal saldo awal otomatis.</p>
    </div>
    <div>
        <a href="<?= e(base_url('/periods')) ?>" class="btn btn-outline-light">Kembali ke Periode</a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-2 small text-center text-md-start">
            <div class="col-md-4"><span class="btn btn-outline-light w-100 disabled">1. Review preview saldo awal</span></div>
            <div class="col-md-4"><span class="btn btn-outline-light w-100 disabled">2. Pastikan jurnal seimbang</span></div>
            <div class="col-md-4"><span class="btn btn-outline-light w-100 disabled">3. Jalankan tutup buku tahunan</span></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Periode Ditutup</div><div class="fs-5 fw-semibold"><?= e((string) $period['period_name']) ?></div><div class="small text-secondary mt-2"><?= e((string) $period['period_code']) ?> · <?= e(active_period_label((string) $period['start_date'], (string) $period['end_date'])) ?></div></div></div></div>
    <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Periode Tahun Baru</div><div class="fs-5 fw-semibold"><?= e((string) ($nextPeriod['period_name'] ?? $proposal['period_name'])) ?></div><div class="small text-secondary mt-2"><?= e((string) ($nextPeriod['period_code'] ?? $proposal['period_code'])) ?> · <?= e(active_period_label((string) ($nextPeriod['start_date'] ?? $proposal['start_date']), (string) ($nextPeriod['end_date'] ?? $proposal['end_date']))) ?></div><div class="small mt-3 <?= $nextPeriod ? 'text-info' : 'text-success' ?>"><?= $nextPeriod ? 'Periode tahun baru sudah ada dan akan dipakai.' : 'Periode tahun baru akan dibuat otomatis.' ?></div></div></div></div>
    <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body p-4"><div class="text-secondary small mb-1">Akun Laba Ditahan</div><div class="fs-5 fw-semibold"><?= e((string) $retained['account_name']) ?></div><div class="small text-secondary mt-2"><?= e((string) $retained['account_code']) ?></div><div class="small mt-3 text-secondary">Laba/rugi bersih tahun berjalan akan ditambahkan ke akun ini saat membuat saldo awal.</div></div></div></div>
</div>

<div class="alert alert-warning border border-warning-subtle bg-warning-subtle text-dark">
    <div class="fw-semibold mb-1">Yang akan dilakukan sistem</div>
    <ul class="mb-0 ps-3">
        <li>Menutup periode aktif tahun berjalan.</li>
        <li>Membuat atau memakai periode tahun baru.</li>
        <li>Membuat jurnal saldo awal otomatis pada tanggal <strong><?= e(format_id_date((string) ($nextPeriod['start_date'] ?? $proposal['start_date']))) ?></strong>.</li>
        <li>Mengaktifkan periode tahun baru sebagai buku kerja berikutnya.</li>
    </ul>
</div>

<div class="alert alert-info border border-info-subtle bg-info-subtle text-dark mb-4">
    <div class="fw-semibold mb-1">Checklist cepat sebelum eksekusi</div>
    <ul class="mb-0 ps-3">
        <li>Pastikan backup terbaru sudah dibuat.</li>
        <li>Pastikan preview jurnal saldo awal sudah seimbang.</li>
        <li>Pastikan tidak ada transaksi tambahan yang masih akan dimasukkan ke periode lama.</li>
    </ul>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card shadow-sm h-100"><div class="card-body p-4">
            <div class="text-secondary small mb-1">Laba / Rugi Bersih Tahun Berjalan</div>
            <div class="fs-4 fw-semibold <?= (float) $preview['net_income'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(ledger_currency((float) $preview['net_income'])) ?></div>
            <div class="text-secondary small mt-3 mb-1">Jurnal Saldo Awal</div>
            <div class="fs-5 fw-semibold"><?= e(number_format((int) $totals['line_count'], 0, ',', '.')) ?> baris</div>
            <div class="small text-secondary mt-2">Total debit: <?= e(ledger_currency((float) $totals['debit'])) ?></div>
            <div class="small text-secondary">Total kredit: <?= e(ledger_currency((float) $totals['credit'])) ?></div>
            <div class="small mt-2 <?= (bool) $totals['is_balanced'] ? 'text-success' : 'text-danger' ?>"><?= (bool) $totals['is_balanced'] ? 'Jurnal saldo awal seimbang dan siap diposting.' : 'Jurnal saldo awal belum seimbang.' ?></div>
        </div></div>
    </div>
    <div class="col-xl-8">
        <div class="card shadow-sm h-100"><div class="card-body p-0">
            <div class="table-responsive"><table class="table table-dark table-hover align-middle mb-0 coa-table">
                <thead><tr><th>Kode</th><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead>
                <tbody>
                <?php if (($preview['opening_lines'] ?? []) === []): ?>
                    <tr><td colspan="4" class="text-center py-4 text-secondary">Tidak ada saldo awal yang perlu dibentuk.</td></tr>
                <?php else: foreach (($preview['opening_lines'] ?? []) as $line): ?>
                    <tr>
                        <td class="fw-semibold"><?= e((string) $line['account_code']) ?></td>
                        <td><?= e((string) $line['account_name']) ?></td>
                        <td class="text-end"><?= e((float) $line['debit'] > 0 ? ledger_currency((float) $line['debit']) : '-') ?></td>
                        <td class="text-end"><?= e((float) $line['credit'] > 0 ? ledger_currency((float) $line['credit']) : '-') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <tfoot><tr><th colspan="2" class="text-end">Total</th><th class="text-end"><?= e(ledger_currency((float) $totals['debit'])) ?></th><th class="text-end"><?= e(ledger_currency((float) $totals['credit'])) ?></th></tr></tfoot>
            </table></div>
        </div></div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="fw-semibold">Eksekusi tutup buku tahunan</div>
            <div class="text-secondary small">Jalankan hanya sekali untuk periode ini. Sistem akan mencegah duplikasi jurnal saldo awal.</div>
        </div>
        <form method="post" action="<?= e(base_url('/periods/year-end-close?id=' . (int) $period['id'])) ?>" onsubmit="return confirm('Lanjutkan tutup buku tahunan otomatis? Sistem akan menutup periode ini dan mengaktifkan periode tahun baru.');">
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="btn btn-warning">Jalankan Tutup Buku Tahunan</button>
        </form>
    </div>
</div>
