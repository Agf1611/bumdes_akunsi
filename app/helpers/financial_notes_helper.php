<?php

declare(strict_types=1);

function financial_notes_currency(float|int|string $amount): string
{
    return 'Rp ' . ledger_currency($amount);
}

function financial_notes_has_rows(array $rows): bool
{
    foreach ($rows as $row) {
        if (abs((float) ($row['amount'] ?? 0)) > 0.004) {
            return true;
        }
    }

    return false;
}

function financial_notes_profile_location(array $profile): string
{
    $parts = [];
    $mapping = [
        'village_name' => 'Desa',
        'district_name' => 'Kec.',
        'regency_name' => 'Kab.',
        'province_name' => 'Prov.',
    ];

    foreach ($mapping as $field => $label) {
        $value = trim((string) ($profile[$field] ?? ''));
        if ($value !== '') {
            $parts[] = $label . ' ' . $value;
        }
    }

    return implode(' | ', $parts);
}

function financial_notes_profile_legal(array $profile): string
{
    $parts = [];
    $mapping = [
        'legal_entity_no' => 'No. Badan Hukum',
        'nib' => 'NIB',
        'npwp' => 'NPWP',
    ];

    foreach ($mapping as $field => $label) {
        $value = trim((string) ($profile[$field] ?? ''));
        if ($value !== '') {
            $parts[] = $label . ': ' . $value;
        }
    }

    return implode(' | ', $parts);
}

function financial_notes_policy_points(): array
{
    return [
        'Laporan disusun menggunakan dasar akrual sederhana berdasarkan transaksi yang tercatat pada aplikasi.',
        'Kas dan bank diakui sebesar saldo nominal yang tersedia pada akhir periode.',
        'Aset, liabilitas, pendapatan, dan beban disajikan berdasarkan saldo akun pada Chart of Accounts yang aktif.',
        'Penyajian Catatan atas Laporan Keuangan ini dimaksudkan sebagai penjelasan tambahan atas laporan laba rugi, perubahan ekuitas, neraca, dan arus kas.',
    ];
}

function financial_notes_net_result_label(float $netIncome): string
{
    if ($netIncome > 0.004) {
        return 'laba bersih';
    }

    if ($netIncome < -0.004) {
        return 'rugi bersih';
    }

    return 'hasil impas';
}

function financial_notes_table_total(array $rows): float
{
    $total = 0.0;
    foreach ($rows as $row) {
        $total += (float) ($row['amount'] ?? 0);
    }

    return $total;
}
