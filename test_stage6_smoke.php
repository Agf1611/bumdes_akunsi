<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
require __DIR__ . '/app/bootstrap.php';

$quickTemplate = journal_quick_template_data('cash_in');
if (!is_array($quickTemplate)) {
    fwrite(STDERR, "quick template missing\n");
    exit(1);
}
ob_start();
render_view('journals/views/form', [
    'title' => 'Tambah Jurnal Umum',
    'header' => null,
    'formData' => [
        'journal_no' => 'Otomatis saat disimpan',
        'journal_date' => $quickTemplate['journal_date'],
        'description' => $quickTemplate['description'],
        'period_id' => '',
        'business_unit_id' => '',
        'print_template' => $quickTemplate['print_template'],
    ],
    'receiptData' => $quickTemplate['receipt'],
    'detailRows' => $quickTemplate['detail_rows'],
    'periodOptions' => [],
    'accountOptions' => [],
    'unitOptions' => [],
    'receiptPartyTitleOptions' => journal_receipt_party_title_options(),
    'receiptFeatureStatus' => ['enabled' => false, 'has_print_template_column' => false, 'has_journal_receipts_table' => false],
    'quickTemplateOptions' => journal_quick_template_options(),
    'activeQuickTemplate' => $quickTemplate,
], 'main');
$html = ob_get_clean();
if (strpos($html, 'Transaksi Cepat') === false || strpos($html, 'Mode cepat aktif') === false) {
    fwrite(STDERR, "form render missing quick template UI\n");
    exit(1);
}

echo "SMOKE_OK\n";
