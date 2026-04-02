<?php declare(strict_types=1); ?>
<?php
$rows = is_array($rows ?? null) ? $rows : [];
$summary = is_array($summary ?? null) ? $summary : [];
$profile = is_array($profile ?? null) ? $profile : app_profile();
$asOfDate = (string) ($asOfDate ?? date('Y-m-d'));
$selectedUnitLabel = (string) ($selectedUnitLabel ?? 'Semua Unit');

$totalQty = (float) ($summary['total_quantity'] ?? 0);
$showUnitColumn = trim($selectedUnitLabel) === '' || strcasecmp(trim($selectedUnitLabel), 'Semua Unit') === 0;

$extractNameMeta = static function (array $row): array {
    $originalName = trim((string) ($row['asset_name'] ?? '-'));
    $normalized = $originalName;
    $qty = null;
    $unit = null;

    $patterns = [
        '/\((\d+)\s*(unit|pcs|pc|buah|roll|pack)\)/iu',
        '/\b(\d+)\s*(unit|pcs|pc|buah|roll|pack)\b/iu',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $originalName, $m)) {
            $qty = (int) $m[1];
            $unit = strtolower((string) $m[2]);
            $normalized = trim((string) preg_replace($pattern, '', $originalName, 1));
            $normalized = trim(preg_replace('/\s{2,}/', ' ', $normalized) ?: $normalized);
            break;
        }
    }

    return [
        'display_name' => $normalized !== '' ? $normalized : $originalName,
        'qty' => $qty,
        'unit' => $unit,
    ];
};

$assetQty = static function (array $row) use ($extractNameMeta): int {
    $rawQty = (int) round((float) ($row['quantity'] ?? 0));
    $meta = $extractNameMeta($row);
    if ($rawQty <= 1 && isset($meta['qty']) && (int) $meta['qty'] > 1) {
        return (int) $meta['qty'];
    }
    return max(1, $rawQty);
};

$formatQty = static function ($value): string {
    return number_format(max(1, (int) $value), 0, ',', '.');
};

$assetUnitName = static function (array $row) use ($extractNameMeta): string {
    $meta = $extractNameMeta($row);
    if (!empty($meta['unit'])) {
        $u = strtolower((string) $meta['unit']);
        return match ($u) {
            'pc', 'pcs' => 'pcs',
            'buah' => 'buah',
            'roll' => 'roll',
            'pack' => 'pack',
            default => 'unit',
        };
    }

    $name = trim((string) (($row['asset_unit_name'] ?? $row['item_unit_name'] ?? $row['quantity_unit_name'] ?? $row['unit_name_item'] ?? $row['asset_unit'] ?? $row['unit_name_asset'] ?? $row['qty_unit_name'] ?? 'unit')));
    return $name !== '' ? $name : 'unit';
};

$assetDisplayName = static function (array $row) use ($extractNameMeta): string {
    $meta = $extractNameMeta($row);
    return (string) ($meta['display_name'] ?? (string) ($row['asset_name'] ?? '-'));
};

$assetUnitCost = static function (array $row) use ($assetQty): float {
    if (isset($row['unit_cost']) && (float) $row['unit_cost'] > 0) {
        return (float) $row['unit_cost'];
    }
    $qty = $assetQty($row);
    $cost = (float) ($row['acquisition_cost'] ?? 0);
    return $qty > 0 ? $cost / $qty : $cost;
};

$unitBadge = static function (array $row): string {
    $code = trim((string) ($row['unit_code'] ?? ''));
    if ($code !== '') {
        return $code;
    }
    $label = trim((string) ($row['unit_name'] ?? ''));
    return $label !== '' ? report_compact_text($label, 8) : '-';
};

render_print_header(
    $profile,
    'Laporan Aset',
    'Per ' . format_id_date($asOfDate),
    $selectedUnitLabel
);
?>
<style>
@page{size:A4 landscape;margin:11mm 10mm 11mm 10mm}
.asset-print-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;margin:0 0 10px}
.asset-print-summary-card{border:1px solid #b8c2cc;border-radius:8px;padding:7px 9px;background:#fafbfd}
.asset-print-summary-card .label{font-size:8px;font-weight:700;color:#5b6472;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px}
.asset-print-summary-card .value{font-size:12px;font-weight:800;color:#111827}
.asset-print-table{width:100%;table-layout:fixed;border-collapse:collapse;font-size:8.2px;line-height:1.14;margin-bottom:0}
.asset-print-table thead{display:table-header-group}
.asset-print-table tfoot{display:table-row-group}
.asset-print-table tr{page-break-inside:avoid;break-inside:avoid}
.asset-print-table th,.asset-print-table td{border:1px solid #aeb8c3;padding:3px 4px;vertical-align:top;word-wrap:break-word;overflow-wrap:anywhere}
.asset-print-table thead th{background:#eef2f6;font-weight:800;text-transform:uppercase;letter-spacing:.02em;text-align:center}
.asset-col-no{width:3%}
.asset-col-code{width:8.5%}
.asset-col-name{width:18%}
.asset-col-category{width:9.5%}
.asset-col-unit-business{width:4.5%}
.asset-col-qty{width:3.5%}
.asset-col-uom{width:4%}
.asset-col-date{width:6.5%}
.asset-col-unit-cost{width:8%}
.asset-col-total{width:8%}
.asset-col-accum{width:7.5%}
.asset-col-book{width:8%}
.asset-col-location{width:9%}
.asset-col-status{width:4.5%}
.asset-code{font-weight:700;word-break:break-word}
.asset-name{font-weight:700}
.asset-cat{color:#374151}
.asset-location{font-size:7.9px}
.asset-nowrap{white-space:nowrap}
.asset-status{font-size:7.8px;font-weight:700;text-transform:uppercase;text-align:center}
.asset-total-row th{background:#f8fafc;font-weight:800}
.asset-notes{margin-top:4mm;font-size:8.2px;color:#4b5563}
.asset-signature-wrap{margin-top:5mm}
@media print{
  .asset-print-summary{gap:6px;margin-bottom:8px}
}
</style>

<div class="asset-print-summary">
    <div class="asset-print-summary-card"><div class="label">Jumlah Register</div><div class="value"><?= e(number_format((int) ($summary['asset_count'] ?? count($rows)), 0, ',', '.')) ?></div></div>
    <div class="asset-print-summary-card"><div class="label">Total Qty</div><div class="value"><?= e(number_format((float) $totalQty, 0, ',', '.')) ?></div></div>
    <div class="asset-print-summary-card"><div class="label">Total Nilai</div><div class="value"><?= e(asset_currency((float) ($summary['total_cost'] ?? 0))) ?></div></div>
    <div class="asset-print-summary-card"><div class="label">Akum. Penyusutan</div><div class="value"><?= e(asset_currency((float) ($summary['total_accumulated_depreciation'] ?? 0))) ?></div></div>
    <div class="asset-print-summary-card"><div class="label">Nilai Buku</div><div class="value"><?= e(asset_currency((float) ($summary['total_book_value'] ?? 0))) ?></div></div>
</div>

<section class="print-sheet">
    <table class="asset-print-table">
        <thead>
            <tr>
                <th class="asset-col-no">No</th>
                <th class="asset-col-code">Kode</th>
                <th class="asset-col-name">Nama Aset</th>
                <th class="asset-col-category">Kategori</th>
                <?php if ($showUnitColumn): ?><th class="asset-col-unit-business">Unit</th><?php endif; ?>
                <th class="asset-col-qty">Qty</th>
                <th class="asset-col-uom">Sat</th>
                <th class="asset-col-date">Tgl</th>
                <th class="asset-col-unit-cost">Harga/Unit</th>
                <th class="asset-col-total">Total</th>
                <th class="asset-col-accum">Akum</th>
                <th class="asset-col-book">Nilai Buku</th>
                <th class="asset-col-location">Lokasi</th>
                <th class="asset-col-status">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="<?= e((string) ($showUnitColumn ? 14 : 13)) ?>" class="text-center">Belum ada data aset.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $rowNo => $row): ?>
                    <?php
                    $qty = $assetQty($row);
                    $unitName = $assetUnitName($row);
                    $unitCost = $assetUnitCost($row);
                    $bookValue = (float) (($row['current_book_value'] ?? $row['acquisition_cost']) ?: 0);
                    $location = trim((string) ($row['location'] ?? ''));
                    $status = asset_status_label((string) ($row['asset_status'] ?? 'ACTIVE'));
                    $category = trim((string) ($row['category_name'] ?? '-'));
                    ?>
                    <tr>
                        <td class="asset-nowrap text-center"><?= e((string) ($rowNo + 1)) ?></td>
                        <td class="asset-code"><?= e((string) ($row['asset_code'] ?? '-')) ?></td>
                        <td><span class="asset-name"><?= e($assetDisplayName($row)) ?></span></td>
                        <td class="asset-cat"><?= e(report_compact_text($category, 18)) ?></td>
                        <?php if ($showUnitColumn): ?><td class="text-center asset-nowrap"><?= e($unitBadge($row)) ?></td><?php endif; ?>
                        <td class="text-center asset-nowrap"><?= e($formatQty($qty)) ?></td>
                        <td class="text-center asset-nowrap"><?= e($unitName) ?></td>
                        <td class="text-center asset-nowrap"><?= e(asset_safe_date((string) ($row['acquisition_date'] ?? ''))) ?></td>
                        <td class="text-end asset-nowrap"><?= e(asset_currency($unitCost)) ?></td>
                        <td class="text-end asset-nowrap"><?= e(asset_currency((float) ($row['acquisition_cost'] ?? 0))) ?></td>
                        <td class="text-end asset-nowrap"><?= e(asset_currency((float) ($row['current_accumulated_depreciation'] ?? 0))) ?></td>
                        <td class="text-end asset-nowrap"><strong><?= e(asset_currency($bookValue)) ?></strong></td>
                        <td class="asset-location"><?= e($location !== '' ? report_compact_text($location, 26) : '-') ?></td>
                        <td class="asset-status"><?= e($status) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="asset-total-row">
                <th colspan="<?= e((string) ($showUnitColumn ? 6 : 5)) ?>" class="text-end">Total</th>
                <th class="text-center asset-nowrap"><?= e(number_format((float) $totalQty, 0, ',', '.')) ?></th>
                <th></th>
                <th></th>
                <th class="text-end asset-nowrap"><?= e(asset_currency((float) ($summary['total_cost'] ?? 0))) ?></th>
                <th class="text-end asset-nowrap"><?= e(asset_currency((float) ($summary['total_accumulated_depreciation'] ?? 0))) ?></th>
                <th class="text-end asset-nowrap"><?= e(asset_currency((float) ($summary['total_book_value'] ?? 0))) ?></th>
                <th></th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <div class="asset-notes"><strong>Catatan:</strong> Print ini ditata untuk pemeriksaan fisik aset dan audit internal BUMDes. COP/letterhead hanya ditampilkan di awal laporan agar hasil cetak lebih ringkas dan profesional.</div>
    <div class="asset-signature-wrap"><?php render_print_signature($profile); ?></div>
</section>
