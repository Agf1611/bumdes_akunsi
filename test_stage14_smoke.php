<?php
declare(strict_types=1);

function e($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
function base_url($p=''){return $p;}
function csrf_token(){return 'x';}
function old($k,$d=''){return $d;}
function old_input($k,$d=null){return $d;}
function journal_receipt_party_title_options(){return ['Dibayar kepada'=>'Dibayar kepada'];}
function journal_receipt_required_fields(): array { return ['party_name'=>'Nama pihak','purpose'=>'Tujuan transaksi']; }
function journal_receipt_recommended_fields(): array { return ['payment_method'=>'Metode pembayaran','reference_no'=>'Nomor referensi']; }
function journal_receipt_completion_summary(array $receipt): array { return ['required_total'=>2,'required_filled'=>2,'recommended_total'=>2,'recommended_filled'=>0,'missing_required'=>[],'missing_recommended'=>[],'is_complete'=>true]; }
function journal_balance_status(float|int|string $debit, float|int|string $credit): array { $d=(float)$debit; $c=(float)$credit; return ['debit'=>$d,'credit'=>$c,'difference'=>abs($d-$c),'difference_side'=>$d>$c?'debit':($d<$c?'credit':'balanced'),'is_balanced'=>abs($d-$c)<0.005]; }
function journal_print_template_options(){return ['standard'=>'Standar','receipt'=>'Kwitansi'];}
function journal_print_template_label($v){return $v;}
function business_unit_options(){return [];}
function current_accounting_period(){return ['id'=>1];}
function report_city_date($p){return 'Desa, 1 Januari 2026';}
function report_treasurer_signature_data($p){return ['position'=>'Bendahara','name'=>'-','signature_url'=>''];}
function report_signature_data($p){return ['position'=>'Direktur','name'=>'-','signature_url'=>''];}
function render_print_header($profile,$title,$period,$unit){echo '<div>'.$title.'</div>';}
function format_id_date($d){return $d;}
function journal_is_receipt_enabled($h){return false;}
function journal_attachment_type_label($a){return 'PDF';}
function journal_attachment_file_size($s){return '1 KB';}
function journal_reference_meta_items(array $detail): array { return []; }

function render_isolated(string $file, array $vars): string {
    extract($vars, EXTR_SKIP);
    ob_start();
    require $file;
    return (string) ob_get_clean();
}

$shared = [
    'title' => 'T',
    'header' => ['id'=>1,'journal_no'=>'JU-1','journal_date'=>'2026-01-01','period_name'=>'2026','total_debit'=>1000,'total_credit'=>1000,'description'=>'x','print_template'=>'standard','period_status'=>'OPEN','amount_in_words'=>'seribu rupiah'],
    'formData' => ['description'=>'x','journal_date'=>'2026-01-01','journal_no'=>'AUTO','period_id'=>'1','business_unit_id'=>'','print_template'=>'standard'],
    'receiptData' => [],
    'detailRows' => [['coa_id'=>'','line_description'=>'','debit_raw'=>'','credit_raw'=>'','entry_tag'=>'OPERASIONAL']],
    'accountOptions' => [],
    'periodOptions' => [],
    'unitOptions' => [],
    'receiptPartyTitleOptions' => [],
    'receiptFeatureStatus' => [],
    'referenceFeatureStatus' => [],
    'partnerOptions' => [],
    'inventoryItemOptions' => [],
    'rawMaterialOptions' => [],
    'assetReferenceOptions' => [],
    'savingAccountOptions' => [],
    'cashflowComponentOptions' => [],
    'entryTagOptions' => ['OPERASIONAL'=>'Operasional'],
    'quickTemplateOptions' => [],
    'activeQuickTemplate' => null,
    'duplicateSource' => null,
    'selectedUnitLabel' => '-',
    'attachments' => [['id'=>1,'attachment_title'=>'Test','original_name'=>'test.pdf','attachment_notes'=>'','file_ext'=>'pdf','file_size'=>1024]],
    'attachmentFeatureStatus' => ['enabled' => false, 'has_journal_attachments_table' => false],
    'profile' => [],
    'reportTitle' => 'Jurnal Umum',
    'periodLabel' => '2026',
    'details' => [['line_no'=>1,'account_code'=>'1.101','account_name'=>'Kas','line_description'=>'Tes','debit'=>1000,'credit'=>0,'entry_tag'=>'OPERASIONAL']],
];

render_isolated(__DIR__ . '/app/modules/journals/views/form.php', $shared);
render_isolated(__DIR__ . '/app/modules/journals/views/detail.php', $shared);
render_isolated(__DIR__ . '/app/modules/journals/views/print.php', $shared);

echo "SMOKE_OK\n";
