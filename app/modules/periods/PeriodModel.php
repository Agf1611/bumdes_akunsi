<?php

declare(strict_types=1);

final class PeriodModel
{
    public function __construct(private PDO $db)
    {
    }

    public function getList(): array
    {
        $sql = 'SELECT ap.*, u.full_name AS updated_by_name
                FROM accounting_periods ap
                LEFT JOIN users u ON u.id = ap.updated_by
                ORDER BY ap.start_date DESC, ap.id DESC';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM accounting_periods WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByCode(string $periodCode, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT * FROM accounting_periods WHERE period_code = :period_code';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':period_code', $periodCode, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function hasOverlap(string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM accounting_periods
                WHERE NOT (end_date < :start_date OR start_date > :end_date)';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();
        try {
            if ($data['is_active']) {
                $this->clearActiveFlag();
            }

            $sql = 'INSERT INTO accounting_periods (
                        period_code, period_name, start_date, end_date, status,
                        is_active, updated_by, created_at, updated_at
                    ) VALUES (
                        :period_code, :period_name, :start_date, :end_date, :status,
                        :is_active, :updated_by, NOW(), NOW()
                    )';
            $stmt = $this->db->prepare($sql);
            $this->bindData($stmt, $data);
            $stmt->execute();
            $id = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function update(int $id, array $data): void
    {
        $this->db->beginTransaction();
        try {
            if ($data['is_active']) {
                $this->clearActiveFlag($id);
            }

            $sql = 'UPDATE accounting_periods SET
                        period_code = :period_code,
                        period_name = :period_name,
                        start_date = :start_date,
                        end_date = :end_date,
                        status = :status,
                        is_active = :is_active,
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindData($stmt, $data);
            $stmt->execute();
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function setActive(int $id, int $updatedBy): void
    {
        $this->db->beginTransaction();
        try {
            $this->clearActiveFlag();
            $stmt = $this->db->prepare('UPDATE accounting_periods SET is_active = 1, updated_by = :updated_by, updated_at = NOW() WHERE id = :id');
            $stmt->bindValue(':updated_by', $updatedBy, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function setStatus(int $id, string $status, int $updatedBy): void
    {
        $stmt = $this->db->prepare('UPDATE accounting_periods SET status = :status, updated_by = :updated_by, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':updated_by', $updatedBy, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function buildClosingChecklist(int $periodId): array
    {
        $period = $this->findById($periodId);
        if ($period === null) {
            throw new RuntimeException('Periode tidak ditemukan.');
        }

        $startDate = (string) $period['start_date'];
        $endDate = (string) $period['end_date'];
        $year = (int) substr($endDate, 0, 4);
        $month = (int) substr($endDate, 5, 2);

        $summary = [
            'journal_count' => $this->countJournals($periodId),
            'unbalanced_journal_count' => $this->countUnbalancedJournals($periodId),
            'journal_without_lines_count' => $this->countJournalsWithoutLines($periodId),
            'bank_reconciliation_problem_count' => $this->countBankReconciliationProblems($periodId),
            'asset_sync_pending_count' => $this->countPendingAssetSync($startDate, $endDate),
            'depreciation_pending_count' => $this->countPendingDepreciation($endDate, $year, $month),
            'latest_backup' => $this->latestBackupInfo(),
        ];

        $checks = [];
        $checks[] = $this->makeCheck(
            'period_status',
            'Periode aktif dan status masih buka',
            ((int) $period['is_active'] === 1 && (string) $period['status'] === 'OPEN') ? 'pass' : 'warning',
            ((int) $period['is_active'] === 1 && (string) $period['status'] === 'OPEN')
                ? 'Periode ini aktif dan bisa ditutup setelah pemeriksaan selesai.'
                : 'Periode ini tidak sedang aktif atau sudah tertutup. Checklist tetap bisa dipakai sebagai bahan review.'
        );

        $checks[] = $this->makeCheck(
            'journals_exist',
            'Transaksi jurnal pada periode sudah tersedia',
            $summary['journal_count'] > 0 ? 'pass' : 'warning',
            $summary['journal_count'] > 0
                ? 'Terdapat ' . number_format((int) $summary['journal_count'], 0, ',', '.') . ' jurnal pada periode ini.'
                : 'Belum ada jurnal dalam periode ini. Tutup buku tetap boleh, tetapi pastikan memang tidak ada transaksi yang belum dicatat.'
        );

        $checks[] = $this->makeCheck(
            'journals_balanced',
            'Semua jurnal sudah seimbang',
            $summary['unbalanced_journal_count'] === 0 ? 'pass' : 'danger',
            $summary['unbalanced_journal_count'] === 0
                ? 'Tidak ditemukan jurnal yang total debit dan kreditnya berbeda.'
                : 'Masih ada ' . number_format((int) $summary['unbalanced_journal_count'], 0, ',', '.') . ' jurnal yang total debit dan kreditnya belum seimbang.'
        );

        $checks[] = $this->makeCheck(
            'journal_lines_complete',
            'Setiap jurnal memiliki detail akun',
            $summary['journal_without_lines_count'] === 0 ? 'pass' : 'danger',
            $summary['journal_without_lines_count'] === 0
                ? 'Semua jurnal memiliki baris detail akun.'
                : 'Masih ada ' . number_format((int) $summary['journal_without_lines_count'], 0, ',', '.') . ' jurnal tanpa detail akun. Periksa modul jurnal sebelum tutup buku.'
        );

        $checks[] = $this->makeCheck(
            'bank_reconciliation',
            'Rekonsiliasi bank untuk periode ini sudah bersih',
            $summary['bank_reconciliation_problem_count'] === 0 ? 'pass' : 'warning',
            $summary['bank_reconciliation_problem_count'] === 0
                ? 'Tidak ditemukan sesi rekonsiliasi bank yang masih menyisakan masalah pada periode ini.'
                : 'Masih ada ' . number_format((int) $summary['bank_reconciliation_problem_count'], 0, ',', '.') . ' sesi rekonsiliasi bank dengan selisih atau baris belum cocok.'
        );

        $checks[] = $this->makeCheck(
            'asset_sync',
            'Sinkron jurnal aset sudah diposting',
            $summary['asset_sync_pending_count'] === 0 ? 'pass' : 'warning',
            $summary['asset_sync_pending_count'] === 0
                ? 'Tidak ada aset baru dalam periode ini yang masih menunggu sinkron jurnal.'
                : 'Masih ada ' . number_format((int) $summary['asset_sync_pending_count'], 0, ',', '.') . ' aset dengan status sinkron jurnal READY/NONE pada rentang periode ini.'
        );

        $checks[] = $this->makeCheck(
            'depreciation',
            'Penyusutan aset bulan akhir periode sudah diposting',
            $summary['depreciation_pending_count'] === 0 ? 'pass' : 'warning',
            $summary['depreciation_pending_count'] === 0
                ? 'Tidak ditemukan aset yang seharusnya disusutkan tetapi belum memiliki posting penyusutan untuk bulan akhir periode.'
                : 'Masih ada ' . number_format((int) $summary['depreciation_pending_count'], 0, ',', '.') . ' aset yang perlu dicek penyusutannya pada bulan akhir periode.'
        );

        $backup = $summary['latest_backup'];
        $checks[] = $this->makeCheck(
            'latest_backup',
            'Backup database terbaru tersedia',
            ($backup['exists'] ?? false) ? 'pass' : 'warning',
            ($backup['exists'] ?? false)
                ? 'Backup terbaru ditemukan: ' . (string) ($backup['name'] ?? '-') . ' (' . (string) ($backup['modified_label'] ?? '-') . ').'
                : 'Belum ditemukan file backup di storage/backups. Sebaiknya buat backup sebelum tutup buku.'
        );

        $criticalFailures = 0;
        $warningCount = 0;
        foreach ($checks as $check) {
            if ($check['status'] === 'danger') {
                $criticalFailures++;
            }
            if ($check['status'] === 'warning') {
                $warningCount++;
            }
        }

        return [
            'period' => $period,
            'summary' => $summary,
            'checks' => $checks,
            'is_ready_to_close' => $criticalFailures === 0,
            'critical_failures' => $criticalFailures,
            'warnings' => $warningCount,
        ];
    }

    private function countJournals(int $periodId): int
    {
        if (!$this->tableExists('journal_headers')) {
            return 0;
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_headers WHERE period_id = :period_id');
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countUnbalancedJournals(int $periodId): int
    {
        if (!$this->tableExists('journal_headers')) {
            return 0;
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_headers WHERE period_id = :period_id AND ABS(total_debit - total_credit) > 0.009');
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countJournalsWithoutLines(int $periodId): int
    {
        if (!$this->tableExists('journal_headers') || !$this->tableExists('journal_lines')) {
            return 0;
        }

        $sql = 'SELECT COUNT(*)
                FROM journal_headers jh
                LEFT JOIN journal_lines jl ON jl.journal_id = jh.id
                WHERE jh.period_id = :period_id
                GROUP BY jh.id
                HAVING COUNT(jl.id) = 0';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($rows);
    }

    private function countBankReconciliationProblems(int $periodId): int
    {
        if (!$this->tableExists('bank_reconciliations')) {
            return 0;
        }

        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM bank_reconciliations
             WHERE period_id = :period_id
               AND (
                   total_unmatched_rows > 0
                   OR ABS((opening_balance + total_statement_net) - closing_balance) > 0.009
               )'
        );
        $stmt->bindValue(':period_id', $periodId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countPendingAssetSync(string $startDate, string $endDate): int
    {
        if (!$this->tableExists('asset_items')) {
            return 0;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM asset_items
             WHERE acquisition_date BETWEEN :start_date AND :end_date
               AND acquisition_sync_status IN ('NONE', 'READY')
               AND is_active = 1"
        );
        $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countPendingDepreciation(string $endDate, int $year, int $month): int
    {
        if (!$this->tableExists('asset_items') || !$this->tableExists('asset_depreciations')) {
            return 0;
        }

        $sql = "SELECT COUNT(*)
                FROM asset_items ai
                WHERE ai.is_active = 1
                  AND ai.depreciation_allowed = 1
                  AND ai.asset_status IN ('ACTIVE','IDLE','MAINTENANCE')
                  AND ai.depreciation_start_date IS NOT NULL
                  AND ai.depreciation_start_date <= :end_date
                  AND NOT EXISTS (
                      SELECT 1
                      FROM asset_depreciations ad
                      WHERE ad.asset_id = ai.id
                        AND ad.period_year = :period_year
                        AND ad.period_month = :period_month
                        AND ad.status = 'POSTED'
                  )";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->bindValue(':period_year', $year, PDO::PARAM_INT);
        $stmt->bindValue(':period_month', $month, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function latestBackupInfo(): array
    {
        $dir = ROOT_PATH . '/storage/backups';
        if (!is_dir($dir)) {
            return [
                'exists' => false,
                'name' => '',
                'path' => '',
                'modified_at' => null,
                'modified_label' => '-',
                'size_bytes' => 0,
            ];
        }

        $latestPath = '';
        $latestTime = 0;
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }
            $mtime = (int) @filemtime($file);
            if ($mtime >= $latestTime) {
                $latestTime = $mtime;
                $latestPath = $file;
            }
        }

        if ($latestPath === '') {
            return [
                'exists' => false,
                'name' => '',
                'path' => '',
                'modified_at' => null,
                'modified_label' => '-',
                'size_bytes' => 0,
            ];
        }

        return [
            'exists' => true,
            'name' => basename($latestPath),
            'path' => $latestPath,
            'modified_at' => $latestTime > 0 ? date('Y-m-d H:i:s', $latestTime) : null,
            'modified_label' => $latestTime > 0 ? date('d/m/Y H:i', $latestTime) : '-',
            'size_bytes' => (int) @filesize($latestPath),
        ];
    }

    private function makeCheck(string $key, string $label, string $status, string $message): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => in_array($status, ['pass', 'warning', 'danger'], true) ? $status : 'warning',
            'message' => $message,
        ];
    }

    private function clearActiveFlag(?int $exceptId = null): void
    {
        $sql = 'UPDATE accounting_periods SET is_active = 0';
        if ($exceptId !== null) {
            $sql .= ' WHERE id <> :except_id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':except_id', $exceptId, PDO::PARAM_INT);
            $stmt->execute();
            return;
        }

        $this->db->exec($sql);
    }

    private function bindData(PDOStatement $stmt, array $data): void
    {
        $stmt->bindValue(':period_code', $data['period_code'], PDO::PARAM_STR);
        $stmt->bindValue(':period_name', $data['period_name'], PDO::PARAM_STR);
        $stmt->bindValue(':start_date', $data['start_date'], PDO::PARAM_STR);
        $stmt->bindValue(':end_date', $data['end_date'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $data['updated_by'], PDO::PARAM_INT);
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
}
