<?php

declare(strict_types=1);

function cash_flow_section_label(string $section): string
{
    return match ($section) {
        'OPERATING' => 'Aktivitas Operasional',
        'INVESTING' => 'Aktivitas Investasi',
        'FINANCING' => 'Aktivitas Pendanaan',
        default => 'Lainnya',
    };
}

function cash_flow_section_badge_class(string $section): string
{
    return match ($section) {
        'OPERATING' => 'text-bg-success',
        'INVESTING' => 'text-bg-warning text-dark',
        'FINANCING' => 'text-bg-info',
        default => 'text-bg-secondary',
    };
}

function cash_flow_assumptions(): array
{
    return [
        'Jika baris jurnal memiliki Komponen Laporan Arus Kas, pilihan manual tersebut menjadi acuan utama.',
        'Akun kas/bank dibaca dari jurnal yang menyentuh akun kas atau bank.',
        'Jika komponen belum diisi, arus kas diklasifikasikan berdasarkan akun lawan, kategori akun, dan kata kunci pada uraian jurnal agar tetap mendekati praktik operasional BUMDes.',
        'Mutasi antar akun kas/bank dengan hasil bersih nol diabaikan karena tidak mengubah total kas keseluruhan.',
        'Jurnal saldo awal pada tanggal mulai diperlakukan sebagai pembukaan saldo kas, bukan arus kas berjalan.',
    ];
}

function cash_flow_limitations(): array
{
    return [
        'Akurasi klasifikasi paling baik jika baris jurnal kas/bank sudah diberi Komponen Laporan Arus Kas.',
        'Jika komponen arus kas belum diisi, sistem tetap memakai klasifikasi otomatis dari COA dan lawan akun.',
        'Jika satu jurnal mencampur banyak tujuan berbeda dalam satu voucher, sistem tetap harus memilih kelompok arus kas yang paling dominan.',
    ];
}

function cash_flow_contains_any(string $haystack, array $keywords): bool
{
    foreach ($keywords as $keyword) {
        $keyword = trim(mb_strtolower((string) $keyword));
        if ($keyword !== '' && str_contains($haystack, $keyword)) {
            return true;
        }
    }

    return false;
}

function cash_flow_determine_section(array $row): array
{
    $explicitGroups = cash_flow_explicit_groups($row);
    if ($explicitGroups !== []) {
        $section = $explicitGroups[0];
        $componentNames = trim((string) ($row['explicit_cashflow_names'] ?? ''));
        $note = $componentNames !== ''
            ? 'Mengikuti komponen arus kas manual: ' . $componentNames . '.'
            : 'Mengikuti komponen arus kas manual dari jurnal.';
        return [$section, $note, count($explicitGroups) > 1];
    }

    $hasOperating = (int) ($row['has_operating'] ?? 0) === 1;
    $hasInvesting = (int) ($row['has_investing'] ?? 0) === 1;
    $hasFinancing = (int) ($row['has_financing'] ?? 0) === 1;
    $netAmount = (float) ($row['cash_debit'] ?? 0) - (float) ($row['cash_credit'] ?? 0);

    $haystack = mb_strtolower(trim(implode(' ', array_filter([
        (string) ($row['description'] ?? ''),
        (string) ($row['counterpart_accounts'] ?? ''),
        (string) ($row['counterpart_types'] ?? ''),
        (string) ($row['counterpart_categories'] ?? ''),
        (string) ($row['journal_no'] ?? ''),
    ], static fn ($value): bool => trim((string) $value) !== ''))));

    if (cash_flow_contains_any($haystack, ['saldo awal', 'opening balance', 'pembukaan saldo'])) {
        return ['OPERATING', 'Jurnal saldo awal tidak dihitung sebagai arus kas berjalan.', true];
    }

    $fixedAssetKeywords = [
        'aset tetap', 'inventaris', 'inventaris kantor', 'peralatan', 'peralatan usaha',
        'modem', 'router', 'access point', 'instalasi', 'jaringan', 'kabel', 'tiang',
        'perangkat', 'perangkat jaringan', 'alat', 'investasi', 'mesin', 'kendaraan',
        'bangunan', 'tanah',
    ];

    $financingKeywords = [
        'laba ditahan', 'utang bagi hasil', 'bagi hasil usaha', 'pembagian hasil usaha',
        'penyertaan modal', 'modal desa', 'modal masyarakat', 'utang jangka panjang',
        'pinjaman investasi', 'kredit investasi', 'rk unit',
    ];

    $matched = [];
    if ($hasOperating) {
        $matched[] = 'OPERATING';
    }
    if ($hasInvesting || cash_flow_contains_any($haystack, $fixedAssetKeywords)) {
        $matched[] = 'INVESTING';
    }
    if ($hasFinancing || cash_flow_contains_any($haystack, $financingKeywords)) {
        $matched[] = 'FINANCING';
    }
    $matched = array_values(array_unique($matched));

    if ($matched === []) {
        return ['OPERATING', 'Jurnal diklasifikasikan ke aktivitas operasional secara default karena lawan akun belum cukup detail.', true];
    }

    if (count($matched) === 1) {
        return [$matched[0], '', false];
    }

    $section = 'OPERATING';
    if (in_array('INVESTING', $matched, true) && cash_flow_contains_any($haystack, $fixedAssetKeywords)) {
        $section = 'INVESTING';
    } elseif ($netAmount > 0 && in_array('FINANCING', $matched, true)) {
        $section = 'FINANCING';
    } elseif ($netAmount < 0 && in_array('INVESTING', $matched, true)) {
        $section = 'INVESTING';
    } elseif (in_array('FINANCING', $matched, true)) {
        $section = 'FINANCING';
    } elseif (in_array('INVESTING', $matched, true)) {
        $section = 'INVESTING';
    }

    $note = 'Jurnal memiliki lawan akun campuran sehingga diklasifikasikan dengan prioritas sederhana ke ' . cash_flow_section_label($section) . '.';
    return [$section, $note, true];
}

function cash_flow_explicit_groups(array $row): array
{
    $raw = trim((string) ($row['explicit_cashflow_groups'] ?? ''));
    if ($raw === '') {
        return [];
    }

    $groups = [];
    foreach (preg_split('/\s*\|\s*/', $raw) ?: [] as $part) {
        $part = strtoupper(trim($part));
        if ($part === '') {
            continue;
        }
        if (str_contains($part, 'OPERATING')) {
            $groups[] = 'OPERATING';
        } elseif (str_contains($part, 'INVESTING')) {
            $groups[] = 'INVESTING';
        } elseif (str_contains($part, 'FINANCING')) {
            $groups[] = 'FINANCING';
        }
    }

    $groups = array_values(array_unique($groups));
    if ($groups === []) {
        return [];
    }

    $priority = ['OPERATING' => 1, 'INVESTING' => 2, 'FINANCING' => 3];
    usort($groups, static fn (string $a, string $b): int => ($priority[$a] ?? 99) <=> ($priority[$b] ?? 99));
    return $groups;
}
