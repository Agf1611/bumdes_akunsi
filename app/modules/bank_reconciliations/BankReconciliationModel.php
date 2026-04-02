<?php

declare(strict_types=1);

final class BankReconciliationModel
{
    public function __construct(private PDO $db)
    {
    }

    public function hasRequiredTables(): bool
    {
        return $this->tableExists('bank_reconciliations')
            && $this->tableExists('bank_reconciliation_lines')
            && $this->tableExists('journal_headers')
            && $this->tableExists('journal_lines');
    }

    public function getBankAccountOptions(): array
    {
        $sql = "SELECT id, account_code, account_name,
                       CASE
                           WHEN LOWER(account_name) LIKE '%bank%' THEN 0
                           WHEN LOWER(account_name) LIKE '%kas%' THEN 1
                           ELSE 2
                       END AS sort_rank
                FROM coa_accounts
                WHERE is_active = 1 AND is_header = 0 AND account_type = 'ASSET'
                ORDER BY sort_rank ASC, account_code ASC, id ASC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPeriods(): array
    {
        $stmt = $this->db->query('SELECT id, period_code, period_name, start_date, end_date, status, is_active FROM accounting_periods ORDER BY start_date DESC, id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listReconciliations(): array
    {
        $sql = 'SELECT r.*, a.account_code, a.account_name,
                       p.period_name,
                       bu.unit_code, bu.unit_name,
                       u.full_name AS created_by_name
                FROM bank_reconciliations r
                INNER JOIN coa_accounts a ON a.id = r.bank_account_coa_id
                LEFT JOIN accounting_periods p ON p.id = r.period_id
                LEFT JOIN business_units bu ON bu.id = r.business_unit_id
                LEFT JOIN users u ON u.id = r.created_by
                ORDER BY r.created_at DESC, r.id DESC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findReconciliationById(int $id): ?array
    {
        $sql = 'SELECT r.*, a.account_code, a.account_name,
                       p.period_name,
                       bu.unit_code, bu.unit_name
                FROM bank_reconciliations r
                INNER JOIN coa_accounts a ON a.id = r.bank_account_coa_id
                LEFT JOIN accounting_periods p ON p.id = r.period_id
                LEFT JOIN business_units bu ON bu.id = r.business_unit_id
                WHERE r.id = :id
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findLineById(int $id): ?array
    {
        $sql = 'SELECT l.*, r.bank_account_coa_id, r.business_unit_id, r.auto_match_tolerance_days,
                       r.statement_start_date, r.statement_end_date,
                       a.account_code, a.account_name
                FROM bank_reconciliation_lines l
                INNER JOIN bank_reconciliations r ON r.id = l.reconciliation_id
                INNER JOIN coa_accounts a ON a.id = r.bank_account_coa_id
                WHERE l.id = :id
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getLinesByReconciliationId(int $reconciliationId): array
    {
        $sql = 'SELECT l.*, j.journal_no, j.journal_date, j.description AS journal_description,
                       bu.unit_code AS journal_unit_code, bu.unit_name AS journal_unit_name
                FROM bank_reconciliation_lines l
                LEFT JOIN journal_headers j ON j.id = l.matched_journal_id
                LEFT JOIN business_units bu ON bu.id = j.business_unit_id
                WHERE l.reconciliation_id = :id
                ORDER BY l.transaction_date ASC, l.line_no ASC, l.id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $reconciliationId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createReconciliation(array $header, array $lines): int
    {
        if ($lines === []) {
            throw new RuntimeException('File mutasi bank tidak berisi data transaksi yang dapat diproses.');
        }

        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO bank_reconciliations (
                    title, bank_account_coa_id, period_id, business_unit_id, statement_no,
                    statement_start_date, statement_end_date, opening_balance, closing_balance,
                    total_statement_rows, total_statement_in, total_statement_out, total_statement_net,
                    total_matched_rows, total_unmatched_rows, matched_amount, unmatched_amount,
                    imported_file_name, stored_file_path, notes, auto_match_tolerance_days,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :title, :bank_account_coa_id, :period_id, :business_unit_id, :statement_no,
                    :statement_start_date, :statement_end_date, :opening_balance, :closing_balance,
                    0, 0, 0, 0, 0, 0, 0, 0,
                    :imported_file_name, :stored_file_path, :notes, :auto_match_tolerance_days,
                    :created_by, :updated_by, NOW(), NOW()
                )'
            );
            $this->bindNullableInt($stmt, ':period_id', $header['period_id'] ?? null);
            $this->bindNullableInt($stmt, ':business_unit_id', $header['business_unit_id'] ?? null);
            $stmt->bindValue(':title', trim((string) ($header['title'] ?? 'Rekonsiliasi Bank')), PDO::PARAM_STR);
            $stmt->bindValue(':bank_account_coa_id', (int) ($header['bank_account_coa_id'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':statement_no', trim((string) ($header['statement_no'] ?? '')), PDO::PARAM_STR);
            $stmt->bindValue(':statement_start_date', (string) ($header['statement_start_date'] ?? ''), PDO::PARAM_STR);
            $stmt->bindValue(':statement_end_date', (string) ($header['statement_end_date'] ?? ''), PDO::PARAM_STR);
            $stmt->bindValue(':opening_balance', (float) ($header['opening_balance'] ?? 0));
            $stmt->bindValue(':closing_balance', (float) ($header['closing_balance'] ?? 0));
            $stmt->bindValue(':imported_file_name', trim((string) ($header['imported_file_name'] ?? '')), PDO::PARAM_STR);
            $stmt->bindValue(':stored_file_path', trim((string) ($header['stored_file_path'] ?? '')), PDO::PARAM_STR);
            $stmt->bindValue(':notes', trim((string) ($header['notes'] ?? '')), PDO::PARAM_STR);
            $stmt->bindValue(':auto_match_tolerance_days', max(0, min(14, (int) ($header['auto_match_tolerance_days'] ?? 3))), PDO::PARAM_INT);
            $stmt->bindValue(':created_by', (int) ($header['created_by'] ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':updated_by', (int) ($header['updated_by'] ?? 0), PDO::PARAM_INT);
            $stmt->execute();

            $reconciliationId = (int) $this->db->lastInsertId();

            $lineStmt = $this->db->prepare(
                'INSERT INTO bank_reconciliation_lines (
                    reconciliation_id, line_no, transaction_date, value_date, description, reference_no,
                    amount_in, amount_out, net_amount, running_balance, raw_payload,
                    match_status, matched_score, created_at
                ) VALUES (
                    :reconciliation_id, :line_no, :transaction_date, :value_date, :description, :reference_no,
                    :amount_in, :amount_out, :net_amount, :running_balance, :raw_payload,
                    :match_status, 0, NOW()
                )'
            );

            foreach ($lines as $index => $line) {
                $lineStmt->bindValue(':reconciliation_id', $reconciliationId, PDO::PARAM_INT);
                $lineStmt->bindValue(':line_no', (int) ($line['line_no'] ?? ($index + 1)), PDO::PARAM_INT);
                $lineStmt->bindValue(':transaction_date', (string) ($line['transaction_date'] ?? ''), PDO::PARAM_STR);
                $this->bindNullableString($lineStmt, ':value_date', $line['value_date'] ?? null);
                $lineStmt->bindValue(':description', (string) ($line['description'] ?? '-'), PDO::PARAM_STR);
                $this->bindNullableString($lineStmt, ':reference_no', $line['reference_no'] ?? null);
                $lineStmt->bindValue(':amount_in', (float) ($line['amount_in'] ?? 0));
                $lineStmt->bindValue(':amount_out', (float) ($line['amount_out'] ?? 0));
                $lineStmt->bindValue(':net_amount', (float) ($line['net_amount'] ?? 0));
                $this->bindNullableFloat($lineStmt, ':running_balance', $line['running_balance'] ?? null);
                $lineStmt->bindValue(':raw_payload', (string) ($line['raw_payload'] ?? ''), PDO::PARAM_STR);
                $lineStmt->bindValue(':match_status', (string) ($line['match_status'] ?? 'UNMATCHED'), PDO::PARAM_STR);
                $lineStmt->execute();
            }

            $this->refreshReconciliationSummary($reconciliationId);

            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->commit();
            }

            return $reconciliationId;
        } catch (Throwable $e) {
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function refreshReconciliationSummary(int $reconciliationId): void
    {
        $sql = "SELECT
                    COUNT(*) AS total_rows,
                    COALESCE(SUM(amount_in), 0) AS total_in,
                    COALESCE(SUM(amount_out), 0) AS total_out,
                    COALESCE(SUM(net_amount), 0) AS total_net,
                    SUM(CASE WHEN match_status IN ('AUTO', 'MANUAL') THEN 1 ELSE 0 END) AS matched_rows,
                    SUM(CASE WHEN match_status = 'UNMATCHED' THEN 1 ELSE 0 END) AS unmatched_rows,
                    COALESCE(SUM(CASE WHEN match_status IN ('AUTO', 'MANUAL') THEN ABS(net_amount) ELSE 0 END), 0) AS matched_amount,
                    COALESCE(SUM(CASE WHEN match_status = 'UNMATCHED' THEN ABS(net_amount) ELSE 0 END), 0) AS unmatched_amount
                FROM bank_reconciliation_lines
                WHERE reconciliation_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $reconciliationId, PDO::PARAM_INT);
        $stmt->execute();
        $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $update = $this->db->prepare(
            'UPDATE bank_reconciliations SET
                total_statement_rows = :total_rows,
                total_statement_in = :total_in,
                total_statement_out = :total_out,
                total_statement_net = :total_net,
                total_matched_rows = :matched_rows,
                total_unmatched_rows = :unmatched_rows,
                matched_amount = :matched_amount,
                unmatched_amount = :unmatched_amount,
                updated_at = NOW()
             WHERE id = :id'
        );
        $update->bindValue(':id', $reconciliationId, PDO::PARAM_INT);
        $update->bindValue(':total_rows', (int) ($summary['total_rows'] ?? 0), PDO::PARAM_INT);
        $update->bindValue(':total_in', (float) ($summary['total_in'] ?? 0));
        $update->bindValue(':total_out', (float) ($summary['total_out'] ?? 0));
        $update->bindValue(':total_net', (float) ($summary['total_net'] ?? 0));
        $update->bindValue(':matched_rows', (int) ($summary['matched_rows'] ?? 0), PDO::PARAM_INT);
        $update->bindValue(':unmatched_rows', (int) ($summary['unmatched_rows'] ?? 0), PDO::PARAM_INT);
        $update->bindValue(':matched_amount', (float) ($summary['matched_amount'] ?? 0));
        $update->bindValue(':unmatched_amount', (float) ($summary['unmatched_amount'] ?? 0));
        $update->execute();
    }

    public function getCandidateJournals(array $reconciliation): array
    {
        $coaId = (int) ($reconciliation['bank_account_coa_id'] ?? 0);
        if ($coaId <= 0) {
            return [];
        }

        $tolerance = max(0, min(14, (int) ($reconciliation['auto_match_tolerance_days'] ?? 3)));
        $startDate = $this->offsetDate((string) ($reconciliation['statement_start_date'] ?? ''), -$tolerance);
        $endDate = $this->offsetDate((string) ($reconciliation['statement_end_date'] ?? ''), $tolerance);

        $sql = 'SELECT j.id, j.journal_no, j.journal_date, j.description, j.business_unit_id,
                       bu.unit_code, bu.unit_name,
                       SUM(CASE WHEN l.coa_id = :coa_id THEN l.debit ELSE 0 END) AS bank_debit,
                       SUM(CASE WHEN l.coa_id = :coa_id THEN l.credit ELSE 0 END) AS bank_credit
                FROM journal_headers j
                INNER JOIN journal_lines l ON l.journal_id = j.id
                LEFT JOIN business_units bu ON bu.id = j.business_unit_id
                WHERE j.journal_date BETWEEN :date_from AND :date_to';

        if (!empty($reconciliation['business_unit_id'])) {
            $sql .= ' AND j.business_unit_id = :unit_id';
        }

        $sql .= ' GROUP BY j.id, j.journal_no, j.journal_date, j.description, j.business_unit_id, bu.unit_code, bu.unit_name
                  HAVING bank_debit > 0.004 OR bank_credit > 0.004
                  ORDER BY j.journal_date ASC, j.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':coa_id', $coaId, PDO::PARAM_INT);
        $stmt->bindValue(':date_from', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':date_to', $endDate, PDO::PARAM_STR);
        if (!empty($reconciliation['business_unit_id'])) {
            $stmt->bindValue(':unit_id', (int) $reconciliation['business_unit_id'], PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findCandidateJournal(array $reconciliation, int $journalId): ?array
    {
        foreach ($this->getCandidateJournals($reconciliation) as $candidate) {
            if ((int) ($candidate['id'] ?? 0) === $journalId) {
                return $candidate;
            }
        }

        return null;
    }

    public function getJournalWindowSummary(array $reconciliation): array
    {
        $coaId = (int) ($reconciliation['bank_account_coa_id'] ?? 0);
        if ($coaId <= 0) {
            return [
                'journal_in' => 0.0,
                'journal_out' => 0.0,
                'journal_net' => 0.0,
                'journal_count' => 0,
            ];
        }

        $sql = 'SELECT
                    COALESCE(SUM(CASE WHEN l.coa_id = :coa_id THEN l.debit ELSE 0 END), 0) AS journal_in,
                    COALESCE(SUM(CASE WHEN l.coa_id = :coa_id THEN l.credit ELSE 0 END), 0) AS journal_out,
                    COUNT(DISTINCT CASE WHEN l.coa_id = :coa_id THEN j.id END) AS journal_count
                FROM journal_headers j
                INNER JOIN journal_lines l ON l.journal_id = j.id
                WHERE j.journal_date BETWEEN :date_from AND :date_to';

        if (!empty($reconciliation['business_unit_id'])) {
            $sql .= ' AND j.business_unit_id = :unit_id';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':coa_id', $coaId, PDO::PARAM_INT);
        $stmt->bindValue(':date_from', (string) ($reconciliation['statement_start_date'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':date_to', (string) ($reconciliation['statement_end_date'] ?? ''), PDO::PARAM_STR);
        if (!empty($reconciliation['business_unit_id'])) {
            $stmt->bindValue(':unit_id', (int) $reconciliation['business_unit_id'], PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $journalIn = (float) ($row['journal_in'] ?? 0);
        $journalOut = (float) ($row['journal_out'] ?? 0);
        return [
            'journal_in' => $journalIn,
            'journal_out' => $journalOut,
            'journal_net' => $journalIn - $journalOut,
            'journal_count' => (int) ($row['journal_count'] ?? 0),
        ];
    }

    public function applyAutoMatches(int $reconciliationId, array $matches): void
    {
        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $reset = $this->db->prepare(
                "UPDATE bank_reconciliation_lines
                 SET match_status = 'UNMATCHED', matched_journal_id = NULL, matched_score = 0, matched_reason = NULL, matched_at = NULL
                 WHERE reconciliation_id = :id AND match_status = 'AUTO'"
            );
            $reset->bindValue(':id', $reconciliationId, PDO::PARAM_INT);
            $reset->execute();

            $apply = $this->db->prepare(
                "UPDATE bank_reconciliation_lines
                 SET match_status = 'AUTO', matched_journal_id = :journal_id, matched_score = :score,
                     matched_reason = :reason, matched_at = NOW()
                 WHERE reconciliation_id = :reconciliation_id AND id = :line_id"
            );

            foreach ($matches as $match) {
                $apply->bindValue(':journal_id', (int) ($match['journal_id'] ?? 0), PDO::PARAM_INT);
                $apply->bindValue(':score', (float) ($match['score'] ?? 0));
                $apply->bindValue(':reason', (string) ($match['reason'] ?? ''), PDO::PARAM_STR);
                $apply->bindValue(':reconciliation_id', $reconciliationId, PDO::PARAM_INT);
                $apply->bindValue(':line_id', (int) ($match['line_id'] ?? 0), PDO::PARAM_INT);
                $apply->execute();
            }

            $updateHeader = $this->db->prepare('UPDATE bank_reconciliations SET last_matched_at = NOW(), updated_at = NOW() WHERE id = :id');
            $updateHeader->bindValue(':id', $reconciliationId, PDO::PARAM_INT);
            $updateHeader->execute();

            $this->refreshReconciliationSummary($reconciliationId);

            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function journalAlreadyMatchedInReconciliation(int $reconciliationId, int $journalId, int $excludeLineId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM bank_reconciliation_lines WHERE reconciliation_id = :reconciliation_id AND matched_journal_id = :journal_id AND id <> :exclude_line_id AND match_status IN ('AUTO', 'MANUAL')";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':reconciliation_id', $reconciliationId, PDO::PARAM_INT);
        $stmt->bindValue(':journal_id', $journalId, PDO::PARAM_INT);
        $stmt->bindValue(':exclude_line_id', $excludeLineId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    public function applyManualMatch(int $lineId, int $journalId, float $score, string $reason): int
    {
        $line = $this->findLineById($lineId);
        if (!$line) {
            throw new RuntimeException('Baris rekonsiliasi tidak ditemukan.');
        }

        $stmt = $this->db->prepare(
            "UPDATE bank_reconciliation_lines
             SET match_status = 'MANUAL', matched_journal_id = :journal_id, matched_score = :score,
                 matched_reason = :reason, matched_at = NOW()
             WHERE id = :id"
        );
        $stmt->bindValue(':journal_id', $journalId, PDO::PARAM_INT);
        $stmt->bindValue(':score', $score);
        $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
        $stmt->bindValue(':id', $lineId, PDO::PARAM_INT);
        $stmt->execute();

        $reconciliationId = (int) ($line['reconciliation_id'] ?? 0);
        $this->refreshReconciliationSummary($reconciliationId);
        return $reconciliationId;
    }

    public function setLineIgnored(int $lineId): int
    {
        $line = $this->findLineById($lineId);
        if (!$line) {
            throw new RuntimeException('Baris rekonsiliasi tidak ditemukan.');
        }

        $stmt = $this->db->prepare(
            "UPDATE bank_reconciliation_lines
             SET match_status = 'IGNORED', matched_journal_id = NULL, matched_score = 0,
                 matched_reason = 'Diabaikan oleh pengguna.', matched_at = NOW()
             WHERE id = :id"
        );
        $stmt->bindValue(':id', $lineId, PDO::PARAM_INT);
        $stmt->execute();

        $reconciliationId = (int) ($line['reconciliation_id'] ?? 0);
        $this->refreshReconciliationSummary($reconciliationId);
        return $reconciliationId;
    }

    public function resetLineMatch(int $lineId): int
    {
        $line = $this->findLineById($lineId);
        if (!$line) {
            throw new RuntimeException('Baris rekonsiliasi tidak ditemukan.');
        }

        $stmt = $this->db->prepare(
            "UPDATE bank_reconciliation_lines
             SET match_status = 'UNMATCHED', matched_journal_id = NULL, matched_score = 0,
                 matched_reason = NULL, matched_at = NULL
             WHERE id = :id"
        );
        $stmt->bindValue(':id', $lineId, PDO::PARAM_INT);
        $stmt->execute();

        $reconciliationId = (int) ($line['reconciliation_id'] ?? 0);
        $this->refreshReconciliationSummary($reconciliationId);
        return $reconciliationId;
    }

    public function resetAllMatches(int $reconciliationId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE bank_reconciliation_lines
             SET match_status = 'UNMATCHED', matched_journal_id = NULL, matched_score = 0,
                 matched_reason = NULL, matched_at = NULL
             WHERE reconciliation_id = :id"
        );
        $stmt->bindValue(':id', $reconciliationId, PDO::PARAM_INT);
        $stmt->execute();

        $updateHeader = $this->db->prepare('UPDATE bank_reconciliations SET last_matched_at = NULL, updated_at = NOW() WHERE id = :id');
        $updateHeader->bindValue(':id', $reconciliationId, PDO::PARAM_INT);
        $updateHeader->execute();

        $this->refreshReconciliationSummary($reconciliationId);
    }

    public function deleteReconciliation(int $id): ?array
    {
        $row = $this->findReconciliationById($id);
        if (!$row) {
            return null;
        }

        $stmt = $this->db->prepare('DELETE FROM bank_reconciliations WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $row;
    }

    private function bindNullableInt(PDOStatement $stmt, string $placeholder, mixed $value): void
    {
        if ($value === null || (int) $value <= 0) {
            $stmt->bindValue($placeholder, null, PDO::PARAM_NULL);
            return;
        }

        $stmt->bindValue($placeholder, (int) $value, PDO::PARAM_INT);
    }

    private function bindNullableString(PDOStatement $stmt, string $placeholder, mixed $value): void
    {
        $text = trim((string) $value);
        if ($text === '') {
            $stmt->bindValue($placeholder, null, PDO::PARAM_NULL);
            return;
        }

        $stmt->bindValue($placeholder, $text, PDO::PARAM_STR);
    }

    private function bindNullableFloat(PDOStatement $stmt, string $placeholder, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($placeholder, null, PDO::PARAM_NULL);
            return;
        }

        $stmt->bindValue($placeholder, (float) $value);
    }

    private function tableExists(string $tableName): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
        );
        $stmt->bindValue(':table_name', $tableName, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    private function offsetDate(string $date, int $days): string
    {
        try {
            $dt = new DateTimeImmutable($date);
            if ($days === 0) {
                return $dt->format('Y-m-d');
            }
            $modifier = ($days > 0 ? '+' : '') . $days . ' days';
            return $dt->modify($modifier)->format('Y-m-d');
        } catch (Throwable) {
            return $date;
        }
    }
}
