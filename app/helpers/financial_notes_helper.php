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
        'Laporan keuangan disusun dengan dasar akrual untuk laporan posisi keuangan, laba rugi, dan perubahan ekuitas; laporan arus kas disajikan berdasarkan arus kas masuk dan keluar.',
        'Mata uang pelaporan adalah Rupiah penuh. Nilai disajikan berdasarkan saldo akun dan jurnal yang tercatat di aplikasi sampai tanggal laporan.',
        'Aset dan kewajiban/liabilitas disajikan menurut klasifikasi lancar dan tidak lancar sepanjang data akun mendukung pemisahan tersebut.',
        'Pendapatan diakui pada saat hak atas pendapatan timbul dan beban diakui pada periode terjadinya sesuai pembukuan BUM Desa.',
        'Catatan atas Laporan Keuangan ini menjadi bagian tidak terpisahkan dari Laporan Laba Rugi, Laporan Perubahan Ekuitas, Laporan Posisi Keuangan, dan Laporan Arus Kas.',
    ];
}

function financial_notes_kepmendes_statement(array $profile): string
{
    $name = trim((string) ($profile['bumdes_name'] ?? 'BUM Desa'));
    $name = $name !== '' ? $name : 'BUM Desa';

    return 'Manajemen ' . $name . ' menyatakan bahwa laporan keuangan disusun dengan mengacu pada KepmenDesa PDTT Nomor 136 Tahun 2022 tentang Panduan Penyusunan Laporan Keuangan BUM Desa.';
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
