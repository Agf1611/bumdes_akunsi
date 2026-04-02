<?php
// Jalankan dari root project: php scripts/final_smoke_check.php
$root = dirname(__DIR__);
$checks = [];

$files = [
    'app/helpers/common_helper.php',
    'app/helpers/journal_helper.php',
    'app/helpers/coa_helper.php',
    'app/helpers/cash_flow_helper.php',
    'app/helpers/report_layout_helper.php',
    'app/modules/cash_flow/CashFlowModel.php',
    'app/modules/cash_flow/CashFlowController.php',
    'app/modules/profit_loss/ProfitLossController.php',
    'app/modules/balance_sheet/BalanceSheetController.php',
    'app/modules/equity_changes/EquityChangesController.php',
    'app/modules/lpj_package/LpjPackageService.php',
];

foreach ($files as $rel) {
    $checks[] = [
        'label' => $rel,
        'ok' => is_file($root . DIRECTORY_SEPARATOR . $rel),
    ];
}

foreach ([
    'app/helpers/common_helper.php',
    'app/helpers/journal_helper.php',
    'app/helpers/coa_helper.php',
    'app/helpers/cash_flow_helper.php',
    'app/helpers/report_layout_helper.php',
] as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . $rel;
    if (is_file($path)) {
        require_once $path;
    }
}

$functions = [
    'format_date',
    'format_currency',
    'journal_reference_meta_items',
    'journal_receipt_completion_summary',
    'cash_flow_determine_section',
    'report_reconciliation_badge_class',
    'report_reconciliation_status',
    'report_reconciliation_note',
];

foreach ($functions as $fn) {
    $checks[] = [
        'label' => 'function ' . $fn,
        'ok' => function_exists($fn),
    ];
}

$passed = 0;
$failed = 0;
foreach ($checks as $check) {
    if ($check['ok']) {
        $passed++;
        echo '[OK] ' . $check['label'] . PHP_EOL;
    } else {
        $failed++;
        echo '[FAIL] ' . $check['label'] . PHP_EOL;
    }
}

echo PHP_EOL;
echo 'TOTAL OK   : ' . $passed . PHP_EOL;
echo 'TOTAL FAIL : ' . $failed . PHP_EOL;
exit($failed > 0 ? 1 : 0);
