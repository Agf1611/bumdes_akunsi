<?php declare(strict_types=1); ?>
<?php
$qty = (float) ($row['quantity'] ?? 1);
$unitName = (string) (($row['unit_name'] ?? '') !== '' ? $row['unit_name'] : 'unit');
$unitCost = $qty > 0 ? ((float) ($row['acquisition_cost'] ?? 0) / $qty) : (float) ($row['acquisition_cost'] ?? 0);
?>
<?php render_print_header($profile, 'Kartu Aset', e((string) $row['asset_code']) . ' · ' . e((string) $row['asset_name']), business_unit_label($row['business_unit_id'] ? ['unit_code' => $row['business_unit_code'] ?? ($row['unit_code'] ?? ''), 'unit_name' => $row['business_unit_name'] ?? ''] : null)); ?>
<div class="print-sheet">
    <table class="table table-sm table-bordered align-middle mb-3 print-table">
        <tbody>
        <tr><th>Kode Aset</th><td><?= e((string) $row['asset_code']) ?></td><th>Nama Aset</th><td><?= e((string) $row['asset_name']) ?></td></tr>
        <tr><th>Kategori</th><td><?= e((string) $row['category_name']) ?> / <?= e(asset_group_label((string) $row['asset_group'])) ?></td><th>Subkategori</th><td><?= e((string) (($row['subcategory_name'] ?? '') !== '' ? $row['subcategory_name'] : '-')) ?></td></tr>
        <tr><th>Qty</th><td><?= e((string) number_format($qty, 0, ',', '.')) ?> <?= e($unitName) ?></td><th>Harga per Unit</th><td><?= e(asset_currency($unitCost)) ?></td></tr>
        <tr><th>Total Nilai</th><td><?= e(asset_currency((float) $row['acquisition_cost'])) ?></td><th>Nilai Buku</th><td><?= e(asset_currency((float) (($row['current_book_value'] ?? $row['acquisition_cost']) ?: 0))) ?></td></tr>
        <tr><th>Tanggal Perolehan</th><td><?= e(asset_safe_date((string) $row['acquisition_date'])) ?></td><th>Lokasi</th><td><?= e((string) (($row['location'] ?? '') !== '' ? $row['location'] : '-')) ?></td></tr>
        <tr><th>Status</th><td><?= e(asset_status_label((string) $row['asset_status'])) ?></td><th>Kondisi</th><td><?= e(asset_condition_label((string) $row['condition_status'])) ?></td></tr>
        <tr><th>Link Jurnal</th><td><?= e((string) (($row['linked_journal_no'] ?? '') !== '' ? $row['linked_journal_no'] : '-')) ?></td><th>Status Sinkron</th><td><?= e(asset_sync_status_label((string) ($row['acquisition_sync_status'] ?? 'NONE'))) ?></td></tr>
        <tr><th>Sumber Dana</th><td colspan="3"><?= e(asset_funding_label((string) ($row['source_of_funds'] ?? ''))) ?><?= (string) (($row['funding_source_detail'] ?? '') !== '' ? ' · ' . $row['funding_source_detail'] : '') ?></td></tr>
        <tr><th>Catatan</th><td colspan="3"><?= nl2br(e((string) (($row['notes'] ?? '') !== '' ? $row['notes'] : '-'))) ?></td></tr>
        </tbody>
    </table>
</div>
<?php render_print_signature($profile); ?>
