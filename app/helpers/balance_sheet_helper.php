<?php

declare(strict_types=1);

function balance_sheet_group_label(string $accountType): string
{
    return match ($accountType) {
        'ASSET' => 'Aset',
        'LIABILITY' => 'Liabilitas',
        'EQUITY' => 'Ekuitas',
        default => 'Lainnya',
    };
}

function balance_sheet_amount(string $accountType, float $totalDebit, float $totalCredit): float
{
    return match ($accountType) {
        'ASSET' => $totalDebit - $totalCredit,
        'LIABILITY', 'EQUITY' => $totalCredit - $totalDebit,
        default => 0.0,
    };
}

function balance_sheet_is_balanced(float $totalAssets, float $totalLiabilitiesAndEquity, float $tolerance = 0.005): bool
{
    return abs($totalAssets - $totalLiabilitiesAndEquity) < $tolerance;
}

function balance_sheet_comparison_label(?array $period): string
{
    if (!$period) {
        return '-';
    }

    $dateTo = trim((string) ($period['end_date'] ?? ''));
    $label = 'Per ' . ($dateTo !== '' ? format_id_date($dateTo) : '-');
    $periodName = trim((string) ($period['period_name'] ?? ''));
    if ($periodName !== '') {
        $label .= ' (' . $periodName . ')';
    }

    return $label;
}
