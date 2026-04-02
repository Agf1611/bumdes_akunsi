<?php
declare(strict_types=1);

function e($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function base_url($p=''){return $p;}
function csrf_token(){return 'token';}
function old($k,$d=''){return $d;}
function old_input($k,$d=null){return $d;}
function app_profile(){return ['bumdes_name'=>'BUMDes Test'];}
function upload_url($p=''){return $p;}
function public_path($p=''){return $p;}
function format_id_date($d){return (string)$d;}
function format_id_long_date($d){return (string)$d;}
function business_unit_label($unit=null){return is_array($unit) ? (($unit['unit_code'] ?? 'UT') . ' - ' . ($unit['unit_name'] ?? 'Unit')) : 'Semua Unit';}
function current_accounting_period_label(){return 'Periode Test';}
function selected_unit_from_filters(array $filters){return null;}
function profile_treasurer_position(array $profile){return 'Bendahara';}
function profile_treasurer_name(array $profile){return 'Bendahara Test';}
function profile_director_name(array $profile){return 'Direktur Test';}
function active_period_label($start,$end){return $start.' s.d. '.$end;}
function ledger_currency($amount){return 'Rp '.number_format((float)$amount, 2, ',', '.');}
function dashboard_currency($amount){return ledger_currency($amount);}
function dashboard_compact_currency($amount){return ledger_currency($amount);}
function dashboard_balance_badge_class($amount){return (float)$amount >= 0 ? 'ok' : 'bad';}
function dashboard_bar_percent($amount,$max){if ((float)$max <= 0) return 0; return min(100, max(0, (float)$amount / (float)$max * 100));}
function dashboard_month_label($monthKey){return (string)$monthKey;}
function asset_group_label($group){return (string)$group;}

class Auth {
    public static function user(): array {
        return ['full_name' => 'Tester', 'role_code' => 'admin', 'role_name' => 'Admin'];
    }
}

require __DIR__ . '/app/helpers/journal_helper.php';

function render_print_header($profile,$title,$period,$unit){echo '<div>'.e($title).'|'.e($period).'|'.e($unit).'</div>';}
function render_print_signature($profile){echo '<div>TTD</div>';}
function report_city_date($p){return 'Desa Test, '.date('Y-m-d');}
function report_treasurer_signature_data($p){return ['position'=>'Bendahara','name'=>'Bendahara Test','signature_url'=>''];}
function report_signature_data($p){return ['position'=>'Direktur','name'=>'Direktur Test','signature_url'=>''];}

function render_isolated(string $file, array $vars): string {
    extract($vars, EXTR_SKIP);
    ob_start();
    require $file;
    return (string) ob_get_clean();
}

$journalShared = [
    'title' => 'Tambah Jurnal Umum',
    'header' => ['id'=>1,'journal_no'=>'JU-1','journal_date'=>'2026-01-01','period_name'=>'2026','total_debit'=>1000,'total_credit'=>1000,'description'=>'Tes','print_template'=>'receipt','period_status'=>'OPEN','party_name'=>'PT Demo','purpose'=>'Pembayaran ATK','payment_method'=>'Transfer','reference_no'=>'INV-1','party_title'=>'Dibayar kepada','notes'=>'Catatan','amount_in_words'=>'seratus ribu rupiah'],
    'formData' => ['description'=>'Pembelian ATK','journal_date'=>'2026-01-01','journal_no'=>'Otomatis saat disimpan','period_id'=>'1','business_unit_id'=>'','print_template'=>'receipt'],
    'receiptData' => ['party_name'=>'PT Demo','purpose'=>'Pembayaran ATK','payment_method'=>'Transfer','reference_no'=>'INV-1','party_title'=>'Dibayar kepada','amount_in_words'=>'','notes'=>'Catatan'],
    'detailRows' => [
        ['coa_id'=>'1','line_description'=>'Kas keluar','debit_raw'=>'0','credit_raw'=>'100000','entry_tag'=>'OPERASIONAL'],
        ['coa_id'=>'2','line_description'=>'ATK','debit_raw'=>'100000','credit_raw'=>'0','entry_tag'=>'OPERASIONAL'],
    ],
    'accountOptions' => [['id'=>1,'account_code'=>'1.101','account_name'=>'Kas'],['id'=>2,'account_code'=>'5.101','account_name'=>'Beban ATK']],
    'periodOptions' => [['id'=>1,'period_name'=>'Januari 2026','period_code'=>'2026-01']],
    'unitOptions' => [['id'=>1,'unit_code'=>'UT','unit_name'=>'Unit Tes']],
    'receiptPartyTitleOptions' => journal_receipt_party_title_options(),
    'receiptFeatureStatus' => ['enabled'=>true,'has_print_template_column'=>true,'has_journal_receipts_table'=>true],
    'referenceOptions' => ['partners'=>[['id'=>1,'code'=>'P001','name'=>'PT Demo']], 'inventory'=>[], 'raw_materials'=>[], 'assets'=>[], 'savings'=>[], 'cashflow_components'=>[], 'entry_tags'=>['OPERASIONAL'=>'Operasional']],
    'quickTemplateOptions' => journal_quick_template_options(),
    'activeQuickTemplate' => null,
    'selectedUnitLabel' => 'UT - Unit Tes',
    'attachments' => [['id'=>1,'attachment_title'=>'Invoice','original_name'=>'invoice.pdf','attachment_notes'=>'Nota supplier','file_ext'=>'pdf','file_size'=>1024]],
    'attachmentFeatureStatus' => ['enabled' => true, 'has_journal_attachments_table' => true],
    'profile' => app_profile(),
    'reportTitle' => 'Jurnal Umum',
    'periodLabel' => 'Januari 2026',
    'details' => [['line_no'=>1,'account_code'=>'1.101','account_name'=>'Kas','line_description'=>'Kas keluar','debit'=>0,'credit'=>100000,'entry_tag'=>'OPERASIONAL','partner_name'=>'PT Demo']],
];

$receivableShared = [
    'featureStatus' => ['partners_table'=>true,'partner_id_column'=>true],
    'filters' => ['partner_id'=>0,'account_id'=>0,'period_id'=>1,'fiscal_year'=>2026,'unit_id'=>0,'date_from'=>'2026-01-01','date_to'=>'2026-01-31'],
    'periods' => [['id'=>1,'period_code'=>'2026-01','period_name'=>'Januari 2026']],
    'partners' => [['id'=>1,'partner_code'=>'P001','partner_name'=>'PT Demo']],
    'accounts' => [['id'=>1,'account_code'=>'1.201','account_name'=>'Piutang Usaha']],
    'units' => [['id'=>1,'unit_code'=>'UT','unit_name'=>'Unit Tes']],
    'rows' => [['journal_date'=>'2026-01-02','journal_no'=>'JU-1','partner_label'=>'P001 - PT Demo','account_code'=>'1.201','account_name'=>'Piutang Usaha','description'=>'Penjualan kredit','unit_label'=>'UT - Unit Tes','entry_tag'=>'OPERASIONAL','debit'=>100000,'credit'=>0,'balance'=>100000]],
    'summary' => ['opening_balance'=>0,'total_debit'=>100000,'total_credit'=>0,'closing_balance'=>100000],
    'movementSummary' => ['debit_total'=>100000,'credit_total'=>0,'journal_count'=>1,'partner_count'=>1,'last_transaction_date'=>'2026-01-02'],
    'agingBuckets' => [['label'=>'0-30 hari','amount'=>100000]],
    'topPartners' => [['partner_code'=>'P001','partner_name'=>'PT Demo','journal_count'=>1,'debit_total'=>100000,'credit_total'=>0,'balance'=>100000]],
    'hasFilters' => true,
    'selectedPeriod' => ['id'=>1,'period_name'=>'Januari 2026'],
    'selectedUnit' => null,
    'selectedPartner' => null,
    'selectedAccount' => null,
    'reportYears' => [2026],
    'profile' => app_profile(),
    'reportTitle' => 'Buku Pembantu Piutang',
    'periodLabel' => 'Januari 2026',
    'selectedUnitLabel' => 'Semua Unit',
];

$payableShared = [
    'filters' => ['partner_id'=>0,'account_id'=>0,'period_id'=>1,'fiscal_year'=>2026,'unit_id'=>0,'date_from'=>'2026-01-01','date_to'=>'2026-01-31'],
    'periods' => [['id'=>1,'period_code'=>'2026-01','period_name'=>'Januari 2026']],
    'partners' => [['id'=>1,'partner_code'=>'V001','partner_name'=>'CV Supplier']],
    'accounts' => [['id'=>1,'account_code'=>'2.101','account_name'=>'Utang Usaha']],
    'units' => [['id'=>1,'unit_code'=>'UT','unit_name'=>'Unit Tes']],
    'rows' => [['journal_date'=>'2026-01-03','journal_no'=>'JU-2','partner_label'=>'V001 - CV Supplier','account_label'=>'2.101 - Utang Usaha','description'=>'Pembelian kredit','unit_label'=>'UT - Unit Tes','debit'=>0,'credit'=>200000,'balance'=>200000]],
    'summary' => ['opening_balance'=>0,'total_debit'=>0,'total_credit'=>200000,'closing_balance'=>200000],
    'movementSummary' => ['debit_total'=>0,'credit_total'=>200000,'journal_count'=>1,'partner_count'=>1,'last_transaction_date'=>'2026-01-03'],
    'agingBuckets' => [['label'=>'0-30 hari','amount'=>200000]],
    'topPartners' => [['partner_code'=>'V001','partner_name'=>'CV Supplier','journal_count'=>1,'debit_total'=>0,'credit_total'=>200000,'balance'=>200000]],
    'hasFilters' => true,
    'selectedPeriod' => ['id'=>1,'period_name'=>'Januari 2026'],
    'selectedUnit' => null,
    'selectedPartner' => null,
    'selectedAccount' => null,
    'reportYears' => [2026],
    'profile' => app_profile(),
    'reportTitle' => 'Buku Pembantu Utang',
    'periodLabel' => 'Januari 2026',
    'selectedUnitLabel' => 'Semua Unit',
];

$dashboardShared = [
    'trend' => [['month_key'=>'2026-01','total_revenue'=>1000000,'total_expense'=>800000,'net_profit'=>200000,'journal_count'=>3]],
    'summary' => ['total_assets'=>3000000,'total_revenue'=>1000000,'total_expense'=>800000,'net_profit'=>200000,'journal_count'=>3,'active_accounts'=>10,'active_detail_accounts'=>8],
    'cashSummary' => ['cash_balance'=>500000,'cash_inflow'=>1000000,'cash_outflow'=>500000,'detected_accounts'=>[['account_code'=>'1.101','account_name'=>'Kas','balance'=>500000]]],
    'recentJournals' => [['id'=>1,'journal_no'=>'JU-1','journal_date'=>'2026-01-03','description'=>'Test','total_debit'=>100000,'unit_code'=>'UT','unit_name'=>'Unit Tes']],
    'unitSummaries' => [['unit_code'=>'UT','unit_name'=>'Unit Tes','journal_count'=>3,'total_revenue'=>1000000,'total_expense'=>800000]],
    'filterErrors' => [],
    'unitFeatureEnabled' => true,
    'selectedUnit' => ['id'=>1,'unit_code'=>'UT','unit_name'=>'Unit Tes'],
    'filters' => ['range_label'=>'01 Jan 2026 - 31 Jan 2026','period_id'=>1,'unit_id'=>1,'date_from'=>'2026-01-01','date_to'=>'2026-01-31','period'=>['id'=>1]],
    'periods' => [['id'=>1,'period_code'=>'2026-01','period_name'=>'Januari 2026']],
    'units' => [['id'=>1,'unit_code'=>'UT','unit_name'=>'Unit Tes']],
    'closingChecklist' => ['checks' => [['label'=>'Jurnal seimbang','status'=>'success','message'=>'OK'],['label'=>'Backup terbaru','status'=>'warning','message'=>'Perlu backup'],['label'=>'Rekonsiliasi bank','status'=>'critical','message'=>'Belum selesai']]],
];

$assetShared = [
    'title' => 'Form Aset',
    'row' => null,
    'formData' => ['entry_mode'=>'new','asset_code'=>'AST-001','asset_name'=>'Router','category_id'=>'1','subcategory_name'=>'Router','business_unit_id'=>'','acquisition_date'=>'2026-01-01','acquisition_cost'=>'1000000','opening_as_of_date'=>'','opening_accumulated_depreciation'=>'','residual_value'=>'0','useful_life_months'=>'36','depreciation_method'=>'straight_line','depreciation_start_date'=>'2026-01-01','depreciation_allowed'=>'1','is_active'=>'1','location'=>'Kantor','supplier_name'=>'CV Teknologi','reference_no'=>'INV-01','source_of_funds'=>'usaha','funding_source_detail'=>'Laba usaha','offset_coa_id'=>'','linked_journal_id'=>'','condition_status'=>'good','asset_status'=>'active','description'=>'Router utama','notes'=>'Catatan'],
    'entryModes' => ['new'=>'Perolehan Baru'],
    'categories' => [['id'=>1,'asset_group'=>'fixed','default_useful_life_months'=>36,'depreciation_allowed'=>1,'category_name'=>'Peralatan']],
    'units' => [['id'=>1,'unit_code'=>'UT','unit_name'=>'Unit Tes']],
    'methods' => ['straight_line'=>'Garis Lurus'],
    'fundingSources' => ['usaha'=>'Hasil Usaha'],
    'coaOptions' => [['id'=>1,'account_code'=>'1.101','account_name'=>'Kas']],
    'journalOptions' => [['id'=>1,'journal_no'=>'JU-1','journal_date'=>'2026-01-01','description'=>'Pembelian Router']],
    'conditions' => ['good'=>'Baik'],
    'statuses' => ['active'=>'Aktif'],
];

$periodFormShared = [
    'title' => 'Form Periode',
    'period' => null,
    'formData' => ['period_code'=>'2026-01','period_name'=>'Januari 2026','start_date'=>'2026-01-01','end_date'=>'2026-01-31','status'=>'OPEN','is_active'=>'1'],
    'statuses' => ['OPEN'=>'Buka','CLOSED'=>'Tutup'],
];

$checklistShared = [
    'checklist' => [
        'period' => ['id'=>1,'period_name'=>'Januari 2026','period_code'=>'2026-01','start_date'=>'2026-01-01','end_date'=>'2026-01-31'],
        'summary' => ['latest_backup' => ['exists'=>true,'name'=>'backup.sql','modified_label'=>'hari ini','size_bytes'=>2048]],
        'checks' => [['label'=>'Jurnal seimbang','status'=>'pass','message'=>'OK'],['label'=>'Backup','status'=>'warning','message'=>'Perlu backup'],['label'=>'Rekonsiliasi','status'=>'danger','message'=>'Belum selesai']],
        'is_ready_to_close' => false,
        'critical_failures' => 1,
        'warnings' => 1,
    ],
];

$yearEndShared = [
    'preview' => [
        'period' => ['id'=>1,'period_name'=>'Tahun 2025','period_code'=>'2025','start_date'=>'2025-01-01','end_date'=>'2025-12-31'],
        'next_period' => ['period_name'=>'Tahun 2026','period_code'=>'2026','start_date'=>'2026-01-01','end_date'=>'2026-12-31'],
        'proposal' => ['period_name'=>'Tahun 2026','period_code'=>'2026','start_date'=>'2026-01-01','end_date'=>'2026-12-31'],
        'retained_earnings' => ['account_name'=>'Laba Ditahan','account_code'=>'3.301'],
        'totals' => ['line_count'=>2,'debit'=>1000000,'credit'=>1000000,'is_balanced'=>true],
        'net_income' => 200000,
        'opening_lines' => [['account_code'=>'1.101','account_name'=>'Kas','debit'=>1000000,'credit'=>0]],
    ],
];

render_isolated(__DIR__ . '/app/modules/journals/views/form.php', $journalShared);
render_isolated(__DIR__ . '/app/modules/journals/views/detail.php', $journalShared);
render_isolated(__DIR__ . '/app/modules/receivable_ledgers/views/index.php', $receivableShared);
render_isolated(__DIR__ . '/app/modules/receivable_ledgers/views/print.php', $receivableShared);
render_isolated(__DIR__ . '/app/modules/payable_ledgers/views/index.php', $payableShared);
render_isolated(__DIR__ . '/app/modules/payable_ledgers/views/print.php', $payableShared);
render_isolated(__DIR__ . '/app/modules/dashboard/views/index.php', $dashboardShared);
render_isolated(__DIR__ . '/app/modules/assets/views/form.php', $assetShared);
render_isolated(__DIR__ . '/app/modules/periods/views/form.php', $periodFormShared);
render_isolated(__DIR__ . '/app/modules/periods/views/checklist.php', $checklistShared);
render_isolated(__DIR__ . '/app/modules/periods/views/year_end_close.php', $yearEndShared);

echo "SMOKE_OK\n";
