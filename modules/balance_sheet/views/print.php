<?php declare(strict_types=1); ?>
<section class="print-sheet">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h4 mb-1"><?= e($profile['bumdes_name'] ?? 'BUMDes') ?></h1>
            <div class="text-muted"><?= e($profile['address'] ?? '-') ?></div>
            <div class="text-muted">Telepon: <?= e($profile['phone'] ?? '-') ?> | Email: <?= e($profile['email'] ?? '-') ?></div>
        </div>
        <?php if (!empty($profile['logo_path'])): ?>
            <img src="<?= e(storage_url((string) $profile['logo_path'])) ?>" alt="Logo" class="logo-thumb">
        <?php endif; ?>
    </div>

    <div class="text-center mb-4">
        <h2 class="h4 mb-1">Laporan Neraca</h2>
        <div class="text-muted">Per <?= e((string) $filters['date_to']) ?></div>
        <div class="text-muted"><?= e($selectedPeriod['period_name'] ?? 'Filter tanggal manual') ?></div>
    </div>

    <?php if (!$report['is_balanced']): ?>
        <div class="alert alert-warning mb-4">
            Neraca belum seimbang. Selisih: <?= e(ledger_currency(abs((float) $report['difference']))) ?>.
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-6">
            <table class="table table-bordered print-table balance-print-table">
                <thead>
                <tr>
                    <th style="width: 20%;">Kode</th>
                    <th>Aset</th>
                    <th style="width: 24%;" class="text-end">Saldo Akhir</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($report['asset_rows'] === []): ?>
                    <tr><td colspan="3" class="text-center text-muted">Tidak ada akun aset untuk filter yang dipilih.</td></tr>
                <?php else: ?>
                    <?php foreach ($report['asset_rows'] as $row): ?>
                        <tr>
                            <td><?= e((string) $row['account_code']) ?></td>
                            <td><?= e((string) $row['account_name']) ?></td>
                            <td class="text-end"><?= e(ledger_currency((float) $row['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="2" class="text-end">Total Aset</th>
                    <th class="text-end"><?= e(ledger_currency((float) $report['total_assets'])) ?></th>
                </tr>
                </tfoot>
            </table>
        </div>
        <div class="col-6">
            <table class="table table-bordered print-table balance-print-table">
                <thead>
                <tr>
                    <th style="width: 20%;">Kode</th>
                    <th>Liabilitas & Ekuitas</th>
                    <th style="width: 24%;" class="text-end">Saldo Akhir</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="3" class="fw-semibold bg-light">Liabilitas</td></tr>
                <?php if ($report['liability_rows'] === []): ?>
                    <tr><td colspan="3" class="text-center text-muted">Tidak ada akun liabilitas untuk filter yang dipilih.</td></tr>
                <?php else: ?>
                    <?php foreach ($report['liability_rows'] as $row): ?>
                        <tr>
                            <td><?= e((string) $row['account_code']) ?></td>
                            <td><?= e((string) $row['account_name']) ?></td>
                            <td class="text-end"><?= e(ledger_currency((float) $row['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr>
                    <td colspan="2" class="text-end fw-semibold">Total Liabilitas</td>
                    <td class="text-end fw-bold"><?= e(ledger_currency((float) $report['total_liabilities'])) ?></td>
                </tr>
                <tr><td colspan="3" class="fw-semibold bg-light">Ekuitas</td></tr>
                <?php if ($report['equity_rows'] === []): ?>
                    <tr><td colspan="3" class="text-center text-muted">Tidak ada akun ekuitas untuk filter yang dipilih.</td></tr>
                <?php else: ?>
                    <?php foreach ($report['equity_rows'] as $row): ?>
                        <tr>
                            <td><?= e((string) $row['account_code']) ?></td>
                            <td><?= e((string) $row['account_name']) ?></td>
                            <td class="text-end"><?= e(ledger_currency((float) $row['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr>
                    <td colspan="2" class="text-end fw-semibold">Total Ekuitas</td>
                    <td class="text-end fw-bold"><?= e(ledger_currency((float) $report['total_equity'])) ?></td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="2" class="text-end">Total Liabilitas + Ekuitas</th>
                    <th class="text-end"><?= e(ledger_currency((float) $report['total_liabilities_equity'])) ?></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-4 text-end text-muted small">Dicetak pada <?= e(date('d-m-Y H:i')) ?></div>
</section>
<script>
    window.print();
</script>
