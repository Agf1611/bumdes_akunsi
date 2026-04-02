<?php

declare(strict_types=1);

function trial_balance_normal_side(?string $accountType): string
{
    return in_array((string) $accountType, ['ASSET', 'EXPENSE'], true) ? 'DEBIT' : 'CREDIT';
}

function trial_balance_closing_balance(float $sumDebit, float $sumCredit, ?string $accountType): float
{
    if (trial_balance_normal_side($accountType) === 'DEBIT') {
        return $sumDebit - $sumCredit;
    }

    return $sumCredit - $sumDebit;
}

function trial_balance_closing_side(float $closingBalance, ?string $accountType): string
{
    if (abs($closingBalance) < 0.00001) {
        return '-';
    }

    $normal = trial_balance_normal_side($accountType);
    if ($closingBalance > 0) {
        return $normal === 'DEBIT' ? 'D' : 'K';
    }

    return $normal === 'DEBIT' ? 'K' : 'D';
}
