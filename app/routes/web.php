<?php

declare(strict_types=1);

$router->get('/', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/pimpinan', [DashboardController::class, 'leadership'], [[RoleMiddleware::class, ['admin', 'bendahara', 'pimpinan']]]);
$router->get('/eis', [DashboardController::class, 'index'], [AuthMiddleware::class]);

$router->get('/settings/profile', [ProfileController::class, 'index'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/settings/profile', [ProfileController::class, 'save'], [[RoleMiddleware::class, ['admin']]]);

$router->get('/business-units', [BusinessUnitController::class, 'index'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/business-units/create', [BusinessUnitController::class, 'create'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/business-units/store', [BusinessUnitController::class, 'store'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/business-units/edit', [BusinessUnitController::class, 'edit'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/business-units/update', [BusinessUnitController::class, 'update'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/business-units/toggle-active', [BusinessUnitController::class, 'toggleActive'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/business-units/delete', [BusinessUnitController::class, 'delete'], [[RoleMiddleware::class, ['admin']]]);

$router->get('/user-accounts', [UserAccountController::class, 'index'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/user-accounts/create', [UserAccountController::class, 'create'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/user-accounts/store', [UserAccountController::class, 'store'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/user-accounts/edit', [UserAccountController::class, 'edit'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/user-accounts/update', [UserAccountController::class, 'update'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/user-accounts/toggle-active', [UserAccountController::class, 'toggleActive'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/audit-logs', [AuditLogController::class, 'index'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/backups', [BackupController::class, 'index'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/backups/create', [BackupController::class, 'create'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/backups/download', [BackupController::class, 'download'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/backups/restore', [BackupController::class, 'restore'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/backups/delete', [BackupController::class, 'delete'], [[RoleMiddleware::class, ['admin']]]);

$router->get('/updates', [UpdateController::class, 'index'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/updates/check', [UpdateController::class, 'check'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/updates/apply', [UpdateController::class, 'apply'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/updates/report', [UpdateController::class, 'report'], [[RoleMiddleware::class, ['admin']]]);

$router->get('/coa', [CoaController::class, 'index'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/coa/create', [CoaController::class, 'create'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/coa/store', [CoaController::class, 'store'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/coa/edit', [CoaController::class, 'edit'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/coa/update', [CoaController::class, 'update'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/coa/toggle-active', [CoaController::class, 'toggleActive'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/coa/delete', [CoaController::class, 'delete'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/coa/export', [CoaController::class, 'export'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/coa/seed-global', [CoaController::class, 'seedGlobalDefaults'], [[RoleMiddleware::class, ['admin']]]);


$router->get('/assets', [AssetController::class, 'index'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/assets/create', [AssetController::class, 'create'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/assets/store', [AssetController::class, 'store'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/assets/edit', [AssetController::class, 'edit'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/assets/update', [AssetController::class, 'update'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/assets/delete', [AssetController::class, 'delete'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/assets/detail', [AssetController::class, 'detail'], [AuthMiddleware::class]);
$router->get('/assets/card-print', [AssetController::class, 'cardPrint'], [AuthMiddleware::class]);
$router->post('/assets/mutation-store', [AssetController::class, 'storeMutation'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/assets/template', [AssetController::class, 'template'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/assets/import', [AssetController::class, 'import'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/assets/export', [AssetController::class, 'export'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/assets/journal/acquisition', [AssetController::class, 'postAcquisitionJournal'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/assets/depreciation/post', [AssetController::class, 'postDepreciation'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/assets/reports/snapshot', [AssetController::class, 'buildSnapshot'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/assets/categories', [AssetController::class, 'categories'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/assets/categories/create', [AssetController::class, 'categoryCreate'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/assets/categories/store', [AssetController::class, 'categoryStore'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/assets/categories/edit', [AssetController::class, 'categoryEdit'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/assets/categories/update', [AssetController::class, 'categoryUpdate'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/assets/categories/toggle-active', [AssetController::class, 'categoryToggleActive'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/assets/depreciation', [AssetController::class, 'depreciation'], [AuthMiddleware::class]);
$router->post('/assets/depreciation/rebuild', [AssetController::class, 'rebuildDepreciation'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/assets/reports', [AssetController::class, 'reports'], [AuthMiddleware::class]);
$router->get('/assets/reports/print', [AssetController::class, 'reportPrint'], [AuthMiddleware::class]);
$router->get('/assets/reports/pdf', [AssetController::class, 'reportPdf'], [AuthMiddleware::class]);

$router->get('/periods/select-working', [PeriodController::class, 'selectWorking'], [AuthMiddleware::class]);
$router->post('/periods/switch-working', [PeriodController::class, 'switchWorking'], [AuthMiddleware::class]);
$router->get('/periods', [PeriodController::class, 'index'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/periods/create', [PeriodController::class, 'create'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/periods/checklist', [PeriodController::class, 'checklist'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/periods/store', [PeriodController::class, 'store'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/periods/edit', [PeriodController::class, 'edit'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/periods/update', [PeriodController::class, 'update'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/periods/set-active', [PeriodController::class, 'setActive'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/periods/toggle-status', [PeriodController::class, 'toggleStatus'], [[RoleMiddleware::class, ['admin']]]);
$router->get('/periods/year-end-close', [PeriodController::class, 'yearEndClose'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/periods/year-end-close', [PeriodController::class, 'executeYearEndClose'], [[RoleMiddleware::class, ['admin']]]);

$router->get('/journals', [JournalController::class, 'index'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/journals/create', [JournalController::class, 'create'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/journals/store', [JournalController::class, 'store'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/journals/edit', [JournalController::class, 'edit'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/journals/update', [JournalController::class, 'update'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/journals/delete', [JournalController::class, 'delete'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/journals/bulk-action', [JournalController::class, 'bulkAction'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/journals/detail', [JournalController::class, 'detail'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/journals/print', [JournalController::class, 'print'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/journals/print-receipt', [JournalController::class, 'printReceipt'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/journals/print-list', [JournalController::class, 'printList'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/journals/attachments/upload', [JournalController::class, 'uploadAttachment'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/journals/attachments/download', [JournalController::class, 'downloadAttachment'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/journals/attachments/delete', [JournalController::class, 'deleteAttachment'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/journals/export', [JournalController::class, 'export'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);

$router->get('/ledger', [LedgerController::class, 'index'], [AuthMiddleware::class]);
$router->get('/ledger/print', [LedgerController::class, 'print'], [AuthMiddleware::class]);
$router->get('/ledger/pdf', [LedgerController::class, 'pdf'], [AuthMiddleware::class]);

$router->get('/trial-balance', [TrialBalanceController::class, 'index'], [AuthMiddleware::class]);
$router->get('/trial-balance/print', [TrialBalanceController::class, 'print'], [AuthMiddleware::class]);
$router->get('/trial-balance/pdf', [TrialBalanceController::class, 'pdf'], [AuthMiddleware::class]);

$router->get('/profit-loss', [ProfitLossController::class, 'index'], [AuthMiddleware::class]);
$router->get('/profit-loss/print', [ProfitLossController::class, 'print'], [AuthMiddleware::class]);
$router->get('/profit-loss/pdf', [ProfitLossController::class, 'pdf'], [AuthMiddleware::class]);

$router->get('/balance-sheet', [BalanceSheetController::class, 'index'], [AuthMiddleware::class]);
$router->get('/balance-sheet/print', [BalanceSheetController::class, 'print'], [AuthMiddleware::class]);
$router->get('/balance-sheet/pdf', [BalanceSheetController::class, 'pdf'], [AuthMiddleware::class]);

$router->get('/cash-flow', [CashFlowController::class, 'index'], [AuthMiddleware::class]);
$router->get('/cash-flow/print', [CashFlowController::class, 'print'], [AuthMiddleware::class]);
$router->get('/cash-flow/pdf', [CashFlowController::class, 'pdf'], [AuthMiddleware::class]);

$router->get('/equity-changes', [EquityChangesController::class, 'index'], [AuthMiddleware::class]);
$router->get('/equity-changes/print', [EquityChangesController::class, 'print'], [AuthMiddleware::class]);
$router->get('/equity-changes/pdf', [EquityChangesController::class, 'pdf'], [AuthMiddleware::class]);

$router->get('/financial-notes', [FinancialNotesController::class, 'index'], [AuthMiddleware::class]);
$router->get('/financial-notes/print', [FinancialNotesController::class, 'print'], [AuthMiddleware::class]);
$router->get('/financial-notes/pdf', [FinancialNotesController::class, 'pdf'], [AuthMiddleware::class]);

$router->get('/lpj', [LpjPackageController::class, 'index'], [AuthMiddleware::class]);
$router->post('/lpj', [LpjPackageController::class, 'index'], [AuthMiddleware::class]);
$router->post('/lpj/print', [LpjPackageController::class, 'print'], [AuthMiddleware::class]);
$router->post('/lpj/pdf', [LpjPackageController::class, 'pdf'], [AuthMiddleware::class]);

$router->get('/bank-reconciliations', [BankReconciliationController::class, 'index'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/bank-reconciliations/store', [BankReconciliationController::class, 'store'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/bank-reconciliations/auto-match', [BankReconciliationController::class, 'autoMatch'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/bank-reconciliations/manual-match', [BankReconciliationController::class, 'manualMatch'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/bank-reconciliations/ignore-line', [BankReconciliationController::class, 'ignoreLine'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/bank-reconciliations/reset-line', [BankReconciliationController::class, 'resetLine'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/bank-reconciliations/reset-all', [BankReconciliationController::class, 'resetAll'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/bank-reconciliations/delete', [BankReconciliationController::class, 'delete'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/bank-reconciliations/print', [BankReconciliationController::class, 'print'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);


if (class_exists('ReferenceMasterController')) {
    $router->get('/reference-masters', [ReferenceMasterController::class, 'index'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
    $router->get('/reference-masters/create', [ReferenceMasterController::class, 'create'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
    $router->post('/reference-masters/store', [ReferenceMasterController::class, 'store'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
    $router->get('/reference-masters/edit', [ReferenceMasterController::class, 'edit'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
    $router->post('/reference-masters/update', [ReferenceMasterController::class, 'update'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
    $router->post('/reference-masters/delete', [ReferenceMasterController::class, 'delete'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
}

if (class_exists('ReceivableLedgerController')) {
    $router->get('/receivable-ledgers', [ReceivableLedgerController::class, 'index'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
    $router->get('/receivable-ledgers/print', [ReceivableLedgerController::class, 'print'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
}

if (class_exists('PayableLedgerController')) {
    $router->get('/payable-ledgers', [PayableLedgerController::class, 'index'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
    $router->get('/payable-ledgers/print', [PayableLedgerController::class, 'print'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
}

$router->get('/imports', [ImportController::class, 'index'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->get('/imports/template', [ImportController::class, 'template'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
$router->post('/imports/coa', [ImportController::class, 'importCoa'], [[RoleMiddleware::class, ['admin']]]);
$router->post('/imports/journal', [ImportController::class, 'importJournal'], [[RoleMiddleware::class, ['admin', 'bendahara']]]);
