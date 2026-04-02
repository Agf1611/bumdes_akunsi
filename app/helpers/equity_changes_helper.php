<?php

declare(strict_types=1);

function equity_change_amount(float $debit, float $credit): float
{
    return $credit - $debit;
}

function equity_change_result_label(float $amount): string
{
    if ($amount > 0.004) {
        return 'Kenaikan Ekuitas';
    }
    if ($amount < -0.004) {
        return 'Penurunan Ekuitas';
    }
    return 'Perubahan Ekuitas';
}
