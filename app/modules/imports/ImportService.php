<?php

declare(strict_types=1);

final class ImportService
{
    public function __construct(
        private ImportModel $importModel,
        private JournalModel $journalModel,
        private PDO $db
    ) {
    }

    public function importCoa(array $rows, bool $overwriteExisting = false): array
    {
        // keep current behavior from existing patched version
        $expectedHeaders = ['account_code', 'account_name', 'account_type', 'account_category', 'parent_code', 'is_header', 'is_active'];
        if ($rows === []) {
            return ['success' => false, 'errors' => ['File COA kosong atau tidak dapat dibaca.'], 'imported' => 0, 'updated' => 0];
        }

        $headerRow = import_normalize_headers($rows[0]);
        if ($headerRow !== $expectedHeaders) {
            return ['success' => false, 'errors' => ['Header template COA tidak sesuai. Gunakan file template resmi agar kolom benar.'], 'imported' => 0, 'updated' => 0];
        }

        $errors = [];
        $preparedRows = [];
        $seenCodes = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $rowNumber = $index + 2;
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $accountCode = function_exists('coa_normalize_account_code') ? coa_normalize_account_code((string) ($row[0] ?? '')) : strtoupper(trim((string) ($row[0] ?? '')));
            $accountName = trim((string) ($row[1] ?? ''));
            $accountType = strtoupper(trim((string) ($row[2] ?? '')));
            $accountCategory = strtoupper(trim((string) ($row[3] ?? '')));
            $parentCode = function_exists('coa_normalize_account_code') ? coa_normalize_account_code((string) ($row[4] ?? '')) : strtoupper(trim((string) ($row[4] ?? '')));
            $isHeader = import_bool_flag((string) ($row[5] ?? ''));
            $isActive = import_bool_flag((string) ($row[6] ?? ''));

            if ($accountCode === '') {
                $errors[] = 'Baris ' . $rowNumber . ': kode akun wajib diisi.';
                continue;
            }
            if (!preg_match('/^[A-Z0-9.\-]{1,30}$/', $accountCode)) {
                $errors[] = 'Baris ' . $rowNumber . ': kode akun hanya boleh berisi huruf besar, angka, titik, atau tanda hubung.';
            }
            if (isset($seenCodes[$accountCode])) {
                $errors[] = 'Baris ' . $rowNumber . ': kode akun ' . $accountCode . ' duplikat di file import.';
            }
            if ($accountName === '') {
                $errors[] = 'Baris ' . $rowNumber . ': nama akun wajib diisi.';
            }
            $types = coa_account_types();
            if (!isset($types[$accountType])) {
                $errors[] = 'Baris ' . $rowNumber . ': tipe akun tidak valid.';
            }
            $categories = coa_categories_for_type($accountType);
            if ($categories === [] || !isset($categories[$accountCategory])) {
                $errors[] = 'Baris ' . $rowNumber . ': kategori akun tidak cocok dengan tipe akun.';
            }
            if ($parentCode !== '' && $parentCode === $accountCode) {
                $errors[] = 'Baris ' . $rowNumber . ': parent akun tidak boleh sama dengan kode akun sendiri.';
            }
            if ($isHeader === null) {
                $errors[] = 'Baris ' . $rowNumber . ': is_header harus diisi 1/0 atau ya/tidak.';
            }
            if ($isActive === null) {
                $errors[] = 'Baris ' . $rowNumber . ': is_active harus diisi 1/0 atau ya/tidak.';
            }

            $existing = $this->importModel->findCoaByCode($accountCode);
            if ($existing && !$overwriteExisting) {
                $errors[] = 'Baris ' . $rowNumber . ': kode akun ' . $accountCode . ' sudah ada di database. Centang opsi timpa agar akun lama diperbarui.';
            }

            $seenCodes[$accountCode] = true;
            $preparedRows[$accountCode] = [
                'row_number' => $rowNumber,
                'account_code' => $accountCode,
                'account_name' => $accountName,
                'account_type' => $accountType,
                'account_category' => $accountCategory,
                'parent_code' => $parentCode,
                'is_header' => $isHeader,
                'is_active' => $isActive,
                'existing_id' => (int) ($existing['id'] ?? 0),
            ];
        }

        foreach ($preparedRows as $row) {
            if ($row['parent_code'] === '') {
                continue;
            }
            $parentInFile = $preparedRows[$row['parent_code']] ?? null;
            $parentInDb = $this->importModel->findCoaByCode($row['parent_code']);
            $parent = $parentInFile ?? $parentInDb;
            if (!$parent) {
                $errors[] = 'Baris ' . $row['row_number'] . ': parent akun ' . $row['parent_code'] . ' tidak ditemukan di file atau database.';
                continue;
            }
            if ((int) ($parent['is_header'] ?? 0) !== 1) {
                $errors[] = 'Baris ' . $row['row_number'] . ': parent akun ' . $row['parent_code'] . ' harus akun header.';
            }
            if ((string) ($parent['account_type'] ?? '') !== $row['account_type']) {
                $errors[] = 'Baris ' . $row['row_number'] . ': tipe akun parent harus sama dengan tipe akun anak.';
            }
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => array_values(array_unique($errors)), 'imported' => 0, 'updated' => 0];
        }

        $imported = 0;
        $updated = 0;
        $resolvedIds = [];
        $pending = $preparedRows;
        $this->db->beginTransaction();
        try {
            while ($pending !== []) {
                $progress = false;
                foreach ($pending as $code => $row) {
                    $parentId = null;
                    if ($row['parent_code'] !== '') {
                        if (isset($resolvedIds[$row['parent_code']])) {
                            $parentId = $resolvedIds[$row['parent_code']];
                        } else {
                            $parentDb = $this->importModel->findCoaByCode($row['parent_code']);
                            if (!$parentDb) {
                                continue;
                            }
                            $parentId = (int) $parentDb['id'];
                        }
                    }

                    $payload = [
                        'account_code' => $row['account_code'],
                        'account_name' => $row['account_name'],
                        'account_type' => $row['account_type'],
                        'account_category' => $row['account_category'],
                        'parent_id' => $parentId,
                        'is_header' => $row['is_header'],
                        'is_active' => $row['is_active'],
                    ];

                    if ($row['existing_id'] > 0) {
                        $this->importModel->updateCoa($row['existing_id'], $payload);
                        $resolvedIds[$code] = $row['existing_id'];
                        $updated++;
                    } else {
                        $resolvedIds[$code] = $this->importModel->insertCoa($payload);
                        $imported++;
                    }

                    unset($pending[$code]);
                    $progress = true;
                }

                if (!$progress) {
                    throw new RuntimeException('Import COA gagal karena ada relasi parent yang tidak dapat diselesaikan. Periksa urutan dan struktur akun.');
                }
            }

            $this->db->commit();
            return ['success' => true, 'errors' => [], 'imported' => $imported, 'updated' => $updated];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'errors' => ['Import COA dibatalkan: ' . $e->getMessage()], 'imported' => 0, 'updated' => 0];
        }
    }

    public function importJournal(array $rows, int $userId, ?int $businessUnitId = null): array
    {
        $expectedHeaders = ['import_ref', 'journal_date', 'description', 'period_code', 'account_code', 'line_description', 'debit', 'credit'];
        if ($rows === []) {
            return [
                'success' => false,
                'errors' => ['File jurnal kosong atau tidak dapat dibaca.'],
                'imported' => 0,
                'feedback_headers' => ['status', 'catatan'],
                'feedback_rows' => [['GAGAL', 'XLSX dibaca kosong. Periksa file template, pembaca XlsxReader, dan ekstensi zip di server.']],
            ];
        }

        $headerRow = import_normalize_headers($rows[0]);
        if ($headerRow !== $expectedHeaders) {
            return [
                'success' => false,
                'errors' => ['Header template jurnal tidak sesuai. Gunakan file template resmi agar kolom benar.'],
                'imported' => 0,
                'feedback_headers' => array_merge($expectedHeaders, ['status', 'catatan']),
                'feedback_rows' => [$headerRow],
            ];
        }

        $errors = [];
        $groups = [];
        $duplicateRowHashes = [];
        $rowIssues = [];
        $sourceRows = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $rowNumber = $index + 2;
            $sourceRows[$rowNumber] = $row;
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $importRef = strtoupper(trim((string) ($row[0] ?? '')));
            $journalDate = import_normalize_date_value($row[1] ?? '');
            $description = trim((string) ($row[2] ?? ''));
            $periodCode = strtoupper(trim((string) ($row[3] ?? '')));
            $accountCode = function_exists('coa_normalize_account_code') ? coa_normalize_account_code((string) ($row[4] ?? '')) : strtoupper(trim((string) ($row[4] ?? '')));
            $lineDescription = trim((string) ($row[5] ?? ''));
            $debit = import_decimal($row[6] ?? '');
            $credit = import_decimal($row[7] ?? '');

            if ($importRef === '') {
                $this->addRowIssue($rowIssues, $rowNumber, 'import_ref wajib diisi untuk mengelompokkan jurnal.');
                continue;
            }
            if (!preg_match('/^[A-Z0-9_\-]{2,50}$/', $importRef)) {
                $this->addRowIssue($rowIssues, $rowNumber, 'import_ref hanya boleh berisi huruf besar, angka, underscore, atau tanda hubung.');
            }
            if (!$this->isValidDate($journalDate)) {
                $this->addRowIssue($rowIssues, $rowNumber, 'journal_date wajib berupa tanggal valid. Gunakan format YYYY-MM-DD, DD/MM/YYYY, atau tanggal Excel dari template resmi.');
            }
            if ($description === '') {
                $this->addRowIssue($rowIssues, $rowNumber, 'description wajib diisi.');
            }
            if ($periodCode === '') {
                $this->addRowIssue($rowIssues, $rowNumber, 'period_code wajib diisi.');
            }
            if ($accountCode === '') {
                $this->addRowIssue($rowIssues, $rowNumber, 'account_code wajib diisi.');
            }
            if ($debit === null || $credit === null) {
                $this->addRowIssue($rowIssues, $rowNumber, 'debit/kredit harus angka valid dengan maksimal 2 desimal.');
                continue;
            }
            if ((float) $debit < 0 || (float) $credit < 0) {
                $this->addRowIssue($rowIssues, $rowNumber, 'debit/kredit tidak boleh negatif.');
            }
            if ((float) $debit > 0 && (float) $credit > 0) {
                $this->addRowIssue($rowIssues, $rowNumber, 'debit dan kredit tidak boleh terisi bersamaan.');
            }
            if ((float) $debit == 0.0 && (float) $credit == 0.0) {
                $this->addRowIssue($rowIssues, $rowNumber, 'debit dan kredit tidak boleh sama-sama nol.');
            }

            $hash = md5($importRef . '|' . $journalDate . '|' . $description . '|' . $periodCode . '|' . $accountCode . '|' . $lineDescription . '|' . $debit . '|' . $credit);
            if (isset($duplicateRowHashes[$hash])) {
                $this->addRowIssue($rowIssues, $rowNumber, 'Terdeteksi duplikat baris jurnal yang sama di file import.');
            }
            $duplicateRowHashes[$hash] = true;

            $groups[$importRef]['header'] = $groups[$importRef]['header'] ?? [
                'import_ref' => $importRef,
                'journal_date' => $journalDate,
                'description' => $description,
                'period_code' => $periodCode,
            ];
            $groups[$importRef]['row_numbers'][] = $rowNumber;

            if (isset($groups[$importRef]['header'])) {
                $existingHeader = $groups[$importRef]['header'];
                if ($existingHeader['journal_date'] !== $journalDate || $existingHeader['description'] !== $description || $existingHeader['period_code'] !== $periodCode) {
                    $groups[$importRef]['header_mismatch'] = true;
                }
            }
            $groups[$importRef]['rows'][] = [
                'row_number' => $rowNumber,
                'account_code' => $accountCode,
                'line_description' => $lineDescription,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        foreach ($groups as $importRef => $group) {
            $header = $group['header'];
            $period = $this->importModel->findPeriodByCode($header['period_code']);
            if (!$period) {
                $suggestion = $this->suggestPeriodCode($header['journal_date']);
                $this->addGroupIssue($rowIssues, $group['row_numbers'] ?? [], 'Import ref ' . $importRef . ': period_code ' . $header['period_code'] . ' tidak ditemukan.' . ($suggestion !== '' ? ' Coba gunakan ' . $suggestion . '.' : ''));
                continue;
            }
            if ((string) $period['status'] !== 'OPEN') {
                $this->addGroupIssue($rowIssues, $group['row_numbers'] ?? [], 'Import ref ' . $importRef . ': periode ' . $header['period_code'] . ' sudah ditutup.');
            }
            if ($header['journal_date'] < (string) $period['start_date'] || $header['journal_date'] > (string) $period['end_date']) {
                $suggestion = $this->suggestPeriodCode($header['journal_date']);
                $this->addGroupIssue($rowIssues, $group['row_numbers'] ?? [], 'Import ref ' . $importRef . ': tanggal jurnal berada di luar rentang periode.' . ($suggestion !== '' ? ' Kemungkinan period_code seharusnya ' . $suggestion . '.' : ''));
            }

            $headerMismatch = (bool) ($group['header_mismatch'] ?? false);
            foreach ($group['rows'] as $row) {
                $account = $this->importModel->findJournalAccountByCode($row['account_code']);
                if (!$account) {
                    $this->addRowIssue($rowIssues, $row['row_number'], 'account_code ' . $row['account_code'] . ' tidak ditemukan atau belum sinkron dengan COA aktif.');
                    continue;
                }
                if ((int) $account['is_active'] !== 1) {
                    $this->addRowIssue($rowIssues, $row['row_number'], 'akun ' . $row['account_code'] . ' nonaktif.');
                }
                if ((int) $account['is_header'] === 1) {
                    $this->addRowIssue($rowIssues, $row['row_number'], 'akun ' . $row['account_code'] . ' adalah akun header dan tidak boleh dipakai jurnal.');
                }
            }

            if (count($group['rows']) < 2) {
                $this->addGroupIssue($rowIssues, $group['row_numbers'] ?? [], 'Import ref ' . $importRef . ': jurnal minimal harus memiliki 2 baris.');
            }

            $totalDebit = 0.0;
            $totalCredit = 0.0;
            foreach ($group['rows'] as $row) {
                $totalDebit += (float) $row['debit'];
                $totalCredit += (float) $row['credit'];
            }
            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                $this->addGroupIssue($rowIssues, $group['row_numbers'] ?? [], 'Import ref ' . $importRef . ': total debit harus sama dengan total kredit.');
            }
            if ($headerMismatch) {
                $this->addGroupIssue($rowIssues, $group['row_numbers'] ?? [], 'Import ref ' . $importRef . ': data header jurnal dalam grup tidak konsisten.');
            }
        }

        if ($rowIssues !== []) {
            $flatErrors = [];
            foreach ($rowIssues as $messages) {
                foreach ($messages as $message) {
                    $flatErrors[] = $message;
                }
            }
            return [
                'success' => false,
                'errors' => array_values(array_unique($flatErrors)),
                'imported' => 0,
                'feedback_headers' => array_merge($expectedHeaders, ['status', 'catatan', 'suggested_period_code']),
                'feedback_rows' => $this->buildFeedbackRows($sourceRows, $rowIssues),
            ];
        }

        $imported = 0;
        $this->db->beginTransaction();
        try {
            foreach ($groups as $group) {
                $period = $this->importModel->findPeriodByCode($group['header']['period_code']);
                $lines = [];
                foreach ($group['rows'] as $row) {
                    $account = $this->importModel->findJournalAccountByCode($row['account_code']);
                    if (!$account) {
                        throw new RuntimeException('Kode akun ' . $row['account_code'] . ' tidak dapat dipetakan ke COA aktif saat import.');
                    }
                    $lines[] = [
                        'coa_id' => (int) ($account['id'] ?? 0),
                        'line_description' => $row['line_description'],
                        'debit' => $row['debit'],
                        'credit' => $row['credit'],
                    ];
                }
                $this->journalModel->create([
                    'journal_date' => $group['header']['journal_date'],
                    'description' => $group['header']['description'],
                    'period_id' => (int) ($period['id'] ?? 0),
                    'business_unit_id' => $businessUnitId,
                    'total_debit' => number_format(array_sum(array_map(static fn(array $line): float => (float) $line['debit'], $lines)), 2, '.', ''),
                    'total_credit' => number_format(array_sum(array_map(static fn(array $line): float => (float) $line['credit'], $lines)), 2, '.', ''),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ], $lines);
                $imported++;
            }

            $this->db->commit();
            return ['success' => true, 'errors' => [], 'imported' => $imported];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'errors' => ['Import jurnal dibatalkan: ' . $e->getMessage()], 'imported' => 0];
        }
    }

    private function buildFeedbackRows(array $sourceRows, array $rowIssues): array
    {
        ksort($sourceRows);
        $rows = [];
        foreach ($sourceRows as $rowNumber => $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }
            $messages = array_values(array_unique($rowIssues[$rowNumber] ?? []));
            $status = $messages === [] ? 'OK' : 'ERROR';
            $suggestedPeriodCode = $this->suggestPeriodCode((string) ($row[1] ?? ''));
            $normalizedDate = import_normalize_date_value($row[1] ?? '');
            $rows[] = array_merge(
                [
                    trim((string) ($row[0] ?? '')),
                    $normalizedDate !== '' ? $normalizedDate : trim((string) ($row[1] ?? '')),
                    trim((string) ($row[2] ?? '')),
                    trim((string) ($row[3] ?? '')),
                    trim((string) ($row[4] ?? '')),
                    trim((string) ($row[5] ?? '')),
                    trim((string) ($row[6] ?? '')),
                    trim((string) ($row[7] ?? '')),
                ],
                [$status, implode(' | ', $messages), $suggestedPeriodCode]
            );
        }

        return $rows;
    }

    private function suggestPeriodCode(string $journalDate): string
    {
        $normalizedDate = import_normalize_date_value($journalDate);
        if ($normalizedDate === '') {
            return '';
        }

        return substr($normalizedDate, 0, 7);
    }

    private function addRowIssue(array &$rowIssues, int $rowNumber, string $message): void
    {
        $rowIssues[$rowNumber] ??= [];
        $rowIssues[$rowNumber][] = 'Baris ' . $rowNumber . ': ' . $message;
    }

    private function addGroupIssue(array &$rowIssues, array $rowNumbers, string $message): void
    {
        foreach ($rowNumbers as $rowNumber) {
            $rowIssues[$rowNumber] ??= [];
            $rowIssues[$rowNumber][] = $message;
        }
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }
}
