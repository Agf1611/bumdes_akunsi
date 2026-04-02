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
        <h2 class="h4 mb-1">Laporan Arus Kas Sederhana</h2>
        <div class="text-muted">Periode <?= e((string) $filters['date_from']) ?> s.d. <?= e((string) $filters['date_to']) ?></div>
        <div class="text-muted"><?= e($selectedPeriod['period_name'] ?? 'Filter tanggal manual') ?></div>
    </div>

    <?php foreach ($warnings as $warning): ?>
        <div class="alert alert-warning mb-3"><?= e($warning) ?></div>
    <?php endforeach; ?>

    <div class="mb-3 text-muted small">
        <strong>Asumsi versi awal:</strong>
        <?= e(implode(' ', $assumptions)) ?>
    </div>

    <?php
    $sections = [
        'OPERATING' => ['title' => 'Aktivitas Operasional', 'rows' => $report['operating_rows'], 'total' => $report['total_operating']],
        'INVESTING' => ['title' => 'Aktivitas Investasi', 'rows' => $report['investing_rows'], 'total' => $report['total_investing']],
        'FINANCING' => ['title' => 'Aktivitas Pendanaan', 'rows' => $report['financing_rows'], 'total' => $report['total_financing']],
    ];
    ?>

    <?php foreach ($sections as $section): ?>
        <h3 class="h6 mt-4 mb-2"><?= e($section['title']) ?></h3>
        <table class="table table-bordered print-table cash-flow-print-table mb-3">
            <thead>
            <tr>
                <th style="width: 13%;">Tanggal</th>
                <th style="width: 14%;">Nomor</th>
                <th>Keterangan</th>
                <th style="width: 16%;" class="text-end">Kas Masuk</th>
                <th style="width: 16%;" class="text-end">Kas Keluar</th>
                <th style="width: 16%;" class="text-end">Bersih</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($section['rows'] === []): ?>
                <tr><td colspan="6" class="text-center text-muted">Tidak ada mutasi untuk bagian ini.</td></tr>
            <?php else: ?>
                <?php foreach ($section['rows'] as $row): ?>
                    <tr>
                        <td><?= e((string) $row['journal_date']) ?></td>
                        <td><?= e((string) $row['journal_no']) ?></td>
                        <td>
                            <div><?= e((string) $row['description']) ?></div>
                            <?php if ($row['classification_note'] !== ''): ?>
                                <div class="small text-muted"><?= e((string) $row['classification_note']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= e(ledger_currency((float) $row['cash_in'])) ?></td>
                        <td class="text-end"><?= e(ledger_currency((float) $row['cash_out'])) ?></td>
                        <td class="text-end"><?= e(ledger_currency(abs((float) $row['net_amount']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="5" class="text-end">Total <?= e($section['title']) ?></th>
                <th class="text-end"><?= e(ledger_currency(abs((float) $section['total']))) ?></th>
            </tr>
            </tfoot>
        </table>
    <?php endforeach; ?>

    <table class="table table-bordered print-table cash-flow-print-table mt-4">
        <tbody>
        <tr>
            <th>Kas Awal</th>
            <td class="text-end"><?= e(ledger_currency((float) $report['opening_cash'])) ?></td>
        </tr>
        <tr>
            <th>Arus Kas Bersih Operasional</th>
            <td class="text-end"><?= e(ledger_currency((float) $report['total_operating'])) ?></td>
        </tr>
        <tr>
            <th>Arus Kas Bersih Investasi</th>
            <td class="text-end"><?= e(ledger_currency((float) $report['total_investing'])) ?></td>
        </tr>
        <tr>
            <th>Arus Kas Bersih Pendanaan</th>
            <td class="text-end"><?= e(ledger_currency((float) $report['total_financing'])) ?></td>
        </tr>
        <tr>
            <th>Kenaikan / Penurunan Kas Bersih</th>
            <td class="text-end"><?= e(ledger_currency((float) $report['net_cash_change'])) ?></td>
        </tr>
        <tr>
            <th>Kas Akhir</th>
            <td class="text-end fw-bold"><?= e(ledger_currency((float) $report['ending_cash'])) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="mt-4 text-end text-muted small">Dicetak pada <?= e(date('d-m-Y H:i')) ?></div>
</section>
<script>
    window.print();
</script>
