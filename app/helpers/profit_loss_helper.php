<?php

declare(strict_types=1);

function profit_loss_amount(string $accountType, float $debit, float $credit): float
{
    if ($accountType === 'REVENUE') {
        return $credit - $debit;
    }

    if ($accountType === 'EXPENSE') {
        return $debit - $credit;
    }

    return 0.0;
}

function profit_loss_section_label(string $accountType): string
{
    return match ($accountType) {
        'REVENUE' => 'Pendapatan',
        'EXPENSE' => 'Beban',
        default => 'Lainnya',
    };
}

function profit_loss_result_label(float $netIncome): string
{
    if ($netIncome > 0) {
        return 'Laba Bersih';
    }

    if ($netIncome < 0) {
        return 'Rugi Bersih';
    }

    return 'Impas';
}

function profit_loss_display_label(): string
{
    return 'Laba (RUGI) Bersih';
}

function profit_loss_currency(float $amount): string
{
    $formatted = ledger_currency(abs($amount));
    return $amount < 0 ? '(' . $formatted . ')' : $formatted;
}

function profit_loss_currency_print(float $amount): string
{
    $formatted = ledger_currency_print(abs($amount));
    return $amount < 0 ? '(' . $formatted . ')' : $formatted;
}
