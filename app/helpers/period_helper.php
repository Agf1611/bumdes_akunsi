<?php

declare(strict_types=1);

function accounting_period_statuses(): array
{
    return [
        'OPEN' => 'Buka',
        'CLOSED' => 'Tutup',
    ];
}

function working_year_window(int $yearsBack = 5): array
{
    $current = (int) date('Y');
    $years = [];
    for ($y = $current; $y >= ($current - max(0, $yearsBack)); $y--) {
        $years[] = $y;
    }
    return $years;
}

function available_working_years(int $yearsBack = 5): array
{
    try {
        if (!Database::isConnected(db_config())) {
            return [];
        }

        $pdo = Database::getInstance(db_config());
        $years = working_year_window($yearsBack);
        if ($years === []) {
            return [];
        }

        $minYear = min($years);
        $maxYear = max($years);
        $stmt = $pdo->prepare(
            'SELECT YEAR(start_date) AS fiscal_year,
                    COUNT(*) AS period_count,
                    SUM(CASE WHEN status = "OPEN" THEN 1 ELSE 0 END) AS open_count,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count
             FROM accounting_periods
             WHERE YEAR(start_date) BETWEEN :min_year AND :max_year
             GROUP BY YEAR(start_date)
             ORDER BY fiscal_year DESC'
        );
        $stmt->bindValue(':min_year', $minYear, PDO::PARAM_INT);
        $stmt->bindValue(':max_year', $maxYear, PDO::PARAM_INT);
        $stmt->execute();

        $result = [];
        foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $year = (int) ($row['fiscal_year'] ?? 0);
            if ($year <= 0) {
                continue;
            }
            $result[] = [
                'year' => $year,
                'period_count' => (int) ($row['period_count'] ?? 0),
                'open_count' => (int) ($row['open_count'] ?? 0),
                'active_count' => (int) ($row['active_count'] ?? 0),
            ];
        }

        return $result;
    } catch (Throwable) {
        return [];
    }
}

function working_year_options(int $yearsBack = 5): array
{
    $rows = available_working_years($yearsBack);
    $options = [];
    foreach ($rows as $row) {
        $year = (int) ($row['year'] ?? 0);
        if ($year > 0) {
            $options[] = $year;
        }
    }
    return $options;
}

function resolve_default_working_year(): int
{
    $currentYear = (int) date('Y');
    $years = working_year_options();
    if (in_array($currentYear, $years, true)) {
        return $currentYear;
    }
    if ($years !== []) {
        return (int) $years[0];
    }
    return $currentYear;
}

function current_working_year(): int
{
    $sessionYear = (int) Session::get('working_fiscal_year', 0);
    $validYears = working_year_options();
    if ($sessionYear > 0 && in_array($sessionYear, $validYears, true)) {
        return $sessionYear;
    }
    return resolve_default_working_year();
}

function working_year_periods(int $year): array
{
    if ($year <= 0) {
        return [];
    }

    try {
        if (!Database::isConnected(db_config())) {
            return [];
        }

        $pdo = Database::getInstance(db_config());
        $stmt = $pdo->prepare(
            'SELECT * FROM accounting_periods
             WHERE YEAR(start_date) = :year
             ORDER BY is_active DESC,
                      CASE WHEN status = "OPEN" THEN 0 ELSE 1 END,
                      start_date DESC,
                      id DESC'
        );
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return [];
    }
}

function working_year_default_period(int $year): ?array
{
    $periods = working_year_periods($year);
    if ($periods === []) {
        return null;
    }

    foreach ($periods as $period) {
        if ((int) ($period['is_active'] ?? 0) === 1 && (string) ($period['status'] ?? '') === 'OPEN') {
            return $period;
        }
    }
    foreach ($periods as $period) {
        if ((string) ($period['status'] ?? '') === 'OPEN') {
            return $period;
        }
    }
    return $periods[0] ?? null;
}

function working_year_date_range(?int $year = null): array
{
    $year = $year !== null && $year > 0 ? $year : current_working_year();
    return [
        'date_from' => sprintf('%04d-01-01', $year),
        'date_to' => sprintf('%04d-12-31', $year),
    ];
}

function initialize_working_year_session(): void
{
    $year = current_working_year();
    Session::put('working_fiscal_year', $year);
    $period = working_year_default_period($year);
    Session::put('working_period_id', (int) ($period['id'] ?? 0));
}

function switch_working_year_session(int $year): bool
{
    $years = working_year_options();
    if (!in_array($year, $years, true)) {
        return false;
    }

    Session::put('working_fiscal_year', $year);
    $period = working_year_default_period($year);
    Session::put('working_period_id', (int) ($period['id'] ?? 0));
    return true;
}

function current_accounting_period(): ?array
{
    static $cached = [];

    $workingYear = current_working_year();
    $sessionPeriodId = (int) Session::get('working_period_id', 0);
    $cacheKey = $workingYear . ':' . $sessionPeriodId;
    if (array_key_exists($cacheKey, $cached)) {
        return is_array($cached[$cacheKey]) ? $cached[$cacheKey] : null;
    }

    try {
        if (!Database::isConnected(db_config())) {
            $cached[$cacheKey] = null;
            return null;
        }

        $pdo = Database::getInstance(db_config());
        if ($workingYear > 0) {
            if ($sessionPeriodId > 0) {
                $stmt = $pdo->prepare('SELECT * FROM accounting_periods WHERE id = :id AND YEAR(start_date) = :year LIMIT 1');
                $stmt->bindValue(':id', $sessionPeriodId, PDO::PARAM_INT);
                $stmt->bindValue(':year', $workingYear, PDO::PARAM_INT);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (is_array($row)) {
                    $cached[$cacheKey] = $row;
                    return $row;
                }
            }

            $period = working_year_default_period($workingYear);
            if (is_array($period)) {
                Session::put('working_period_id', (int) ($period['id'] ?? 0));
                $cached[$cacheKey] = $period;
                return $period;
            }

            $cached[$cacheKey] = null;
            return null;
        }

        $stmt = $pdo->query('SELECT * FROM accounting_periods WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cached[$cacheKey] = is_array($row) ? $row : null;
        return $cached[$cacheKey];
    } catch (Throwable) {
        $cached[$cacheKey] = null;
        return null;
    }
}

function current_accounting_period_label(): string
{
    $period = current_accounting_period();
    if (!$period) {
        $year = current_working_year();
        return $year > 0 ? 'Tahun kerja ' . $year : 'Periode akuntansi belum aktif';
    }

    $name = trim((string) ($period['period_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }

    $start = (string) ($period['start_date'] ?? '');
    $end = (string) ($period['end_date'] ?? '');
    if ($start === '' || $end === '') {
        return 'Periode aktif belum lengkap';
    }

    try {
        $startDate = new DateTimeImmutable($start);
        $endDate = new DateTimeImmutable($end);
        return $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
    } catch (Throwable) {
        return 'Periode aktif belum valid';
    }
}

function validate_transaction_period(?string $transactionDate = null): array
{
    $period = current_accounting_period();
    if (!$period) {
        return [false, 'Belum ada periode akuntansi untuk tahun kerja yang dipilih.'];
    }

    if ((string) ($period['status'] ?? '') !== 'OPEN') {
        return [false, 'Periode akuntansi tahun kerja saat ini sudah ditutup.'];
    }

    $date = trim((string) $transactionDate);
    if ($date === '') {
        return [true, 'Periode aktif valid untuk transaksi.'];
    }

    try {
        $trx = new DateTimeImmutable($date);
        $start = new DateTimeImmutable((string) $period['start_date']);
        $end = new DateTimeImmutable((string) $period['end_date']);
    } catch (Throwable) {
        return [false, 'Tanggal transaksi atau periode akuntansi belum valid.'];
    }

    if ($trx < $start || $trx > $end) {
        return [false, 'Tanggal transaksi berada di luar periode akuntansi tahun kerja yang dipilih.'];
    }

    return [true, 'Tanggal transaksi valid pada periode akuntansi aktif.'];
}

function accounting_report_year_options(): array
{
    try {
        if (!Database::isConnected(db_config())) {
            return [(int) date('Y')];
        }

        $pdo = Database::getInstance(db_config());
        $stmt = $pdo->query("SELECT DISTINCT YEAR(start_date) AS y FROM accounting_periods UNION SELECT DISTINCT YEAR(end_date) AS y FROM accounting_periods ORDER BY y DESC");
        $years = [];
        foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $year = (int) ($row['y'] ?? 0);
            if ($year > 0) {
                $years[] = $year;
            }
        }
        $years = array_values(array_unique($years));
        if ($years === []) {
            $years[] = (int) date('Y');
        }
        $workingYear = current_working_year();
        if ($workingYear > 0 && in_array($workingYear, $years, true)) {
            $years = array_values(array_unique(array_merge([$workingYear], $years)));
        } else {
            rsort($years);
        }
        return $years;
    } catch (Throwable) {
        return [(int) date('Y')];
    }
}

function apply_fiscal_year_filter(array $filters): array
{
    $year = (int) ($filters['fiscal_year'] ?? 0);
    $periodId = (int) ($filters['period_id'] ?? 0);
    $dateFrom = trim((string) ($filters['date_from'] ?? ''));
    $dateTo = trim((string) ($filters['date_to'] ?? ''));

    if ($year <= 0 && $periodId <= 0 && $dateFrom === '' && $dateTo === '') {
        $year = current_working_year();
        if ($year > 0) {
            $filters['fiscal_year'] = $year;
        }
    }

    if ($year > 0 && $periodId <= 0 && $dateFrom === '' && $dateTo === '') {
        $filters['date_from'] = sprintf('%04d-01-01', $year);
        $filters['date_to'] = sprintf('%04d-12-31', $year);
    }

    return $filters;
}
