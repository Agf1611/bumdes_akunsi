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
            $operationalStatus = $model->getOperationalStatus($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);

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
                'taskCenter' => $this->buildTaskCenter($operationalStatus, (string) (Auth::user()['role_code'] ?? '')),
                'workspaceRecentItems' => workspace_recent_items(),
                'workspaceFavoritePages' => workspace_favorite_pages(),
                'workspaceSavedFilters' => workspace_saved_filters(),
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
            $operationalStatus = $model->getOperationalStatus($filters['date_from'], $filters['date_to'], (int) $filters['unit_id']);

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
                'taskCenter' => $this->buildTaskCenter($operationalStatus, (string) (Auth::user()['role_code'] ?? 'pimpinan')),
                'workspaceRecentItems' => workspace_recent_items(),
                'workspaceFavoritePages' => workspace_favorite_pages(),
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
        $periodToId = (int) get_query('period_to_id', 0);
        $filterScope = report_normalize_filter_scope((string) get_query('filter_scope', 'period'));
        $unitId = (int) get_query('unit_id', 0);
        $dateFromInput = trim((string) get_query('date_from', ''));
        $dateToInput = trim((string) get_query('date_to', ''));

        $supportsUnits = $model->supportsBusinessUnits();
        if (!$supportsUnits && $unitId > 0) {
            $errors[] = 'Filter unit usaha belum aktif karena migrasi unit usaha belum lengkap. Sistem memakai Semua Unit.';
            $unitId = 0;
        }

        $selectedPeriod = null;
        $selectedEndPeriod = null;
        if ($periodId > 0) {
            $selectedPeriod = $model->findPeriodById($periodId);
            if (!$selectedPeriod) {
                $errors[] = 'Periode yang dipilih tidak ditemukan. Filter dikembalikan ke periode default.';
                $periodId = 0;
            }
        }
        if ($periodToId > 0) {
            $selectedEndPeriod = $model->findPeriodById($periodToId);
            if (!$selectedEndPeriod) {
                $errors[] = 'Periode akhir yang dipilih tidak ditemukan. Sistem memakai periode awal saja.';
                $periodToId = 0;
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
        $defaultEnd = (string) (($selectedEndPeriod['end_date'] ?? null) ?: ($selectedPeriod['end_date'] ?? ''));

        if ($defaultStart === '' || $defaultEnd === '') {
            $workingRange = working_year_date_range();
            $defaultStart = (string) ($workingRange['date_from'] ?? (new DateTimeImmutable('first day of this month'))->format('Y-m-d'));
            $defaultEnd = (string) ($workingRange['date_to'] ?? (new DateTimeImmutable('last day of this month'))->format('Y-m-d'));
        }

        $rangeMode = $filterScope === 'period_range' && $periodToId > 0 ? 'period_range' : 'period_default';
        if ($dateFromInput !== '' || $dateToInput !== '') {
            $rangeMode = $filterScope === 'manual' ? 'manual' : $rangeMode;
        }

        $dateFrom = $rangeMode === 'manual' && $dateFromInput !== '' ? $dateFromInput : (string) $defaultStart;
        $dateTo = $rangeMode === 'manual' && $dateToInput !== '' ? $dateToInput : (string) $defaultEnd;

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

        if ($selectedPeriod && $rangeMode === 'manual') {
            $periodStart = (string) ($selectedPeriod['start_date'] ?? '');
            $periodEnd = (string) ($selectedPeriod['end_date'] ?? '');
            if ($periodStart !== '' && $periodEnd !== '' && ($dateFrom < $periodStart || $dateTo > $periodEnd)) {
                $errors[] = 'Rentang tanggal manual dipakai apa adanya meskipun melewati batas periode yang dipilih. Ini berguna untuk melihat total lintas bulan, misalnya 1 Januari sampai 31 Maret.';
            }
        }

        return [
            'period_id' => $periodId,
            'period_to_id' => $periodToId,
            'filter_scope' => $filterScope,
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

    private function buildTaskCenter(array $status, string $roleCode): array
    {
        $closing = is_array($status['closing_checklist'] ?? null) ? $status['closing_checklist'] : [];
        $latestBackup = is_array($status['latest_backup'] ?? null) ? $status['latest_backup'] : [];
        $updateSignal = is_array($status['update_signal'] ?? null) ? $status['update_signal'] : [];
        $summary = (array) ($updateSignal['summary'] ?? []);

        $tasks = [
            [
                'title' => 'Checklist tutup buku',
                'status' => (bool) ($closing['is_ready_to_close'] ?? false) ? 'success' : (((int) ($closing['critical_failures'] ?? 0)) > 0 ? 'danger' : 'warning'),
                'value' => (bool) ($closing['is_ready_to_close'] ?? false) ? 'Siap tutup' : (((int) ($closing['critical_failures'] ?? 0)) > 0 ? 'Ada blocker' : 'Perlu review'),
                'note' => 'Kritis ' . number_format((int) ($closing['critical_failures'] ?? 0), 0, ',', '.') . ' · Warning ' . number_format((int) ($closing['warnings'] ?? 0), 0, ',', '.'),
                'url' => '/periods/checklist?id=' . (int) (($closing['period']['id'] ?? current_accounting_period()['id'] ?? 0)),
            ],
            [
                'title' => 'Rekonsiliasi bank',
                'status' => ((int) ($status['reconciliation_issues'] ?? 0)) > 0 ? 'warning' : 'success',
                'value' => ((int) ($status['reconciliation_issues'] ?? 0)) > 0 ? 'Belum bersih' : 'Bersih',
                'note' => number_format((int) ($status['reconciliation_issues'] ?? 0), 0, ',', '.') . ' sesi perlu ditinjau',
                'url' => '/bank-reconciliations',
            ],
            [
                'title' => 'Jurnal tanpa lampiran',
                'status' => ((int) ($status['journals_without_attachments'] ?? 0)) > 0 ? 'warning' : 'success',
                'value' => number_format((int) ($status['journals_without_attachments'] ?? 0), 0, ',', '.'),
                'note' => 'Kwitansi/receipt pada rentang aktif yang belum punya bukti',
                'url' => '/journals',
            ],
            [
                'title' => 'Kas / bank negatif',
                'status' => ((int) ($status['negative_cash_accounts'] ?? 0)) > 0 ? 'danger' : 'success',
                'value' => number_format((int) ($status['negative_cash_accounts'] ?? 0), 0, ',', '.'),
                'note' => 'Akun kas/bank dengan saldo minus sampai tanggal dashboard',
                'url' => '/cash-flow',
            ],
        ];

        if ($roleCode === 'admin') {
            $backupStatus = (string) ($latestBackup['stale_level'] ?? (!empty($latestBackup['exists']) ? 'ok' : 'warning'));
            $tasks[] = [
                'title' => 'Backup terakhir',
                'status' => $backupStatus === 'critical' ? 'danger' : ($backupStatus === 'warning' ? 'warning' : 'success'),
                'value' => !empty($latestBackup['exists']) ? ($backupStatus === 'ok' ? 'Aman' : 'Perlu backup') : 'Belum ada',
                'note' => !empty($latestBackup['exists'])
                    ? (string) ($latestBackup['name'] ?? '-') . ' · ' . (string) ($latestBackup['age_label'] ?? 'baru dibuat')
                    : 'Segera buat backup database',
                'url' => '/backups',
            ];
            $tasks[] = [
                'title' => 'Update aplikasi',
                'status' => (bool) ($summary['update_available'] ?? false) ? 'warning' : 'success',
                'value' => (bool) ($summary['update_available'] ?? false) ? 'Ada update' : 'Terbaru',
                'note' => 'Perubahan file: ' . number_format((int) (($summary['changed_count'] ?? 0) + ($summary['new_count'] ?? 0)), 0, ',', '.'),
                'url' => '/updates',
            ];
        }

        if ($roleCode === 'pimpinan') {
            $tasks = array_slice($tasks, 0, 3);
        }

        return $tasks;
    }
}
