<?php

declare(strict_types=1);

final class DashboardController extends Controller
{
    private function model(): DashboardModel
    {
        return new DashboardModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            $model = $this->model();
            $filters = $this->resolveFilters($model);
            $supportsUnits = $model->supportsBusinessUnits();
            $selectedUnit = ($supportsUnits && $filters['unit_id'] > 0) ? find_business_unit($filters['unit_id']) : null;

            $summary = $model->getSummaryMetrics($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);
            $cashSummary = $model->getCashBankSummary($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);
            $trend = $model->getMonthlyTrend($filters['date_to'], 6, (int) $filters['unit_id']);
            $recentJournals = $model->getRecentJournals($filters['date_from'], $filters['date_to'], 5, (int) $filters['unit_id']);
            $unitSummaries = $supportsUnits ? $model->getUnitSummaries($filters['date_from'], $filters['date_to']) : [];

            $this->view('dashboard/views/index', [
                'title' => 'Dashboard Eksekutif / EIS',
                'filters' => $filters,
                'periods' => $model->getPeriods(),
                'units' => $supportsUnits ? business_unit_options() : [],
                'unitFeatureEnabled' => $supportsUnits,
                'selectedUnit' => $selectedUnit,
                'summary' => $summary,
                'cashSummary' => $cashSummary,
                'trend' => $trend,
                'recentJournals' => $recentJournals,
                'unitSummaries' => $unitSummaries,
                'filterErrors' => $filters['errors'],
                'dbConnected' => Database::isConnected(db_config()),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            render_error_page(500, 'Dashboard Eksekutif belum dapat dibuka. Periksa koneksi database dan modul laporan yang sudah dipasang.', $e);
        }
    }


    public function leadership(): void
    {
        try {
            $model = $this->model();
            $filters = $this->resolveFilters($model);
            $supportsUnits = $model->supportsBusinessUnits();
            $selectedUnit = ($supportsUnits && $filters['unit_id'] > 0) ? find_business_unit($filters['unit_id']) : null;
            $selectedPeriod = $filters['period'];

            $summary = $model->getSummaryMetrics($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);
            $cashSummary = $model->getCashBankSummary($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);
            $trend = $model->getMonthlyTrend($filters['date_to'], 6, (int) $filters['unit_id']);
            $recentJournals = $model->getRecentJournals($filters['date_from'], $filters['date_to'], 8, (int) $filters['unit_id']);
            $topRevenueAccounts = $model->getTopAccounts($filters['date_from'], $filters['date_to'], 'REVENUE', 5, (int) $filters['unit_id']);
            $topExpenseAccounts = $model->getTopAccounts($filters['date_from'], $filters['date_to'], 'EXPENSE', 5, (int) $filters['unit_id']);
            $unitSummaries = $supportsUnits ? $model->getUnitSummaries($filters['date_from'], $filters['date_to']) : [];

            $closingChecklist = null;
            if (is_array($selectedPeriod) && isset($selectedPeriod['id'])) {
                try {
                    $periodModel = new PeriodModel(Database::getInstance(db_config()));
                    $closingChecklist = $periodModel->buildClosingChecklist((int) $selectedPeriod['id']);
                } catch (Throwable) {
                    $closingChecklist = null;
                }
            }

            $this->view('dashboard/views/leadership', [
                'title' => 'Dashboard Pimpinan',
                'filters' => $filters,
                'periods' => $model->getPeriods(),
                'units' => $supportsUnits ? business_unit_options() : [],
                'unitFeatureEnabled' => $supportsUnits,
                'selectedUnit' => $selectedUnit,
                'summary' => $summary,
                'cashSummary' => $cashSummary,
                'trend' => $trend,
                'recentJournals' => $recentJournals,
                'topRevenueAccounts' => $topRevenueAccounts,
                'topExpenseAccounts' => $topExpenseAccounts,
                'unitSummaries' => $unitSummaries,
                'closingChecklist' => $closingChecklist,
                'filterErrors' => $filters['errors'],
                'dbConnected' => Database::isConnected(db_config()),
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            render_error_page(500, 'Dashboard pimpinan belum dapat dibuka. Periksa koneksi database dan modul laporan yang sudah dipasang.', $e);
        }
    }

    private function resolveFilters(DashboardModel $model): array
    {
        $errors = [];

        $periodId = (int) get_query('period_id', 0);
        $unitId = (int) get_query('unit_id', 0);
        $dateFromInput = trim((string) get_query('date_from', ''));
        $dateToInput = trim((string) get_query('date_to', ''));

        $supportsUnits = $model->supportsBusinessUnits();
        if (!$supportsUnits && $unitId > 0) {
            $errors[] = 'Filter unit usaha belum aktif karena migrasi unit usaha belum lengkap. Sistem memakai Semua Unit.';
            $unitId = 0;
        }

        $selectedPeriod = null;
        if ($periodId > 0) {
            $selectedPeriod = $model->findPeriodById($periodId);
            if (!$selectedPeriod) {
                $errors[] = 'Periode yang dipilih tidak ditemukan. Filter dikembalikan ke periode default.';
                $periodId = 0;
            }
        }
        if ($supportsUnits && $unitId > 0) {
            $selectedUnit = find_business_unit($unitId);
            if (!$selectedUnit || (int) ($selectedUnit['is_active'] ?? 0) !== 1) {
                $errors[] = 'Unit usaha yang dipilih tidak ditemukan atau tidak aktif. Filter unit dikembalikan ke Semua Unit.';
                $unitId = 0;
            }
        }

        if ($selectedPeriod === null) {
            $selectedPeriod = current_accounting_period();
            if ($selectedPeriod) {
                $periodId = (int) ($selectedPeriod['id'] ?? 0);
            }
        }

        $defaultStart = (string) ($selectedPeriod['start_date'] ?? '');
        $defaultEnd = (string) ($selectedPeriod['end_date'] ?? '');

        if ($defaultStart === '' || $defaultEnd === '') {
            $workingRange = working_year_date_range();
            $defaultStart = (string) ($workingRange['date_from'] ?? (new DateTimeImmutable('first day of this month'))->format('Y-m-d'));
            $defaultEnd = (string) ($workingRange['date_to'] ?? (new DateTimeImmutable('last day of this month'))->format('Y-m-d'));
        }

        $dateFrom = $dateFromInput !== '' ? $dateFromInput : (string) $defaultStart;
        $dateTo = $dateToInput !== '' ? $dateToInput : (string) $defaultEnd;

        if (!$this->isValidDate($dateFrom)) {
            $errors[] = 'Tanggal mulai filter tidak valid. Sistem memakai tanggal default.';
            $dateFrom = (string) $defaultStart;
        }
        if (!$this->isValidDate($dateTo)) {
            $errors[] = 'Tanggal akhir filter tidak valid. Sistem memakai tanggal default.';
            $dateTo = (string) $defaultEnd;
        }
        if ($this->isValidDate($dateFrom) && $this->isValidDate($dateTo) && $dateFrom > $dateTo) {
            $errors[] = 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir. Sistem memakai rentang default.';
            $dateFrom = (string) $defaultStart;
            $dateTo = (string) $defaultEnd;
        }

        $rangeMode = 'period_default';
        if ($dateFromInput !== '' || $dateToInput !== '') {
            $rangeMode = 'manual';
        }

        if ($selectedPeriod && $rangeMode === 'manual') {
            $periodStart = (string) ($selectedPeriod['start_date'] ?? '');
            $periodEnd = (string) ($selectedPeriod['end_date'] ?? '');
            if ($periodStart !== '' && $periodEnd !== '' && ($dateFrom < $periodStart || $dateTo > $periodEnd)) {
                $errors[] = 'Rentang tanggal manual dipakai apa adanya meskipun melewati batas periode yang dipilih. Ini berguna untuk melihat total lintas bulan, misalnya 1 Januari sampai 31 Maret.';
            }
        }

        return [
            'period_id' => $periodId,
            'unit_id' => $unitId,
            'period' => $selectedPeriod,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'range_label' => dashboard_date_label($dateFrom, $dateTo),
            'range_mode' => $rangeMode,
            'errors' => $errors,
        ];
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '') {
            return false;
        }

        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }
}
