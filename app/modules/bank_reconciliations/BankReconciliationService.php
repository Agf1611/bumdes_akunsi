<?php

declare(strict_types=1);

final class BankReconciliationService
{
    public static function directory(): string
    {
        return ROOT_PATH . '/storage/bank_reconciliations';
    }

    public static function ensureDirectory(): void
    {
        $dir = self::directory();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    public static function absolutePath(?string $relativePath): ?string
    {
        $relativePath = trim((string) $relativePath);
        if ($relativePath === '') {
            return null;
        }

        $safe = str_replace('..', '', str_replace('\\', '/', $relativePath));
        $path = self::directory() . '/' . ltrim(basename($safe), '/');
        return is_file($path) ? $path : null;
    }

    public function storeUploadedStatement(array $file): array
    {
        self::ensureDirectory();

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('File CSV mutasi bank wajib dipilih.');
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload file CSV mutasi bank gagal. Silakan pilih file yang valid.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            throw new RuntimeException('Ukuran file CSV maksimal 5 MB.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('File mutasi bank tidak valid. Silakan unggah ulang.');
        }

        $originalName = basename((string) ($file['name'] ?? 'mutasi-bank.csv'));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt'], true)) {
            throw new RuntimeException('File mutasi bank harus berformat CSV.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpName);
        $allowedMime = ['text/plain', 'text/csv', 'application/vnd.ms-excel', 'application/csv'];
        if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
            throw new RuntimeException('Format file mutasi bank tidak dikenali sebagai CSV.');
        }

        $storedName = 'bank-statement-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.csv';
        $target = self::directory() . '/' . $storedName;
        if (!move_uploaded_file($tmpName, $target)) {
            throw new RuntimeException('File mutasi bank gagal disimpan ke server.');
        }

        return [
            'original_name' => $originalName,
            'stored_file_path' => $storedName,
            'absolute_path' => $target,
            'size' => (int) (@filesize($target) ?: 0),
        ];
    }

    public function parseCsvFile(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('File CSV mutasi bank tidak ditemukan di server.');
        }

        $delimiter = $this->detectDelimiter($filePath);
        $handle = @fopen($filePath, 'rb');
        if (!is_resource($handle)) {
            throw new RuntimeException('File CSV mutasi bank tidak dapat dibuka.');
        }

        $header = null;
        $rows = [];
        $lineNo = 0;

        try {
            while (($data = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $lineNo++;
                if ($data === [null] || $data === false) {
                    continue;
                }

                $trimmed = array_map(static fn ($value): string => trim((string) $value), $data);
                $nonEmpty = array_filter($trimmed, static fn (string $value): bool => $value !== '');
                if ($nonEmpty === []) {
                    continue;
                }

                if ($header === null) {
                    $header = $this->mapHeaders($trimmed);
                    continue;
                }

                $row = $this->parseRow($header, $trimmed, $lineNo);
                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        } finally {
            fclose($handle);
        }

        if ($header === null) {
            throw new RuntimeException('Header CSV mutasi bank tidak ditemukan.');
        }

        if ($rows === []) {
            throw new RuntimeException('Tidak ada baris transaksi yang dapat dibaca dari CSV mutasi bank.');
        }

        return $rows;
    }

    public function summarizeRows(array $rows): array
    {
        $summary = [
            'row_count' => 0,
            'total_in' => 0.0,
            'total_out' => 0.0,
            'net_amount' => 0.0,
            'date_from' => '',
            'date_to' => '',
        ];

        foreach ($rows as $row) {
            $summary['row_count']++;
            $summary['total_in'] += (float) ($row['amount_in'] ?? 0);
            $summary['total_out'] += (float) ($row['amount_out'] ?? 0);
            $summary['net_amount'] += (float) ($row['net_amount'] ?? 0);

            $txDate = (string) ($row['transaction_date'] ?? '');
            if ($txDate !== '') {
                if ($summary['date_from'] === '' || $txDate < $summary['date_from']) {
                    $summary['date_from'] = $txDate;
                }
                if ($summary['date_to'] === '' || $txDate > $summary['date_to']) {
                    $summary['date_to'] = $txDate;
                }
            }
        }

        return $summary;
    }

    public function buildAutoMatchPlan(array $reconciliation, array $lines, array $candidates): array
    {
        $plan = [];
        $usedJournalIds = [];
        foreach ($lines as $line) {
            if ((string) ($line['match_status'] ?? '') === 'MANUAL' && !empty($line['matched_journal_id'])) {
                $usedJournalIds[] = (int) $line['matched_journal_id'];
            }
        }

        foreach ($lines as $line) {
            $status = (string) ($line['match_status'] ?? 'UNMATCHED');
            if ($status === 'MANUAL' || $status === 'IGNORED') {
                continue;
            }

            $best = null;
            foreach ($candidates as $candidate) {
                $journalId = (int) ($candidate['id'] ?? 0);
                if ($journalId <= 0 || in_array($journalId, $usedJournalIds, true)) {
                    continue;
                }

                $evaluation = $this->evaluateCandidate($reconciliation, $line, $candidate);
                if ($evaluation === null) {
                    continue;
                }

                if ($best === null || (float) $evaluation['score'] > (float) $best['score']) {
                    $best = $evaluation;
                }
            }

            if ($best !== null && (float) $best['score'] >= 68.0) {
                $plan[] = [
                    'line_id' => (int) ($line['id'] ?? 0),
                    'journal_id' => (int) ($best['journal']['id'] ?? 0),
                    'score' => (float) $best['score'],
                    'reason' => (string) $best['reason'],
                ];
                $usedJournalIds[] = (int) ($best['journal']['id'] ?? 0);
            }
        }

        return $plan;
    }

    public function buildSuggestions(array $reconciliation, array $lines, array $candidates, int $perLine = 5): array
    {
        $suggestions = [];
        foreach ($lines as $line) {
            $status = (string) ($line['match_status'] ?? 'UNMATCHED');
            if ($status === 'MANUAL' || $status === 'IGNORED') {
                continue;
            }

            $items = [];
            foreach ($candidates as $candidate) {
                $evaluation = $this->evaluateCandidate($reconciliation, $line, $candidate);
                if ($evaluation === null) {
                    continue;
                }

                $items[] = [
                    'journal_id' => (int) ($candidate['id'] ?? 0),
                    'journal_no' => (string) ($candidate['journal_no'] ?? '-'),
                    'journal_date' => (string) ($candidate['journal_date'] ?? ''),
                    'description' => (string) ($candidate['description'] ?? ''),
                    'unit_label' => business_unit_label($candidate),
                    'score' => (float) $evaluation['score'],
                    'score_label' => bank_reconciliation_match_quality((float) $evaluation['score']),
                    'reason' => (string) $evaluation['reason'],
                    'bank_debit' => (float) ($candidate['bank_debit'] ?? 0),
                    'bank_credit' => (float) ($candidate['bank_credit'] ?? 0),
                ];
            }

            usort($items, static function (array $a, array $b): int {
                $scoreCompare = (float) $b['score'] <=> (float) $a['score'];
                if ($scoreCompare !== 0) {
                    return $scoreCompare;
                }
                return strcmp((string) $a['journal_date'], (string) $b['journal_date']);
            });

            $suggestions[(int) ($line['id'] ?? 0)] = array_slice($items, 0, max(1, $perLine));
        }

        return $suggestions;
    }

    public function evaluateCandidate(array $reconciliation, array $line, array $candidate): ?array
    {
        $expectedIn = (float) ($line['amount_in'] ?? 0);
        $expectedOut = (float) ($line['amount_out'] ?? 0);
        $expectedAmount = $expectedIn > 0.004 ? $expectedIn : $expectedOut;
        if ($expectedAmount <= 0.004) {
            return null;
        }

        $candidateAmount = $expectedIn > 0.004
            ? (float) ($candidate['bank_debit'] ?? 0)
            : (float) ($candidate['bank_credit'] ?? 0);

        if (abs($candidateAmount - $expectedAmount) > 0.01) {
            return null;
        }

        $tolerance = max(0, min(14, (int) ($reconciliation['auto_match_tolerance_days'] ?? 3)));
        $dateGap = $this->dateDifferenceInDays((string) ($line['transaction_date'] ?? ''), (string) ($candidate['journal_date'] ?? ''));
        if ($dateGap === null || $dateGap > max($tolerance, 7)) {
            return null;
        }

        $score = 60.0;
        $reasonParts = ['Nominal sesuai.'];

        if ($dateGap === 0) {
            $score += 30;
            $reasonParts[] = 'Tanggal sama.';
        } elseif ($dateGap === 1) {
            $score += 22;
            $reasonParts[] = 'Selisih tanggal 1 hari.';
        } elseif ($dateGap <= 3) {
            $score += 16;
            $reasonParts[] = 'Selisih tanggal ' . $dateGap . ' hari.';
        } else {
            $score += max(6, 16 - ($dateGap * 2));
            $reasonParts[] = 'Masih dalam toleransi tanggal.';
        }

        $textScore = $this->descriptionScore((string) ($line['description'] ?? ''), (string) ($candidate['description'] ?? ''), (string) ($candidate['journal_no'] ?? ''), (string) ($line['reference_no'] ?? ''));
        if ($textScore > 0) {
            $score += $textScore;
            $reasonParts[] = 'Keterangan mendekati jurnal.';
        }

        if (!empty($reconciliation['business_unit_id']) && (int) ($candidate['business_unit_id'] ?? 0) === (int) $reconciliation['business_unit_id']) {
            $score += 5;
            $reasonParts[] = 'Unit usaha sesuai.';
        }

        $score = min(100.0, $score);

        return [
            'journal' => $candidate,
            'score' => $score,
            'reason' => implode(' ', $reasonParts),
        ];
    }

    private function parseRow(array $headerMap, array $row, int $lineNo): ?array
    {
        $dateText = $this->pickValue($row, $headerMap, ['transaction_date']);
        $description = trim((string) $this->pickValue($row, $headerMap, ['description']));
        $referenceNo = trim((string) $this->pickValue($row, $headerMap, ['reference_no']));

        if ($description === '' && $referenceNo === '') {
            return null;
        }

        $transactionDate = $this->parseFlexibleDate($dateText);
        if ($transactionDate === null) {
            return null;
        }

        $valueDate = $this->parseFlexibleDate((string) $this->pickValue($row, $headerMap, ['value_date']));
        $balance = $this->parseDecimal((string) $this->pickValue($row, $headerMap, ['balance']));

        $amountIn = 0.0;
        $amountOut = 0.0;
        $debitText = (string) $this->pickValue($row, $headerMap, ['debit']);
        $creditText = (string) $this->pickValue($row, $headerMap, ['credit']);
        $amountText = (string) $this->pickValue($row, $headerMap, ['amount']);
        $typeText = $this->asciiLower((string) $this->pickValue($row, $headerMap, ['type']));

        if ($debitText !== '' || $creditText !== '') {
            $amountOut = max(0.0, $this->parseDecimal($debitText) ?? 0.0);
            $amountIn = max(0.0, $this->parseDecimal($creditText) ?? 0.0);
        } else {
            $amount = $this->parseDecimal($amountText) ?? 0.0;
            if ($amount < 0) {
                $amountOut = abs($amount);
            } elseif (str_contains($typeText, 'debet') || str_contains($typeText, 'debit') || $typeText === 'db' || $typeText === 'd') {
                $amountOut = abs($amount);
            } else {
                $amountIn = abs($amount);
            }
        }

        if ($amountIn <= 0.004 && $amountOut <= 0.004) {
            return null;
        }

        $rawPayload = json_encode([
            'line_no' => $lineNo,
            'row' => $row,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [
            'line_no' => $lineNo,
            'transaction_date' => $transactionDate,
            'value_date' => $valueDate,
            'description' => $description !== '' ? $description : ($referenceNo !== '' ? $referenceNo : 'Mutasi bank'),
            'reference_no' => $referenceNo,
            'amount_in' => $amountIn,
            'amount_out' => $amountOut,
            'net_amount' => $amountIn - $amountOut,
            'running_balance' => $balance,
            'raw_payload' => is_string($rawPayload) ? $rawPayload : '',
            'match_status' => 'UNMATCHED',
        ];
    }

    private function mapHeaders(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $index => $label) {
            $normalized = $this->normalizeHeader($label);
            if ($normalized === '') {
                continue;
            }

            foreach ($this->headerAliases() as $target => $aliases) {
                if (in_array($normalized, $aliases, true) && !isset($map[$target])) {
                    $map[$target] = $index;
                    break;
                }
            }
        }

        if (!isset($map['transaction_date']) || !isset($map['description']) || (!isset($map['debit']) && !isset($map['credit']) && !isset($map['amount']))) {
            throw new RuntimeException('Header CSV belum dikenali. Pastikan ada kolom tanggal, keterangan, dan debit/kredit atau amount.');
        }

        return $map;
    }

    private function headerAliases(): array
    {
        return [
            'transaction_date' => ['tanggal', 'tgl', 'trxdate', 'transactiondate', 'postingdate', 'datetransaction'],
            'value_date' => ['valuedate', 'effective_date', 'effectivedate', 'tanggalefektif'],
            'description' => ['keterangan', 'uraian', 'deskripsi', 'description', 'remark', 'remarks', 'narrative', 'particulars'],
            'reference_no' => ['referensi', 'reference', 'referenceno', 'ref', 'refno', 'nobukti', 'nomorreferensi'],
            'debit' => ['debit', 'debet', 'keluar', 'amountout', 'nominalkeluar'],
            'credit' => ['credit', 'kredit', 'masuk', 'amountin', 'nominalmasuk'],
            'amount' => ['amount', 'nominal', 'jumlah', 'mutasi', 'mutation', 'nilai'],
            'type' => ['type', 'jenis', 'debitcredit', 'debetkredit', 'dk'],
            'balance' => ['balance', 'saldo', 'runningbalance', 'saldoakhir'],
        ];
    }

    private function pickValue(array $row, array $headerMap, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($headerMap[$key])) {
                return trim((string) ($row[(int) $headerMap[$key]] ?? ''));
            }
        }

        return '';
    }

    private function detectDelimiter(string $filePath): string
    {
        $sample = (string) file_get_contents($filePath, false, null, 0, 4096);
        $firstLine = strtok($sample, "\r\n") ?: $sample;
        $counts = [
            ',' => substr_count($firstLine, ','),
            ';' => substr_count($firstLine, ';'),
            "\t" => substr_count($firstLine, "\t"),
        ];
        arsort($counts);
        $delimiter = (string) array_key_first($counts);
        return $delimiter !== '' ? $delimiter : ',';
    }

    private function normalizeHeader(string $value): string
    {
        $value = $this->removeBom(trim($value));
        $value = $this->asciiLower($value);
        $value = str_replace([' ', '-', '/', '.', '(', ')', ':'], '', $value);
        return preg_replace('/[^a-z0-9_]/', '', $value) ?: '';
    }

    private function removeBom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?: $value;
    }

    private function asciiLower(string $value): string
    {
        return strtolower($value);
    }

    private function parseFlexibleDate(string $value): ?string
    {
        $value = trim($this->removeBom($value));
        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'd.m.Y', 'm/d/Y', 'd M Y', 'd F Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($dt instanceof DateTimeImmutable) {
                return $dt->format('Y-m-d');
            }
        }

        try {
            return (new DateTimeImmutable($value))->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function parseDecimal(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $negative = false;
        if (str_starts_with($value, '(') && str_ends_with($value, ')')) {
            $negative = true;
            $value = trim($value, '()');
        }

        $value = str_replace(['Rp', 'rp', 'IDR', 'idr', ' '], '', $value);
        $value = preg_replace('/[^0-9,.-]/', '', $value) ?: '';
        if ($value === '') {
            return null;
        }

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

        if (!is_numeric($value)) {
            return null;
        }

        $number = (float) $value;
        return $negative ? -1 * $number : $number;
    }

    private function dateDifferenceInDays(string $left, string $right): ?int
    {
        try {
            $a = new DateTimeImmutable($left);
            $b = new DateTimeImmutable($right);
            return (int) abs((int) $a->diff($b)->format('%a'));
        } catch (Throwable) {
            return null;
        }
    }

    private function descriptionScore(string $statementDescription, string $journalDescription, string $journalNo, string $referenceNo): float
    {
        $statement = $this->normalizeText($statementDescription . ' ' . $referenceNo);
        $journal = $this->normalizeText($journalDescription . ' ' . $journalNo);
        if ($statement === '' || $journal === '') {
            return 0.0;
        }

        if ($journalNo !== '' && str_contains($statement, $this->normalizeText($journalNo))) {
            return 10.0;
        }

        $statementTokens = $this->meaningfulTokens($statement);
        $journalTokens = $this->meaningfulTokens($journal);
        if ($statementTokens === [] || $journalTokens === []) {
            return 0.0;
        }

        $matches = array_intersect($statementTokens, $journalTokens);
        return min(12.0, count($matches) * 4.0);
    }

    private function normalizeText(string $value): string
    {
        $value = $this->asciiLower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?: '';
        return trim($value);
    }

    private function meaningfulTokens(string $text): array
    {
        $tokens = preg_split('/\s+/', trim($text)) ?: [];
        $tokens = array_values(array_filter($tokens, static fn (string $token): bool => strlen($token) >= 4));
        return array_slice(array_unique($tokens), 0, 10);
    }
}
