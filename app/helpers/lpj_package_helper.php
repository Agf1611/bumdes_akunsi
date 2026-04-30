<?php

declare(strict_types=1);


function lpj_strtolower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function lpj_strlen(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function lpj_substr(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null ? mb_substr($value, $start) : mb_substr($value, $start, $length);
    }

    return $length === null ? substr($value, $start) : substr($value, $start, $length);
}

function lpj_package_type_options(): array
{
    return [
        'auto' => 'Otomatis dari periode',
        'semesteran' => 'LPJ Semesteran',
        'tahunan' => 'LPJ Tahunan',
    ];
}

function lpj_detect_package_type(array $filters, ?array $selectedPeriod = null): string
{
    $selected = trim((string) ($filters['package_type'] ?? 'auto'));
    if (in_array($selected, ['semesteran', 'tahunan'], true)) {
        return $selected;
    }

    $periodName = lpj_strtolower(trim((string) ($selectedPeriod['period_name'] ?? '')));
    if ($periodName !== '') {
        if (str_contains($periodName, 'semester') || str_contains($periodName, 'smt')) {
            return 'semesteran';
        }
        if (str_contains($periodName, 'tahunan') || str_contains($periodName, 'tahun')) {
            return 'tahunan';
        }
    }

    $dateFrom = trim((string) ($filters['date_from'] ?? ''));
    $dateTo = trim((string) ($filters['date_to'] ?? ''));
    if ($dateFrom === '' || $dateTo === '') {
        return 'semesteran';
    }

    try {
        $from = new DateTimeImmutable($dateFrom);
        $to = new DateTimeImmutable($dateTo);
        $days = (int) $from->diff($to)->format('%a') + 1;
        return $days <= 184 ? 'semesteran' : 'tahunan';
    } catch (Throwable) {
        return 'semesteran';
    }
}

function lpj_package_type_label(string $type): string
{
    return match ($type) {
        'tahunan' => 'LPJ Tahunan',
        'semesteran' => 'LPJ Semesteran',
        default => 'Paket LPJ',
    };
}

function lpj_document_title(string $type): string
{
    return lpj_package_type_label($type) . ' BUM Desa';
}

function lpj_summary_cards(array $summary): array
{
    return [
        ['label' => 'Pendapatan', 'value' => ledger_currency((float) ($summary['revenue'] ?? 0)), 'note' => 'Akumulasi pendapatan periode berjalan'],
        ['label' => 'Beban', 'value' => ledger_currency((float) ($summary['expense'] ?? 0)), 'note' => 'Total beban yang dibukukan'],
        ['label' => 'Laba / Rugi Bersih', 'value' => ledger_currency((float) ($summary['net_income'] ?? 0)), 'note' => profit_loss_result_label((float) ($summary['net_income'] ?? 0))],
        ['label' => 'Kas Akhir', 'value' => ledger_currency((float) ($summary['closing_cash'] ?? 0)), 'note' => 'Saldo kas dan bank akhir periode'],
        ['label' => 'Total Aset', 'value' => ledger_currency((float) ($summary['total_assets'] ?? 0)), 'note' => 'Posisi aset pada akhir periode'],
        ['label' => 'Liabilitas + Ekuitas', 'value' => ledger_currency((float) ($summary['total_liabilities_equity'] ?? 0)), 'note' => 'Sisi pasiva neraca'],
        ['label' => 'Jumlah Jurnal', 'value' => number_format((int) ($summary['journal_count'] ?? 0), 0, ',', '.'), 'note' => 'Transaksi yang tercatat pada periode'],
        ['label' => 'Unit Aktif dalam Paket', 'value' => number_format((int) ($summary['journal_unit_count'] ?? 0), 0, ',', '.'), 'note' => 'Jumlah unit yang punya transaksi'],
    ];
}

function lpj_shorten(?string $text, int $limit = 180): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '-';
    }

    if (lpj_strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(lpj_substr($text, 0, max(1, $limit - 3))) . '...';
}

function lpj_narrative_input_defaults(array $input = []): array
{
    return [
        'document_no' => trim((string) ($input['document_no'] ?? '')),
        'approval_date' => trim((string) ($input['approval_date'] ?? date('Y-m-d'))),
        'executive_summary' => trim((string) ($input['executive_summary'] ?? '')),
        'business_overview' => trim((string) ($input['business_overview'] ?? '')),
        'activities_summary' => trim((string) ($input['activities_summary'] ?? '')),
        'problems_summary' => trim((string) ($input['problems_summary'] ?? '')),
        'follow_up_summary' => trim((string) ($input['follow_up_summary'] ?? '')),
        'advisor_name' => trim((string) ($input['advisor_name'] ?? '')),
        'advisor_position' => trim((string) ($input['advisor_position'] ?? 'Penasihat')),
        'supervisor_name' => trim((string) ($input['supervisor_name'] ?? '')),
        'supervisor_position' => trim((string) ($input['supervisor_position'] ?? 'Pengawas')),
        'approval_basis' => trim((string) ($input['approval_basis'] ?? '')),
        'meeting_reference' => trim((string) ($input['meeting_reference'] ?? '')),
        'recipient_name' => trim((string) ($input['recipient_name'] ?? '')),
        'recipient_position' => trim((string) ($input['recipient_position'] ?? '')),
        'recipient_institution' => trim((string) ($input['recipient_institution'] ?? '')),
        'appendix_summary' => trim((string) ($input['appendix_summary'] ?? '')),
    ];
}

function lpj_approval_city_date(array $profile, string $approvalDate): string
{
    $city = trim((string) ($profile['signature_city'] ?? ''));
    $date = format_id_long_date($approvalDate !== '' ? $approvalDate : date('Y-m-d'));
    return $city !== '' ? $city . ', ' . $date : $date;
}

function lpj_signatories(array $profile, array $input): array
{
    $input = lpj_narrative_input_defaults($input);

    return [
        [
            'role' => 'Disusun oleh',
            'position' => profile_treasurer_position($profile),
            'name' => profile_treasurer_name($profile),
            'signature_url' => upload_url((string) ($profile['treasurer_signature_path'] ?? '')),
            'signature_path' => public_path((string) ($profile['treasurer_signature_path'] ?? '')),
        ],
        [
            'role' => 'Menyetujui',
            'position' => trim((string) ($profile['director_position'] ?? 'Direktur')) ?: 'Direktur',
            'name' => profile_director_name($profile),
            'signature_url' => upload_url((string) ($profile['signature_path'] ?? '')),
            'signature_path' => public_path((string) ($profile['signature_path'] ?? '')),
        ],
        [
            'role' => 'Diketahui',
            'position' => trim((string) $input['advisor_position']) !== '' ? trim((string) $input['advisor_position']) : 'Penasihat',
            'name' => trim((string) $input['advisor_name']) !== '' ? trim((string) $input['advisor_name']) : '(isi sebelum cetak)',
            'signature_url' => '',
            'signature_path' => '',
        ],
        [
            'role' => 'Ditelaah',
            'position' => trim((string) $input['supervisor_position']) !== '' ? trim((string) $input['supervisor_position']) : 'Pengawas',
            'name' => trim((string) $input['supervisor_name']) !== '' ? trim((string) $input['supervisor_name']) : '(isi sebelum cetak)',
            'signature_url' => '',
            'signature_path' => '',
        ],
    ];
}


function lpj_section_outline(array $viewData): array
{
    $sections = [
        ['title' => 'Sampul Pengantar', 'note' => 'Lembar pengantar resmi penyerahan dokumen LPJ'],
        ['title' => 'Halaman Pengesahan', 'note' => 'Pernyataan persetujuan dan tanda tangan pejabat terkait'],
        ['title' => 'Daftar Isi', 'note' => 'Ringkasan susunan dokumen LPJ'],
        ['title' => 'Ringkasan Eksekutif', 'note' => 'Ikhtisar kinerja dan posisi keuangan'],
        ['title' => 'Laporan Laba Rugi', 'note' => 'Pendapatan, beban, dan hasil usaha periode berjalan'],
        ['title' => 'Laporan Perubahan Ekuitas', 'note' => 'Perubahan penyertaan modal, saldo laba, dan ekuitas akhir'],
        ['title' => 'Laporan Posisi Keuangan (Neraca)', 'note' => 'Aset, kewajiban/liabilitas, dan ekuitas akhir periode'],
        ['title' => 'Laporan Arus Kas', 'note' => 'Arus kas operasi, investasi, dan pendanaan'],
        ['title' => 'Catatan atas Laporan Keuangan', 'note' => 'Pernyataan acuan, dasar penyusunan, kebijakan, rincian pos, dan pengungkapan lain'],
        ['title' => 'Keadaan, Masalah, dan Tindak Lanjut', 'note' => 'Narasi operasional dan perbaikan berikutnya'],
    ];

    $appendixCount = 0;
    if (!empty($viewData['issues'])) {
        $appendixCount++;
    }
    $sections[] = ['title' => 'Lembar Disposisi dan Lampiran', 'note' => 'Catatan penyerahan, disposisi, dan ringkasan lampiran dokumen'];
    if ($appendixCount > 0) {
        $sections[] = ['title' => 'Lampiran Sorotan Perhatian', 'note' => 'Daftar kendala atau catatan penting periode laporan'];
    }
    $sections[] = ['title' => 'Tanda Terima Dokumen', 'note' => 'Bukti serah terima paket LPJ kepada pihak penerima'];

    return $sections;
}

function lpj_document_reference(array $profile, array $narratives): string
{
    $documentNo = trim((string) ($narratives['document_no'] ?? ''));
    if ($documentNo !== '') {
        return $documentNo;
    }

    $shortName = strtoupper(preg_replace('/[^A-Z0-9]+/', '', substr((string) ($profile['bumdes_name'] ?? 'BUMDES'), 0, 12)));
    $shortName = $shortName !== '' ? $shortName : 'BUMDES';
    return 'LPJ/' . $shortName . '/' . date('Y');
}

function lpj_formal_statement(array $profile, array $viewData): string
{
    $packageTitle = lpj_document_title((string) ($viewData['packageType'] ?? 'semesteran'));
    $periodLabel = report_period_label((array) ($viewData['filters'] ?? []), is_array($viewData['selectedPeriod'] ?? null) ? $viewData['selectedPeriod'] : null);
    $unitLabel = trim((string) ($viewData['selectedUnitLabel'] ?? 'Semua Unit'));
    $bumdesName = trim((string) ($profile['bumdes_name'] ?? 'BUM Desa'));

    return sprintf(
        'Dokumen %s ini disusun sebagai laporan pertanggungjawaban resmi %s untuk periode %s dengan lingkup %s. Susunan laporan keuangan mengacu pada KepmenDesa PDTT Nomor 136 Tahun 2022, meliputi Laporan Laba Rugi, Laporan Perubahan Ekuitas, Laporan Posisi Keuangan, Laporan Arus Kas, dan Catatan atas Laporan Keuangan. Setelah diperiksa dan ditelaah seperlunya, laporan ini dinyatakan layak digunakan sebagai bahan pembahasan internal, penyampaian kepada Pemerintah Desa, serta dokumentasi pembinaan sesuai kebutuhan administrasi BUM Desa.',
        $packageTitle,
        $bumdesName,
        $periodLabel,
        $unitLabel
    );
}

function lpj_approval_basis(array $narratives, array $profile): string
{
    $value = trim((string) ($narratives['approval_basis'] ?? ''));
    if ($value !== '') {
        return $value;
    }

    $legal = trim(report_profile_legal($profile));
    return $legal !== ''
        ? 'Dokumen ini disusun dengan mengacu pada identitas dan legalitas lembaga: ' . $legal . '.'
        : 'Dokumen ini disusun berdasarkan pembukuan periode berjalan, hasil rekapitulasi laporan keuangan, dan data profil resmi BUM Desa.';
}

function lpj_meeting_reference(array $narratives): string
{
    $value = trim((string) ($narratives['meeting_reference'] ?? ''));
    return $value !== '' ? $value : '-';
}

function lpj_visible_note_rows(array $rows, int $limit = 5): array
{
    $visible = [];
    foreach ($rows as $row) {
        if (abs((float) ($row['amount'] ?? 0)) <= 0.004) {
            continue;
        }
        $visible[] = $row;
        if (count($visible) >= $limit) {
            break;
        }
    }

    return $visible;
}

function lpj_textarea_value(array $narratives, string $key, string $fallback = ''): string
{
    $value = trim((string) ($narratives[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
}

function lpj_recipient_summary(array $narratives): string
{
    $name = trim((string) ($narratives['recipient_name'] ?? ''));
    $position = trim((string) ($narratives['recipient_position'] ?? ''));
    $institution = trim((string) ($narratives['recipient_institution'] ?? ''));
    $parts = array_values(array_filter([$name, $position, $institution], static fn(string $v): bool => $v !== ''));
    return $parts !== [] ? implode(' / ', $parts) : 'Pemerintah Desa / pihak penerima dokumen';
}

function lpj_appendix_summary(array $narratives, array $viewData): string
{
    $value = trim((string) ($narratives['appendix_summary'] ?? ''));
    if ($value !== '') {
        return $value;
    }
    $count = count(lpj_section_outline($viewData));
    return 'Dokumen inti LPJ berikut lampiran pendukung, ringkasan laporan keuangan, CaLK ringkas, dan halaman pengesahan. Total bagian utama: ' . $count . ' bagian.';
}

function lpj_cover_letter_paragraph(array $profile, array $viewData): string
{
    $recipient = lpj_recipient_summary((array) ($viewData['signatoryInput'] ?? []));
    $packageTitle = lpj_document_title((string) ($viewData['packageType'] ?? 'semesteran'));
    $periodLabel = report_period_label((array) ($viewData['filters'] ?? []), is_array($viewData['selectedPeriod'] ?? null) ? $viewData['selectedPeriod'] : null);
    $bumdesName = trim((string) ($profile['bumdes_name'] ?? 'BUM Desa'));
    return sprintf('Bersama ini kami menyampaikan %s %s untuk periode %s kepada %s sebagai bahan telaah, arsip, dan tindak lanjut administrasi sesuai kebutuhan pembinaan dan pertanggungjawaban.', $packageTitle, $bumdesName, $periodLabel, $recipient);
}
