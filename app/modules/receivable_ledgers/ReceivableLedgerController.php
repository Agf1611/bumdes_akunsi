<?php

declare(strict_types=1);

final class ReceivableLedgerController extends Controller
{
    private function model(): ReceivableLedgerModel
    {
        return new ReceivableLedgerModel(Database::getInstance(db_config()));
    }

    public function index(): void
    {
        try {
            [$viewData] = $this->buildReportData();
            $this->view('receivable_ledgers/views/index', $viewData);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman buku pembantu piutang belum dapat dibuka. Periksa log error terbaru untuk detail teknisnya.', $e);
        }
    }

    public function print(): void
    {
        try {
            [$viewData, $selectedPeriod, $selectedUnit, $selectedPartner, $selectedAccount] = $this->buildReportData();
            $viewData['title'] = 'Cetak Buku Pembantu Piutang';
            $viewData['profile'] = app_profile();
            $viewData['reportTitle'] = 'Buku Pembantu Piutang';
            $viewData['periodLabel'] = report_period_label($viewData['filters'], $selectedPeriod);
            $viewData['selectedUnitLabel'] = business_unit_label($selectedUnit);
            $viewData['selectedPartner'] = $selectedPartner;
            $viewData['selectedAccount'] = $selectedAccount;
            $this->view('receivable_ledgers/views/print', $viewData, 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak buku pembantu piutang belum dapat dibuka.', $e);
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
        $periods = $this->model()->getPeriods();
        $partners = $this->model()->getPartnerOptions();
        $accounts = $this->model()->getReceivableAccountOptions();
        $units = business_unit_options();
        $rows = [];
        $selectedPeriod = null;
        $selectedUnit = null;
        $selectedPartner = null;
        $selectedAccount = null;
        $summary = ['opening_balance' => 0.0, 'total_debit' => 0.0, 'total_credit' => 0.0, 'closing_balance' => 0.0];
        $movementSummary = ['debit_total' => 0.0, 'credit_total' => 0.0, 'journal_count' => 0, 'partner_count' => 0, 'last_transaction_date' => null];
        $agingBuckets = [];
        $topPartners = [];
        $reconciliation = ['control_balance' => 0.0, 'difference' => 0.0, 'is_reconciled' => true];
        $hasFilters = $filters['partner_id'] > 0 || $filters['period_id'] > 0 || $filters['period_to_id'] > 0 || $filters['unit_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '' || $filters['account_id'] > 0;

        if ($hasFilters) {
            [$filters, $selectedPeriod, $selectedUnit, $selectedPartner, $selectedAccount] = $this->resolveFilters($filters, $status);

            $partnerId = ($selectedPartner !== null && $status['partner_id_column']) ? (int) $selectedPartner['id'] : null;
            $accountId = $selectedAccount !== null ? (int) $selectedAccount['id'] : null;
            $dateFrom = $filters['date_from'] !== '' ? $filters['date_from'] : null;
            $dateTo = $filters['date_to'] !== '' ? $filters['date_to'] : null;
            $unitId = ($status['business_unit_column']) ? (int) $filters['unit_id'] : 0;

            $running = $this->model()->getOpeningBalance($partnerId, $dateFrom, $unitId, (int) ($accountId ?? 0));
            $summary['opening_balance'] = $running;
            $movementSummary = $this->model()->getMovementSummary($partnerId, $accountId, $dateFrom, $dateTo, $unitId);
            $agingBuckets = $this->model()->getAgingBuckets($partnerId, $accountId, $dateTo ?? date('Y-m-d'), $unitId);
            $topPartners = $this->model()->getTopPartners($accountId, $dateTo ?? date('Y-m-d'), $unitId, 7);
            $mutations = $this->model()->getMutations($partnerId, $dateFrom, $dateTo, $unitId, (int) ($accountId ?? 0));

            $reconciliation['control_balance'] = $this->model()->getControlClosingBalance($partnerId, $accountId, $dateTo, $unitId);

            foreach ($mutations as $mutation) {
                $debit = (float) $mutation['debit'];
                $credit = (float) $mutation['credit'];
                $running += $debit - $credit;
                $summary['total_debit'] += $debit;
                $summary['total_credit'] += $credit;
                $rows[] = [
                    'journal_date' => (string) ($mutation['journal_date'] ?? ''),
                    'journal_no' => (string) ($mutation['journal_no'] ?? ''),
                    'description' => trim((string) ($mutation['line_description'] ?? '')) !== '' ? (string) ($mutation['line_description'] ?? '') : (string) ($mutation['journal_description'] ?? ''),
                    'partner_label' => trim((string) ((($mutation['partner_code'] ?? '') !== '' ? ($mutation['partner_code'] . ' - ') : '') . ($mutation['partner_name'] ?? 'Tanpa Mitra'))) ?: 'Tanpa Mitra',
                    'account_code' => (string) ($mutation['account_code'] ?? ''),
                    'account_name' => (string) ($mutation['account_name'] ?? ''),
                    'entry_tag' => (string) ($mutation['entry_tag'] ?? ''),
                    'unit_label' => trim((string) ($mutation['unit_name'] ?? '')) !== '' ? trim((string) (($mutation['unit_code'] ?? '') . ' - ' . ($mutation['unit_name'] ?? '')), ' -') : 'Semua / belum ditentukan',
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $running,
                ];
            }
            $summary['closing_balance'] = $running;
            $reconciliation['difference'] = (float) $summary['closing_balance'] - (float) $reconciliation['control_balance'];
            $reconciliation['is_reconciled'] = abs((float) $reconciliation['difference']) <= 0.01;
        }

        return [[
            'title' => 'Buku Pembantu Piutang',
            'featureStatus' => $status,
            'filters' => $filters,
            'periods' => $periods,
            'partners' => $partners,
            'accounts' => $accounts,
            'units' => $units,
            'rows' => $rows,
            'summary' => $summary,
            'movementSummary' => $movementSummary,
            'agingBuckets' => $agingBuckets,
            'topPartners' => $topPartners,
            'reconciliation' => $reconciliation,
            'hasFilters' => $hasFilters,
            'selectedPeriod' => $selectedPeriod,
            'selectedUnit' => $selectedUnit,
            'selectedPartner' => $selectedPartner,
            'selectedAccount' => $selectedAccount,
            'reportYears' => accounting_report_year_options(),
        ], $selectedPeriod, $selectedUnit, $selectedPartner, $selectedAccount];
    }

    private function resolveFilters(array $filters, array $status): array
    {
        $errors = [];
        $selectedPeriod = null;
        $selectedUnit = null;
        $selectedPartner = null;
        $selectedAccount = null;

        if ($filters['partner_id'] > 0) {
            if (!$status['partners_table'] || !$status['partner_id_column']) {
                $errors[] = 'Filter mitra belum dapat dipakai karena metadata partner jurnal belum lengkap.';
            } else {
                $selectedPartner = $this->model()->findPartnerById((int) $filters['partner_id']);
                if (!$selectedPartner) {
                    $errors[] = 'Mitra yang dipilih tidak ditemukan.';
                }
            }
        }
        if ($filters['account_id'] > 0) {
            $selectedAccount = $this->model()->findAccountById((int) $filters['account_id']);
            if (!$selectedAccount) {
                $errors[] = 'Akun piutang yang dipilih tidak ditemukan.';
            }
        }
        if ($filters['unit_id'] > 0) {
            if (!$status['business_unit_column']) {
                $errors[] = 'Filter unit belum dapat dipakai karena kolom unit usaha pada jurnal belum tersedia.';
            } else {
                $selectedUnit = find_business_unit((int) $filters['unit_id']);
                if (!$selectedUnit) {
                    $errors[] = 'Unit usaha yang dipilih tidak ditemukan.';
                }
            }
        }
        [$filters, $selectedPeriod, , $periodErrors] = report_resolve_period_filter($filters, fn (int $id): ?array => $this->model()->findPeriodById($id));
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
        if ($selectedPeriod && $filters['date_from'] !== '' && $filters['date_from'] < (string) $selectedPeriod['start_date']) {
            $errors[] = 'Tanggal mulai filter tidak boleh lebih kecil dari tanggal mulai periode yang dipilih.';
        }
        if ($selectedPeriod && $filters['date_to'] !== '' && $filters['date_to'] > (string) $selectedPeriod['end_date']) {
            $errors[] = 'Tanggal akhir filter tidak boleh lebih besar dari tanggal akhir periode yang dipilih.';
        }

        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect('/receivable-ledgers');
        }

        return [$filters, $selectedPeriod, $selectedUnit, $selectedPartner, $selectedAccount];
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
            'entry_tag_column' => false,
            'line_description_column' => false,
            'business_unit_column' => false,
            'account_category_column' => false,
            'account_type_column' => false,
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
