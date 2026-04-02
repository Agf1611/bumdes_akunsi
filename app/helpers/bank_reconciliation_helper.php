<?php

declare(strict_types=1);

function bank_reconciliation_status_label(?string $status): string
{
    return match ((string) $status) {
        'AUTO' => 'Auto Match',
        'MANUAL' => 'Manual Match',
        'IGNORED' => 'Diabaikan',
        default => 'Belum Match',
    };
}

function bank_reconciliation_status_badge_class(?string $status): string
{
    return match ((string) $status) {
        'AUTO' => 'text-bg-success',
        'MANUAL' => 'text-bg-primary',
        'IGNORED' => 'text-bg-secondary',
        default => 'text-bg-warning text-dark',
    };
}

function bank_reconciliation_direction_label(float $amountIn, float $amountOut): string
{
    if ($amountIn > 0.004 && $amountOut <= 0.004) {
        return 'Masuk';
    }

    if ($amountOut > 0.004 && $amountIn <= 0.004) {
        return 'Keluar';
    }

    return 'Campuran';
}

function bank_reconciliation_currency(float|int|string $amount): string
{
    return ledger_currency((float) $amount);
}

function bank_reconciliation_match_quality(float $score): string
{
    if ($score >= 95) {
        return 'Sangat kuat';
    }

    if ($score >= 80) {
        return 'Kuat';
    }

    if ($score >= 65) {
        return 'Cukup';
    }

    return 'Perlu cek';
}

function bank_reconciliation_statement_balance_ok(array $reconciliation): bool
{
    $opening = (float) ($reconciliation['opening_balance'] ?? 0);
    $closing = (float) ($reconciliation['closing_balance'] ?? 0);
    $net = (float) ($reconciliation['total_statement_net'] ?? 0);
    return abs(($opening + $net) - $closing) < 0.01;
}

function bank_reconciliation_balance_gap(array $reconciliation): float
{
    $opening = (float) ($reconciliation['opening_balance'] ?? 0);
    $closing = (float) ($reconciliation['closing_balance'] ?? 0);
    $net = (float) ($reconciliation['total_statement_net'] ?? 0);
    return ($opening + $net) - $closing;
}

function bank_reconciliation_filters_label(array $reconciliation): string
{
    $parts = [];
    if (!empty($reconciliation['period_name'])) {
        $parts[] = 'Periode ' . (string) $reconciliation['period_name'];
    }
    if (!empty($reconciliation['unit_name'])) {
        $parts[] = 'Unit ' . (string) $reconciliation['unit_name'];
    }
    return $parts === [] ? 'Semua transaksi bank dalam rentang ini.' : implode(' | ', $parts);
}

function bank_reconciliation_statement_label(array $reconciliation): string
{
    $label = format_id_date((string) ($reconciliation['statement_start_date'] ?? ''))
        . ' s.d. '
        . format_id_date((string) ($reconciliation['statement_end_date'] ?? ''));

    $statementNo = trim((string) ($reconciliation['statement_no'] ?? ''));
    if ($statementNo !== '') {
        $label .= ' | No. Mutasi: ' . $statementNo;
    }

    return $label;
}
