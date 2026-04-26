<?php

declare(strict_types=1);

function journal_print_template_options(): array
{
    return [
        'standard' => 'Cetak detail jurnal standar',
        'receipt' => 'Cetak sebagai bukti transaksi / kwitansi',
    ];
}

function journal_is_receipt_enabled(array $journal): bool
{
    return (string) ($journal['print_template'] ?? 'standard') === 'receipt';
}

function journal_print_template_label(string $template): string
{
    return journal_is_receipt_enabled(['print_template' => $template])
        ? 'Kwitansi / Bukti Transaksi'
        : 'Detail Standar';
}

function journal_workflow_statuses(): array
{
    return [
        'DRAFT' => 'Draft',
        'SUBMITTED' => 'Diajukan',
        'APPROVED' => 'Disetujui',
        'POSTED' => 'Posted',
        'VOIDED' => 'Dibatalkan',
        'REVERSED' => 'Direversal',
    ];
}

function journal_workflow_label(?string $status): string
{
    $status = strtoupper(trim((string) $status));
    $labels = journal_workflow_statuses();
    return $labels[$status] ?? ($status !== '' ? $status : 'Posted');
}

function journal_workflow_badge_class(?string $status): string
{
    return match (strtoupper(trim((string) $status))) {
        'DRAFT' => 'text-bg-secondary',
        'SUBMITTED' => 'text-bg-info',
        'APPROVED' => 'text-bg-success',
        'POSTED' => 'text-bg-primary',
        'VOIDED' => 'text-bg-danger',
        'REVERSED' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };
}

function journal_workflow_action_label(string $action): string
{
    return match ($action) {
        'submit' => 'Ajukan',
        'approve' => 'Setujui',
        'post' => 'Post',
        'void' => 'Void',
        'reverse' => 'Reversal',
        default => ucfirst($action),
    };
}

function journal_workflow_allowed_actions(?string $status, string|array|null $roles): array
{
    $status = strtoupper(trim((string) ($status ?: 'POSTED')));
    $roles = is_array($roles) ? array_map('strval', $roles) : [(string) $roles];
    $isAdmin = in_array('admin', $roles, true);
    $isBendahara = in_array('bendahara', $roles, true);
    $isPimpinan = in_array('pimpinan', $roles, true);

    return match ($status) {
        'DRAFT' => array_values(array_filter([
            ($isAdmin || $isBendahara) ? 'submit' : null,
            $isAdmin ? 'post' : null,
        ])),
        'SUBMITTED' => array_values(array_filter([
            ($isAdmin || $isPimpinan) ? 'approve' : null,
            $isAdmin ? 'post' : null,
        ])),
        'APPROVED' => ($isAdmin || $isPimpinan) ? ['post'] : [],
        'POSTED' => $isAdmin ? ['reverse', 'void'] : [],
        default => [],
    };
}


function journal_receipt_is_complete(array $journal): bool
{
    if (!journal_is_receipt_enabled($journal)) {
        return false;
    }

    return trim((string) ($journal['party_name'] ?? '')) !== ''
        && trim((string) ($journal['purpose'] ?? '')) !== '';
}

function journal_receipt_party_title_options(): array
{
    return [
        'Dibayar kepada' => 'Dibayar kepada',
        'Diterima dari' => 'Diterima dari',
        'Diserahkan kepada' => 'Diserahkan kepada',
        'Diterima oleh' => 'Diterima oleh',
    ];
}

function journal_amount_in_words(float|int|string $amount): string
{
    $number = (int) round((float) $amount);
    if ($number <= 0) {
        return 'nol rupiah';
    }

    return trim(journal_spell_number($number)) . ' rupiah';
}

function journal_spell_number(int $number): string
{
    $base = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    if ($number < 12) {
        return $base[$number];
    }

    if ($number < 20) {
        return journal_spell_number($number - 10) . ' belas';
    }

    if ($number < 100) {
        return journal_spell_number((int) floor($number / 10)) . ' puluh' . (($number % 10 !== 0) ? ' ' . journal_spell_number($number % 10) : '');
    }

    if ($number < 200) {
        return 'seratus' . (($number - 100 > 0) ? ' ' . journal_spell_number($number - 100) : '');
    }

    if ($number < 1000) {
        return journal_spell_number((int) floor($number / 100)) . ' ratus' . (($number % 100 !== 0) ? ' ' . journal_spell_number($number % 100) : '');
    }

    if ($number < 2000) {
        return 'seribu' . (($number - 1000 > 0) ? ' ' . journal_spell_number($number - 1000) : '');
    }

    if ($number < 1000000) {
        return journal_spell_number((int) floor($number / 1000)) . ' ribu' . (($number % 1000 !== 0) ? ' ' . journal_spell_number($number % 1000) : '');
    }

    if ($number < 1000000000) {
        return journal_spell_number((int) floor($number / 1000000)) . ' juta' . (($number % 1000000 !== 0) ? ' ' . journal_spell_number($number % 1000000) : '');
    }

    if ($number < 1000000000000) {
        return journal_spell_number((int) floor($number / 1000000000)) . ' miliar' . (($number % 1000000000 !== 0) ? ' ' . journal_spell_number($number % 1000000000) : '');
    }

    return journal_spell_number((int) floor($number / 1000000000000)) . ' triliun' . (($number % 1000000000000 !== 0) ? ' ' . journal_spell_number($number % 1000000000000) : '');
}


function journal_attachment_file_size(int|float|string $bytes): string
{
    $size = max(0, (float) $bytes);
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }

    return number_format($size, $unitIndex === 0 ? 0 : 2, ',', '.') . ' ' . $units[$unitIndex];
}

function journal_attachment_type_label(array $attachment): string
{
    $ext = strtolower((string) ($attachment['file_ext'] ?? ''));
    return match ($ext) {
        'pdf' => 'PDF',
        'jpg', 'jpeg', 'png', 'webp' => 'Gambar',
        default => strtoupper($ext !== '' ? $ext : 'FILE'),
    };
}


function journal_quick_template_options(): array
{
    return [
        'revenue' => [
            'label' => 'Penerimaan Pendapatan',
            'description' => 'Untuk kas/bank masuk dari penjualan jasa, dagang, atau pendapatan usaha.',
        ],
        'expense' => [
            'label' => 'Pembayaran Operasional',
            'description' => 'Untuk pembayaran beban, biaya rutin, supplier, atau honor.',
        ],
        'transfer' => [
            'label' => 'Transfer Kas / Bank',
            'description' => 'Untuk perpindahan antar kas, bank, atau setoran internal.',
        ],
        'asset' => [
            'label' => 'Pembelian Aset / Persediaan',
            'description' => 'Untuk pembelian aset tetap, perlengkapan, atau persediaan.',
        ],
        'capital' => [
            'label' => 'Modal / Pinjaman / Bagi Hasil',
            'description' => 'Untuk penyertaan modal, pinjaman, atau pembagian hasil usaha.',
        ],
        'opening' => [
            'label' => 'Saldo Awal / Penyesuaian',
            'description' => 'Untuk pembukaan saldo awal atau jurnal penyesuaian tertentu.',
        ],
    ];
}

function journal_quick_template_data(string $templateKey): ?array
{
    $templateKey = strtolower(trim($templateKey));
    $templateKey = match ($templateKey) {
        'cash_in' => 'revenue',
        'cash_out' => 'expense',
        default => $templateKey,
    };

    return match ($templateKey) {
        'revenue' => [
            'template_key' => 'revenue',
            'template_name' => 'Penerimaan Pendapatan',
            'journal_date' => date('Y-m-d'),
            'description' => 'Penerimaan pendapatan usaha',
            'print_template' => 'receipt',
            'receipt' => [
                'party_title' => 'Diterima dari',
                'party_name' => '',
                'purpose' => 'Penerimaan pendapatan usaha',
                'amount_in_words' => '',
                'payment_method' => 'Tunai / Transfer',
                'reference_no' => '',
                'notes' => '',
            ],
            'detail_rows' => [
                ['coa_id' => '', 'line_description' => 'Kas / bank bertambah', 'debit_raw' => '', 'credit_raw' => ''],
                ['coa_id' => '', 'line_description' => 'Pendapatan yang diakui', 'debit_raw' => '', 'credit_raw' => ''],
            ],
        ],
        'expense' => [
            'template_key' => 'expense',
            'template_name' => 'Pembayaran Operasional',
            'journal_date' => date('Y-m-d'),
            'description' => 'Pembayaran operasional',
            'print_template' => 'receipt',
            'receipt' => [
                'party_title' => 'Dibayar kepada',
                'party_name' => '',
                'purpose' => 'Pembayaran operasional',
                'amount_in_words' => '',
                'payment_method' => 'Tunai / Transfer',
                'reference_no' => '',
                'notes' => '',
            ],
            'detail_rows' => [
                ['coa_id' => '', 'line_description' => 'Beban / biaya yang dibayarkan', 'debit_raw' => '', 'credit_raw' => ''],
                ['coa_id' => '', 'line_description' => 'Kas / bank berkurang', 'debit_raw' => '', 'credit_raw' => ''],
            ],
        ],
        'transfer' => [
            'template_key' => 'transfer',
            'template_name' => 'Transfer Kas / Bank',
            'journal_date' => date('Y-m-d'),
            'description' => 'Transfer antar kas / bank',
            'print_template' => 'standard',
            'receipt' => [
                'party_title' => 'Diserahkan kepada',
                'party_name' => '',
                'purpose' => 'Transfer kas / bank internal',
                'amount_in_words' => '',
                'payment_method' => 'Transfer internal',
                'reference_no' => '',
                'notes' => '',
            ],
            'detail_rows' => [
                ['coa_id' => '', 'line_description' => 'Akun tujuan transfer', 'debit_raw' => '', 'credit_raw' => ''],
                ['coa_id' => '', 'line_description' => 'Akun sumber transfer', 'debit_raw' => '', 'credit_raw' => ''],
            ],
        ],
        'asset' => [
            'template_key' => 'asset',
            'template_name' => 'Pembelian Aset / Persediaan',
            'journal_date' => date('Y-m-d'),
            'description' => 'Pembelian aset / persediaan',
            'print_template' => 'standard',
            'receipt' => [
                'party_title' => 'Dibayar kepada',
                'party_name' => '',
                'purpose' => 'Pembelian aset / persediaan',
                'amount_in_words' => '',
                'payment_method' => 'Tunai / Transfer',
                'reference_no' => '',
                'notes' => '',
            ],
            'detail_rows' => [
                ['coa_id' => '', 'line_description' => 'Aset / persediaan bertambah', 'debit_raw' => '', 'credit_raw' => ''],
                ['coa_id' => '', 'line_description' => 'Kas / utang pembelian', 'debit_raw' => '', 'credit_raw' => ''],
            ],
        ],
        'capital' => [
            'template_key' => 'capital',
            'template_name' => 'Modal / Pinjaman / Bagi Hasil',
            'journal_date' => date('Y-m-d'),
            'description' => 'Transaksi modal, pinjaman, atau bagi hasil',
            'print_template' => 'standard',
            'receipt' => [
                'party_title' => 'Diterima dari',
                'party_name' => '',
                'purpose' => 'Modal / pinjaman / bagi hasil',
                'amount_in_words' => '',
                'payment_method' => 'Tunai / Transfer',
                'reference_no' => '',
                'notes' => '',
            ],
            'detail_rows' => [
                ['coa_id' => '', 'line_description' => 'Kas / bank atau akun terkait', 'debit_raw' => '', 'credit_raw' => ''],
                ['coa_id' => '', 'line_description' => 'Modal / pinjaman / laba ditahan', 'debit_raw' => '', 'credit_raw' => ''],
            ],
        ],
        'opening' => [
            'template_key' => 'opening',
            'template_name' => 'Saldo Awal / Penyesuaian',
            'journal_date' => date('Y-m-d'),
            'description' => 'Saldo awal atau penyesuaian',
            'print_template' => 'standard',
            'receipt' => [
                'party_title' => 'Dibayar kepada',
                'party_name' => '',
                'purpose' => 'Saldo awal / penyesuaian',
                'amount_in_words' => '',
                'payment_method' => '',
                'reference_no' => '',
                'notes' => 'Gunakan tag entri yang sesuai untuk saldo awal atau penyesuaian.',
            ],
            'detail_rows' => [
                ['coa_id' => '', 'line_description' => 'Akun debit pembukaan / penyesuaian', 'debit_raw' => '', 'credit_raw' => ''],
                ['coa_id' => '', 'line_description' => 'Akun kredit lawan', 'debit_raw' => '', 'credit_raw' => ''],
            ],
        ],
        default => null,
    };
}

function journal_quick_template_label(?string $templateKey): string
{
    $template = $templateKey !== null ? journal_quick_template_data($templateKey) : null;
    return is_array($template) ? (string) ($template['template_name'] ?? '-') : '-';
}


function journal_receipt_completion_summary(array $journal): array
{
    $summary = [
        'enabled' => journal_is_receipt_enabled($journal),
        'complete' => false,
        'missing_fields' => [],
        'label' => 'Tidak memakai kwitansi',
    ];

    if (!$summary['enabled']) {
        return $summary;
    }

    $missing = [];
    if (trim((string) ($journal['party_name'] ?? '')) === '') {
        $missing[] = 'nama pihak';
    }
    if (trim((string) ($journal['purpose'] ?? '')) === '') {
        $missing[] = 'tujuan';
    }

    $summary['missing_fields'] = $missing;
    $summary['complete'] = $missing === [];
    $summary['label'] = $summary['complete'] ? 'Siap dicetak sebagai kwitansi' : 'Lengkapi metadata kwitansi';

    return $summary;
}

function journal_reference_meta_items(array $detail): array
{
    $items = [];
    if (trim((string) ($detail['partner_name'] ?? '')) !== '') { $items[] = 'Mitra: ' . (string) $detail['partner_name']; }
    if (trim((string) ($detail['item_name'] ?? '')) !== '') { $items[] = 'Persediaan: ' . (string) $detail['item_name']; }
    if (trim((string) ($detail['material_name'] ?? '')) !== '') { $items[] = 'Bahan baku: ' . (string) $detail['material_name']; }
    if (trim((string) ($detail['asset_name'] ?? '')) !== '') { $items[] = 'Aset: ' . (string) $detail['asset_name']; }
    if (trim((string) ($detail['saving_account_name'] ?? '')) !== '') { $items[] = 'Simpanan: ' . (string) $detail['saving_account_name']; }
    if (trim((string) ($detail['component_name'] ?? '')) !== '') { $items[] = 'Arus kas: ' . (string) $detail['component_name']; }
    if (trim((string) ($detail['entry_tag'] ?? '')) !== '') { $items[] = 'Tag: ' . (string) $detail['entry_tag']; }
    return $items;
}

function journal_extract_numeric_amount(string $value): ?float
{
    $value = trim(str_replace(["Rp", "rp", " ", "Â "], '', $value));
    if ($value === '') {
        return null;
    }

    $value = preg_replace('/[^0-9,.-]/', '', $value) ?? '';
    if ($value === '' || $value === '-' || $value === ',' || $value === '.') {
        return null;
    }

    $lastDot = strrpos($value, '.');
    $lastComma = strrpos($value, ',');

    if ($lastDot !== false && $lastComma !== false) {
        if ($lastDot > $lastComma) {
            $value = str_replace(',', '', $value);
        } else {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
    } elseif ($lastComma !== false) {
        if (preg_match('/,\d{1,2}$/', $value) === 1) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
    } elseif ($lastDot !== false && preg_match('/\.\d{1,2}$/', $value) !== 1) {
        $value = str_replace('.', '', $value);
    }

    return is_numeric($value) ? (float) $value : null;
}

function journal_normalize_amount_in_words(string $rawValue, float|int|string $fallbackAmount): string
{
    $rawValue = trim($rawValue);
    if ($rawValue === '') {
        return journal_amount_in_words($fallbackAmount);
    }

    $numeric = journal_extract_numeric_amount($rawValue);
    if ($numeric !== null) {
        return journal_amount_in_words($numeric);
    }

    return $rawValue;
}
