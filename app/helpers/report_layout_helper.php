<?php

declare(strict_types=1);

function report_filters_query(array $filters, array $extra = []): string
{
    $query = array_merge([
        'period_id' => $filters['period_id'] ?? '',
        'date_from' => $filters['date_from'] ?? '',
        'date_to' => $filters['date_to'] ?? '',
        'unit_id' => $filters['unit_id'] ?? '',
        'account_id' => $filters['account_id'] ?? '',
    ], $extra);

    $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '' && $value !== 0 && $value !== '0');
    return http_build_query($query);
}

function report_period_label(array $filters, ?array $selectedPeriod = null, bool $asOf = false): string
{
    $dateFrom = trim((string) ($filters['date_from'] ?? ''));
    $dateTo = trim((string) ($filters['date_to'] ?? ''));

    if ($asOf) {
        $label = 'Per ' . ($dateTo !== '' ? format_id_date($dateTo) : '-');
        if ($selectedPeriod && !empty($selectedPeriod['period_name'])) {
            $label .= ' (' . (string) $selectedPeriod['period_name'] . ')';
        }
        return $label;
    }

    $label = ($dateFrom !== '' ? format_id_date($dateFrom) : '-') . ' s.d. ' . ($dateTo !== '' ? format_id_date($dateTo) : '-');
    if ($selectedPeriod && !empty($selectedPeriod['period_name'])) {
        $label .= ' (' . (string) $selectedPeriod['period_name'] . ')';
    }
    return $label;
}

function report_selected_unit_label(array $filters): string
{
    return business_unit_label(selected_unit_from_filters($filters));
}

function report_profile_location(array $profile): string
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

function report_profile_legal(array $profile): string
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

function report_header_data(array $profile, string $title, string $periodLabel, string $unitLabel): array
{
    return [
        'logo_url' => upload_url((string) ($profile['logo_path'] ?? '')),
        'bumdes_name' => (string) ($profile['bumdes_name'] ?? 'BUMDes'),
        'address' => (string) ($profile['address'] ?? '-'),
        'location_meta' => report_profile_location($profile),
        'legal_meta' => report_profile_legal($profile),
        'phone' => (string) ($profile['phone'] ?? '-'),
        'email' => (string) ($profile['email'] ?? '-'),
        'report_title' => $title,
        'period_label' => $periodLabel,
        'unit_label' => $unitLabel,
    ];
}

function report_signature_data(array $profile): array
{
    return [
        'city' => (string) ($profile['signature_city'] ?? ''),
        'print_date' => format_id_long_date(date('Y-m-d')),
        'position' => (string) ($profile['director_position'] ?? 'Direktur'),
        'name' => (string) (($profile['director_name'] ?? '') !== '' ? $profile['director_name'] : ($profile['leader_name'] ?? '-')),
        'signature_url' => upload_url((string) ($profile['signature_path'] ?? '')),
        'show_stamp' => (int) ($profile['show_stamp'] ?? 1) === 1,
    ];
}

function report_city_date(array $profile): string
{
    $city = trim((string) ($profile['signature_city'] ?? ''));
    $printDate = format_id_long_date(date('Y-m-d'));
    return $city !== '' ? $city . ', ' . $printDate : $printDate;
}

function report_treasurer_signature_data(array $profile): array
{
    return [
        'position' => profile_treasurer_position($profile),
        'name' => profile_treasurer_name($profile),
        'signature_url' => upload_url((string) ($profile['treasurer_signature_path'] ?? '')),
        'show_stamp' => false,
    ];
}

function receipt_payment_is_non_cash(?string $paymentMethod): bool
{
    $method = mb_strtolower(trim((string) $paymentMethod));
    if ($method === '') {
        return false;
    }

    foreach (['transfer', 'bank', 'non tunai', 'non-tunai', 'giro', 'qris', 'debit', 'kredit'] as $keyword) {
        if (str_contains($method, $keyword)) {
            return true;
        }
    }

    return false;
}

function receipt_signature_mode(array $profile): string
{
    $mode = (string) ($profile['receipt_signature_mode'] ?? 'treasurer_recipient_director');
    $allowed = [
        'treasurer_only',
        'treasurer_recipient',
        'treasurer_director',
        'treasurer_recipient_director',
    ];

    return in_array($mode, $allowed, true) ? $mode : 'treasurer_recipient_director';
}

function receipt_requires_recipient(array $profile, ?string $paymentMethod): bool
{
    $mode = receipt_signature_mode($profile);
    if (!in_array($mode, ['treasurer_recipient', 'treasurer_recipient_director'], true)) {
        return false;
    }

    $isNonCash = receipt_payment_is_non_cash($paymentMethod);
    if ($isNonCash) {
        return (int) ($profile['receipt_require_recipient_transfer'] ?? 0) === 1;
    }

    return (int) ($profile['receipt_require_recipient_cash'] ?? 1) === 1;
}

function receipt_requires_director(array $profile, float $amount = 0.0): bool
{
    $mode = receipt_signature_mode($profile);
    if (!in_array($mode, ['treasurer_director', 'treasurer_recipient_director'], true)) {
        return false;
    }

    $directorName = trim(profile_director_name($profile));
    if ($directorName === '' || $directorName === '-') {
        return false;
    }

    $threshold = (float) ($profile['director_sign_threshold'] ?? 0);
    if ($threshold <= 0) {
        return true;
    }

    return $amount >= $threshold;
}

function render_print_header(array $profile, string $title, string $periodLabel, string $unitLabel): void
{
    $data = report_header_data($profile, $title, $periodLabel, $unitLabel);
    ?>
    <div class="report-letterhead mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="report-logo-wrap">
                <?php if ($data['logo_url'] !== ''): ?>
                    <img src="<?= e($data['logo_url']) ?>" alt="Logo BUMDes" class="report-logo-img">
                <?php else: ?>
                    <div class="report-logo-fallback">BUMDes</div>
                <?php endif; ?>
            </div>
            <div class="flex-grow-1 text-center report-org-block">
                <div class="report-org-top">BADAN USAHA MILIK DESA</div>
                <div class="report-org-top">BUM DESA</div>
                <div class="report-org-name"><?= e(strtoupper($data['bumdes_name'])) ?></div>
                <div class="report-org-meta">Alamat: <?= e($data['address']) ?></div>
                <?php if ($data['location_meta'] !== ''): ?>
                    <div class="report-org-meta"><?= e($data['location_meta']) ?></div>
                <?php endif; ?>
                <div class="report-org-meta">Telepon: <?= e($data['phone']) ?> &nbsp;&nbsp; Email: <?= e($data['email']) ?></div>
                <?php if ($data['legal_meta'] !== ''): ?>
                    <div class="report-org-meta report-org-meta--legal"><?= e($data['legal_meta']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="report-divider mt-2"></div>
        <div class="text-center mt-3">
            <div class="report-title"><?= e($data['report_title']) ?></div>
            <div class="report-subtitle">Periode: <?= e($data['period_label']) ?></div>
            <div class="report-subtitle">Unit Usaha: <?= e($data['unit_label']) ?></div>
            <div class="report-subtitle"><?= e(report_print_generated_meta()) ?></div>
        </div>
    </div>
    <?php
}

function render_print_signature(array $profile): void
{
    $data = report_signature_data($profile);
    $cityDate = report_city_date($profile);
    ?>
    <div class="report-signature-block mt-5">
        <div class="text-end">
            <div><?= e($cityDate) ?></div>
            <div><?= e($data['position']) ?></div>
            <div class="signature-image-wrap my-2">
                <?php if ($data['signature_url'] !== ''): ?>
                    <img src="<?= e($data['signature_url']) ?>" alt="Tanda Tangan" class="signature-image">
                <?php else: ?>
                    <div class="signature-spacer" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
            <div class="fw-semibold text-decoration-underline"><?= e($data['name']) ?></div>
        </div>
    </div>
    <?php
}

function report_print_generated_meta(): string
{
    $user = Auth::user();
    $parts = ['Dicetak pada ' . format_id_long_date(date('Y-m-d')) . ' ' . date('H:i') . ' WIB'];
    if (is_array($user) && ($user['full_name'] ?? '') !== '') {
        $parts[] = 'oleh ' . (string) $user['full_name'];
    }
    return implode(' | ', $parts);
}


function ledger_currency_print(float $amount): string
{
    $negative = $amount < 0;
    $formatted = number_format(abs($amount), 0, ',', '.');
    return $negative ? '-' . $formatted : $formatted;
}

function report_account_type_label(string $type): string
{
    $map = [
        'ASSET' => 'ASET',
        'LIABILITY' => 'KEWAJIBAN',
        'EQUITY' => 'EKUITAS',
        'REVENUE' => 'PENDAPATAN',
        'EXPENSE' => 'BEBAN',
    ];

    $upper = strtoupper(trim($type));
    return $map[$upper] ?? $upper;
}

function report_compact_text(string $value, int $length = 42): string
{
    $value = trim($value);
    if ($value === '') {
        return '-';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length - 3) . '...' : $value;
    }

    return strlen($value) > $length ? substr($value, 0, $length - 3) . '...' : $value;
}


function report_print_heading_block(string $headline, string $caption = '(dalam rupiah penuh)'): void
{
    ?>
    <div class="text-center mb-3" style="margin-top:-6px;">
        <div style="font-weight:700;font-size:15px;line-height:1.35;"><?= e($headline) ?></div>
        <div style="font-size:11px;color:#4b5563;"><?= e($caption) ?></div>
    </div>
    <?php
}

function report_print_meta_table(array $items): void
{
    if ($items === []) {
        return;
    }
    ?>
    <table class="print-meta-table mb-3">
        <?php foreach ($items as $row): ?>
            <tr>
                <th><?= e((string) ($row['label'] ?? '')) ?></th>
                <td><?= e((string) ($row['value'] ?? '-')) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
}

function report_print_period_headline(array $filters, ?array $selectedPeriod = null): string
{
    return 'Untuk periode ' . report_period_label($filters, $selectedPeriod);
}

function report_print_asof_headline(array $filters, ?array $selectedPeriod = null): string
{
    $dateTo = trim((string) ($filters['date_to'] ?? ''));
    if ($dateTo !== '') {
        return 'Per ' . format_id_date($dateTo);
    }

    return report_period_label($filters, $selectedPeriod, true);
}


if (!function_exists('report_reconciliation_badge_class')) {
    function report_reconciliation_badge_class(float $difference): string
    {
        return abs($difference) <= 0.01 ? 'text-bg-success' : 'text-bg-warning';
    }
}

if (!function_exists('report_reconciliation_status')) {
    function report_reconciliation_status(float $difference): string
    {
        return abs($difference) <= 0.01 ? 'Sinkron' : 'Perlu cek';
    }
}

if (!function_exists('report_reconciliation_note')) {
    function report_reconciliation_note(float $difference, string $leftLabel, string $rightLabel): string
    {
        if (abs($difference) <= 0.01) {
            return trim($leftLabel) . ' sudah sama dengan ' . trim($rightLabel) . '.';
        }
        return trim($leftLabel) . ' belum sama dengan ' . trim($rightLabel) . '.';
    }
}
