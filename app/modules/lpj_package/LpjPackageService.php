<?php

declare(strict_types=1);

final class LpjPackageService
{
    public function __construct(private PDO $db)
    {
    }

    public function build(array $input = [], bool $strict = false): array
    {
        $activePeriod = current_accounting_period();
        $defaultPeriodId = $activePeriod ? (int) ($activePeriod['id'] ?? 0) : 0;

        $filters = [
            'period_id' => (int) ($input['period_id'] ?? $defaultPeriodId),
            'period_to_id' => (int) ($input['period_to_id'] ?? 0),
            'filter_scope' => report_normalize_filter_scope((string) ($input['filter_scope'] ?? 'period')),
            'fiscal_year' => (int) ($input['fiscal_year'] ?? 0),
            'date_from' => trim((string) ($input['date_from'] ?? '')),
            'date_to' => trim((string) ($input['date_to'] ?? '')),
            'unit_id' => (int) ($input['unit_id'] ?? 0),
            'package_type' => trim((string) ($input['package_type'] ?? 'auto')),
        ];

        $filters = apply_fiscal_year_filter($filters);

        $narratives = lpj_narrative_input_defaults($input);
        $periods = $this->periods();
        $selectedPeriod = null;
        $selectedUnit = null;
        $packageType = lpj_detect_package_type($filters);
        $report = $this->emptyPackage($narratives);

        $hasRequestedFilters = $filters['period_id'] > 0 || $filters['period_to_id'] > 0 || $filters['date_from'] !== '' || $filters['date_to'] !== '';
        if ($hasRequestedFilters) {
            [$filters, $selectedPeriod, $selectedUnit] = $this->resolveFilters($filters);
            $packageType = lpj_detect_package_type($filters, $selectedPeriod);
            $report = $this->compilePackage($filters, $selectedPeriod, $selectedUnit, $narratives, $packageType);
        } elseif ($strict) {
            throw new RuntimeException('Silakan pilih periode atau isi tanggal filter terlebih dahulu untuk membuat Paket LPJ.');
        }

        $viewData = [
            'title' => 'Paket LPJ BUMDes',
            'filters' => $filters,
            'reportYears' => accounting_report_year_options(),
            'periods' => $periods,
            'units' => business_unit_options(),
            'selectedPeriod' => $selectedPeriod,
            'selectedUnit' => $selectedUnit,
            'selectedUnitLabel' => business_unit_label($selectedUnit),
            'packageType' => $packageType,
            'packageLabel' => lpj_package_type_label($packageType),
            'hasReport' => $report['has_report'],
            'summary' => $report['summary'],
            'profitLoss' => $report['profit_loss'],
            'balanceSheet' => $report['balance_sheet'],
            'cashFlow' => $report['cash_flow'],
            'equityChanges' => $report['equity_changes'],
            'financialNotes' => $report['financial_notes'],
            'narratives' => $report['narratives'],
            'signatoryInput' => $report['signatory_input'],
            'signatories' => $report['signatories'],
            'issues' => $report['issues'],
            'sections' => $report['sections'],
            'profile' => app_profile(),
        ];

        return [$viewData, $selectedPeriod, $selectedUnit];
    }

    private function periods(): array
    {
        return $this->profitLossModel()->getPeriods();
    }

    private function resolveFilters(array $filters): array
    {
        $errors = [];
        $period = null;
        $unit = null;
        $filters['fiscal_year'] = (int) ($filters['fiscal_year'] ?? 0);
        $filters = apply_fiscal_year_filter($filters);

        [$filters, $period, , $periodErrors] = report_resolve_period_filter($filters, fn (int $id): ?array => $this->profitLossModel()->findPeriodById($id));
        $errors = array_merge($errors, $periodErrors);

        if ((int) $filters['unit_id'] > 0) {
            $unit = find_business_unit((int) $filters['unit_id']);
            if (!$unit || (int) ($unit['is_active'] ?? 0) !== 1) {
                $errors[] = 'Unit usaha yang dipilih tidak ditemukan atau tidak aktif.';
            }
        }

        if ($filters['date_from'] === '' && $filters['date_to'] === '') {
            $errors[] = 'Silakan pilih periode atau isi tanggal filter terlebih dahulu.';
        }
        if ($filters['date_from'] !== '' && !$this->isValidDate($filters['date_from'])) {
            $errors[] = 'Tanggal mulai tidak valid.';
        }
        if ($filters['date_to'] !== '' && !$this->isValidDate($filters['date_to'])) {
            $errors[] = 'Tanggal akhir tidak valid.';
        }
        if ($filters['date_from'] === '' && $filters['date_to'] !== '') {
            $filters['date_from'] = '1900-01-01';
        }
        if ($filters['date_to'] === '' && $filters['date_from'] !== '') {
            $filters['date_to'] = $filters['date_from'];
        }
        if ($filters['date_from'] !== '' && $filters['date_to'] !== '' && $filters['date_to'] < $filters['date_from']) {
            $errors[] = 'Tanggal akhir tidak boleh lebih kecil dari tanggal mulai.';
        }
        if ($period && $filters['filter_scope'] !== 'manual' && $filters['date_from'] < (string) $period['start_date']) {
            $errors[] = 'Tanggal mulai filter tidak boleh lebih kecil dari tanggal mulai periode yang dipilih.';
        }
        if ($period && $filters['filter_scope'] !== 'manual' && (int) ($filters['period_to_id'] ?? 0) <= 0 && $filters['date_to'] > (string) $period['end_date']) {
            $errors[] = 'Tanggal akhir filter tidak boleh lebih besar dari tanggal akhir periode yang dipilih.';
        }

        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        return [$filters, $period, $unit];
    }

    private function compilePackage(array $filters, ?array $selectedPeriod, ?array $selectedUnit, array $narratives, string $packageType): array
    {
        $dateFrom = (string) $filters['date_from'];
        $dateTo = (string) $filters['date_to'];
        $unitId = (int) $filters['unit_id'];
        $profile = app_profile();

        $profitLoss = $this->compileProfitLoss($dateFrom, $dateTo, $unitId);
        $balanceSheet = $this->compileBalanceSheet($dateFrom, $dateTo, $unitId, $selectedPeriod);
        $cashFlow = $this->compileCashFlow($dateFrom, $dateTo, $unitId);
        $equityChanges = $this->compileEquityChanges($dateFrom, $dateTo, $unitId);
        $financialNotes = $this->compileFinancialNotes($filters);
        $journalSummary = $this->journalSummary($dateFrom, $dateTo, $unitId);

        $summary = [
            'revenue' => (float) $profitLoss['total_revenue'],
            'expense' => (float) $profitLoss['total_expense'],
            'net_income' => (float) $profitLoss['net_income'],
            'opening_cash' => (float) $cashFlow['opening_cash'],
            'closing_cash' => (float) $cashFlow['closing_cash'],
            'total_assets' => (float) $balanceSheet['total_assets'],
            'total_liabilities' => (float) $balanceSheet['total_liabilities'],
            'total_equity' => (float) $balanceSheet['total_equity'],
            'total_liabilities_equity' => (float) $balanceSheet['total_liabilities_equity'],
            'journal_count' => (int) ($journalSummary['journal_count'] ?? 0),
            'journal_unit_count' => (int) ($journalSummary['unit_count'] ?? 0),
            'first_journal_date' => (string) ($journalSummary['first_journal_date'] ?? ''),
            'last_journal_date' => (string) ($journalSummary['last_journal_date'] ?? ''),
        ];

        $issues = [];
        foreach ((array) ($cashFlow['warnings'] ?? []) as $warning) {
            $issues[] = (string) $warning;
        }
        if (!(bool) ($balanceSheet['is_balanced'] ?? true)) {
            $issues[] = 'Neraca belum seimbang dengan selisih ' . ledger_currency(abs((float) ($balanceSheet['difference'] ?? 0))) . '.';
        }
        if ($summary['journal_count'] === 0) {
            $issues[] = 'Belum ada jurnal pada periode yang dipilih sehingga paket LPJ hanya menampilkan struktur laporan kosong.';
        }
        $issues = array_values(array_unique(array_filter($issues, static fn ($item): bool => trim((string) $item) !== '')));

        $narratives = $this->finalizeNarratives($narratives, [
            'package_label' => lpj_package_type_label($packageType),
            'profile' => $profile,
            'filters' => $filters,
            'selected_period' => $selectedPeriod,
            'selected_unit_label' => business_unit_label($selectedUnit),
            'summary' => $summary,
            'issues' => $issues,
        ]);

        return [
            'has_report' => true,
            'summary' => $summary,
            'profit_loss' => $profitLoss,
            'balance_sheet' => $balanceSheet,
            'cash_flow' => $cashFlow,
            'equity_changes' => $equityChanges,
            'financial_notes' => $financialNotes,
            'narratives' => $narratives,
            'signatory_input' => $narratives,
            'signatories' => lpj_signatories($profile, $narratives),
            'issues' => $issues,
            'sections' => [
                ['title' => 'Ringkasan Eksekutif', 'note' => 'Sorotan utama kinerja dan posisi keuangan'],
                ['title' => 'Laporan Laba Rugi', 'note' => 'Pendapatan, beban, dan hasil usaha periode berjalan'],
                ['title' => 'Laporan Posisi Keuangan (Neraca)', 'note' => 'Aset, liabilitas, ekuitas, dan pembanding'],
                ['title' => 'Laporan Arus Kas', 'note' => 'Kas awal, arus operasional, investasi, pendanaan, dan kas akhir'],
                ['title' => 'Laporan Perubahan Ekuitas', 'note' => 'Mutasi modal, laba rugi, dan saldo akhir'],
                ['title' => 'Catatan atas Laporan Keuangan', 'note' => 'Ringkasan informasi umum, kebijakan, dan akun penting'],
                ['title' => 'Keadaan, Masalah, dan Tindak Lanjut', 'note' => 'Narasi operasional untuk LPJ dan pembinaan'],
            ],
        ];
    }

    private function finalizeNarratives(array $narratives, array $context): array
    {
        $profile = (array) ($context['profile'] ?? []);
        $summary = (array) ($context['summary'] ?? []);
        $issues = (array) ($context['issues'] ?? []);
        $packageLabel = (string) ($context['package_label'] ?? 'Paket LPJ');
        $selectedUnitLabel = (string) ($context['selected_unit_label'] ?? 'Semua Unit');
        $filters = (array) ($context['filters'] ?? []);
        $selectedPeriod = $context['selected_period'] ?? null;
        $periodText = report_period_label($filters, is_array($selectedPeriod) ? $selectedPeriod : null);
        $bumdesName = (string) ($profile['bumdes_name'] ?? 'BUM Desa');

        $defaults = [
            'executive_summary' => sprintf(
                '%s %s untuk periode %s menunjukkan pendapatan sebesar %s, beban sebesar %s, sehingga menghasilkan %s sebesar %s. Posisi kas akhir tercatat %s dan total aset sebesar %s.',
                $packageLabel,
                $bumdesName,
                $periodText,
                ledger_currency((float) ($summary['revenue'] ?? 0)),
                ledger_currency((float) ($summary['expense'] ?? 0)),
                profit_loss_result_label((float) ($summary['net_income'] ?? 0)),
                ledger_currency((float) ($summary['net_income'] ?? 0)),
                ledger_currency((float) ($summary['closing_cash'] ?? 0)),
                ledger_currency((float) ($summary['total_assets'] ?? 0))
            ),
            'business_overview' => sprintf(
                'Selama periode laporan, operasional %s difokuskan pada unit %s dengan penekanan pada ketertiban pencatatan transaksi, pengendalian kas/bank, dan penyajian laporan keuangan yang siap dipakai sebagai dokumen pertanggungjawaban ke desa maupun pembina.',
                $bumdesName,
                $selectedUnitLabel
            ),
            'activities_summary' => 'Kegiatan utama pada periode ini mencakup pencatatan transaksi usaha, pengelolaan kas dan bank, pembaruan buku besar, penyusunan laporan laba rugi, neraca, arus kas, perubahan ekuitas, serta Catatan atas Laporan Keuangan untuk mendukung paket pertanggungjawaban resmi.',
            'problems_summary' => $issues !== []
                ? 'Beberapa hal yang perlu mendapat perhatian pada periode ini adalah: ' . implode(' ', array_map(static fn (string $item): string => '- ' . $item, $issues))
                : 'Tidak terdapat kendala material yang menonjol pada periode laporan. Namun demikian, pengelola tetap perlu menjaga konsistensi pencatatan, kelengkapan bukti transaksi, dan kecocokan kas/bank dengan pembukuan.',
            'follow_up_summary' => 'Tindak lanjut yang disarankan setelah paket LPJ ini adalah memastikan seluruh bukti transaksi terdokumentasi, melakukan rekonsiliasi kas dan bank, menindaklanjuti jurnal yang masih belum lengkap, serta menggunakan hasil laporan ini sebagai dasar evaluasi dan perencanaan periode berikutnya.',
            'approval_basis' => lpj_approval_basis($narratives, $profile),
            'meeting_reference' => '',
        ];

        foreach ($defaults as $key => $fallback) {
            $narratives[$key] = lpj_textarea_value($narratives, $key, $fallback);
        }
        if (!$this->isValidDate((string) ($narratives['approval_date'] ?? ''))) {
            $narratives['approval_date'] = date('Y-m-d');
        }

        return $narratives;
    }

    private function journalSummary(string $dateFrom, string $dateTo, int $unitId = 0): array
    {
        $sql = 'SELECT COUNT(*) AS journal_count,
                       COUNT(DISTINCT CASE WHEN business_unit_id IS NOT NULL THEN business_unit_id END) AS unit_count,
                       MIN(journal_date) AS first_journal_date,
                       MAX(journal_date) AS last_journal_date
                FROM journal_headers
                WHERE journal_date >= :date_from AND journal_date <= :date_to';
        $params = [':date_from' => $dateFrom, ':date_to' => $dateTo];
        if ($unitId > 0) {
            $sql .= ' AND business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === ':unit_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['journal_count' => 0, 'unit_count' => 0, 'first_journal_date' => '', 'last_journal_date' => ''];
    }

    private function compileProfitLoss(string $dateFrom, string $dateTo, int $unitId): array
    {
        $report = ['revenue_rows' => [], 'expense_rows' => [], 'total_revenue' => 0.0, 'total_expense' => 0.0, 'net_income' => 0.0, 'row_count' => 0];
        $rawRows = $this->profitLossModel()->getRows($dateFrom, $dateTo, $unitId);
        foreach ($rawRows as $row) {
            $amount = profit_loss_amount((string) $row['account_type'], (float) $row['period_debit'], (float) $row['period_credit']);
            $entry = ['account_code' => (string) $row['account_code'], 'account_name' => (string) $row['account_name'], 'amount' => $amount];
            if ((string) $row['account_type'] === 'REVENUE') {
                $report['revenue_rows'][] = $entry;
                $report['total_revenue'] += $amount;
            } elseif ((string) $row['account_type'] === 'EXPENSE') {
                $report['expense_rows'][] = $entry;
                $report['total_expense'] += $amount;
            }
        }
        $report['net_income'] = $report['total_revenue'] - $report['total_expense'];
        $report['row_count'] = count($report['revenue_rows']) + count($report['expense_rows']);
        return $report;
    }

    private function compileBalanceSheet(string $dateFrom, string $dateTo, int $unitId, ?array $selectedPeriod): array
    {
        $report = $this->emptyBalanceReport();
        $rawRows = $this->balanceSheetModel()->getRows($dateTo, $unitId);
        foreach ($rawRows as $row) {
            $amount = balance_sheet_amount((string) $row['account_type'], (float) $row['closing_total_debit'], (float) $row['closing_total_credit']);
            $entry = ['account_code' => (string) $row['account_code'], 'account_name' => (string) $row['account_name'], 'account_type' => (string) $row['account_type'], 'amount' => $amount, 'comparison_amount' => 0.0];
            if ((string) $row['account_type'] === 'ASSET') {
                $report['asset_rows'][] = $entry;
                $report['total_assets'] += $amount;
            } elseif ((string) $row['account_type'] === 'LIABILITY') {
                $report['liability_rows'][] = $entry;
                $report['total_liabilities'] += $amount;
            } elseif ((string) $row['account_type'] === 'EQUITY') {
                $report['equity_rows'][] = $entry;
                $report['total_equity'] += $amount;
            }
        }
        $report['current_earnings'] = $this->balanceSheetModel()->getCurrentEarnings($dateFrom, $dateTo, $unitId);
        $report['total_equity'] += $report['current_earnings'];
        $report['total_liabilities_equity'] = $report['total_liabilities'] + $report['total_equity'];
        $report['difference'] = $report['total_assets'] - $report['total_liabilities_equity'];
        $report['is_balanced'] = balance_sheet_is_balanced((float) $report['total_assets'], (float) $report['total_liabilities_equity']);
        $report['row_count'] = count($report['asset_rows']) + count($report['liability_rows']) + count($report['equity_rows']);

        if ($selectedPeriod) {
            $comparisonPeriod = $this->balanceSheetModel()->findPreviousPeriod((string) $selectedPeriod['start_date'], (int) ($selectedPeriod['id'] ?? 0));
            if ($comparisonPeriod) {
                $comparison = $this->compileBalanceSheet((string) $comparisonPeriod['start_date'], (string) $comparisonPeriod['end_date'], $unitId, null);
                $report['asset_rows'] = $this->mergeComparisonRows($report['asset_rows'], $comparison['asset_rows']);
                $report['liability_rows'] = $this->mergeComparisonRows($report['liability_rows'], $comparison['liability_rows']);
                $report['equity_rows'] = $this->mergeComparisonRows($report['equity_rows'], $comparison['equity_rows']);
                $report['comparison_enabled'] = true;
                $report['comparison_period'] = $comparisonPeriod;
                $report['comparison_label'] = balance_sheet_comparison_label($comparisonPeriod);
                $report['comparison_total_assets'] = (float) $comparison['total_assets'];
                $report['comparison_total_liabilities'] = (float) $comparison['total_liabilities'];
                $report['comparison_total_equity'] = (float) $comparison['total_equity'];
                $report['comparison_total_liabilities_equity'] = (float) $comparison['total_liabilities_equity'];
                $report['comparison_current_earnings'] = (float) $comparison['current_earnings'];
                $report['comparison_difference'] = (float) $comparison['difference'];
                $report['comparison_is_balanced'] = (bool) $comparison['is_balanced'];
            }
        }
        return $report;
    }

    private function compileCashFlow(string $dateFrom, string $dateTo, int $unitId): array
    {
        $report = ['opening_cash' => 0.0, 'total_operating' => 0.0, 'total_investing' => 0.0, 'total_financing' => 0.0, 'net_cash_change' => 0.0, 'closing_cash' => 0.0, 'row_count' => 0, 'warnings' => []];
        $cashAccounts = $this->cashFlowModel()->getDetectedCashAccounts();
        if ($cashAccounts === []) {
            $report['warnings'][] = 'Belum ada akun kas/bank yang terdeteksi dari COA. Pastikan nama akun kas atau bank sudah konsisten.';
            return $report;
        }
        $cashAccountIds = array_map(static fn (array $row): int => (int) $row['id'], $cashAccounts);
        $report['opening_cash'] = $this->cashFlowModel()->getOpeningCashBalance($cashAccountIds, $dateFrom, $unitId);
        $journalRows = $this->cashFlowModel()->getJournalRows($cashAccountIds, $dateFrom, $dateTo, $unitId);
        foreach ($journalRows as $row) {
            $netAmount = (float) $row['cash_debit'] - (float) $row['cash_credit'];
            if (abs($netAmount) < 0.005) {
                continue;
            }
            [$section, , $ambiguous] = cash_flow_determine_section($row);
            if ($ambiguous) {
                $report['warnings'][] = 'Jurnal ' . (string) $row['journal_no'] . ' memiliki lawan akun campuran sehingga klasifikasi arus kas memakai prioritas sederhana.';
            }
            if ($section === 'OPERATING') {
                $report['total_operating'] += $netAmount;
            } elseif ($section === 'INVESTING') {
                $report['total_investing'] += $netAmount;
            } else {
                $report['total_financing'] += $netAmount;
            }
            $report['row_count']++;
        }
        $report['net_cash_change'] = $report['total_operating'] + $report['total_investing'] + $report['total_financing'];
        $report['closing_cash'] = $report['opening_cash'] + $report['net_cash_change'];
        $report['warnings'] = array_values(array_unique($report['warnings']));
        return $report;
    }

    private function compileEquityChanges(string $dateFrom, string $dateTo, int $unitId): array
    {
        $report = ['rows' => [], 'total_opening_equity' => 0.0, 'total_movement_equity' => 0.0, 'total_closing_equity' => 0.0, 'net_income' => 0.0, 'final_equity_total' => 0.0, 'row_count' => 0];
        $rawRows = $this->equityChangesModel()->getEquityRows($dateFrom, $dateTo, $unitId);
        foreach ($rawRows as $row) {
            $openingAmount = equity_change_amount((float) $row['opening_debit'], (float) $row['opening_credit']);
            $movementAmount = equity_change_amount((float) $row['movement_debit'], (float) $row['movement_credit']);
            $closingAmount = equity_change_amount((float) $row['closing_debit'], (float) $row['closing_credit']);
            $report['rows'][] = ['account_code' => (string) $row['account_code'], 'account_name' => (string) $row['account_name'], 'opening_amount' => $openingAmount, 'movement_amount' => $movementAmount, 'closing_amount' => $closingAmount];
            $report['total_opening_equity'] += $openingAmount;
            $report['total_movement_equity'] += $movementAmount;
            $report['total_closing_equity'] += $closingAmount;
        }
        $report['net_income'] = $this->equityChangesModel()->getNetIncome($dateFrom, $dateTo, $unitId);
        $report['final_equity_total'] = $report['total_closing_equity'] + $report['net_income'];
        $report['row_count'] = count($report['rows']);
        return $report;
    }

    private function compileFinancialNotes(array $filters): array
    {
        $profile = app_profile();
        $dateFrom = (string) $filters['date_from'];
        $dateTo = (string) $filters['date_to'];
        $unitId = (int) $filters['unit_id'];

        $cashRows = $this->financialNotesModel()->getNamedAssetRows($dateTo, ['kas', 'bank'], $unitId);
        $receivableRows = $this->financialNotesModel()->getNamedAssetRows($dateTo, ['piutang'], $unitId);
        $inventoryRows = $this->financialNotesModel()->getNamedAssetRows($dateTo, ['persediaan', 'stok'], $unitId);
        $fixedAssetRows = $this->financialNotesModel()->getNamedAssetRows($dateTo, ['aset tetap', 'inventaris', 'peralatan', 'kendaraan', 'bangunan', 'mesin', 'tanah'], $unitId);
        $depreciationRows = $this->financialNotesModel()->getNamedAssetRows($dateTo, ['akumulasi', 'penyusutan'], $unitId);
        $liabilityRows = $this->financialNotesModel()->getRowsByType($dateTo, 'LIABILITY', $unitId);
        $equityRows = $this->financialNotesModel()->getRowsByType($dateTo, 'EQUITY', $unitId);
        $revenueRows = $this->financialNotesModel()->getProfitLossRows($dateFrom, $dateTo, 'REVENUE', $unitId);
        $expenseRows = $this->financialNotesModel()->getProfitLossRows($dateFrom, $dateTo, 'EXPENSE', $unitId);
        $netIncome = $this->financialNotesModel()->getNetIncome($dateFrom, $dateTo, $unitId);

        return [
            ['title' => 'Informasi Umum BUMDes', 'paragraphs' => array_values(array_filter([
                'BUM Desa ' . ((string) ($profile['bumdes_name'] ?? 'BUMDes')) . ' menyusun laporan keuangan untuk periode ' . format_id_long_date($dateFrom) . ' sampai dengan ' . format_id_long_date($dateTo) . '.',
                trim((string) ($profile['address'] ?? '')) !== '' ? 'Alamat entitas: ' . trim((string) $profile['address']) . '.' : '',
                financial_notes_profile_location($profile) !== '' ? 'Wilayah administrasi: ' . financial_notes_profile_location($profile) . '.' : '',
                financial_notes_profile_legal($profile) !== '' ? 'Identitas legal lembaga: ' . financial_notes_profile_legal($profile) . '.' : '',
            ])), 'rows' => []],
            ['title' => 'Dasar Penyusunan dan Kebijakan Akuntansi', 'paragraphs' => financial_notes_policy_points(), 'rows' => []],
            ['title' => 'Kas dan Setara Kas', 'paragraphs' => ['Kas dan setara kas merupakan saldo akun kas serta rekening bank yang digunakan untuk operasional BUMDes pada akhir periode laporan.'], 'rows' => $cashRows],
            ['title' => 'Piutang dan Persediaan', 'paragraphs' => ['Piutang dan persediaan disajikan berdasarkan saldo akun yang masih tercatat sampai akhir periode laporan. Pengelola perlu meninjau piutang yang menunggak dan memastikan persediaan sesuai kondisi fisik.'], 'rows' => array_merge($receivableRows, $inventoryRows)],
            ['title' => 'Aset Tetap dan Penyusutan', 'paragraphs' => ['Aset tetap disajikan berdasarkan akun aset tetap yang tercatat pada Chart of Accounts. Akumulasi penyusutan ditampilkan terpisah sebagai pengurang nilai buku.', 'Total aset tetap bruto sebesar ' . financial_notes_currency(financial_notes_table_total($fixedAssetRows)) . ' dan akumulasi penyusutan sebesar ' . financial_notes_currency(financial_notes_table_total($depreciationRows)) . '.'], 'rows' => array_merge($fixedAssetRows, $depreciationRows)],
            ['title' => 'Liabilitas, Ekuitas, dan Kinerja Periode', 'paragraphs' => ['Total liabilitas pada akhir periode adalah ' . financial_notes_currency(financial_notes_table_total($liabilityRows)) . ' dan total ekuitas tercatat sebesar ' . financial_notes_currency(financial_notes_table_total($equityRows) + $netIncome) . ' termasuk hasil usaha berjalan.', 'Selama periode berjalan, BUMDes membukukan total pendapatan sebesar ' . financial_notes_currency(financial_notes_table_total($revenueRows)) . ' dan total beban sebesar ' . financial_notes_currency(financial_notes_table_total($expenseRows)) . '.'], 'rows' => array_merge($liabilityRows, $equityRows, $revenueRows, $expenseRows)],
        ];
    }

    private function emptyPackage(array $narratives): array
    {
        return [
            'has_report' => false,
            'summary' => ['revenue' => 0.0, 'expense' => 0.0, 'net_income' => 0.0, 'opening_cash' => 0.0, 'closing_cash' => 0.0, 'total_assets' => 0.0, 'total_liabilities' => 0.0, 'total_equity' => 0.0, 'total_liabilities_equity' => 0.0, 'journal_count' => 0, 'journal_unit_count' => 0, 'first_journal_date' => '', 'last_journal_date' => ''],
            'profit_loss' => ['revenue_rows' => [], 'expense_rows' => [], 'total_revenue' => 0.0, 'total_expense' => 0.0, 'net_income' => 0.0, 'row_count' => 0],
            'balance_sheet' => $this->emptyBalanceReport(),
            'cash_flow' => ['opening_cash' => 0.0, 'total_operating' => 0.0, 'total_investing' => 0.0, 'total_financing' => 0.0, 'net_cash_change' => 0.0, 'closing_cash' => 0.0, 'row_count' => 0, 'warnings' => []],
            'equity_changes' => ['rows' => [], 'total_opening_equity' => 0.0, 'total_movement_equity' => 0.0, 'total_closing_equity' => 0.0, 'net_income' => 0.0, 'final_equity_total' => 0.0, 'row_count' => 0],
            'financial_notes' => [],
            'narratives' => $narratives,
            'signatory_input' => $narratives,
            'signatories' => lpj_signatories(app_profile(), $narratives),
            'issues' => [],
            'sections' => [
                ['title' => 'Ringkasan Eksekutif', 'note' => 'Sorotan utama kinerja dan posisi keuangan'],
                ['title' => 'Laporan Keuangan', 'note' => 'Laba rugi, neraca, arus kas, perubahan ekuitas'],
                ['title' => 'Catatan dan Tindak Lanjut', 'note' => 'CaLK ringkas serta narasi pelengkap LPJ'],
            ],
        ];
    }

    private function emptyBalanceReport(): array
    {
        return ['asset_rows' => [], 'liability_rows' => [], 'equity_rows' => [], 'current_earnings' => 0.0, 'total_assets' => 0.0, 'total_liabilities' => 0.0, 'total_equity' => 0.0, 'total_liabilities_equity' => 0.0, 'difference' => 0.0, 'is_balanced' => true, 'row_count' => 0, 'comparison_enabled' => false, 'comparison_period' => null, 'comparison_label' => '', 'comparison_total_assets' => 0.0, 'comparison_total_liabilities' => 0.0, 'comparison_total_equity' => 0.0, 'comparison_total_liabilities_equity' => 0.0, 'comparison_current_earnings' => 0.0, 'comparison_difference' => 0.0, 'comparison_is_balanced' => true];
    }

    private function mergeComparisonRows(array $currentRows, array $comparisonRows): array
    {
        $currentMap = [];
        foreach ($currentRows as $row) {
            $key = $this->rowKey($row);
            $row['comparison_amount'] = (float) ($row['comparison_amount'] ?? 0.0);
            $currentMap[$key] = $row;
        }
        foreach ($comparisonRows as $row) {
            $key = $this->rowKey($row);
            if (isset($currentMap[$key])) {
                $currentMap[$key]['comparison_amount'] = (float) ($row['amount'] ?? 0.0);
                continue;
            }
            $row['comparison_amount'] = (float) ($row['amount'] ?? 0.0);
            $row['amount'] = 0.0;
            $currentMap[$key] = $row;
        }
        uasort($currentMap, static fn (array $left, array $right): int => strcmp((string) ($left['account_code'] ?? ''), (string) ($right['account_code'] ?? '')));
        return array_values($currentMap);
    }

    private function rowKey(array $row): string
    {
        return implode('|', [(string) ($row['account_type'] ?? ''), (string) ($row['account_code'] ?? ''), (string) ($row['account_name'] ?? '')]);
    }

    private function profitLossModel(): ProfitLossModel { return new ProfitLossModel($this->db); }
    private function balanceSheetModel(): BalanceSheetModel { return new BalanceSheetModel($this->db); }
    private function cashFlowModel(): CashFlowModel { return new CashFlowModel($this->db); }
    private function equityChangesModel(): EquityChangesModel { return new EquityChangesModel($this->db); }
    private function financialNotesModel(): FinancialNotesModel { return new FinancialNotesModel($this->db); }

    private function isValidDate(string $date): bool
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt instanceof DateTimeImmutable && $dt->format('Y-m-d') === $date;
    }
}
