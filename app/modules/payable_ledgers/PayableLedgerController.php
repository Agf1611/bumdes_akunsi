<?php

declare(strict_types=1);

final class PayableLedgerController extends Controller
{
    private function model(): PayableLedgerModel
    {
        return new PayableLedgerModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $this->view('payable_ledgers/views/index', $viewData);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman BP Utang belum dapat dibuka. Periksa log error terbaru untuk detail teknisnya.', $e);
        }
    }

    public function print(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit] = $this->buildReportData();
            $viewData['title'] = 'Cetak Buku Pembantu Utang';
            $viewData['profile'] = app_profile();
            $viewData['reportTitle'] = 'Buku Pembantu Utang';
            $viewData['periodLabel'] = report_period_label($viewData['filters'], $selectedPeriod);
            $viewData['selectedUnitLabel'] = business_unit_label($selectedUnit);
            $this->view('payable_ledgers/views/print', $viewData, 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak BP Utang belum dapat dibuka.', $e);
        }
    }

    private function buildReportData(): array
    {
        $filters = [
            'partner_id' => (int) get_query('partner_id', 0),
            'account_id' => (int) get_query('account_id', 0),
            'period_id' => (int) get_query('period_id', 0),
            'period_to_id' => (int) get_query('period_to_id', 0),
            'filter_scope' => report_normalize_filter_scope((string) get_query('filter_scope', 'period')),
            'fiscal_year' => (int) get_query('fiscal_year', 0),
            'unit_id' => (int) get_query('unit_id', 0),
            'date_from' => trim((string) get_query('date_from', '')),
            'date_to' => trim((string) get_query('date_to', '')),
        ];

        $filters = apply_fiscal_year_filter($filters);
        $status = $this->normalizeFeatureStatus($this->model()->getFeatureStatus());
        $partners = $this->model()->getPartnerOptions();
        $accounts = $this->model()->getAccountOptions();
        $periods = $this->model()->getPeriods();
        $units = business_unit_options();
        $selectedPartner = null;
        $selectedAccount = null;
        $selectedPeriod = null;
        $selectedUnit = null;
        $rows = [];
        $summary = ['opening_balance' => 0.0, 'total_debit' => 0.0, 'total_credit' => 0.0, 'closing_balance' => 0.0];
        $movementSummary = ['debit_total' => 0.0, 'credit_total' => 0.0, 'journal_count' => 0, 'partner_count' => 0, 'last_transaction_date' => null];
        $agingBuckets = [];
        $topPartners = [];
        $hasFilters = $filters['partner_id'] > 0 || $filters['account_id'] > 0 || $filters['period_id'] > 0 || $filters['period_to_id'] > 0 || $filters['unit_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '';

        if ($hasFilters) {
            [$filters, $selectedPartner, $selectedAccount, $selectedPeriod, $selectedUnit] = $this->resolveFilters($filters, $status);
            $partnerId = ($selectedPartner !== null && $status['partner_id_column']) ? (int) $selectedPartner['id'] : null;
            $accountId = $selectedAccount !== null ? (int) $selectedAccount['id'] : null;
            $dateFrom = $filters['date_from'] !== '' ? $filters['date_from'] : null;
            $dateTo = $filters['date_to'] !== '' ? $filters['date_to'] : null;
            $unitId = $status['business_unit_column'] ? (int) $filters['unit_id'] : 0;

            $opening = $this->model()->getOpeningBalance($partnerId, $accountId, $dateFrom, $unitId);
            $running = $opening;
            $summary['opening_balance'] = $opening;
            $movementSummary = $this->model()->getMovementSummary($partnerId, $accountId, $dateFrom, $dateTo, $unitId);
            $agingBuckets = $this->model()->getAgingBuckets($partnerId, $accountId, $dateTo ?? date('Y-m-d'), $unitId);
            $topPartners = $this->model()->getTopPartners($accountId, $dateTo ?? date('Y-m-d'), $unitId, 7);
            $mutations = $this->model()->getMutations($partnerId, $accountId, $dateFrom, $dateTo, $unitId);

            foreach ($mutations as $mutation) {
                $debit = (float) $mutation['debit'];
                $credit = (float) $mutation['credit'];
                $running += ($credit - $debit);
                $summary['total_debit'] += $debit;
                $summary['total_credit'] += $credit;
                $rows[] = [
                    'journal_date' => (string) ($mutation['journal_date'] ?? ''),
                    'journal_no' => (string) ($mutation['journal_no'] ?? ''),
                    'description' => trim((string) ($mutation['line_description'] ?? '')) !== '' ? (string) ($mutation['line_description'] ?? '') : (string) ($mutation['journal_description'] ?? ''),
                    'partner_label' => trim((string) ((($mutation['partner_code'] ?? '') !== '' ? ($mutation['partner_code'] . ' - ') : '') . ($mutation['partner_name'] ?? 'Tanpa Mitra'))) ?: 'Tanpa Mitra',
                    'account_label' => trim((string) (($mutation['account_code'] ?? '') . ' - ' . ($mutation['account_name'] ?? '')), ' -'),
                    'unit_label' => trim((string) ($mutation['unit_name'] ?? '')) !== '' ? trim((string) (($mutation['unit_code'] ?? '') . ' - ' . ($mutation['unit_name'] ?? '')), ' -') : 'Semua / belum ditentukan',
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $running,
                ];
            }
            $summary['closing_balance'] = $running;
        }

        return [[
            'title' => 'Buku Pembantu Utang',
            'featureStatus' => $status,
            'partners' => $partners,
            'accounts' => $accounts,
            'reportYears' => accounting_report_year_options(),
            'periods' => $periods,
            'units' => $units,
            'filters' => $filters,
            'selectedPartner' => $selectedPartner,
            'selectedAccount' => $selectedAccount,
            'selectedPeriod' => $selectedPeriod,
            'selectedUnit' => $selectedUnit,
            'rows' => $rows,
            'summary' => $summary,
            'movementSummary' => $movementSummary,
            'agingBuckets' => $agingBuckets,
            'topPartners' => $topPartners,
            'hasFilters' => $hasFilters,
        ], $selectedPeriod, $selectedUnit];
    }

    private function resolveFilters(array $filters, array $status): array
    {
        $errors = [];
        $partner = null;
        $account = null;
        $period = null;
        $unit = null;

        if ($filters['partner_id'] > 0) {
            if (!$status['partners_table'] || !$status['partner_id_column']) {
                $errors[] = 'Filter mitra belum dapat dipakai karena metadata partner jurnal belum lengkap.';
            } else {
                $partner = $this->model()->findPartnerById((int) $filters['partner_id']);
                if (!$partner) {
                    $errors[] = 'Mitra/kreditur yang dipilih tidak ditemukan.';
                }
            }
        }
        if ($filters['account_id'] > 0) {
            $account = $this->model()->findAccountById((int) $filters['account_id']);
            if (!$account) {
                $errors[] = 'Akun utang yang dipilih tidak ditemukan.';
            } elseif ((int) ($account['is_header'] ?? 0) === 1) {
                $errors[] = 'Buku pembantu utang hanya dapat ditampilkan untuk akun detail.';
            }
        }
        if ($filters['unit_id'] > 0) {
            if (!$status['business_unit_column']) {
                $errors[] = 'Filter unit belum dapat dipakai karena kolom unit usaha pada jurnal belum tersedia.';
            } else {
                $unit = find_business_unit((int) $filters['unit_id']);
                if (!$unit) {
                    $errors[] = 'Unit usaha yang dipilih tidak ditemukan.';
                }
            }
        }
        [$filters, $period, , $periodErrors] = report_resolve_period_filter($filters, fn (int $id): ?array => $this->model()->findPeriodById($id));
        $errors = array_merge($errors, $periodErrors);
        if ($filters['date_from'] !== '' && !$this->isValidDate($filters['date_from'])) {
            $errors[] = 'Tanggal mulai tidak valid.';
        }
        if ($filters['date_to'] !== '' && !$this->isValidDate($filters['date_to'])) {
            $errors[] = 'Tanggal akhir tidak valid.';
        }
        if ($filters['date_from'] !== '' && $filters['date_to'] !== '' && $filters['date_to'] < $filters['date_from']) {
            $errors[] = 'Tanggal akhir tidak boleh lebih kecil dari tanggal mulai.';
        }
        if ($period && $filters['date_from'] !== '' && $filters['date_from'] < (string) $period['start_date']) {
            $errors[] = 'Tanggal mulai filter tidak boleh lebih kecil dari tanggal mulai periode yang dipilih.';
        }
        if ($period && $filters['date_to'] !== '' && $filters['date_to'] > (string) $period['end_date']) {
            $errors[] = 'Tanggal akhir filter tidak boleh lebih besar dari tanggal akhir periode yang dipilih.';
        }
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect('/payable-ledgers');
        }

        return [$filters, $partner, $account, $period, $unit];
    }

    private function normalizeFeatureStatus(array $status): array
    {
        $defaults = [
            'journal_headers_table' => false,
            'journal_lines_table' => false,
            'coa_accounts_table' => false,
            'partners_table' => false,
            'business_units_table' => false,
            'partner_id_column' => false,
            'line_description_column' => false,
            'business_unit_column' => false,
            'account_type_column' => false,
            'account_category_column' => false,
            'account_code_column' => false,
            'partner_code_column' => false,
            'partner_name_column' => false,
            'partner_type_column' => false,
            'partner_active_column' => false,
            'unit_code_column' => false,
            'unit_name_column' => false,
        ];
        return array_merge($defaults, $status);
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }
}
