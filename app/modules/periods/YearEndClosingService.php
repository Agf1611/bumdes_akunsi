<?php

declare(strict_types=1);

final class YearEndClosingService
{
    public function __construct(private PDO $db)
    {
    }

    public function preview(int $periodId): array
    {
        $periodModel = new PeriodModel($this->db);
        $period = $periodModel->findById($periodId);
        if ($period === null) {
            throw new RuntimeException('Periode tidak ditemukan.');
        }

        $this->assertEligible($period);

        $nextYear = ((int) substr((string) $period['end_date'], 0, 4)) + 1;
        $nextPeriod = $this->findExistingNextYearPeriod($nextYear);
        $retainedEarnings = $this->findPreferredRetainedEarningsAccount();
        $balances = $this->getClosingBalanceRows((string) $period['end_date']);
        $netIncome = $this->getNetIncome((string) $period['start_date'], (string) $period['end_date']);
        $openingLines = $this->buildOpeningLines($balances, $retainedEarnings, $netIncome);

        return [
            'period' => $period,
            'next_year' => $nextYear,
            'next_period' => $nextPeriod,
            'proposal' => $this->buildNextPeriodProposal($nextYear),
            'retained_earnings' => $retainedEarnings,
            'net_income' => $netIncome,
            'balances' => $balances,
            'opening_lines' => $openingLines,
            'totals' => $this->summarizeOpeningLines($openingLines),
        ];
    }

    public function execute(int $periodId, int $userId): array
    {
        $preview = $this->preview($periodId);
        $period = $preview['period'];
        $nextYear = (int) $preview['next_year'];

        $this->db->beginTransaction();
        try {
            $nextPeriod = $preview['next_period'];
            if ($nextPeriod === null) {
                $proposal = $preview['proposal'];
                $periodModel = new PeriodModel($this->db);
                $nextPeriodId = $periodModel->create([
                    'period_code' => (string) $proposal['period_code'],
                    'period_name' => (string) $proposal['period_name'],
                    'start_date' => (string) $proposal['start_date'],
                    'end_date' => (string) $proposal['end_date'],
                    'status' => 'OPEN',
                    'is_active' => false,
                    'updated_by' => $userId,
                ]);
                $nextPeriod = $periodModel->findById($nextPeriodId);
            }

            if ($nextPeriod === null) {
                throw new RuntimeException('Periode tahun baru tidak berhasil dibuat.');
            }

            $openingDate = (string) $nextPeriod['start_date'];
            $openingDescription = '[AUTO OPENING YEAR] Saldo awal tahun ' . $nextYear . ' dari tutup buku ' . (string) $period['period_code'];

            if ($this->openingJournalExists((int) $nextPeriod['id'], $openingDate, $openingDescription)) {
                throw new RuntimeException('Jurnal saldo awal otomatis untuk tahun ' . $nextYear . ' sudah pernah dibuat.');
            }

            $totals = $this->summarizeOpeningLines($preview['opening_lines']);
            if ((float) $totals['debit'] <= 0 || abs((float) $totals['debit'] - (float) $totals['credit']) > 0.005) {
                throw new RuntimeException('Jurnal saldo awal tidak seimbang. Periksa saldo akun sebelum menutup tahun.');
            }

            $journalId = $this->insertOpeningJournal((int) $nextPeriod['id'], $openingDate, $openingDescription, $preview['opening_lines'], $userId);

            $this->db->prepare('UPDATE accounting_periods SET status = :status, is_active = 0, updated_by = :updated_by, updated_at = NOW() WHERE id = :id')
                ->execute([
                    ':status' => 'CLOSED',
                    ':updated_by' => $userId,
                    ':id' => (int) $period['id'],
                ]);

            $this->db->prepare('UPDATE accounting_periods SET status = :status, is_active = 1, updated_by = :updated_by, updated_at = NOW() WHERE id = :id')
                ->execute([
                    ':status' => 'OPEN',
                    ':updated_by' => $userId,
                    ':id' => (int) $nextPeriod['id'],
                ]);

            $this->db->commit();

            return [
                'closed_period' => $period,
                'next_period' => $nextPeriod,
                'opening_journal_id' => $journalId,
                'opening_description' => $openingDescription,
                'totals' => $totals,
                'retained_earnings' => $preview['retained_earnings'],
                'net_income' => $preview['net_income'],
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function assertEligible(array $period): void
    {
        if ((string) ($period['status'] ?? '') !== 'OPEN') {
            throw new RuntimeException('Hanya periode yang masih buka yang bisa ditutup otomatis ke tahun baru.');
        }
        if ((int) ($period['is_active'] ?? 0) !== 1) {
            throw new RuntimeException('Hanya periode aktif yang bisa dipakai untuk tutup buku tahunan otomatis.');
        }
        $endDate = (string) ($period['end_date'] ?? '');
        if ($endDate === '' || substr($endDate, 5, 5) !== '12-31') {
            throw new RuntimeException('Fitur ini hanya untuk periode yang berakhir di 31 Desember.');
        }
    }

    private function buildNextPeriodProposal(int $year): array
    {
        return [
            'period_code' => (string) $year,
            'period_name' => 'Tahun ' . $year,
            'start_date' => sprintf('%04d-01-01', $year),
            'end_date' => sprintf('%04d-12-31', $year),
        ];
    }

    private function findExistingNextYearPeriod(int $year): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM accounting_periods WHERE start_date = :start_date AND end_date = :end_date LIMIT 1');
        $stmt->execute([
            ':start_date' => sprintf('%04d-01-01', $year),
            ':end_date' => sprintf('%04d-12-31', $year),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getClosingBalanceRows(string $dateTo): array
    {
        $sql = "SELECT
                    a.id,
                    a.account_code,
                    a.account_name,
                    a.account_type,
                    a.account_category,
                    COALESCE(SUM(CASE WHEN h.journal_date <= :date_to THEN l.debit ELSE 0 END), 0) AS total_debit,
                    COALESCE(SUM(CASE WHEN h.journal_date <= :date_to THEN l.credit ELSE 0 END), 0) AS total_credit
                FROM coa_accounts a
                LEFT JOIN journal_lines l ON l.coa_id = a.id
                LEFT JOIN journal_headers h ON h.id = l.journal_id
                WHERE a.is_active = 1
                  AND a.is_header = 0
                  AND a.account_type IN ('ASSET', 'LIABILITY', 'EQUITY')
                GROUP BY a.id, a.account_code, a.account_name, a.account_type, a.account_category
                HAVING total_debit <> total_credit
                ORDER BY FIELD(a.account_type, 'ASSET', 'LIABILITY', 'EQUITY'), a.account_code ASC, a.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':date_to', $dateTo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getNetIncome(string $dateFrom, string $dateTo): float
    {
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN a.account_type = 'REVENUE' THEN (l.credit - l.debit) ELSE 0 END), 0) AS total_revenue,
                    COALESCE(SUM(CASE WHEN a.account_type = 'EXPENSE' THEN (l.debit - l.credit) ELSE 0 END), 0) AS total_expense
                FROM coa_accounts a
                INNER JOIN journal_lines l ON l.coa_id = a.id
                INNER JOIN journal_headers h ON h.id = l.journal_id
                WHERE a.is_active = 1
                  AND a.is_header = 0
                  AND a.account_type IN ('REVENUE', 'EXPENSE')
                  AND h.journal_date >= :date_from
                  AND h.journal_date <= :date_to";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':date_from' => $dateFrom,
            ':date_to' => $dateTo,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_revenue' => 0, 'total_expense' => 0];
        return ((float) $row['total_revenue']) - ((float) $row['total_expense']);
    }

    private function findPreferredRetainedEarningsAccount(): array
    {
        $stmt = $this->db->query("SELECT id, account_code, account_name, account_type, account_category FROM coa_accounts WHERE is_active = 1 AND is_header = 0 AND account_type = 'EQUITY' AND account_category = 'RETAINED_EARNINGS' ORDER BY account_code ASC, id ASC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }

        $stmt = $this->db->query("SELECT id, account_code, account_name, account_type, account_category FROM coa_accounts WHERE is_active = 1 AND is_header = 0 AND account_type = 'EQUITY' ORDER BY account_code ASC, id ASC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }

        $parentId = null;
        $stmt = $this->db->query("SELECT id FROM coa_accounts WHERE account_type = 'EQUITY' AND is_header = 1 ORDER BY account_code ASC, id ASC LIMIT 1");
        $header = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($header) {
            $parentId = (int) $header['id'];
        }

        $insert = $this->db->prepare('INSERT INTO coa_accounts (account_code, account_name, account_type, account_category, parent_id, is_header, is_active, created_at, updated_at) VALUES (:account_code, :account_name, :account_type, :account_category, :parent_id, 0, 1, NOW(), NOW())');
        $insert->bindValue(':account_code', '3.999', PDO::PARAM_STR);
        $insert->bindValue(':account_name', 'Laba Ditahan', PDO::PARAM_STR);
        $insert->bindValue(':account_type', 'EQUITY', PDO::PARAM_STR);
        $insert->bindValue(':account_category', 'RETAINED_EARNINGS', PDO::PARAM_STR);
        if ($parentId !== null) {
            $insert->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
        } else {
            $insert->bindValue(':parent_id', null, PDO::PARAM_NULL);
        }
        $insert->execute();

        return [
            'id' => (int) $this->db->lastInsertId(),
            'account_code' => '3.999',
            'account_name' => 'Laba Ditahan',
            'account_type' => 'EQUITY',
            'account_category' => 'RETAINED_EARNINGS',
        ];
    }

    private function buildOpeningLines(array $balances, array $retainedEarnings, float $netIncome): array
    {
        $aggregate = [];
        foreach ($balances as $row) {
            $accountType = (string) $row['account_type'];
            $amount = $accountType === 'ASSET'
                ? ((float) $row['total_debit']) - ((float) $row['total_credit'])
                : ((float) $row['total_credit']) - ((float) $row['total_debit']);

            if (abs($amount) < 0.005) {
                continue;
            }

            $aggregate[(int) $row['id']] = [
                'coa_id' => (int) $row['id'],
                'account_code' => (string) $row['account_code'],
                'account_name' => (string) $row['account_name'],
                'account_type' => $accountType,
                'signed_amount' => $amount,
            ];
        }

        $retainedId = (int) $retainedEarnings['id'];
        if (!isset($aggregate[$retainedId])) {
            $aggregate[$retainedId] = [
                'coa_id' => $retainedId,
                'account_code' => (string) $retainedEarnings['account_code'],
                'account_name' => (string) $retainedEarnings['account_name'],
                'account_type' => 'EQUITY',
                'signed_amount' => 0.0,
            ];
        }
        $aggregate[$retainedId]['signed_amount'] += $netIncome;

        $lines = [];
        foreach ($aggregate as $item) {
            $amount = (float) $item['signed_amount'];
            if (abs($amount) < 0.005) {
                continue;
            }
            $debit = 0.0;
            $credit = 0.0;
            if ((string) $item['account_type'] === 'ASSET') {
                if ($amount >= 0) {
                    $debit = $amount;
                } else {
                    $credit = abs($amount);
                }
            } else {
                if ($amount >= 0) {
                    $credit = $amount;
                } else {
                    $debit = abs($amount);
                }
            }
            $lines[] = [
                'coa_id' => (int) $item['coa_id'],
                'account_code' => (string) $item['account_code'],
                'account_name' => (string) $item['account_name'],
                'account_type' => (string) $item['account_type'],
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
            ];
        }

        usort($lines, static fn (array $a, array $b): int => strcmp((string) $a['account_code'], (string) $b['account_code']));
        return $lines;
    }

    private function summarizeOpeningLines(array $lines): array
    {
        $debit = 0.0;
        $credit = 0.0;
        foreach ($lines as $line) {
            $debit += (float) ($line['debit'] ?? 0);
            $credit += (float) ($line['credit'] ?? 0);
        }

        return [
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'line_count' => count($lines),
            'is_balanced' => abs($debit - $credit) < 0.005,
        ];
    }

    private function openingJournalExists(int $periodId, string $journalDate, string $description): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_headers WHERE period_id = :period_id AND journal_date = :journal_date AND description = :description');
        $stmt->execute([
            ':period_id' => $periodId,
            ':journal_date' => $journalDate,
            ':description' => $description,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function insertOpeningJournal(int $periodId, string $journalDate, string $description, array $lines, int $userId): int
    {
        $journalNo = $this->generateJournalNumber($periodId);
        $totals = $this->summarizeOpeningLines($lines);

        $stmt = $this->db->prepare('INSERT INTO journal_headers (journal_no, journal_date, description, period_id, total_debit, total_credit, created_by, updated_by, created_at, updated_at) VALUES (:journal_no, :journal_date, :description, :period_id, :total_debit, :total_credit, :created_by, :updated_by, NOW(), NOW())');
        $stmt->execute([
            ':journal_no' => $journalNo,
            ':journal_date' => $journalDate,
            ':description' => $description,
            ':period_id' => $periodId,
            ':total_debit' => (float) $totals['debit'],
            ':total_credit' => (float) $totals['credit'],
            ':created_by' => $userId,
            ':updated_by' => $userId,
        ]);
        $journalId = (int) $this->db->lastInsertId();

        $lineStmt = $this->db->prepare('INSERT INTO journal_lines (journal_id, line_no, coa_id, line_description, debit, credit, created_at) VALUES (:journal_id, :line_no, :coa_id, :line_description, :debit, :credit, NOW())');
        $lineNo = 1;
        foreach ($lines as $line) {
            $lineStmt->execute([
                ':journal_id' => $journalId,
                ':line_no' => $lineNo++,
                ':coa_id' => (int) $line['coa_id'],
                ':line_description' => 'Saldo awal tahun',
                ':debit' => (float) $line['debit'],
                ':credit' => (float) $line['credit'],
            ]);
        }

        return $journalId;
    }

    private function generateJournalNumber(int $periodId): string
    {
        $stmt = $this->db->prepare('SELECT period_code FROM accounting_periods WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
        $periodCode = (string) ($stmt->fetchColumn() ?: 'GEN');

        $prefix = 'JU/' . strtoupper($periodCode) . '/';
        $stmt = $this->db->prepare('SELECT journal_no FROM journal_headers WHERE journal_no LIKE :prefix ORDER BY id DESC LIMIT 1');
        $stmt->bindValue(':prefix', $prefix . '%', PDO::PARAM_STR);
        $stmt->execute();
        $lastNo = (string) ($stmt->fetchColumn() ?: '');
        $next = 1;
        if ($lastNo !== '' && preg_match('/(\d{4})$/', $lastNo, $m)) {
            $next = ((int) $m[1]) + 1;
        }
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
