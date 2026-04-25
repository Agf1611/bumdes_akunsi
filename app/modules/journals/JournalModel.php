<?php

declare(strict_types=1);

final class JournalModel
{
    private ?bool $hasPrintTemplateColumn = null;
    private ?bool $hasJournalReceiptsTable = null;
    private ?bool $hasJournalAttachmentsTable = null;
    private ?bool $hasJournalReferenceColumns = null;
    private ?bool $hasWorkflowStatusColumn = null;

    public function __construct(private PDO $db)
    {
    }

    public function isReceiptFeatureEnabled(): bool
    {
        return $this->hasPrintTemplateColumn() && $this->hasJournalReceiptsTable();
    }

    public function getReceiptFeatureStatus(): array
    {
        return [
            'has_print_template_column' => $this->hasPrintTemplateColumn(),
            'has_journal_receipts_table' => $this->hasJournalReceiptsTable(),
            'enabled' => $this->isReceiptFeatureEnabled(),
        ];
    }

    public function getAttachmentFeatureStatus(): array
    {
        return [
            'has_journal_attachments_table' => $this->hasJournalAttachmentsTable(),
            'enabled' => $this->hasJournalAttachmentsTable(),
        ];
    }

    public function getList(array $filters = [], ?array $pagination = null): array
    {
        [$sql, $params] = $this->buildListSql($filters, false, $pagination);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countList(array $filters = []): int
    {
        [$sql, $params] = $this->buildListSql($filters, true, null);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function buildListSql(array $filters, bool $countOnly, ?array $pagination): array
    {
        $select = [
            'j.id',
            'j.journal_no',
            'j.journal_date',
            'j.description',
            'j.period_id',
            'j.business_unit_id',
            'j.total_debit',
            'j.total_credit',
            'j.created_at',
            $this->hasWorkflowStatusColumn() ? 'j.workflow_status' : "'POSTED' AS workflow_status",
            $this->hasPrintTemplateColumn() ? 'j.print_template' : "'standard' AS print_template",
            'p.period_code',
            'p.period_name',
            'p.status AS period_status',
            'bu.unit_code',
            'bu.unit_name',
            $this->hasJournalAttachmentsTable() ? '(SELECT COUNT(*) FROM journal_attachments ja WHERE ja.journal_id = j.id) AS attachment_count' : '0 AS attachment_count',
        ];

        $joins = [
            'INNER JOIN accounting_periods p ON p.id = j.period_id',
            'LEFT JOIN business_units bu ON bu.id = j.business_unit_id',
        ];

        if ($this->hasJournalReceiptsTable()) {
            $select[] = 'r.party_title';
            $select[] = 'r.party_name';
            $select[] = 'r.purpose';
            $select[] = 'r.amount_in_words';
            $select[] = 'r.payment_method';
            $select[] = 'r.reference_no';
            $select[] = 'r.notes';
            $joins[] = 'LEFT JOIN journal_receipts r ON r.journal_id = j.id';
        } else {
            $select[] = 'NULL AS party_title';
            $select[] = 'NULL AS party_name';
            $select[] = 'NULL AS purpose';
            $select[] = 'NULL AS amount_in_words';
            $select[] = 'NULL AS payment_method';
            $select[] = 'NULL AS reference_no';
            $select[] = 'NULL AS notes';
        }

        $sql = $countOnly
            ? "SELECT COUNT(*)\nFROM journal_headers j\n" . implode("\n", $joins) . "\nWHERE 1=1"
            : "SELECT " . implode(",\n                       ", $select) . "\nFROM journal_headers j\n" . implode("\n", $joins) . "\nWHERE 1=1";

        $params = [];

        if (!empty($filters['period_id'])) {
            $sql .= ' AND j.period_id = :period_id';
            $params[':period_id'] = (int) $filters['period_id'];
        }
        if (!empty($filters['unit_id'])) {
            $sql .= ' AND j.business_unit_id = :unit_id';
            $params[':unit_id'] = (int) $filters['unit_id'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND j.journal_date >= :date_from';
            $params[':date_from'] = (string) $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND j.journal_date <= :date_to';
            $params[':date_to'] = (string) $filters['date_to'];
        }

        if (!$countOnly) {
            $sql .= ' ORDER BY j.journal_date DESC, j.id DESC';
            if (is_array($pagination)) {
                $limit = max(1, (int) ($pagination['limit'] ?? 10));
                $offset = max(0, (int) ($pagination['offset'] ?? 0));
                $sql .= ' LIMIT :_limit OFFSET :_offset';
                $params[':_limit'] = $limit;
                $params[':_offset'] = $offset;
            }
        }

        return [$sql, $params];
    }

    public function getPrintList(array $filters = []): array
    {
        $journals = $this->getList($filters);
        if ($journals === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $journals);
        $detailsByJournal = $this->getDetailsByJournalIds($ids);

        foreach ($journals as &$journal) {
            $journal['details'] = $detailsByJournal[(int) $journal['id']] ?? [];
        }
        unset($journal);

        usort($journals, static function (array $a, array $b): int {
            $cmp = strcmp((string) $a['journal_date'], (string) $b['journal_date']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return ((int) $a['id']) <=> ((int) $b['id']);
        });

        return $journals;
    }

    public function findHeaderById(int $id): ?array
    {
        $select = [
            'j.*',
            'p.period_code',
            'p.period_name',
            'p.status AS period_status',
            'p.start_date',
            'p.end_date',
            'bu.unit_code',
            'bu.unit_name',
            $this->hasWorkflowStatusColumn() ? 'j.workflow_status' : "'POSTED' AS workflow_status",
            $this->hasJournalAttachmentsTable() ? '(SELECT COUNT(*) FROM journal_attachments ja WHERE ja.journal_id = j.id) AS attachment_count' : '0 AS attachment_count',
        ];

        $joins = [
            'INNER JOIN accounting_periods p ON p.id = j.period_id',
            'LEFT JOIN business_units bu ON bu.id = j.business_unit_id',
        ];

        if ($this->hasJournalReceiptsTable()) {
            $select[] = 'r.party_title';
            $select[] = 'r.party_name';
            $select[] = 'r.purpose';
            $select[] = 'r.amount_in_words';
            $select[] = 'r.payment_method';
            $select[] = 'r.reference_no';
            $select[] = 'r.notes';
            $joins[] = 'LEFT JOIN journal_receipts r ON r.journal_id = j.id';
        } else {
            $select[] = 'NULL AS party_title';
            $select[] = 'NULL AS party_name';
            $select[] = 'NULL AS purpose';
            $select[] = 'NULL AS amount_in_words';
            $select[] = 'NULL AS payment_method';
            $select[] = 'NULL AS reference_no';
            $select[] = 'NULL AS notes';
        }

        if (!$this->hasPrintTemplateColumn()) {
            $select[] = "'standard' AS print_template";
        }

        $sql = "SELECT " . implode(",\n                       ", $select) . "\n"
            . "FROM journal_headers j\n"
            . implode("\n", $joins)
            . "\nWHERE j.id = :id\nLIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findHeadersByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $select = [
            'j.*',
            'p.period_code',
            'p.period_name',
            'p.status AS period_status',
            'p.start_date',
            'p.end_date',
            'bu.unit_code',
            'bu.unit_name',
            $this->hasJournalAttachmentsTable() ? '(SELECT COUNT(*) FROM journal_attachments ja WHERE ja.journal_id = j.id) AS attachment_count' : '0 AS attachment_count',
        ];

        if ($this->hasJournalReceiptsTable()) {
            $select[] = 'r.party_title';
            $select[] = 'r.party_name';
            $select[] = 'r.purpose';
            $select[] = 'r.amount_in_words';
            $select[] = 'r.payment_method';
            $select[] = 'r.reference_no';
            $select[] = 'r.notes';
        } else {
            $select[] = 'NULL AS party_title';
            $select[] = 'NULL AS party_name';
            $select[] = 'NULL AS purpose';
            $select[] = 'NULL AS amount_in_words';
            $select[] = 'NULL AS payment_method';
            $select[] = 'NULL AS reference_no';
            $select[] = 'NULL AS notes';
        }

        if (!$this->hasPrintTemplateColumn()) {
            $select[] = "'standard' AS print_template";
        }

        $sql = 'SELECT ' . implode(', ', $select)
            . ' FROM journal_headers j'
            . ' INNER JOIN accounting_periods p ON p.id = j.period_id'
            . ' LEFT JOIN business_units bu ON bu.id = j.business_unit_id'
            . ($this->hasJournalReceiptsTable() ? ' LEFT JOIN journal_receipts r ON r.journal_id = j.id' : '')
            . ' WHERE j.id IN (' . $placeholders . ')';

        $stmt = $this->db->prepare($sql);
        foreach ($ids as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $rows[(int) ($row['id'] ?? 0)] = $row;
        }

        return $rows;
    }

    public function updateBusinessUnit(int $id, ?int $businessUnitId, int $updatedBy): void
    {
        $stmt = $this->db->prepare('UPDATE journal_headers
            SET business_unit_id = :business_unit_id, updated_by = :updated_by, updated_at = NOW()
            WHERE id = :id');
        if ($businessUnitId !== null && $businessUnitId > 0) {
            $stmt->bindValue(':business_unit_id', $businessUnitId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':business_unit_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':updated_by', $updatedBy > 0 ? $updatedBy : null, $updatedBy > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getDetailsByJournalId(int $journalId): array
    {
        $sql = $this->buildDetailSelectSql('l.journal_id = :journal_id');
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':journal_id', $journalId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getDetailsByJournalIds(array $journalIds): array
    {
        $journalIds = array_values(array_filter(array_map('intval', $journalIds), static fn (int $id): bool => $id > 0));
        if ($journalIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($journalIds), '?'));
        $sql = $this->buildDetailSelectSql("l.journal_id IN ($placeholders)", 'l.journal_id ASC, l.line_no ASC, l.id ASC');
        $stmt = $this->db->prepare($sql);
        foreach ($journalIds as $index => $journalId) {
            $stmt->bindValue($index + 1, $journalId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $grouped = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $grouped[(int) $row['journal_id']][] = $row;
        }

        return $grouped;
    }

    public function getAttachmentsByJournalId(int $journalId): array
    {
        if (!$this->hasJournalAttachmentsTable()) {
            return [];
        }

        $sql = 'SELECT a.*, u.full_name AS uploaded_by_name
                FROM journal_attachments a
                LEFT JOIN users u ON u.id = a.uploaded_by
                WHERE a.journal_id = :journal_id
                ORDER BY a.created_at ASC, a.id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':journal_id', $journalId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findAttachmentById(int $id): ?array
    {
        if (!$this->hasJournalAttachmentsTable()) {
            return null;
        }

        $sql = 'SELECT a.*,
                       j.journal_no,
                       j.journal_date,
                       j.description AS journal_description,
                       j.period_id,
                       p.period_name,
                       p.status AS period_status,
                       u.full_name AS uploaded_by_name
                FROM journal_attachments a
                INNER JOIN journal_headers j ON j.id = a.journal_id
                INNER JOIN accounting_periods p ON p.id = j.period_id
                LEFT JOIN users u ON u.id = a.uploaded_by
                WHERE a.id = :id
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createAttachment(array $data): int
    {
        if (!$this->hasJournalAttachmentsTable()) {
            throw new RuntimeException('Fitur lampiran jurnal belum aktif di database. Import patch_stage5_journal_attachments.sql terlebih dahulu.');
        }

        $sql = 'INSERT INTO journal_attachments (
                    journal_id, attachment_title, attachment_notes, original_name, stored_name, stored_file_path, mime_type, file_ext, file_size, uploaded_by, created_at, updated_at
                ) VALUES (
                    :journal_id, :attachment_title, :attachment_notes, :original_name, :stored_name, :stored_file_path, :mime_type, :file_ext, :file_size, :uploaded_by, NOW(), NOW()
                )';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':journal_id', (int) $data['journal_id'], PDO::PARAM_INT);
        $stmt->bindValue(':attachment_title', (string) ($data['attachment_title'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':attachment_notes', (string) ($data['attachment_notes'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':original_name', (string) ($data['original_name'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':stored_name', (string) ($data['stored_name'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':stored_file_path', (string) ($data['stored_file_path'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':mime_type', (string) ($data['mime_type'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':file_ext', (string) ($data['file_ext'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':file_size', (int) ($data['file_size'] ?? 0), PDO::PARAM_INT);
        if (!empty($data['uploaded_by'])) {
            $stmt->bindValue(':uploaded_by', (int) $data['uploaded_by'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':uploaded_by', null, PDO::PARAM_NULL);
        }
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    public function deleteAttachment(int $id): void
    {
        if (!$this->hasJournalAttachmentsTable()) {
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM journal_attachments WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getOpenPeriods(): array
    {
        $year = current_working_year();
        $sql = "SELECT id, period_code, period_name, start_date, end_date, status, is_active
                FROM accounting_periods
                WHERE status = 'OPEN'";
        if ($year > 0) {
            $sql .= ' AND YEAR(start_date) = :year';
        }
        $sql .= ' ORDER BY start_date DESC, id DESC';
        $stmt = $this->db->prepare($sql);
        if ($year > 0) {
            $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getFilterPeriods(): array
    {
        $year = current_working_year();
        $sql = "SELECT id, period_code, period_name, start_date, end_date, status, is_active
                FROM accounting_periods";
        if ($year > 0) {
            $sql .= ' WHERE YEAR(start_date) = :year';
        }
        $sql .= ' ORDER BY start_date DESC, id DESC';
        $stmt = $this->db->prepare($sql);
        if ($year > 0) {
            $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findPeriodById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM accounting_periods WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAccountOptions(?int $userId = null): array
    {
        $allowDirectPostingExists = $this->columnExists('coa_accounts', 'allow_direct_posting');
        $userIdSql = ($userId !== null && $userId > 0) ? (string) (int) $userId : '0';
        $userRecentCase = 'CASE WHEN j.created_by = ' . $userIdSql . ' AND j.journal_date >= DATE_SUB(CURDATE(), INTERVAL 120 DAY) THEN 1 ELSE 0 END';
        $userUsageCase = 'CASE WHEN j.created_by = ' . $userIdSql . ' THEN 1 ELSE 0 END';
        $userLastUsedExpr = 'MAX(CASE WHEN j.created_by = ' . $userIdSql . ' THEN j.journal_date ELSE NULL END)';

        $sql = 'SELECT a.id,
                       a.account_code,
                       a.account_name,
                       a.account_type,
                       COUNT(l.id) AS global_usage_count,
                       SUM(' . $userUsageCase . ') AS user_usage_count,
                       SUM(' . $userRecentCase . ') AS user_recent_usage_count,
                       ' . $userLastUsedExpr . ' AS user_last_used_at
                FROM coa_accounts a
                LEFT JOIN journal_lines l ON l.coa_id = a.id
                LEFT JOIN journal_headers j ON j.id = l.journal_id
                WHERE a.is_active = 1 AND ' . ($allowDirectPostingExists ? 'a.allow_direct_posting = 1' : 'a.is_header = 0') . '
                GROUP BY a.id, a.account_code, a.account_name, a.account_type
                ORDER BY
                    CASE WHEN SUM(' . $userRecentCase . ') > 0 THEN 0 ELSE 1 END ASC,
                    SUM(' . $userRecentCase . ') DESC,
                    SUM(' . $userUsageCase . ') DESC,
                    COUNT(l.id) DESC,
                    ' . $userLastUsedExpr . ' DESC,
                    a.account_code ASC, a.id ASC';

        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row['global_usage_count'] = (int) ($row['global_usage_count'] ?? 0);
            $row['user_usage_count'] = (int) ($row['user_usage_count'] ?? 0);
            $row['user_recent_usage_count'] = (int) ($row['user_recent_usage_count'] ?? 0);
            $row['is_suggested'] = ($row['user_recent_usage_count'] > 0 || $row['user_usage_count'] > 0 || $row['global_usage_count'] > 0) ? 1 : 0;
        }
        unset($row);

        return $rows;
    }

    public function getReferenceOptions(): array
    {
        return [
            'partners' => $this->fetchReferenceOptions('reference_partners', 'partner_code', 'partner_name'),
            'inventory' => $this->fetchReferenceOptions('inventory_items', 'item_code', 'item_name'),
            'raw_materials' => $this->fetchReferenceOptions('raw_materials', 'material_code', 'material_name'),
            'savings' => $this->fetchReferenceOptions('saving_accounts', 'account_no', 'account_name'),
            'cashflow_components' => $this->fetchReferenceOptions('cashflow_components', 'component_code', 'component_name'),
            'assets' => $this->fetchReferenceOptions('asset_items', 'asset_code', 'asset_name'),
            'entry_tags' => [
                '' => 'Tidak Spesifik',
                'OPERASIONAL' => 'Operasional',
                'SALDO_AWAL' => 'Saldo Awal',
                'PENYESUAIAN' => 'Penyesuaian',
                'PENUTUPAN' => 'Penutupan',
                'PEMBUKAAN' => 'Pembukaan',
            ],
        ];
    }

    public function findJournalAccountById(int $id): ?array
    {
        $sql = 'SELECT id, account_code, account_name, is_header, is_active
                FROM coa_accounts WHERE id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function previewJournalNumber(int $periodId): string
    {
        return $this->generateJournalNumber($periodId);
    }

    public function findPotentialDuplicate(array $header, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT id, journal_no, journal_date, description, total_debit, total_credit
                FROM journal_headers
                WHERE period_id = :period_id
                  AND journal_date = :journal_date
                  AND description = :description
                  AND total_debit = :total_debit
                  AND total_credit = :total_credit';

        $unitId = $header['business_unit_id'] ?? null;
        if ($unitId === null || $unitId === '' || (int) $unitId <= 0) {
            $sql .= ' AND business_unit_id IS NULL';
        } else {
            $sql .= ' AND business_unit_id = :business_unit_id';
        }

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
        }

        $sql .= ' ORDER BY id DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':period_id', (int) ($header['period_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':journal_date', (string) ($header['journal_date'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':description', (string) ($header['description'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':total_debit', (string) ($header['total_debit'] ?? '0'));
        $stmt->bindValue(':total_credit', (string) ($header['total_credit'] ?? '0'));
        if ($unitId !== null && $unitId !== '' && (int) $unitId > 0) {
            $stmt->bindValue(':business_unit_id', (int) $unitId, PDO::PARAM_INT);
        }
        if ($excludeId !== null && $excludeId > 0) {
            $stmt->bindValue(':exclude_id', (int) $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $header, array $lines, ?array $receipt = null): int
    {
        if ((string) ($header['print_template'] ?? 'standard') === 'receipt' && !$this->isReceiptFeatureEnabled()) {
            throw new RuntimeException('Fitur kwitansi belum aktif di database. Import patch_journal_print_receipt.sql terlebih dahulu.');
        }

        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $attempt = 0;
            begin_create:
            $attempt++;
            $journalNo = $this->generateJournalNumber((int) $header['period_id']);
            try {
                $headerId = $this->insertHeader($journalNo, $header);
            } catch (PDOException $e) {
                if ($attempt < 3 && $this->isDuplicateJournalNoException($e)) {
                    goto begin_create;
                }
                throw $e;
            }
            $this->insertLines($headerId, $lines);
            $this->saveReceipt($headerId, $header, $receipt);
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return $headerId;
        } catch (Throwable $e) {
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function update(int $id, array $header, array $lines, ?array $receipt = null): void
    {
        if ((string) ($header['print_template'] ?? 'standard') === 'receipt' && !$this->isReceiptFeatureEnabled()) {
            throw new RuntimeException('Fitur kwitansi belum aktif di database. Import patch_journal_print_receipt.sql terlebih dahulu.');
        }

        $existing = $this->findHeaderById($id);
        if (!$existing) {
            throw new RuntimeException('Jurnal tidak ditemukan.');
        }

        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $journalNo = (string) $existing['journal_no'];
            if ((int) $existing['period_id'] !== (int) $header['period_id']) {
                $attempt = 0;
                do {
                    $attempt++;
                    $journalNo = $this->generateJournalNumber((int) $header['period_id']);
                    try {
                        $this->performUpdate($id, $journalNo, $header);
                        break;
                    } catch (PDOException $e) {
                        if ($attempt >= 3 || !$this->isDuplicateJournalNoException($e)) {
                            throw $e;
                        }
                    }
                } while ($attempt < 3);
            } else {
                $this->performUpdate($id, $journalNo, $header);
            }

            $delete = $this->db->prepare('DELETE FROM journal_lines WHERE journal_id = :journal_id');
            $delete->bindValue(':journal_id', $id, PDO::PARAM_INT);
            $delete->execute();
            $this->insertLines($id, $lines);
            $this->saveReceipt($id, $header, $receipt);

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

    public function delete(int $id): void
    {
        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }
        try {
            if ($this->hasJournalReceiptsTable()) {
                $deleteReceipt = $this->db->prepare('DELETE FROM journal_receipts WHERE journal_id = :journal_id');
                $deleteReceipt->bindValue(':journal_id', $id, PDO::PARAM_INT);
                $deleteReceipt->execute();
            }

            $deleteLines = $this->db->prepare('DELETE FROM journal_lines WHERE journal_id = :journal_id');
            $deleteLines->bindValue(':journal_id', $id, PDO::PARAM_INT);
            $deleteLines->execute();

            $deleteHeader = $this->db->prepare('DELETE FROM journal_headers WHERE id = :id');
            $deleteHeader->bindValue(':id', $id, PDO::PARAM_INT);
            $deleteHeader->execute();

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

    private function performUpdate(int $id, string $journalNo, array $header): void
    {
        if ($this->hasPrintTemplateColumn()) {
            $sql = 'UPDATE journal_headers
                    SET journal_no = :journal_no,
                        journal_date = :journal_date,
                        description = :description,
                        period_id = :period_id,
                        business_unit_id = :business_unit_id,
                        print_template = :print_template,
                        total_debit = :total_debit,
                        total_credit = :total_credit,
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE id = :id';
        } else {
            $sql = 'UPDATE journal_headers
                    SET journal_no = :journal_no,
                        journal_date = :journal_date,
                        description = :description,
                        period_id = :period_id,
                        business_unit_id = :business_unit_id,
                        total_debit = :total_debit,
                        total_credit = :total_credit,
                        updated_by = :updated_by,
                        updated_at = NOW()
                    WHERE id = :id';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':journal_no', $journalNo, PDO::PARAM_STR);
        $stmt->bindValue(':journal_date', $header['journal_date'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $header['description'], PDO::PARAM_STR);
        $stmt->bindValue(':period_id', (int) $header['period_id'], PDO::PARAM_INT);
        if (!empty($header['business_unit_id'])) {
            $stmt->bindValue(':business_unit_id', (int) $header['business_unit_id'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':business_unit_id', null, PDO::PARAM_NULL);
        }
        if ($this->hasPrintTemplateColumn()) {
            $stmt->bindValue(':print_template', (string) ($header['print_template'] ?? 'standard'), PDO::PARAM_STR);
        }
        $stmt->bindValue(':total_debit', $header['total_debit']);
        $stmt->bindValue(':total_credit', $header['total_credit']);
        $stmt->bindValue(':updated_by', (int) $header['updated_by'], PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function insertHeader(string $journalNo, array $header): int
    {
        if ($this->hasPrintTemplateColumn()) {
            $sql = 'INSERT INTO journal_headers (
                        journal_no, journal_date, description, period_id, business_unit_id, print_template,
                        total_debit, total_credit, created_by, updated_by,' . ($this->hasWorkflowStatusColumn() ? ' workflow_status, posted_at, posted_by,' : '') . '
                        created_at, updated_at
                    ) VALUES (
                        :journal_no, :journal_date, :description, :period_id, :business_unit_id, :print_template,
                        :total_debit, :total_credit, :created_by, :updated_by,' . ($this->hasWorkflowStatusColumn() ? ' :workflow_status, NOW(), :posted_by,' : '') . '
                        NOW(), NOW()
                    )';
        } else {
            $sql = 'INSERT INTO journal_headers (
                        journal_no, journal_date, description, period_id, business_unit_id,
                        total_debit, total_credit, created_by, updated_by,' . ($this->hasWorkflowStatusColumn() ? ' workflow_status, posted_at, posted_by,' : '') . '
                        created_at, updated_at
                    ) VALUES (
                        :journal_no, :journal_date, :description, :period_id, :business_unit_id,
                        :total_debit, :total_credit, :created_by, :updated_by,' . ($this->hasWorkflowStatusColumn() ? ' :workflow_status, NOW(), :posted_by,' : '') . '
                        NOW(), NOW()
                    )';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':journal_no', $journalNo, PDO::PARAM_STR);
        $stmt->bindValue(':journal_date', $header['journal_date'], PDO::PARAM_STR);
        $stmt->bindValue(':description', $header['description'], PDO::PARAM_STR);
        $stmt->bindValue(':period_id', (int) $header['period_id'], PDO::PARAM_INT);
        if (!empty($header['business_unit_id'])) {
            $stmt->bindValue(':business_unit_id', (int) $header['business_unit_id'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':business_unit_id', null, PDO::PARAM_NULL);
        }
        if ($this->hasPrintTemplateColumn()) {
            $stmt->bindValue(':print_template', (string) ($header['print_template'] ?? 'standard'), PDO::PARAM_STR);
        }
        $stmt->bindValue(':total_debit', $header['total_debit']);
        $stmt->bindValue(':total_credit', $header['total_credit']);
        $stmt->bindValue(':created_by', (int) $header['created_by'], PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', (int) $header['updated_by'], PDO::PARAM_INT);
        if ($this->hasWorkflowStatusColumn()) {
            $stmt->bindValue(':workflow_status', 'POSTED', PDO::PARAM_STR);
            $stmt->bindValue(':posted_by', (int) $header['created_by'], PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    private function insertLines(int $journalId, array $lines): void
    {
        if ($this->hasJournalReferenceColumns()) {
            $sql = 'INSERT INTO journal_lines (
                        journal_id, line_no, coa_id, line_description, debit, credit, partner_id, inventory_item_id, raw_material_id, asset_id, saving_account_id, cashflow_component_id, entry_tag, created_at
                    ) VALUES (
                        :journal_id, :line_no, :coa_id, :line_description, :debit, :credit, :partner_id, :inventory_item_id, :raw_material_id, :asset_id, :saving_account_id, :cashflow_component_id, :entry_tag, NOW()
                    )';
        } else {
            $sql = 'INSERT INTO journal_lines (
                        journal_id, line_no, coa_id, line_description, debit, credit, created_at
                    ) VALUES (
                        :journal_id, :line_no, :coa_id, :line_description, :debit, :credit, NOW()
                    )';
        }
        $stmt = $this->db->prepare($sql);
        foreach ($lines as $index => $line) {
            $stmt->bindValue(':journal_id', $journalId, PDO::PARAM_INT);
            $stmt->bindValue(':line_no', $index + 1, PDO::PARAM_INT);
            $stmt->bindValue(':coa_id', (int) $line['coa_id'], PDO::PARAM_INT);
            $stmt->bindValue(':line_description', $line['line_description'], PDO::PARAM_STR);
            $stmt->bindValue(':debit', $line['debit']);
            $stmt->bindValue(':credit', $line['credit']);
            if ($this->hasJournalReferenceColumns()) {
                foreach (['partner_id','inventory_item_id','raw_material_id','asset_id','saving_account_id','cashflow_component_id'] as $column) {
                    if (!empty($line[$column])) {
                        $stmt->bindValue(':' . $column, (int) $line[$column], PDO::PARAM_INT);
                    } else {
                        $stmt->bindValue(':' . $column, null, PDO::PARAM_NULL);
                    }
                }
                $stmt->bindValue(':entry_tag', (string) ($line['entry_tag'] ?? ''), PDO::PARAM_STR);
            }
            $stmt->execute();
        }
    }

    private function saveReceipt(int $journalId, array $header, ?array $receipt): void
    {
        if (!$this->hasJournalReceiptsTable()) {
            return;
        }

        $delete = $this->db->prepare('DELETE FROM journal_receipts WHERE journal_id = :journal_id');
        $delete->bindValue(':journal_id', $journalId, PDO::PARAM_INT);
        $delete->execute();

        if ((string) ($header['print_template'] ?? 'standard') !== 'receipt' || $receipt === null) {
            return;
        }

        $sql = 'INSERT INTO journal_receipts (
                    journal_id, party_title, party_name, purpose, amount_in_words, payment_method, reference_no, notes, created_at, updated_at
                ) VALUES (
                    :journal_id, :party_title, :party_name, :purpose, :amount_in_words, :payment_method, :reference_no, :notes, NOW(), NOW()
                )';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':journal_id', $journalId, PDO::PARAM_INT);
        $stmt->bindValue(':party_title', (string) ($receipt['party_title'] ?? 'Dibayar kepada'), PDO::PARAM_STR);
        $stmt->bindValue(':party_name', (string) ($receipt['party_name'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':purpose', (string) ($receipt['purpose'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':amount_in_words', (string) ($receipt['amount_in_words'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':payment_method', (string) ($receipt['payment_method'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':reference_no', (string) ($receipt['reference_no'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':notes', (string) ($receipt['notes'] ?? ''), PDO::PARAM_STR);
        $stmt->execute();
    }

    private function generateJournalNumber(int $periodId): string
    {
        $period = $this->findPeriodById($periodId);
        if (!$period) {
            throw new RuntimeException('Periode jurnal tidak ditemukan saat membuat nomor jurnal.');
        }

        $prefix = 'JU/' . strtoupper((string) $period['period_code']) . '/';
        $sql = 'SELECT COALESCE(MAX(CAST(RIGHT(journal_no, 4) AS UNSIGNED)), 0)
                FROM journal_headers
                WHERE journal_no LIKE :prefix';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':prefix', $prefix . '%', PDO::PARAM_STR);
        $stmt->execute();
        $lastSequence = (int) ($stmt->fetchColumn() ?: 0);
        $next = $lastSequence + 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function isDuplicateJournalNoException(PDOException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'Duplicate') || str_contains($message, 'duplicate');
    }

    private function hasPrintTemplateColumn(): bool
    {
        if ($this->hasPrintTemplateColumn !== null) {
            return $this->hasPrintTemplateColumn;
        }

        $this->hasPrintTemplateColumn = $this->columnExists('journal_headers', 'print_template');
        return $this->hasPrintTemplateColumn;
    }

    private function hasJournalReceiptsTable(): bool
    {
        if ($this->hasJournalReceiptsTable !== null) {
            return $this->hasJournalReceiptsTable;
        }

        $this->hasJournalReceiptsTable = $this->tableExists('journal_receipts');
        return $this->hasJournalReceiptsTable;
    }

    private function hasJournalAttachmentsTable(): bool
    {
        if ($this->hasJournalAttachmentsTable !== null) {
            return $this->hasJournalAttachmentsTable;
        }

        $this->hasJournalAttachmentsTable = $this->tableExists('journal_attachments');
        return $this->hasJournalAttachmentsTable;
    }

    private function hasWorkflowStatusColumn(): bool
    {
        if ($this->hasWorkflowStatusColumn !== null) {
            return $this->hasWorkflowStatusColumn;
        }

        $this->hasWorkflowStatusColumn = $this->columnExists('journal_headers', 'workflow_status');
        return $this->hasWorkflowStatusColumn;
    }


    private function buildDetailSelectSql(string $whereClause, string $orderBy = 'l.line_no ASC, l.id ASC'): string
    {
        $select = [
            'l.id', 'l.journal_id', 'l.line_no', 'l.coa_id', 'l.line_description', 'l.debit', 'l.credit',
            'a.account_code', 'a.account_name'
        ];
        $joins = ['INNER JOIN coa_accounts a ON a.id = l.coa_id'];
        if ($this->hasJournalReferenceColumns()) {
            $select = array_merge($select, [
                'l.partner_id', 'l.inventory_item_id', 'l.raw_material_id', 'l.asset_id', 'l.saving_account_id', 'l.cashflow_component_id', 'l.entry_tag',
                'rp.partner_code', 'rp.partner_name',
                'ii.item_code', 'ii.item_name',
                'rm.material_code', 'rm.material_name',
                'sa.account_no AS saving_account_no', 'sa.account_name AS saving_account_name',
                'cfc.component_code', 'cfc.component_name',
                $this->tableExists('asset_items') ? 'ai.asset_code, ai.asset_name' : 'NULL AS asset_code, NULL AS asset_name'
            ]);
            if ($this->tableExists('reference_partners')) {
                $joins[] = 'LEFT JOIN reference_partners rp ON rp.id = l.partner_id';
            } else {
                $select[] = 'NULL AS partner_code'; $select[] = 'NULL AS partner_name';
            }
            if ($this->tableExists('inventory_items')) {
                $joins[] = 'LEFT JOIN inventory_items ii ON ii.id = l.inventory_item_id';
            } else {
                $select[] = 'NULL AS item_code'; $select[] = 'NULL AS item_name';
            }
            if ($this->tableExists('raw_materials')) {
                $joins[] = 'LEFT JOIN raw_materials rm ON rm.id = l.raw_material_id';
            } else {
                $select[] = 'NULL AS material_code'; $select[] = 'NULL AS material_name';
            }
            if ($this->tableExists('saving_accounts')) {
                $joins[] = 'LEFT JOIN saving_accounts sa ON sa.id = l.saving_account_id';
            } else {
                $select[] = 'NULL AS saving_account_no'; $select[] = 'NULL AS saving_account_name';
            }
            if ($this->tableExists('cashflow_components')) {
                $joins[] = 'LEFT JOIN cashflow_components cfc ON cfc.id = l.cashflow_component_id';
            } else {
                $select[] = 'NULL AS component_code'; $select[] = 'NULL AS component_name';
            }
            if ($this->tableExists('asset_items')) {
                $joins[] = 'LEFT JOIN asset_items ai ON ai.id = l.asset_id';
            }
        }
        return 'SELECT ' . implode(', ', $select) . ' FROM journal_lines l ' . implode(' ', $joins) . ' WHERE ' . $whereClause . ' ORDER BY ' . $orderBy;
    }

    private function fetchReferenceOptions(string $table, string $codeField, string $nameField): array
    {
        if (!$this->tableExists($table)) {
            return [];
        }
        $sql = 'SELECT id, ' . $codeField . ' AS code, ' . $nameField . ' AS name FROM ' . $table . ' WHERE is_active = 1 ORDER BY ' . $codeField . ' ASC, id ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function hasJournalReferenceColumns(): bool
    {
        if ($this->hasJournalReferenceColumns !== null) {
            return $this->hasJournalReferenceColumns;
        }
        $this->hasJournalReferenceColumns = $this->columnExists('journal_lines', 'partner_id')
            && $this->columnExists('journal_lines', 'inventory_item_id')
            && $this->columnExists('journal_lines', 'raw_material_id')
            && $this->columnExists('journal_lines', 'saving_account_id')
            && $this->columnExists('journal_lines', 'cashflow_component_id')
            && $this->columnExists('journal_lines', 'entry_tag');
        return $this->hasJournalReferenceColumns;
    }

    private function tableExists(string $table): bool
    {
        try {
            $sql = 'SELECT 1
                    FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = :table_name
                    LIMIT 1';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':table_name', $table, PDO::PARAM_STR);
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $sql = 'SELECT 1
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = :table_name
                      AND COLUMN_NAME = :column_name
                    LIMIT 1';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':table_name', $table, PDO::PARAM_STR);
            $stmt->bindValue(':column_name', $column, PDO::PARAM_STR);
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }
}
