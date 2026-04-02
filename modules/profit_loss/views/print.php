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
        <h2 class="h4 mb-1">Laporan Laba Rugi</h2>
        <div class="text-muted"><?= e((string) $filters['date_from']) ?> s.d. <?= e((string) $filters['date_to']) ?></div>
        <div class="text-muted"><?= e($selectedPeriod['period_name'] ?? 'Filter tanggal manual') ?></div>
    </div>

    <table class="table table-bordered print-table income-print-table">
        <thead>
        <tr>
            <th style="width: 18%;">Kode Akun</th>
            <th>Nama Akun</th>
            <th style="width: 18%;" class="text-end">Nilai</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td colspan="3" class="fw-semibold bg-light">Pendapatan</td>
        </tr>
        <?php if ($report['revenue_rows'] === []): ?>
            <tr>
                <td colspan="3" class="text-center text-muted">Tidak ada akun pendapatan untuk filter yang dipilih.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($report['revenue_rows'] as $row): ?>
                <tr>
                    <td><?= e((string) $row['account_code']) ?></td>
                    <td><?= e((string) $row['account_name']) ?></td>
                    <td class="text-end"><?= e(ledger_currency((float) $row['amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        <tr>
            <td colspan="2" class="text-end fw-semibold">Total Pendapatan</td>
            <td class="text-end fw-bold"><?= e(ledger_currency((float) $report['total_revenue'])) ?></td>
        </tr>

        <tr>
            <td colspan="3" class="fw-semibold bg-light">Beban</td>
        </tr>
        <?php if ($report['expense_rows'] === []): ?>
            <tr>
                <td colspan="3" class="text-center text-muted">Tidak ada akun beban untuk filter yang dipilih.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($report['expense_rows'] as $row): ?>
                <tr>
                    <td><?= e((string) $row['account_code']) ?></td>
                    <td><?= e((string) $row['account_name']) ?></td>
                    <td class="text-end"><?= e(ledger_currency((float) $row['amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        <tr>
            <td colspan="2" class="text-end fw-semibold">Total Beban</td>
            <td class="text-end fw-bold"><?= e(ledger_currency((float) $report['total_expense'])) ?></td>
        </tr>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="2" class="text-end"><?= e(profit_loss_result_label((float) $report['net_income'])) ?></th>
            <th class="text-end"><?= e(ledger_currency(abs((float) $report['net_income']))) ?></th>
        </tr>
        </tfoot>
    </table>

    <div class="mt-4 text-end text-muted small">Dicetak pada <?= e(date('d-m-Y H:i')) ?></div>
</section>
<script>
    window.print();
</script>
