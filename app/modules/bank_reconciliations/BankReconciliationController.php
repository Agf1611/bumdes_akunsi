<?php

declare(strict_types=1);

final class BankReconciliationController extends Controller
{
    private function model(): BankReconciliationModel
    {
        return new BankReconciliationModel(Database::getInstance(db_config()));
    }

    private function service(): BankReconciliationService
    {
        return new BankReconciliationService();
    }

    public function index(): void
    {
        try {
            $this->ensureReady();

            $reconciliations = $this->model()->listReconciliations();
            $selectedId = (int) get_query('id', 0);
            if ($selectedId <= 0 && $reconciliations !== []) {
                $selectedId = (int) ($reconciliations[0]['id'] ?? 0);
            }

            $selected = $selectedId > 0 ? $this->model()->findReconciliationById($selectedId) : null;
            $lines = [];
            $suggestions = [];
            $journalSummary = [
                'journal_in' => 0.0,
                'journal_out' => 0.0,
                'journal_net' => 0.0,
                'journal_count' => 0,
            ];

            if ($selected) {
                $lines = $this->model()->getLinesByReconciliationId((int) $selected['id']);
                $candidates = $this->model()->getCandidateJournals($selected);
                $suggestions = $this->service()->buildSuggestions($selected, $lines, $candidates);
                $journalSummary = $this->model()->getJournalWindowSummary($selected);
            }

            $this->view('bank_reconciliations/views/index', [
                'title' => 'Rekonsiliasi Bank',
                'reconciliations' => $reconciliations,
                'selected' => $selected,
                'lines' => $lines,
                'suggestions' => $suggestions,
                'journalSummary' => $journalSummary,
                'bankAccounts' => $this->model()->getBankAccountOptions(),
                'periods' => $this->model()->getPeriods(),
                'units' => business_unit_options(),
                'defaults' => $this->defaultFormValues(),
            ]);
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Modul rekonsiliasi bank belum dapat dibuka. Jalankan patch bank_reconciliation_module.sql terlebih dahulu.', $e);
        }
    }

    public function store(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        $storedFile = null;
        try {
            $this->ensureReady();
            $input = $this->validateInput();

            $storedFile = $this->service()->storeUploadedStatement($_FILES['statement_file'] ?? []);
            $rows = $this->service()->parseCsvFile((string) $storedFile['absolute_path']);
            $summary = $this->service()->summarizeRows($rows);

            $header = [
                'title' => $input['title'] !== '' ? $input['title'] : ('Rekonsiliasi ' . (string) $input['bank_account_label'] . ' ' . format_id_month_year((string) ($summary['date_from'] ?: date('Y-m-d')))),
                'bank_account_coa_id' => $input['bank_account_coa_id'],
                'period_id' => $input['period_id'],
                'business_unit_id' => $input['business_unit_id'],
                'statement_no' => $input['statement_no'],
                'statement_start_date' => $input['statement_start_date'] !== '' ? $input['statement_start_date'] : (string) ($summary['date_from'] ?: date('Y-m-d')),
                'statement_end_date' => $input['statement_end_date'] !== '' ? $input['statement_end_date'] : (string) ($summary['date_to'] ?: date('Y-m-d')),
                'opening_balance' => $input['opening_balance'],
                'closing_balance' => $input['closing_balance'],
                'imported_file_name' => $storedFile['original_name'],
                'stored_file_path' => $storedFile['stored_file_path'],
                'notes' => $input['notes'],
                'auto_match_tolerance_days' => $input['auto_match_tolerance_days'],
                'created_by' => (int) (Auth::user()['id'] ?? 0),
                'updated_by' => (int) (Auth::user()['id'] ?? 0),
            ];

            $reconciliationId = $this->model()->createReconciliation($header, $rows);
            $autoMatchCount = 0;
            $autoMatchWarning = '';
            try {
                $reconciliation = $this->model()->findReconciliationById($reconciliationId);
                $lineRows = $this->model()->getLinesByReconciliationId($reconciliationId);
                $candidates = $reconciliation ? $this->model()->getCandidateJournals($reconciliation) : [];
                $plan = $reconciliation ? $this->service()->buildAutoMatchPlan($reconciliation, $lineRows, $candidates) : [];
                if ($plan !== []) {
                    $this->model()->applyAutoMatches($reconciliationId, $plan);
                }
                $autoMatchCount = count($plan);
            } catch (Throwable $matchError) {
                log_error($matchError);
                $autoMatchWarning = ' Auto match belum dijalankan otomatis; Anda masih bisa menjalankannya dari halaman detail.';
            }

            $finalHeader = $this->model()->findReconciliationById($reconciliationId) ?: $header;
            audit_log('rekonsiliasi_bank', 'create', 'Import mutasi bank dan membuat sesi rekonsiliasi baru.', [
                'entity_type' => 'bank_reconciliation',
                'entity_id' => (string) $reconciliationId,
                'after' => [
                    'title' => $finalHeader['title'] ?? $header['title'],
                    'statement_start_date' => $finalHeader['statement_start_date'] ?? $header['statement_start_date'],
                    'statement_end_date' => $finalHeader['statement_end_date'] ?? $header['statement_end_date'],
                    'total_rows' => $finalHeader['total_statement_rows'] ?? 0,
                    'matched_rows' => $finalHeader['total_matched_rows'] ?? 0,
                ],
                'context' => [
                    'uploaded_file' => $storedFile['original_name'],
                    'parsed_rows' => count($rows),
                    'auto_matches' => $autoMatchCount,
                ],
            ]);

            flash('success', 'Rekonsiliasi bank berhasil dibuat. Baris CSV: ' . count($rows) . ' | Auto match: ' . $autoMatchCount . '.' . $autoMatchWarning);
            $this->redirect('/bank-reconciliations?id=' . $reconciliationId);
        } catch (Throwable $e) {
            if ($storedFile !== null && !empty($storedFile['absolute_path']) && is_file((string) $storedFile['absolute_path'])) {
                @unlink((string) $storedFile['absolute_path']);
            }
            log_error($e);
            flash('error', 'Rekonsiliasi bank gagal dibuat. ' . $e->getMessage());
            $this->redirect('/bank-reconciliations');
        }
    }

    public function autoMatch(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        $id = (int) post('id', 0);
        try {
            $this->ensureReady();
            $reconciliation = $this->requireReconciliation($id);
            $lines = $this->model()->getLinesByReconciliationId($id);
            $candidates = $this->model()->getCandidateJournals($reconciliation);
            $plan = $this->service()->buildAutoMatchPlan($reconciliation, $lines, $candidates);
            $this->model()->applyAutoMatches($id, $plan);

            audit_log('rekonsiliasi_bank', 'auto_match', 'Menjalankan auto match rekonsiliasi bank.', [
                'entity_type' => 'bank_reconciliation',
                'entity_id' => (string) $id,
                'context' => ['matched_rows' => count($plan)],
            ]);

            flash('success', 'Auto match selesai. Baris yang berhasil dicocokkan: ' . count($plan) . '.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Auto match gagal. ' . $e->getMessage());
        }

        $this->redirect('/bank-reconciliations?id=' . $id);
    }

    public function manualMatch(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        $lineId = (int) post('line_id', 0);
        $journalId = (int) post('journal_id', 0);
        try {
            $this->ensureReady();
            $line = $this->model()->findLineById($lineId);
            if (!$line) {
                throw new RuntimeException('Baris rekonsiliasi tidak ditemukan.');
            }
            $reconciliation = $this->requireReconciliation((int) $line['reconciliation_id']);
            $candidate = $this->model()->findCandidateJournal($reconciliation, $journalId);
            if (!$candidate) {
                throw new RuntimeException('Jurnal yang dipilih berada di luar rentang dan filter rekonsiliasi ini.');
            }
            if ($this->model()->journalAlreadyMatchedInReconciliation((int) $reconciliation['id'], $journalId, $lineId)) {
                throw new RuntimeException('Jurnal ini sudah dipakai oleh baris mutasi lain pada sesi rekonsiliasi yang sama.');
            }

            $evaluation = $this->service()->evaluateCandidate($reconciliation, $line, $candidate);
            if ($evaluation === null) {
                throw new RuntimeException('Nominal jurnal bank tidak sama dengan mutasi bank. Manual match dibatalkan agar data tetap aman.');
            }

            $reconciliationId = $this->model()->applyManualMatch($lineId, $journalId, (float) $evaluation['score'], 'Manual match. ' . (string) $evaluation['reason']);
            audit_log('rekonsiliasi_bank', 'manual_match', 'Memilih jurnal secara manual untuk mutasi bank.', [
                'entity_type' => 'bank_reconciliation_line',
                'entity_id' => (string) $lineId,
                'context' => ['journal_id' => $journalId, 'reconciliation_id' => $reconciliationId],
            ]);
            flash('success', 'Manual match berhasil disimpan.');
            $this->redirect('/bank-reconciliations?id=' . $reconciliationId);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Manual match gagal. ' . $e->getMessage());
            $this->redirect('/bank-reconciliations');
        }
    }

    public function ignoreLine(): void
    {
        $this->handleLineStateChange('ignore', static fn (BankReconciliationModel $model, int $lineId): int => $model->setLineIgnored($lineId), 'Baris rekonsiliasi diabaikan.');
    }

    public function resetLine(): void
    {
        $this->handleLineStateChange('reset_line', static fn (BankReconciliationModel $model, int $lineId): int => $model->resetLineMatch($lineId), 'Match baris rekonsiliasi direset.');
    }

    public function resetAll(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        $id = (int) post('id', 0);
        try {
            $this->ensureReady();
            $this->requireReconciliation($id);
            $this->model()->resetAllMatches($id);
            audit_log('rekonsiliasi_bank', 'reset_all', 'Semua hasil match rekonsiliasi bank direset.', [
                'entity_type' => 'bank_reconciliation',
                'entity_id' => (string) $id,
                'severity' => 'warning',
            ]);
            flash('success', 'Semua hasil match berhasil direset.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Reset semua match gagal. ' . $e->getMessage());
        }

        $this->redirect('/bank-reconciliations?id=' . $id);
    }

    public function delete(): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        $id = (int) post('id', 0);
        try {
            $this->ensureReady();
            $existing = $this->requireReconciliation($id);
            $deleted = $this->model()->deleteReconciliation($id);
            if ($deleted && ($absolutePath = BankReconciliationService::absolutePath((string) ($existing['stored_file_path'] ?? ''))) !== null) {
                @unlink($absolutePath);
            }
            audit_log('rekonsiliasi_bank', 'delete', 'Menghapus sesi rekonsiliasi bank.', [
                'entity_type' => 'bank_reconciliation',
                'entity_id' => (string) $id,
                'severity' => 'warning',
                'before' => [
                    'title' => $existing['title'] ?? '',
                    'statement_start_date' => $existing['statement_start_date'] ?? '',
                    'statement_end_date' => $existing['statement_end_date'] ?? '',
                ],
            ]);
            flash('success', 'Sesi rekonsiliasi berhasil dihapus.');
        } catch (Throwable $e) {
            log_error($e);
            flash('error', 'Hapus sesi rekonsiliasi gagal. ' . $e->getMessage());
        }

        $this->redirect('/bank-reconciliations');
    }

    public function print(): void
    {
        $id = (int) get_query('id', 0);
        try {
            $this->ensureReady();
            $reconciliation = $this->requireReconciliation($id);
            $lines = $this->model()->getLinesByReconciliationId($id);
            $journalSummary = $this->model()->getJournalWindowSummary($reconciliation);

            $this->view('bank_reconciliations/views/print', [
                'title' => 'Cetak Rekonsiliasi Bank',
                'profile' => app_profile(),
                'reconciliation' => $reconciliation,
                'lines' => $lines,
                'journalSummary' => $journalSummary,
                'reportTitle' => 'Rekonsiliasi Bank - ' . (string) ($reconciliation['account_code'] ?? '') . ' ' . (string) ($reconciliation['account_name'] ?? ''),
                'periodLabel' => bank_reconciliation_statement_label($reconciliation),
                'selectedUnitLabel' => bank_reconciliation_filters_label($reconciliation),
            ], 'print');
        } catch (Throwable $e) {
            log_error($e);
            http_response_code(500);
            render_error_page(500, 'Halaman cetak rekonsiliasi bank belum dapat dibuka.', $e);
        }
    }

    private function handleLineStateChange(string $action, callable $handler, string $successMessage): void
    {
        if (!verify_csrf((string) post('_token'))) {
            http_response_code(419);
            render_error_page(419, 'Token keamanan tidak valid.');
            return;
        }

        $lineId = (int) post('line_id', 0);
        try {
            $this->ensureReady();
            $reconciliationId = $handler($this->model(), $lineId);
            audit_log('rekonsiliasi_bank', $action, $successMessage, [
                'entity_type' => 'bank_reconciliation_line',
                'entity_id' => (string) $lineId,
                'severity' => $action === 'ignore' ? 'warning' : 'info',
                'context' => ['reconciliation_id' => $reconciliationId],
            ]);
            flash('success', $successMessage);
            $this->redirect('/bank-reconciliations?id=' . $reconciliationId);
        } catch (Throwable $e) {
            log_error($e);
            flash('error', $successMessage . ' ' . $e->getMessage());
            $this->redirect('/bank-reconciliations');
        }
    }

    private function requireReconciliation(int $id): array
    {
        if ($id <= 0) {
            throw new RuntimeException('ID rekonsiliasi tidak valid.');
        }

        $reconciliation = $this->model()->findReconciliationById($id);
        if (!$reconciliation) {
            throw new RuntimeException('Sesi rekonsiliasi tidak ditemukan.');
        }

        return $reconciliation;
    }

    private function validateInput(): array
    {
        $bankAccountId = (int) post('bank_account_coa_id', 0);
        if ($bankAccountId <= 0) {
            throw new RuntimeException('Akun bank / kas harus dipilih.');
        }

        $bankAccount = null;
        foreach ($this->model()->getBankAccountOptions() as $account) {
            if ((int) ($account['id'] ?? 0) === $bankAccountId) {
                $bankAccount = $account;
                break;
            }
        }

        if (!$bankAccount) {
            throw new RuntimeException('Akun bank / kas tidak ditemukan atau tidak aktif.');
        }

        $periodId = (int) post('period_id', 0);
        if ($periodId > 0) {
            $exists = false;
            foreach ($this->model()->getPeriods() as $period) {
                if ((int) ($period['id'] ?? 0) === $periodId) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                throw new RuntimeException('Periode akuntansi yang dipilih tidak valid.');
            }
        }

        $businessUnitId = (int) post('business_unit_id', 0);
        if ($businessUnitId > 0 && !find_business_unit($businessUnitId)) {
            throw new RuntimeException('Unit usaha yang dipilih tidak ditemukan.');
        }

        $tolerance = (int) post('auto_match_tolerance_days', 3);
        $tolerance = max(0, min(14, $tolerance));

        return [
            'title' => trim((string) post('title', '')),
            'bank_account_coa_id' => $bankAccountId,
            'bank_account_label' => (string) (($bankAccount['account_code'] ?? '') . ' - ' . ($bankAccount['account_name'] ?? '')),
            'period_id' => $periodId > 0 ? $periodId : null,
            'business_unit_id' => $businessUnitId > 0 ? $businessUnitId : null,
            'statement_no' => trim((string) post('statement_no', '')),
            'statement_start_date' => trim((string) post('statement_start_date', '')),
            'statement_end_date' => trim((string) post('statement_end_date', '')),
            'opening_balance' => $this->parseMoney((string) post('opening_balance', '0')),
            'closing_balance' => $this->parseMoney((string) post('closing_balance', '0')),
            'notes' => trim((string) post('notes', '')),
            'auto_match_tolerance_days' => $tolerance,
        ];
    }

    private function parseMoney(string $value): float
    {
        $value = trim($value);
        if ($value === '') {
            return 0.0;
        }

        $value = str_replace(['Rp', 'rp', ' '], '', $value);
        $commaPos = strrpos($value, ',');
        $dotPos = strrpos($value, '.');
        if ($commaPos !== false && $dotPos !== false) {
            if ($commaPos > $dotPos) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($commaPos !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function defaultFormValues(): array
    {
        $activePeriod = current_accounting_period();
        return [
            'period_id' => $activePeriod['id'] ?? '',
            'statement_start_date' => $activePeriod['start_date'] ?? date('Y-m-01'),
            'statement_end_date' => $activePeriod['end_date'] ?? date('Y-m-t'),
            'auto_match_tolerance_days' => '3',
            'opening_balance' => '0',
            'closing_balance' => '0',
        ];
    }

    private function ensureReady(): void
    {
        if (!Database::isConnected(db_config())) {
            throw new RuntimeException('Koneksi database belum tersedia.');
        }

        if (!$this->model()->hasRequiredTables()) {
            throw new RuntimeException('Tabel rekonsiliasi bank belum tersedia. Jalankan patch_stage4_bank_reconciliation.sql terlebih dahulu.');
        }
    }
}
