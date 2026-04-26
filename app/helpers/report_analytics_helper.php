<?php

declare(strict_types=1);

function report_normalize_comparison_mode(string $mode, array $allowed = ['ytd', 'previous_period', 'previous_year', 'none']): string
{
    $mode = trim($mode);
    return in_array($mode, $allowed, true) ? $mode : $allowed[0];
}

function report_query_flag(mixed $value, bool $default = true): bool
{
    if ($value === null || $value === '') {
        return $default;
    }

    if (is_bool($value)) {
        return $value;
    }

    return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
}

function report_resolve_comparison_range(
    string $mode,
    string $dateFrom,
    string $dateTo,
    ?array $selectedPeriod = null,
    ?array $comparisonPeriod = null
): array {
    $mode = report_normalize_comparison_mode($mode);
    if (!report_is_valid_date($dateFrom) || !report_is_valid_date($dateTo)) {
        return ['enabled' => false, 'mode' => 'none'];
    }

    if (is_array($comparisonPeriod) && isset($comparisonPeriod['start_date'], $comparisonPeriod['end_date'])) {
        $label = trim((string) ($comparisonPeriod['period_name'] ?? 'Periode Pembanding'));
        return [
            'enabled' => true,
            'mode' => 'custom_period',
            'label' => $label !== '' ? $label : 'Periode Pembanding',
            'column_label' => $label !== '' ? $label : 'Periode Pembanding',
            'date_from' => (string) $comparisonPeriod['start_date'],
            'date_to' => (string) $comparisonPeriod['end_date'],
            'as_of_date' => (string) $comparisonPeriod['end_date'],
        ];
    }

    $from = new DateTimeImmutable($dateFrom);
    $to = new DateTimeImmutable($dateTo);
    $rangeDays = max(1, (int) $from->diff($to)->days + 1);

    return match ($mode) {
        'previous_period' => (function () use ($from, $rangeDays): array {
            $comparisonTo = $from->modify('-1 day');
            $comparisonFrom = $comparisonTo->modify('-' . ($rangeDays - 1) . ' days');
            return [
                'enabled' => true,
                'mode' => 'previous_period',
                'label' => 'Periode Lalu',
                'column_label' => 'Periode Lalu',
                'date_from' => $comparisonFrom->format('Y-m-d'),
                'date_to' => $comparisonTo->format('Y-m-d'),
                'as_of_date' => $comparisonTo->format('Y-m-d'),
            ];
        })(),
        'previous_year' => [
            'enabled' => true,
            'mode' => 'previous_year',
            'label' => 'Tahun Lalu',
            'column_label' => 'Tahun Lalu',
            'date_from' => $from->modify('-1 year')->format('Y-m-d'),
            'date_to' => $to->modify('-1 year')->format('Y-m-d'),
            'as_of_date' => $to->modify('-1 year')->format('Y-m-d'),
        ],
        'ytd' => [
            'enabled' => true,
            'mode' => 'ytd',
            'label' => 'Akumulasi Tahun Berjalan',
            'column_label' => 'Akumulasi',
            'date_from' => sprintf('%04d-01-01', (int) $to->format('Y')),
            'date_to' => $to->format('Y-m-d'),
            'as_of_date' => $to->format('Y-m-d'),
        ],
        default => ['enabled' => false, 'mode' => 'none'],
    };
}

function report_variance(float $currentAmount, float $comparisonAmount): array
{
    $nominal = $currentAmount - $comparisonAmount;
    $direction = abs($nominal) < 0.0001 ? 'stable' : ($nominal > 0 ? 'up' : 'down');
    $percent = abs($comparisonAmount) < 0.0001 ? null : (($nominal / abs($comparisonAmount)) * 100);

    return [
        'nominal' => $nominal,
        'percent' => $percent,
        'direction' => $direction,
        'label' => $direction === 'up' ? 'Naik' : ($direction === 'down' ? 'Turun' : 'Stabil'),
    ];
}

function report_variance_percent_label(?float $percent): string
{
    if ($percent === null) {
        return 'n/a';
    }

    return number_format($percent, 1, ',', '.') . '%';
}

function report_build_trend_series(array $points, array $mapping): array
{
    $series = [];
    foreach ($points as $point) {
        $row = ['label' => (string) ($point['label'] ?? '')];
        foreach ($mapping as $outputKey => $inputKey) {
            $row[$outputKey] = (float) ($point[$inputKey] ?? 0);
        }
        $series[] = $row;
    }
    return $series;
}

function report_drilldown_url(?int $accountId, array $filters, string $sourceReport, array $overrides = []): string
{
    $query = array_merge([
        'source_report' => $sourceReport,
        'account_id' => $accountId,
        'period_id' => $filters['period_id'] ?? null,
        'unit_id' => $filters['unit_id'] ?? null,
        'date_from' => $filters['date_from'] ?? null,
        'date_to' => $filters['date_to'] ?? null,
    ], $overrides);

    $query = array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== 0 && $value !== '0');
    return base_url('/reports/drilldown?' . http_build_query($query));
}

function report_is_valid_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
}

function report_download_xlsx(string $filename, string $sheetName, string $reportTitle, array $filters, array $header, array $rows, array $meta = []): never
{
    $profile = app_profile();
    $user = Auth::user();
    $exportRows = [
        [$reportTitle],
        ['Periode', report_period_label($filters, null)],
        ['Unit Usaha', report_selected_unit_label($filters)],
        ['Waktu Export', date('Y-m-d H:i:s')],
        ['User', (string) ($user['full_name'] ?? $user['username'] ?? '-')],
    ];

    foreach ($meta as $label => $value) {
        $exportRows[] = [(string) $label, (string) $value];
    }

    $exportRows[] = [];
    $exportRows[] = $header;
    foreach ($rows as $row) {
        $exportRows[] = $row;
    }

    $exporter = new AssetSpreadsheetExporter();
    $exporter->download($filename, [[
        'name' => $sheetName,
        'rows' => $exportRows,
        'header_rows' => 1,
        'freeze_row' => count($meta) + 6,
        'auto_filter' => true,
    ]]);
}
