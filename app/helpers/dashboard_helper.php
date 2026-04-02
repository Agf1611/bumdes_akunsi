<?php

declare(strict_types=1);

function dashboard_currency(float $amount): string
{
    $prefix = $amount < 0 ? '-Rp ' : 'Rp ';
    return $prefix . number_format(abs($amount), 2, ',', '.');
}

function dashboard_compact_currency(float $amount): string
{
    $negative = $amount < 0;
    $value = abs($amount);
    $suffix = '';

    if ($value >= 1000000000) {
        $value /= 1000000000;
        $suffix = ' M';
    } elseif ($value >= 1000000) {
        $value /= 1000000;
        $suffix = ' Jt';
    } elseif ($value >= 1000) {
        $value /= 1000;
        $suffix = ' Rb';
    }

    $formatted = 'Rp ' . number_format($value, $suffix === '' ? 2 : 1, ',', '.') . $suffix;
    return $negative ? '-' . $formatted : $formatted;
}

function dashboard_month_label(string $monthKey): string
{
    $date = DateTimeImmutable::createFromFormat('Y-m', $monthKey);
    if (!$date) {
        return $monthKey;
    }

    $map = [
        'Jan' => 'Jan',
        'Feb' => 'Feb',
        'Mar' => 'Mar',
        'Apr' => 'Apr',
        'May' => 'Mei',
        'Jun' => 'Jun',
        'Jul' => 'Jul',
        'Aug' => 'Agu',
        'Sep' => 'Sep',
        'Oct' => 'Okt',
        'Nov' => 'Nov',
        'Dec' => 'Des',
    ];

    $english = $date->format('M');
    $month = $map[$english] ?? $english;
    return $month . ' ' . $date->format('y');
}

function dashboard_bar_percent(float $value, float $maxValue): float
{
    if ($maxValue <= 0) {
        return 0.0;
    }

    $percent = ($value / $maxValue) * 100;
    if ($percent < 0) {
        $percent = 0;
    }
    if ($percent > 100) {
        $percent = 100;
    }

    return round($percent, 2);
}

function dashboard_balance_badge_class(float $value): string
{
    if ($value > 0) {
        return 'text-bg-success';
    }

    if ($value < 0) {
        return 'text-bg-danger';
    }

    return 'text-bg-secondary';
}

function dashboard_date_label(string $dateFrom, string $dateTo): string
{
    try {
        $from = new DateTimeImmutable($dateFrom);
        $to = new DateTimeImmutable($dateTo);
    } catch (Throwable) {
        return $dateFrom . ' s.d. ' . $dateTo;
    }

    return $from->format('d M Y') . ' - ' . $to->format('d M Y');
}
