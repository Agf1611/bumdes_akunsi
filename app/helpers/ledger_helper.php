<?php

declare(strict_types=1);

function ledger_normal_balance_side(?string $accountType): string
{
    return in_array((string) $accountType, ['ASSET', 'EXPENSE'], true) ? 'DEBIT' : 'CREDIT';
}

function ledger_apply_balance(float $runningBalance, float $debit, float $credit, ?string $accountType): float
{
    if (ledger_normal_balance_side($accountType) === 'DEBIT') {
        return $runningBalance + $debit - $credit;
    }

    return $runningBalance + $credit - $debit;
}

function ledger_currency(float|int|string $amount): string
{
    return number_format((float) $amount, 2, ',', '.');
}
