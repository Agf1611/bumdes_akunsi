<?php

declare(strict_types=1);

final class AssetModel
{
    private ?array $assetItemsColumnCache = null;

    public function __construct(private PDO $db)
    {
    }

    private function assetItemsHasColumn(string $column): bool
    {
        if ($this->assetItemsColumnCache === null) {
            $this->assetItemsColumnCache = [];
            try {
                $stmt = $this->db->query('SHOW COLUMNS FROM asset_items');
                $cols = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
                foreach ($cols as $col) {
                    $name = (string) ($col['Field'] ?? '');
                    if ($name !== '') {
                        $this->assetItemsColumnCache[$name] = true;
                    }
                }
            } catch (Throwable) {
                $this->assetItemsColumnCache = [];
            }
        }

        return isset($this->assetItemsColumnCache[$column]);
    }

    private function assetItemsHasQtySupport(): bool
    {
        return $this->assetItemsHasColumn('quantity') && $this->assetItemsHasColumn('unit_name');
    }

    private function assetQuantitySelectSql(string $assetAlias = 'a'): string
    {
        if ($this->assetItemsHasQtySupport()) {
            return "COALESCE({$assetAlias}.quantity, 1) AS quantity, NULLIF({$assetAlias}.unit_name, '') AS unit_name, CASE WHEN COALESCE({$assetAlias}.quantity, 0) > 0 THEN ROUND({$assetAlias}.acquisition_cost / {$assetAlias}.quantity, 2) ELSE {$assetAlias}.acquisition_cost END AS unit_cost,";
        }

        return "1 AS quantity, 'unit' AS unit_name, {$assetAlias}.acquisition_cost AS unit_cost,";
    }

    private function normalizeAssetPresentationRow(array $row): array
    {
        if (!isset($row['business_unit_code']) && isset($row['unit_code'])) {
            $row['business_unit_code'] = $row['unit_code'];
        }
        if (!isset($row['business_unit_name']) && isset($row['unit_name']) && !$this->assetItemsHasQtySupport()) {
            $row['business_unit_name'] = $row['unit_name'];
        }

        $qty = isset($row['quantity']) ? (float) $row['quantity'] : 1.0;
        if ($qty <= 0) {
            $qty = 1.0;
        }
        $row['quantity'] = $qty;

        $unitName = trim((string) ($row['unit_name'] ?? ''));
        if ($unitName === '') {
            $unitName = 'unit';
        }
        $row['unit_name'] = $unitName;

        if (!isset($row['unit_cost']) || $row['unit_cost'] === null || $row['unit_cost'] === '') {
            $row['unit_cost'] = $qty > 0 ? round((float) ($row['acquisition_cost'] ?? 0) / $qty, 2) : (float) ($row['acquisition_cost'] ?? 0);
        }

        $row['acquisition_sync_status'] = $this->resolveAcquisitionSyncStatus($row);

        return $row;
    }

    private function resolveAcquisitionSyncStatus(array $data): string
    {
        $linkedJournalId = (int) ($data['linked_journal_id'] ?? 0);
        if ($linkedJournalId > 0) {
            return 'POSTED';
        }

        $entryMode = strtoupper(trim((string) ($data['entry_mode'] ?? 'ACQUISITION')));
        if ($entryMode === 'ACQUISITION') {
            return 'READY';
        }

        return 'NONE';
    }

    public function getCategories(bool $includeInactive = true, string $group = ''): array
    {
        $sql = 'SELECT c.*, 
                       (SELECT COUNT(*) FROM asset_items a WHERE a.category_id = c.id) AS asset_count
                FROM asset_categories c
                WHERE 1=1';
        $params = [];
        if (!$includeInactive) {
            $sql .= ' AND c.is_active = 1';
        }
        if ($group !== '') {
            $sql .= ' AND c.asset_group = :asset_group';
            $params[':asset_group'] = $group;
        }
        $sql .= ' ORDER BY c.asset_group ASC, c.category_name ASC';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findCategoryById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM asset_categories WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findCategoryByCode(string $code, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT * FROM asset_categories WHERE category_code = :category_code';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category_code', $code, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }


    public function findBusinessUnitByCode(string $code): ?array
    {
        $stmt = $this->db->prepare('SELECT id, unit_code, unit_name, is_active FROM business_units WHERE unit_code = :unit_code LIMIT 1');
        $stmt->bindValue(':unit_code', strtoupper(trim($code)), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function syncJournalAcquisitionAssets(array $header, array $details, int $userId): array
    {
        $journalId = (int) ($header['id'] ?? 0);
        if ($journalId <= 0) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'warnings' => ['ID jurnal tidak ditemukan untuk sinkron aset.']];
        }

        $summary = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'warnings' => []];
        $candidates = $this->buildJournalAssetCandidates($header, $details);
        if ($candidates === []) {
            return $summary;
        }

        foreach ($candidates as $candidate) {
            try {
                $existing = $this->findAssetByCode((string) $candidate['asset_code']);
                if ($existing) {
                    if (!$this->canRefreshAutoSyncedAsset((int) $existing['id'])) {
                        $summary['skipped']++;
                        $summary['warnings'][] = 'Aset auto-sync ' . (string) ($existing['asset_code'] ?? '') . ' tidak diperbarui karena sudah memiliki mutasi/penyusutan lanjutan.';
                        continue;
                    }
                    $current = $this->findAssetById((int) $existing['id']);
                    if (!$current) {
                        $summary['skipped']++;
                        continue;
                    }
                    $payload = $this->mergeAssetPayloadWithCurrent($candidate, $current);
                    $this->updateAsset((int) $existing['id'], $payload, $userId);
                    $summary['updated']++;
                    continue;
                }

                $this->createAsset($candidate, $userId);
                $summary['created']++;
            } catch (Throwable $e) {
                $summary['skipped']++;
                $summary['warnings'][] = $e->getMessage();
            }
        }

        return $summary;
    }

    public function purgeAutoSyncedAssetsByJournalId(int $journalId, int $userId): array
    {
        $assets = $this->getAutoSyncedAssetsByJournalId($journalId);
        $summary = ['deleted' => 0, 'blocked' => 0, 'warnings' => []];
        foreach ($assets as $asset) {
            try {
                $this->deleteAutoSyncedAsset((int) $asset['id'], $userId);
                $summary['deleted']++;
            } catch (Throwable $e) {
                $summary['blocked']++;
                $summary['warnings'][] = $e->getMessage();
            }
        }
        return $summary;
    }

    public function getAutoSyncedAssetsByJournalId(int $journalId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM asset_items WHERE linked_journal_id = :journal_id AND notes LIKE :marker ORDER BY id ASC");
        $stmt->bindValue(':journal_id', $journalId, PDO::PARAM_INT);
        $stmt->bindValue(':marker', '%[AUTO-JOURNAL:%', PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function buildJournalAssetCandidates(array $header, array $details): array
    {
        $journalId = (int) ($header['id'] ?? 0);
        $journalNo = (string) ($header['journal_no'] ?? ('JRN-' . $journalId));
        $journalDate = (string) ($header['journal_date'] ?? date('Y-m-d'));
        $businessUnitId = !empty($header['business_unit_id']) ? (int) $header['business_unit_id'] : null;
        $creditCoaId = $this->detectCounterpartCoaId($details);
        $candidates = [];

        foreach ($details as $index => $detail) {
            $lineNo = (int) ($detail['line_no'] ?? ($index + 1));
            $debit = (float) ($detail['debit'] ?? 0);
            if ($debit <= 0) {
                continue;
            }
            if ((int) ($detail['asset_id'] ?? 0) > 0) {
                continue;
            }
            if (!$this->isJournalDetailEligibleForAutoAsset($detail)) {
                continue;
            }

            $category = $this->resolveCategoryForJournalDetail($detail);
            if (!$category) {
                continue;
            }

            [$qty, $unitName] = $this->extractJournalQuantityAndUnit($detail, $header);
            $assetName = $this->buildAssetNameFromJournalDetail($detail, $header);
            $marker = $this->buildAutoJournalMarker($journalId, $lineNo);
            $assetCode = $this->buildAutoJournalAssetCode($journalId, $lineNo);
            $depreciationAllowed = (int) ($category['depreciation_allowed'] ?? 1) === 1;
            $usefulLife = !empty($category['default_useful_life_months']) ? (int) $category['default_useful_life_months'] : 36;
            $depreciationMethod = (string) ($category['default_depreciation_method'] ?? 'STRAIGHT_LINE');
            $notes = trim($marker . ' Dibuat otomatis dari jurnal ' . $journalNo . ' baris #' . $lineNo . '.');
            $description = trim('Auto-sync dari jurnal: ' . ((string) ($detail['line_description'] ?? '') !== '' ? (string) $detail['line_description'] : (string) ($header['description'] ?? '')));

            $candidates[] = [
                'asset_code' => $assetCode,
                'asset_name' => $assetName,
                'category_id' => (int) $category['id'],
                'subcategory_name' => '',
                'business_unit_id' => $businessUnitId,
                'quantity' => $qty,
                'unit_name' => $unitName,
                'entry_mode' => 'ACQUISITION',
                'acquisition_date' => $journalDate,
                'acquisition_cost' => $debit,
                'opening_as_of_date' => null,
                'opening_accumulated_depreciation' => 0,
                'residual_value' => 0,
                'useful_life_months' => $depreciationAllowed ? $usefulLife : null,
                'depreciation_method' => $depreciationMethod,
                'depreciation_start_date' => $depreciationAllowed ? $journalDate : null,
                'depreciation_allowed' => $depreciationAllowed,
                'offset_coa_id' => $creditCoaId,
                'location' => '',
                'supplier_name' => '',
                'source_of_funds' => 'HASIL_USAHA',
                'funding_source_detail' => '',
                'reference_no' => $journalNo,
                'linked_journal_id' => $journalId,
                'condition_status' => 'GOOD',
                'asset_status' => 'ACTIVE',
                'acquisition_sync_status' => 'POSTED',
                'is_active' => 1,
                'description' => $description,
                'notes' => $notes,
            ];
        }

        return $candidates;
    }

    private function isJournalDetailEligibleForAutoAsset(array $detail): bool
    {
        $entryTag = strtoupper(trim((string) ($detail['entry_tag'] ?? '')));
        if (in_array($entryTag, ['SALDO_AWAL', 'PEMBUKAAN', 'PENUTUPAN'], true)) {
            return false;
        }

        $coaId = (int) ($detail['coa_id'] ?? 0);
        if ($coaId > 0 && $this->findActiveCategoryByAssetCoaId($coaId)) {
            return true;
        }

        $text = strtolower(trim(((string) ($detail['account_code'] ?? '')) . ' ' . ((string) ($detail['account_name'] ?? '')) . ' ' . ((string) ($detail['line_description'] ?? ''))));
        if ($text === '') {
            return false;
        }

        $keywords = ['aset', 'peralatan', 'mesin', 'inventaris', 'modem', 'router', 'mikrotik', 'switch', 'starlink', 'ups', 'kabel', 'patchcord', 'splitter', 'odp', 'olt', 'instalasi'];
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return preg_match('/^1\.(2|3)\b/', (string) ($detail['account_code'] ?? '')) === 1;
    }

    private function resolveCategoryForJournalDetail(array $detail): ?array
    {
        $coaId = (int) ($detail['coa_id'] ?? 0);
        $byCoa = $coaId > 0 ? $this->findActiveCategoryByAssetCoaId($coaId) : null;
        if ($byCoa) {
            return $byCoa;
        }

        $text = strtolower(trim(((string) ($detail['account_name'] ?? '')) . ' ' . ((string) ($detail['line_description'] ?? ''))));
        $fallbackCode = 'EQUIPMENT';
        if (preg_match('/modem|router|mikrotik|switch|starlink|ups|kabel|fo|wifi|odp|olt|patchcord|splitter|instalasi/', $text)) {
            $fallbackCode = 'NETWORK';
        } elseif (preg_match('/mesin|genset|pompa/', $text)) {
            $fallbackCode = 'MACHINE';
        } elseif (preg_match('/inventaris|meja|kursi|lemari/', $text)) {
            $fallbackCode = 'INVENTORY';
        } elseif (preg_match('/laptop|printer|komputer|pc|server/', $text)) {
            $fallbackCode = 'IT';
        }

        return $this->findCategoryByCode($fallbackCode) ?: $this->findCategoryByCode('OTHER');
    }

    public function findActiveCategoryByAssetCoaId(int $coaId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM asset_categories WHERE is_active = 1 AND asset_coa_id = :asset_coa_id ORDER BY id ASC LIMIT 1');
        $stmt->bindValue(':asset_coa_id', $coaId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function detectCounterpartCoaId(array $details): ?int
    {
        $bestCoaId = null;
        $bestAmount = 0.0;
        foreach ($details as $detail) {
            $credit = (float) ($detail['credit'] ?? 0);
            if ($credit > $bestAmount && (int) ($detail['coa_id'] ?? 0) > 0) {
                $bestAmount = $credit;
                $bestCoaId = (int) $detail['coa_id'];
            }
        }
        return $bestCoaId;
    }

    private function extractJournalQuantityAndUnit(array $detail, array $header): array
    {
        $texts = [
            (string) ($detail['line_description'] ?? ''),
            (string) ($header['description'] ?? ''),
            (string) ($detail['account_name'] ?? ''),
        ];
        foreach ($texts as $text) {
            if (preg_match('/(\d+)\s*(unit|units|pcs|pc|buah|bh|roll|set|meter|m|km)/i', $text, $m)) {
                $qty = max(1, (int) $m[1]);
                $unit = strtolower($m[2]);
                $unit = match ($unit) {
                    'units' => 'unit',
                    'pc' => 'pcs',
                    'bh' => 'buah',
                    default => $unit,
                };
                return [$qty, $unit];
            }
            if (preg_match('/\((\d+)\s*(unit|units|pcs|pc|buah|bh|roll|set)\)/i', $text, $m)) {
                $qty = max(1, (int) $m[1]);
                $unit = strtolower($m[2]);
                $unit = $unit === 'pc' ? 'pcs' : ($unit === 'units' ? 'unit' : $unit);
                return [$qty, $unit];
            }
        }
        return [1, 'unit'];
    }

    private function buildAssetNameFromJournalDetail(array $detail, array $header): string
    {
        $name = trim((string) ($detail['line_description'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($header['description'] ?? ''));
        }
        if ($name === '') {
            $name = trim((string) ($detail['account_name'] ?? 'Aset dari Jurnal'));
        }
        $name = preg_replace('/^pembelian\s+/i', '', $name);
        $name = preg_replace('/^perolehan\s+/i', '', $name);
        return trim((string) $name) !== '' ? trim((string) $name) : 'Aset dari Jurnal';
    }

    private function buildAutoJournalMarker(int $journalId, int $lineNo): string
    {
        return '[AUTO-JOURNAL:' . $journalId . ':' . $lineNo . ']';
    }

    private function buildAutoJournalAssetCode(int $journalId, int $lineNo): string
    {
        return 'AJR-' . $journalId . '-' . str_pad((string) $lineNo, 2, '0', STR_PAD_LEFT);
    }

    private function mergeAssetPayloadWithCurrent(array $candidate, array $current): array
    {
        $payload = $candidate;
        $payload['location'] = (string) ($current['location'] ?? '');
        $payload['supplier_name'] = (string) ($current['supplier_name'] ?? '');
        $payload['funding_source_detail'] = (string) ($current['funding_source_detail'] ?? '');
        $payload['description'] = (string) ($candidate['description'] ?? $current['description'] ?? '');
        $payload['notes'] = (string) ($candidate['notes'] ?? $current['notes'] ?? '');
        $payload['condition_status'] = (string) ($current['condition_status'] ?? 'GOOD');
        $payload['asset_status'] = (string) ($current['asset_status'] ?? 'ACTIVE');
        $payload['is_active'] = (int) ($current['is_active'] ?? 1);
        $payload['opening_as_of_date'] = $current['opening_as_of_date'] ?? null;
        $payload['opening_accumulated_depreciation'] = (float) ($current['opening_accumulated_depreciation'] ?? 0);
        return $payload;
    }

    private function canRefreshAutoSyncedAsset(int $assetId): bool
    {
        $depStmt = $this->db->prepare("SELECT COUNT(*) FROM asset_depreciations WHERE asset_id = :asset_id AND status = 'POSTED'");
        $depStmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $depStmt->execute();
        if ((int) $depStmt->fetchColumn() > 0) {
            return false;
        }

        $mutStmt = $this->db->prepare('SELECT COUNT(*) FROM asset_mutations WHERE asset_id = :asset_id');
        $mutStmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $mutStmt->execute();
        return (int) $mutStmt->fetchColumn() <= 1;
    }

    private function deleteAutoSyncedAsset(int $assetId, int $userId): void
    {
        $asset = $this->findAssetById($assetId);
        if (!$asset) {
            return;
        }
        if (!$this->canRefreshAutoSyncedAsset($assetId)) {
            throw new RuntimeException('Aset auto-sync ' . (string) ($asset['asset_code'] ?? '') . ' tidak bisa dihapus otomatis karena sudah memiliki penyusutan atau mutasi lanjutan.');
        }

        $this->db->beginTransaction();
        try {
            foreach (['asset_year_snapshots', 'asset_accounting_events', 'asset_depreciations', 'asset_mutations'] as $table) {
                $stmt = $this->db->prepare('DELETE FROM ' . $table . ' WHERE asset_id = :asset_id');
                $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
                $stmt->execute();
            }
            $del = $this->db->prepare('DELETE FROM asset_items WHERE id = :id LIMIT 1');
            $del->bindValue(':id', $assetId, PDO::PARAM_INT);
            $del->execute();
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function createCategory(array $data, int $userId): int
    {
        $sql = 'INSERT INTO asset_categories (
                    category_code, category_name, asset_group, default_useful_life_months,
                    default_depreciation_method, depreciation_allowed, asset_coa_id,
                    accumulated_depreciation_coa_id, depreciation_expense_coa_id,
                    disposal_gain_coa_id, disposal_loss_coa_id,
                    description, is_active, created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :category_code, :category_name, :asset_group, :default_useful_life_months,
                    :default_depreciation_method, :depreciation_allowed, :asset_coa_id,
                    :accumulated_depreciation_coa_id, :depreciation_expense_coa_id,
                    :disposal_gain_coa_id, :disposal_loss_coa_id,
                    :description, :is_active, :created_by, :updated_by, NOW(), NOW()
                )';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category_code', $data['category_code'], PDO::PARAM_STR);
        $stmt->bindValue(':category_name', $data['category_name'], PDO::PARAM_STR);
        $stmt->bindValue(':asset_group', $data['asset_group'], PDO::PARAM_STR);
        if ($data['default_useful_life_months'] !== null) {
            $stmt->bindValue(':default_useful_life_months', (int) $data['default_useful_life_months'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':default_useful_life_months', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':default_depreciation_method', $data['default_depreciation_method'], PDO::PARAM_STR);
        $stmt->bindValue(':depreciation_allowed', $data['depreciation_allowed'] ? 1 : 0, PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':asset_coa_id', $data['asset_coa_id'] ?? null);
        $this->bindNullableInt($stmt, ':accumulated_depreciation_coa_id', $data['accumulated_depreciation_coa_id'] ?? null);
        $this->bindNullableInt($stmt, ':depreciation_expense_coa_id', $data['depreciation_expense_coa_id'] ?? null);
        $this->bindNullableInt($stmt, ':disposal_gain_coa_id', $data['disposal_gain_coa_id'] ?? null);
        $this->bindNullableInt($stmt, ':disposal_loss_coa_id', $data['disposal_loss_coa_id'] ?? null);
        $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':created_by', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    public function updateCategory(int $id, array $data, int $userId): void
    {
        $sql = 'UPDATE asset_categories SET
                    category_code = :category_code,
                    category_name = :category_name,
                    asset_group = :asset_group,
                    default_useful_life_months = :default_useful_life_months,
                    default_depreciation_method = :default_depreciation_method,
                    depreciation_allowed = :depreciation_allowed,
                    asset_coa_id = :asset_coa_id,
                    accumulated_depreciation_coa_id = :accumulated_depreciation_coa_id,
                    depreciation_expense_coa_id = :depreciation_expense_coa_id,
                    disposal_gain_coa_id = :disposal_gain_coa_id,
                    disposal_loss_coa_id = :disposal_loss_coa_id,
                    description = :description,
                    is_active = :is_active,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':category_code', $data['category_code'], PDO::PARAM_STR);
        $stmt->bindValue(':category_name', $data['category_name'], PDO::PARAM_STR);
        $stmt->bindValue(':asset_group', $data['asset_group'], PDO::PARAM_STR);
        if ($data['default_useful_life_months'] !== null) {
            $stmt->bindValue(':default_useful_life_months', (int) $data['default_useful_life_months'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':default_useful_life_months', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':default_depreciation_method', $data['default_depreciation_method'], PDO::PARAM_STR);
        $stmt->bindValue(':depreciation_allowed', $data['depreciation_allowed'] ? 1 : 0, PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':asset_coa_id', $data['asset_coa_id'] ?? null);
        $this->bindNullableInt($stmt, ':accumulated_depreciation_coa_id', $data['accumulated_depreciation_coa_id'] ?? null);
        $this->bindNullableInt($stmt, ':depreciation_expense_coa_id', $data['depreciation_expense_coa_id'] ?? null);
        $this->bindNullableInt($stmt, ':disposal_gain_coa_id', $data['disposal_gain_coa_id'] ?? null);
        $this->bindNullableInt($stmt, ':disposal_loss_coa_id', $data['disposal_loss_coa_id'] ?? null);
        $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function setCategoryActive(int $id, bool $active, int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE asset_categories SET is_active = :is_active, updated_by = :updated_by, updated_at = NOW() WHERE id = :id');
        $stmt->bindValue(':is_active', $active ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function categoryHasAssets(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM asset_items WHERE category_id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    public function journalExists(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM journal_headers WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getJournalOptions(int $limit = 100): array
    {
        $limit = max(10, min(200, $limit));
        $sql = 'SELECT j.id, j.journal_no, j.journal_date, j.description, bu.unit_code, bu.unit_name
                FROM journal_headers j
                LEFT JOIN business_units bu ON bu.id = j.business_unit_id
                ORDER BY j.journal_date DESC, j.id DESC
                LIMIT ' . $limit;
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getCoaOptions(): array
    {
        $sql = 'SELECT id, account_code, account_name, account_type
                FROM coa_accounts
                WHERE is_active = 1 AND is_header = 0
                ORDER BY account_code ASC';
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findCoaById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, account_code, account_name, is_header, is_active FROM coa_accounts WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findCoaByCode(string $code): ?array
    {
        $stmt = $this->db->prepare('SELECT id, account_code, account_name, is_header, is_active FROM coa_accounts WHERE account_code = :code LIMIT 1');
        $stmt->bindValue(':code', trim($code), PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPeriodByDate(string $date): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM accounting_periods WHERE start_date <= :date AND end_date >= :date ORDER BY id DESC LIMIT 1');
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAssets(array $filters = []): array
    {
        $asOfDate = trim((string) ($filters['as_of_date'] ?? ''));
        if ($asOfDate === '') {
            $asOfDate = date('Y-m-d');
        }

        $quantitySelect = $this->assetQuantitySelectSql('a');
        $sql = "SELECT a.*, 
                       c.category_code, c.category_name, c.asset_group,
                       bu.unit_code AS business_unit_code, bu.unit_name AS business_unit_name,
                       j.journal_no AS linked_journal_no,
                       {$quantitySelect}
                       dep.accumulated_depreciation AS current_accumulated_depreciation,
                       dep.book_value AS current_book_value,
                       dep.depreciation_date AS current_depreciation_date,
                       (SELECT COUNT(*) FROM asset_mutations am WHERE am.asset_id = a.id) AS mutation_count
                FROM asset_items a
                INNER JOIN asset_categories c ON c.id = a.category_id
                LEFT JOIN business_units bu ON bu.id = a.business_unit_id
                LEFT JOIN journal_headers j ON j.id = a.linked_journal_id
                LEFT JOIN (
                    SELECT d1.asset_id, d1.accumulated_depreciation, d1.book_value, d1.depreciation_date
                    FROM asset_depreciations d1
                    INNER JOIN (
                        SELECT asset_id, MAX(depreciation_date) AS max_depreciation_date
                        FROM asset_depreciations
                        WHERE depreciation_date <= :as_of_date
                        GROUP BY asset_id
                    ) dx ON dx.asset_id = d1.asset_id AND dx.max_depreciation_date = d1.depreciation_date
                ) dep ON dep.asset_id = a.id
                WHERE 1=1";

        $params = [':as_of_date' => $asOfDate];
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $sql .= ' AND (a.asset_code LIKE :search OR a.asset_name LIKE :search OR a.subcategory_name LIKE :search OR a.reference_no LIKE :search OR a.supplier_name LIKE :search OR a.funding_source_detail LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        $unitId = (int) ($filters['unit_id'] ?? 0);
        if ($unitId > 0) {
            $sql .= ' AND a.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }
        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $sql .= ' AND a.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $sql .= ' AND a.asset_status = :asset_status';
            $params[':asset_status'] = $status;
        }
        $condition = trim((string) ($filters['condition'] ?? ''));
        if ($condition !== '') {
            $sql .= ' AND a.condition_status = :condition_status';
            $params[':condition_status'] = $condition;
        }
        $active = trim((string) ($filters['active'] ?? ''));
        if ($active === '1' || $active === '0') {
            $sql .= ' AND a.is_active = :is_active';
            $params[':is_active'] = (int) $active;
        }
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $sql .= ' AND a.acquisition_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $sql .= ' AND a.acquisition_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        $group = trim((string) ($filters['group'] ?? ''));
        if ($group !== '') {
            $sql .= ' AND c.asset_group = :asset_group';
            $params[':asset_group'] = $group;
        }
        $fundingSource = trim((string) ($filters['funding_source'] ?? ''));
        if ($fundingSource !== '') {
            $sql .= ' AND a.source_of_funds = :source_of_funds';
            $params[':source_of_funds'] = $fundingSource;
        }

        $sql .= ' ORDER BY a.acquisition_date DESC, a.asset_code ASC, a.id DESC';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row = $this->normalizeAssetPresentationRow($row);
        }
        unset($row);
        return $rows;
    }

    public function findAssetById(int $id, ?string $asOfDate = null): ?array
    {
        $quantitySelect = $this->assetQuantitySelectSql('a');
        $sql = 'SELECT a.*, c.category_code, c.category_name, c.asset_group,
                       c.asset_coa_id, c.accumulated_depreciation_coa_id, c.depreciation_expense_coa_id,
                       c.disposal_gain_coa_id, c.disposal_loss_coa_id,
                       bu.unit_code AS business_unit_code, bu.unit_name AS business_unit_name,
                       j.journal_no AS linked_journal_no,
                       ' . $quantitySelect . '
                       coa_offset.account_code AS offset_account_code,
                       coa_offset.account_name AS offset_account_name
                FROM asset_items a
                INNER JOIN asset_categories c ON c.id = a.category_id
                LEFT JOIN business_units bu ON bu.id = a.business_unit_id
                LEFT JOIN journal_headers j ON j.id = a.linked_journal_id
                LEFT JOIN coa_accounts coa_offset ON coa_offset.id = a.offset_coa_id
                WHERE a.id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $dep = $this->getAssetCurrentDepreciation($id, $asOfDate ?: date('Y-m-d'));
        $row['current_accumulated_depreciation'] = $dep['accumulated_depreciation'];
        $row['current_book_value'] = $dep['book_value'];
        $row['current_depreciation_date'] = $dep['depreciation_date'];
        return $this->normalizeAssetPresentationRow($row);
    }

    public function findAssetByCode(string $code, ?int $excludeId = null): ?array
    {
        $sql = 'SELECT * FROM asset_items WHERE asset_code = :asset_code';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':asset_code', $code, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createAsset(array $data, int $userId): int
    {
        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $withQty = $this->assetItemsHasQtySupport();
            $columnParts = [
                'asset_code', 'asset_name', 'category_id', 'subcategory_name', 'business_unit_id',
            ];
            $valueParts = [
                ':asset_code', ':asset_name', ':category_id', ':subcategory_name', ':business_unit_id',
            ];
            if ($withQty) {
                $columnParts[] = 'quantity';
                $columnParts[] = 'unit_name';
                $valueParts[] = ':quantity';
                $valueParts[] = ':unit_name';
            }
            $columnParts = array_merge($columnParts, [
                'entry_mode', 'acquisition_date', 'acquisition_cost', 'opening_as_of_date', 'opening_accumulated_depreciation',
                'residual_value', 'useful_life_months', 'depreciation_method', 'depreciation_start_date', 'depreciation_allowed',
                'offset_coa_id', 'location', 'supplier_name', 'source_of_funds', 'funding_source_detail', 'reference_no', 'linked_journal_id',
                'condition_status', 'asset_status', 'acquisition_sync_status', 'is_active', 'description', 'notes',
                'created_by', 'updated_by', 'created_at', 'updated_at'
            ]);
            $valueParts = array_merge($valueParts, [
                ':entry_mode', ':acquisition_date', ':acquisition_cost', ':opening_as_of_date', ':opening_accumulated_depreciation',
                ':residual_value', ':useful_life_months', ':depreciation_method', ':depreciation_start_date', ':depreciation_allowed',
                ':offset_coa_id', ':location', ':supplier_name', ':source_of_funds', ':funding_source_detail', ':reference_no', ':linked_journal_id',
                ':condition_status', ':asset_status', ':acquisition_sync_status', ':is_active', ':description', ':notes',
                ':created_by', ':updated_by', 'NOW()', 'NOW()'
            ]);
            $sql = 'INSERT INTO asset_items (' . implode(', ', $columnParts) . ') VALUES (' . implode(', ', $valueParts) . ')';
            $stmt = $this->db->prepare($sql);
            $this->bindAssetData($stmt, $data, $userId, false);
            $stmt->execute();
            $assetId = (int) $this->db->lastInsertId();

            $this->insertMutation($assetId, [
                'mutation_date' => $data['acquisition_date'],
                'mutation_type' => $data['entry_mode'] === 'OPENING' ? 'UPDATE' : 'ACQUISITION',
                'from_business_unit_id' => null,
                'to_business_unit_id' => $data['business_unit_id'],
                'from_location' => '',
                'to_location' => $data['location'],
                'old_status' => '',
                'new_status' => $data['asset_status'],
                'reference_no' => $data['reference_no'],
                'linked_journal_id' => $data['linked_journal_id'],
                'amount' => $data['acquisition_cost'],
                'notes' => trim((string) ($data['notes'] ?? '')) !== '' ? (string) $data['notes'] : ($data['entry_mode'] === 'OPENING' ? 'Input saldo awal aset.' : 'Pencatatan aset baru.'),
            ], $userId);

            $this->rebuildDepreciationForAsset($assetId);
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return $assetId;
        } catch (Throwable $e) {
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function updateAsset(int $id, array $data, int $userId): void
    {
        $current = $this->findAssetById($id);
        if (!$current) {
            throw new RuntimeException('Aset tidak ditemukan.');
        }
        $ownTransaction = !$this->db->inTransaction();
        if ($ownTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $setParts = [
                        'asset_code = :asset_code',
                        'asset_name = :asset_name',
                        'category_id = :category_id',
                        'subcategory_name = :subcategory_name',
                        'business_unit_id = :business_unit_id',
                    ];
            if ($this->assetItemsHasQtySupport()) {
                $setParts[] = 'quantity = :quantity';
                $setParts[] = 'unit_name = :unit_name';
            }
            $setParts = array_merge($setParts, [
                        'entry_mode = :entry_mode',
                        'acquisition_date = :acquisition_date',
                        'acquisition_cost = :acquisition_cost',
                        'opening_as_of_date = :opening_as_of_date',
                        'opening_accumulated_depreciation = :opening_accumulated_depreciation',
                        'residual_value = :residual_value',
                        'useful_life_months = :useful_life_months',
                        'depreciation_method = :depreciation_method',
                        'depreciation_start_date = :depreciation_start_date',
                        'depreciation_allowed = :depreciation_allowed',
                        'offset_coa_id = :offset_coa_id',
                        'location = :location',
                        'supplier_name = :supplier_name',
                        'source_of_funds = :source_of_funds',
                        'funding_source_detail = :funding_source_detail',
                        'reference_no = :reference_no',
                        'linked_journal_id = :linked_journal_id',
                        'condition_status = :condition_status',
                        'asset_status = :asset_status',
                        'acquisition_sync_status = :acquisition_sync_status',
                        'is_active = :is_active',
                        'description = :description',
                        'notes = :notes',
                        'updated_by = :updated_by',
                        'updated_at = NOW()'
                    ]);
            $sql = 'UPDATE asset_items SET ' . implode(",
                        ", $setParts) . "
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $this->bindAssetData($stmt, $data, $userId, true, $id);
            $stmt->execute();

            $changedNotes = [];
            if ((int) ($current['business_unit_id'] ?? 0) !== (int) ($data['business_unit_id'] ?? 0)) {
                $changedNotes[] = 'Unit usaha diperbarui.';
            }
            if ((string) ($current['location'] ?? '') !== (string) $data['location']) {
                $changedNotes[] = 'Lokasi aset diperbarui.';
            }
            if ((string) ($current['asset_status'] ?? '') !== (string) $data['asset_status']) {
                $changedNotes[] = 'Status aset diperbarui.';
            }
            if ((float) ($current['acquisition_cost'] ?? 0) !== (float) $data['acquisition_cost']) {
                $changedNotes[] = 'Nilai perolehan diperbarui.';
            }
            $noteText = $changedNotes === [] ? 'Perubahan data aset.' : implode(' ', $changedNotes);

            $this->insertMutation($id, [
                'mutation_date' => date('Y-m-d'),
                'mutation_type' => 'UPDATE',
                'from_business_unit_id' => $current['business_unit_id'] !== null ? (int) $current['business_unit_id'] : null,
                'to_business_unit_id' => $data['business_unit_id'],
                'from_location' => (string) ($current['location'] ?? ''),
                'to_location' => (string) $data['location'],
                'old_status' => (string) ($current['asset_status'] ?? ''),
                'new_status' => (string) $data['asset_status'],
                'reference_no' => (string) $data['reference_no'],
                'linked_journal_id' => $data['linked_journal_id'],
                'amount' => $data['acquisition_cost'],
                'notes' => $noteText,
            ], $userId);

            $this->rebuildDepreciationForAsset($id);
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


    public function deleteAsset(int $id, int $userId): array
    {
        $asset = $this->findAssetById($id);
        if (!$asset) {
            throw new RuntimeException('Aset tidak ditemukan.');
        }

        if ((int) ($asset['linked_journal_id'] ?? 0) > 0) {
            throw new RuntimeException('Aset sudah tertaut ke jurnal perolehan, jadi tidak boleh dihapus dari master.');
        }

        $postedDepStmt = $this->db->prepare("SELECT COUNT(*) FROM asset_depreciations WHERE asset_id = :asset_id AND status = 'POSTED'");
        $postedDepStmt->bindValue(':asset_id', $id, PDO::PARAM_INT);
        $postedDepStmt->execute();
        if ((int) $postedDepStmt->fetchColumn() > 0) {
            throw new RuntimeException('Aset sudah memiliki penyusutan terposting. Hapus jurnal penyusutan terkait terlebih dahulu.');
        }

        $linkedMutationStmt = $this->db->prepare('SELECT COUNT(*) FROM asset_mutations WHERE asset_id = :asset_id AND linked_journal_id IS NOT NULL');
        $linkedMutationStmt->bindValue(':asset_id', $id, PDO::PARAM_INT);
        $linkedMutationStmt->execute();
        if ((int) $linkedMutationStmt->fetchColumn() > 0) {
            throw new RuntimeException('Aset sudah memiliki mutasi yang tertaut jurnal sehingga tidak aman dihapus otomatis.');
        }

        $this->db->beginTransaction();
        try {
            foreach (['asset_year_snapshots', 'asset_depreciations', 'asset_mutations'] as $table) {
                $stmt = $this->db->prepare('DELETE FROM ' . $table . ' WHERE asset_id = :asset_id');
                $stmt->bindValue(':asset_id', $id, PDO::PARAM_INT);
                $stmt->execute();
            }

            $deleteAsset = $this->db->prepare('DELETE FROM asset_items WHERE id = :id');
            $deleteAsset->bindValue(':id', $id, PDO::PARAM_INT);
            $deleteAsset->execute();

            $this->db->commit();
            return ['deleted' => true, 'asset_code' => (string) ($asset['asset_code'] ?? '')];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function storeMutation(int $assetId, array $data, int $userId): void
    {
        $asset = $this->findAssetById($assetId);
        if (!$asset) {
            throw new RuntimeException('Aset tidak ditemukan.');
        }

        $this->db->beginTransaction();
        try {
            $currentUnitId = $asset['business_unit_id'] !== null ? (int) $asset['business_unit_id'] : null;
            $currentLocation = (string) ($asset['location'] ?? '');
            $currentStatus = (string) ($asset['asset_status'] ?? 'ACTIVE');
            $newUnitId = $currentUnitId;
            $newLocation = $currentLocation;
            $newStatus = $currentStatus;
            $newIsActive = (int) ($asset['is_active'] ?? 1);

            switch ($data['mutation_type']) {
                case 'TRANSFER_UNIT':
                    $newUnitId = $data['to_business_unit_id'];
                    break;
                case 'TRANSFER_LOCATION':
                    $newLocation = $data['to_location'];
                    break;
                case 'STATUS_CHANGE':
                case 'MAINTENANCE':
                    $newStatus = $data['new_status'];
                    if (in_array($newStatus, ['SOLD', 'DAMAGED', 'DISPOSED'], true)) {
                        $newIsActive = 0;
                    }
                    break;
                case 'SELL':
                    $newStatus = 'SOLD';
                    $newIsActive = 0;
                    break;
                case 'DAMAGE':
                    $newStatus = 'DAMAGED';
                    $newIsActive = 0;
                    break;
                case 'DISPOSE':
                    $newStatus = 'DISPOSED';
                    $newIsActive = 0;
                    break;
            }

            $stmt = $this->db->prepare('UPDATE asset_items SET business_unit_id = :business_unit_id, location = :location, asset_status = :asset_status, is_active = :is_active, updated_by = :updated_by, updated_at = NOW() WHERE id = :id');
            if ($newUnitId !== null) {
                $stmt->bindValue(':business_unit_id', $newUnitId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':business_unit_id', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':location', $newLocation, PDO::PARAM_STR);
            $stmt->bindValue(':asset_status', $newStatus, PDO::PARAM_STR);
            $stmt->bindValue(':is_active', $newIsActive, PDO::PARAM_INT);
            $stmt->bindValue(':updated_by', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $assetId, PDO::PARAM_INT);
            $stmt->execute();

            $this->insertMutation($assetId, [
                'mutation_date' => $data['mutation_date'],
                'mutation_type' => $data['mutation_type'],
                'from_business_unit_id' => $currentUnitId,
                'to_business_unit_id' => $newUnitId,
                'from_location' => $currentLocation,
                'to_location' => $newLocation,
                'old_status' => $currentStatus,
                'new_status' => $newStatus,
                'reference_no' => $data['reference_no'],
                'linked_journal_id' => $data['linked_journal_id'],
                'amount' => $data['amount'],
                'notes' => $data['notes'],
            ], $userId);

            $this->rebuildDepreciationForAsset($assetId);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getMutations(int $assetId): array
    {
        $sql = 'SELECT m.*, 
                       fbu.unit_code AS from_unit_code, fbu.unit_name AS from_unit_name,
                       tbu.unit_code AS to_unit_code, tbu.unit_name AS to_unit_name,
                       j.journal_no AS linked_journal_no,
                       u.full_name AS created_by_name
                FROM asset_mutations m
                LEFT JOIN business_units fbu ON fbu.id = m.from_business_unit_id
                LEFT JOIN business_units tbu ON tbu.id = m.to_business_unit_id
                LEFT JOIN journal_headers j ON j.id = m.linked_journal_id
                LEFT JOIN users u ON u.id = m.created_by
                WHERE m.asset_id = :asset_id
                ORDER BY m.mutation_date DESC, m.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getDepreciationSchedule(int $assetId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = 'SELECT d.*, j.journal_no AS linked_journal_no
                FROM asset_depreciations d
                LEFT JOIN journal_headers j ON j.id = d.linked_journal_id
                WHERE d.asset_id = :asset_id';
        $params = [':asset_id' => $assetId];
        if ($dateFrom !== null && $dateFrom !== '') {
            $sql .= ' AND d.depreciation_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $sql .= ' AND d.depreciation_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        $sql .= ' ORDER BY d.period_year ASC, d.period_month ASC, d.id ASC';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getDepreciationRegister(array $filters = []): array
    {
        $sql = 'SELECT d.*, a.asset_code, a.asset_name, a.asset_status, a.business_unit_id,
                       c.category_name, c.asset_group, bu.unit_code, bu.unit_name
                FROM asset_depreciations d
                INNER JOIN asset_items a ON a.id = d.asset_id
                INNER JOIN asset_categories c ON c.id = a.category_id
                LEFT JOIN business_units bu ON bu.id = a.business_unit_id
                WHERE 1=1';
        $params = [];
        $unitId = (int) ($filters['unit_id'] ?? 0);
        if ($unitId > 0) {
            $sql .= ' AND a.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }
        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $sql .= ' AND a.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $sql .= ' AND a.asset_status = :asset_status';
            $params[':asset_status'] = $status;
        }
        $group = trim((string) ($filters['group'] ?? ''));
        if ($group !== '') {
            $sql .= ' AND c.asset_group = :asset_group';
            $params[':asset_group'] = $group;
        }
        $fundingSource = trim((string) ($filters['funding_source'] ?? ''));
        if ($fundingSource !== '') {
            $sql .= ' AND a.source_of_funds = :source_of_funds';
            $params[':source_of_funds'] = $fundingSource;
        }
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $sql .= ' AND d.depreciation_date >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $sql .= ' AND d.depreciation_date <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        $sql .= ' ORDER BY d.depreciation_date DESC, a.asset_code ASC';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function rebuildDepreciationForAll(?int $assetId = null): int
    {
        $ids = [];
        if ($assetId !== null && $assetId > 0) {
            $ids[] = $assetId;
        } else {
            $stmt = $this->db->query('SELECT id FROM asset_items ORDER BY id ASC');
            $ids = array_map(static fn ($row): int => (int) $row['id'], $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }

        $count = 0;
        foreach ($ids as $id) {
            $this->rebuildDepreciationForAsset($id);
            $count++;
        }
        return $count;
    }

    public function rebuildDepreciationForAsset(int $assetId): void
    {
        $asset = $this->findAssetById($assetId);
        if (!$asset) {
            return;
        }

        $postedCountStmt = $this->db->prepare("SELECT COUNT(*) FROM asset_depreciations WHERE asset_id = :asset_id AND status = 'POSTED'");
        $postedCountStmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $postedCountStmt->execute();
        if ((int) $postedCountStmt->fetchColumn() > 0) {
            return;
        }

        $deleteStmt = $this->db->prepare('DELETE FROM asset_depreciations WHERE asset_id = :asset_id');
        $deleteStmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $deleteStmt->execute();

        $depreciationAllowed = (int) ($asset['depreciation_allowed'] ?? 0) === 1;
        $usefulLifeMonths = (int) ($asset['useful_life_months'] ?? 0);
        $acquisitionCost = (float) ($asset['acquisition_cost'] ?? 0);
        $openingAccumulated = (float) ($asset['opening_accumulated_depreciation'] ?? 0);
        $residualValue = (float) ($asset['residual_value'] ?? 0);
        $depreciableBase = max(0.0, $acquisitionCost - $residualValue - $openingAccumulated);
        $startDate = (string) (($asset['depreciation_start_date'] ?? '') !== '' ? $asset['depreciation_start_date'] : ($asset['acquisition_date'] ?? ''));
        if (!$depreciationAllowed || $usefulLifeMonths <= 0 || $depreciableBase <= 0 || $startDate === '') {
            return;
        }

        try {
            $start = new DateTimeImmutable($startDate);
        } catch (Throwable) {
            return;
        }

        $disposalDate = $this->getDepreciationStopDate($assetId);
        $insert = $this->db->prepare('INSERT INTO asset_depreciations (
                asset_id, period_year, period_month, depreciation_date,
                depreciation_amount, accumulated_depreciation, book_value,
                status, linked_journal_id, created_at, updated_at
            ) VALUES (
                :asset_id, :period_year, :period_month, :depreciation_date,
                :depreciation_amount, :accumulated_depreciation, :book_value,
                :status, :linked_journal_id, NOW(), NOW()
            )');

        $monthly = round($depreciableBase / $usefulLifeMonths, 2);
        $accumulated = $openingAccumulated;
        $periodDate = $start->modify('last day of this month');
        for ($i = 1; $i <= $usefulLifeMonths; $i++) {
            if ($disposalDate !== null && $periodDate > $disposalDate->modify('last day of this month')) {
                break;
            }
            $remaining = max(0.0, ($acquisitionCost - $residualValue) - $accumulated);
            if ($remaining <= 0) {
                break;
            }
            $amount = $i === $usefulLifeMonths ? round(min($remaining, $monthly), 2) : round(min($remaining, $monthly), 2);
            $accumulated = round($accumulated + $amount, 2);
            $bookValue = round(max($residualValue, $acquisitionCost - $accumulated), 2);

            $insert->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
            $insert->bindValue(':period_year', (int) $periodDate->format('Y'), PDO::PARAM_INT);
            $insert->bindValue(':period_month', (int) $periodDate->format('n'), PDO::PARAM_INT);
            $insert->bindValue(':depreciation_date', $periodDate->format('Y-m-d'), PDO::PARAM_STR);
            $insert->bindValue(':depreciation_amount', $amount);
            $insert->bindValue(':accumulated_depreciation', $accumulated);
            $insert->bindValue(':book_value', $bookValue);
            $insert->bindValue(':status', 'CALCULATED', PDO::PARAM_STR);
            $insert->bindValue(':linked_journal_id', null, PDO::PARAM_NULL);
            $insert->execute();
            $periodDate = $periodDate->modify('first day of next month')->modify('last day of this month');
        }
    }

    public function getAssetCurrentDepreciation(int $assetId, string $asOfDate): array
    {
        $stmt = $this->db->prepare('SELECT accumulated_depreciation, book_value, depreciation_date
                                    FROM asset_depreciations
                                    WHERE asset_id = :asset_id AND depreciation_date <= :as_of_date
                                    ORDER BY depreciation_date DESC, id DESC
                                    LIMIT 1');
        $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $stmt->bindValue(':as_of_date', $asOfDate, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'accumulated_depreciation' => (float) $row['accumulated_depreciation'],
                'book_value' => (float) $row['book_value'],
                'depreciation_date' => (string) $row['depreciation_date'],
            ];
        }

        $asset = $this->findRawAssetById($assetId);
        if (!$asset) {
            return ['accumulated_depreciation' => 0.0, 'book_value' => 0.0, 'depreciation_date' => ''];
        }
        $openingAccumulated = (float) ($asset['opening_accumulated_depreciation'] ?? 0);
        return [
            'accumulated_depreciation' => $openingAccumulated,
            'book_value' => max(0.0, (float) $asset['acquisition_cost'] - $openingAccumulated),
            'depreciation_date' => (string) ($asset['opening_as_of_date'] ?? ''),
        ];
    }

    public function getReportData(array $filters = []): array
    {
        $asOfDate = trim((string) ($filters['as_of_date'] ?? ''));
        if ($asOfDate === '') {
            $asOfDate = date('Y-m-d');
        }
        $comparisonDate = trim((string) ($filters['comparison_date'] ?? ''));
        if ($comparisonDate === '') {
            $comparisonDate = asset_comparison_date($asOfDate);
        }
        $filters['as_of_date'] = $asOfDate;
        $rows = $this->getAssets($filters);
        $summary = [
            'asset_count' => 0,
            'active_count' => 0,
            'total_quantity' => 0.0,
            'total_cost' => 0.0,
            'total_residual' => 0.0,
            'total_accumulated_depreciation' => 0.0,
            'total_book_value' => 0.0,
            'comparison_book_value' => 0.0,
            'comparison_accumulated' => 0.0,
            'book_value_delta' => 0.0,
        ];
        foreach ($rows as &$row) {
            $summary['asset_count']++;
            if ((int) $row['is_active'] === 1) {
                $summary['active_count']++;
            }
            $summary['total_quantity'] += (float) ($row['quantity'] ?? 1);
            $summary['total_cost'] += (float) $row['acquisition_cost'];
            $summary['total_residual'] += (float) $row['residual_value'];
            $summary['total_accumulated_depreciation'] += (float) ($row['current_accumulated_depreciation'] ?? 0);
            $currentBookValue = (float) (($row['current_book_value'] ?? $row['acquisition_cost']) ?: 0);
            $summary['total_book_value'] += $currentBookValue;

            $comparison = $this->getAssetSnapshotValue((int) $row['id'], $comparisonDate);
            $row['comparison_accumulated_depreciation'] = $comparison['accumulated_depreciation'];
            $row['comparison_book_value'] = $comparison['book_value'];
            $row['comparison_date'] = $comparisonDate;
            $row['book_value_delta'] = round($currentBookValue - (float) $comparison['book_value'], 2);
            $summary['comparison_book_value'] += (float) $comparison['book_value'];
            $summary['comparison_accumulated'] += (float) $comparison['accumulated_depreciation'];
            $summary['book_value_delta'] += (float) $row['book_value_delta'];
        }
        unset($row);

        return [
            'rows' => $rows,
            'summary' => $summary,
            'as_of_date' => $asOfDate,
            'comparison_date' => $comparisonDate,
        ];
    }

    public function getAuditSummary(): array
    {
        $summary = [
            'asset_count' => 0,
            'opening_count' => 0,
            'acquisition_count' => 0,
            'categories_missing_map' => 0,
            'acquisition_without_journal' => 0,
            'stored_status_mismatch' => 0,
            'depreciation_calculated' => 0,
            'depreciation_posted' => 0,
            'unit_comparisons' => [],
            'units_missing_register' => [],
            'units_with_delta' => [],
        ];

        $totals = $this->db->query("SELECT
                COUNT(*) AS asset_count,
                SUM(CASE WHEN entry_mode = 'OPENING' THEN 1 ELSE 0 END) AS opening_count,
                SUM(CASE WHEN entry_mode = 'ACQUISITION' THEN 1 ELSE 0 END) AS acquisition_count
            FROM asset_items")->fetch(PDO::FETCH_ASSOC) ?: [];
        $summary['asset_count'] = (int) ($totals['asset_count'] ?? 0);
        $summary['opening_count'] = (int) ($totals['opening_count'] ?? 0);
        $summary['acquisition_count'] = (int) ($totals['acquisition_count'] ?? 0);

        $summary['categories_missing_map'] = (int) ($this->db->query("SELECT COUNT(*) FROM asset_categories
            WHERE is_active = 1
              AND (
                    asset_coa_id IS NULL
                    OR (
                        depreciation_allowed = 1
                        AND (
                            accumulated_depreciation_coa_id IS NULL
                            OR depreciation_expense_coa_id IS NULL
                        )
                    )
                )")->fetchColumn() ?: 0);

        $summary['acquisition_without_journal'] = (int) ($this->db->query("SELECT COUNT(*) FROM asset_items
            WHERE entry_mode = 'ACQUISITION'
              AND (linked_journal_id IS NULL OR linked_journal_id = 0)")->fetchColumn() ?: 0);

        $summary['stored_status_mismatch'] = (int) ($this->db->query("SELECT COUNT(*) FROM asset_items
            WHERE linked_journal_id IS NOT NULL
              AND linked_journal_id > 0
              AND acquisition_sync_status <> 'POSTED'")->fetchColumn() ?: 0);

        $summary['depreciation_calculated'] = (int) ($this->db->query("SELECT COUNT(*) FROM asset_depreciations WHERE status = 'CALCULATED'")->fetchColumn() ?: 0);
        $summary['depreciation_posted'] = (int) ($this->db->query("SELECT COUNT(*) FROM asset_depreciations WHERE status = 'POSTED'")->fetchColumn() ?: 0);

        $registerRows = $this->db->query("SELECT
                bu.id AS unit_id,
                bu.unit_code,
                bu.unit_name,
                COUNT(a.id) AS register_count,
                COALESCE(SUM(a.acquisition_cost), 0) AS register_total
            FROM business_units bu
            LEFT JOIN asset_items a ON a.business_unit_id = bu.id
            GROUP BY bu.id, bu.unit_code, bu.unit_name
            ORDER BY bu.unit_code ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $ledgerStmt = $this->db->query("SELECT
                h.business_unit_id AS unit_id,
                COALESCE(SUM(l.debit - l.credit), 0) AS ledger_total
            FROM journal_headers h
            INNER JOIN journal_lines l ON l.journal_id = h.id
            INNER JOIN coa_accounts c ON c.id = l.coa_id
            WHERE c.account_type = 'ASSET'
              AND c.account_category = 'FIXED_ASSET'
              AND c.account_name NOT LIKE 'Akumulasi%'
            GROUP BY h.business_unit_id");
        $ledgerByUnit = [];
        foreach ($ledgerStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $ledgerRow) {
            $ledgerByUnit[(int) ($ledgerRow['unit_id'] ?? 0)] = (float) ($ledgerRow['ledger_total'] ?? 0);
        }

        foreach ($registerRows as $row) {
            $unitId = (int) ($row['unit_id'] ?? 0);
            $registerTotal = (float) ($row['register_total'] ?? 0);
            $ledgerTotal = (float) ($ledgerByUnit[$unitId] ?? 0);
            $comparison = [
                'unit_id' => $unitId,
                'unit_code' => (string) ($row['unit_code'] ?? ''),
                'unit_name' => (string) ($row['unit_name'] ?? ''),
                'register_count' => (int) ($row['register_count'] ?? 0),
                'register_total' => $registerTotal,
                'ledger_total' => $ledgerTotal,
                'delta' => round($registerTotal - $ledgerTotal, 2),
            ];
            $summary['unit_comparisons'][] = $comparison;

            if ($comparison['register_count'] === 0 && abs($ledgerTotal) >= 0.005) {
                $summary['units_missing_register'][] = $comparison;
            } elseif (abs($comparison['delta']) >= 0.005) {
                $summary['units_with_delta'][] = $comparison;
            }
        }

        return $summary;
    }

    private function bindAssetData(PDOStatement $stmt, array $data, int $userId, bool $withId = false, int $id = 0): void
    {
        if ($withId) {
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }
        $stmt->bindValue(':asset_code', $data['asset_code'], PDO::PARAM_STR);
        $stmt->bindValue(':asset_name', $data['asset_name'], PDO::PARAM_STR);
        $stmt->bindValue(':category_id', (int) $data['category_id'], PDO::PARAM_INT);
        $stmt->bindValue(':subcategory_name', $data['subcategory_name'], PDO::PARAM_STR);
        $this->bindNullableInt($stmt, ':business_unit_id', $data['business_unit_id']);
        if ($this->assetItemsHasQtySupport()) {
            $stmt->bindValue(':quantity', (float) ($data['quantity'] ?? 1));
            $stmt->bindValue(':unit_name', (string) (($data['unit_name'] ?? '') !== '' ? $data['unit_name'] : 'unit'), PDO::PARAM_STR);
        }
        $stmt->bindValue(':entry_mode', $data['entry_mode'], PDO::PARAM_STR);
        $stmt->bindValue(':acquisition_date', $data['acquisition_date'], PDO::PARAM_STR);
        $stmt->bindValue(':acquisition_cost', (float) $data['acquisition_cost']);
        if (($data['opening_as_of_date'] ?? '') !== '') {
            $stmt->bindValue(':opening_as_of_date', $data['opening_as_of_date'], PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':opening_as_of_date', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':opening_accumulated_depreciation', (float) ($data['opening_accumulated_depreciation'] ?? 0));
        $stmt->bindValue(':residual_value', (float) $data['residual_value']);
        if ($data['useful_life_months'] !== null) {
            $stmt->bindValue(':useful_life_months', (int) $data['useful_life_months'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':useful_life_months', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':depreciation_method', $data['depreciation_method'], PDO::PARAM_STR);
        if (($data['depreciation_start_date'] ?? '') !== '') {
            $stmt->bindValue(':depreciation_start_date', $data['depreciation_start_date'], PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':depreciation_start_date', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':depreciation_allowed', $data['depreciation_allowed'] ? 1 : 0, PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':offset_coa_id', $data['offset_coa_id'] ?? null);
        $stmt->bindValue(':location', $data['location'], PDO::PARAM_STR);
        $stmt->bindValue(':supplier_name', $data['supplier_name'], PDO::PARAM_STR);
        $stmt->bindValue(':source_of_funds', $data['source_of_funds'], PDO::PARAM_STR);
        $stmt->bindValue(':funding_source_detail', $data['funding_source_detail'], PDO::PARAM_STR);
        $stmt->bindValue(':reference_no', $data['reference_no'], PDO::PARAM_STR);
        $this->bindNullableInt($stmt, ':linked_journal_id', $data['linked_journal_id']);
        $stmt->bindValue(':condition_status', $data['condition_status'], PDO::PARAM_STR);
        $stmt->bindValue(':asset_status', $data['asset_status'], PDO::PARAM_STR);
        $stmt->bindValue(':acquisition_sync_status', (string) ($data['acquisition_sync_status'] ?? $this->resolveAcquisitionSyncStatus($data)), PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindValue(':notes', $data['notes'], PDO::PARAM_STR);
        if ($withId) {
            $stmt->bindValue(':updated_by', $userId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':created_by', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':updated_by', $userId, PDO::PARAM_INT);
        }
    }

    private function insertMutation(int $assetId, array $data, int $userId): void
    {
        $sql = 'INSERT INTO asset_mutations (
                    asset_id, mutation_date, mutation_type, from_business_unit_id, to_business_unit_id,
                    from_location, to_location, old_status, new_status, reference_no,
                    linked_journal_id, amount, notes, created_by, created_at
                ) VALUES (
                    :asset_id, :mutation_date, :mutation_type, :from_business_unit_id, :to_business_unit_id,
                    :from_location, :to_location, :old_status, :new_status, :reference_no,
                    :linked_journal_id, :amount, :notes, :created_by, NOW()
                )';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $stmt->bindValue(':mutation_date', $data['mutation_date'], PDO::PARAM_STR);
        $stmt->bindValue(':mutation_type', $data['mutation_type'], PDO::PARAM_STR);
        $this->bindNullableInt($stmt, ':from_business_unit_id', $data['from_business_unit_id']);
        $this->bindNullableInt($stmt, ':to_business_unit_id', $data['to_business_unit_id']);
        $stmt->bindValue(':from_location', (string) ($data['from_location'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':to_location', (string) ($data['to_location'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':old_status', (string) ($data['old_status'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':new_status', (string) ($data['new_status'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':reference_no', (string) ($data['reference_no'] ?? ''), PDO::PARAM_STR);
        $this->bindNullableInt($stmt, ':linked_journal_id', $data['linked_journal_id'] ?? null);
        if ($data['amount'] !== null && $data['amount'] !== '') {
            $stmt->bindValue(':amount', (float) $data['amount']);
        } else {
            $stmt->bindValue(':amount', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':notes', (string) ($data['notes'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':created_by', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function bindNullableInt(PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($param, (int) $value, PDO::PARAM_INT);
    }

    private function getDepreciationStopDate(int $assetId): ?DateTimeImmutable
    {
        $stmt = $this->db->prepare("SELECT mutation_date
                                    FROM asset_mutations
                                    WHERE asset_id = :asset_id
                                      AND new_status IN ('SOLD','DAMAGED','DISPOSED')
                                    ORDER BY mutation_date ASC, id ASC
                                    LIMIT 1");
        $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $stmt->execute();
        $date = $stmt->fetchColumn();
        if (!is_string($date) || $date === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($date);
        } catch (Throwable) {
            return null;
        }
    }

    private function findRawAssetById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM asset_items WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }


    private function getAssetSnapshotValue(int $assetId, string $date): array
    {
        try {
            $snapshotYear = (int) (new DateTimeImmutable($date))->format('Y');
            $isYearEnd = (new DateTimeImmutable($date))->format('m-d') === '12-31';
            if ($isYearEnd) {
                $stmt = $this->db->prepare('SELECT accumulated_depreciation, book_value FROM asset_year_snapshots WHERE asset_id = :asset_id AND snapshot_year = :snapshot_year LIMIT 1');
                $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
                $stmt->bindValue(':snapshot_year', $snapshotYear, PDO::PARAM_INT);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return [
                        'accumulated_depreciation' => (float) $row['accumulated_depreciation'],
                        'book_value' => (float) $row['book_value'],
                    ];
                }
            }
        } catch (Throwable) {
        }
        $dep = $this->getAssetCurrentDepreciation($assetId, $date);
        return [
            'accumulated_depreciation' => (float) ($dep['accumulated_depreciation'] ?? 0),
            'book_value' => (float) ($dep['book_value'] ?? 0),
        ];
    }

    public function buildYearSnapshot(int $year, int $userId): int
    {
        $snapshotDate = sprintf('%04d-12-31', $year);
        $assets = $this->getAssets(['as_of_date' => $snapshotDate]);
        $delete = $this->db->prepare('DELETE FROM asset_year_snapshots WHERE snapshot_year = :snapshot_year');
        $delete->bindValue(':snapshot_year', $year, PDO::PARAM_INT);
        $delete->execute();
        $insert = $this->db->prepare('INSERT INTO asset_year_snapshots (asset_id, snapshot_year, snapshot_date, business_unit_id, acquisition_cost, accumulated_depreciation, book_value, asset_status, created_by, created_at) VALUES (:asset_id, :snapshot_year, :snapshot_date, :business_unit_id, :acquisition_cost, :accumulated_depreciation, :book_value, :asset_status, :created_by, NOW())');
        $count = 0;
        foreach ($assets as $asset) {
            $insert->bindValue(':asset_id', (int) $asset['id'], PDO::PARAM_INT);
            $insert->bindValue(':snapshot_year', $year, PDO::PARAM_INT);
            $insert->bindValue(':snapshot_date', $snapshotDate, PDO::PARAM_STR);
            $this->bindNullableInt($insert, ':business_unit_id', $asset['business_unit_id']);
            $insert->bindValue(':acquisition_cost', (float) $asset['acquisition_cost']);
            $insert->bindValue(':accumulated_depreciation', (float) ($asset['current_accumulated_depreciation'] ?? 0));
            $insert->bindValue(':book_value', (float) (($asset['current_book_value'] ?? $asset['acquisition_cost']) ?: 0));
            $insert->bindValue(':asset_status', (string) $asset['asset_status'], PDO::PARAM_STR);
            $insert->bindValue(':created_by', $userId, PDO::PARAM_INT);
            $insert->execute();
            $count++;
        }
        return $count;
    }

    public function postAcquisitionJournal(int $assetId, int $userId): int
    {
        $asset = $this->findAssetById($assetId);
        if (!$asset) {
            throw new RuntimeException('Aset tidak ditemukan.');
        }
        if ((string) ($asset['entry_mode'] ?? 'ACQUISITION') !== 'ACQUISITION') {
            throw new RuntimeException('Aset saldo awal tidak perlu dibuat jurnal perolehan baru.');
        }
        if ((int) ($asset['linked_journal_id'] ?? 0) > 0) {
            throw new RuntimeException('Jurnal perolehan aset ini sudah pernah diposting.');
        }
        if ((int) ($asset['asset_coa_id'] ?? 0) <= 0) {
            throw new RuntimeException('Mapping akun aset pada kategori belum diisi.');
        }
        if ((int) ($asset['offset_coa_id'] ?? 0) <= 0) {
            throw new RuntimeException('Akun lawan perolehan belum diisi pada aset.');
        }
        $period = $this->findPeriodByDate((string) $asset['acquisition_date']);
        if (!$period) {
            throw new RuntimeException('Periode akuntansi untuk tanggal perolehan tidak ditemukan.');
        }
        $journalModel = new JournalModel($this->db);
        $header = [
            'journal_date' => (string) $asset['acquisition_date'],
            'description' => 'Perolehan aset: ' . (string) $asset['asset_name'] . ' (' . (string) $asset['asset_code'] . ')',
            'period_id' => (int) $period['id'],
            'business_unit_id' => $asset['business_unit_id'] !== null ? (int) $asset['business_unit_id'] : null,
            'total_debit' => (float) $asset['acquisition_cost'],
            'total_credit' => (float) $asset['acquisition_cost'],
            'created_by' => $userId,
            'updated_by' => $userId,
            'print_template' => 'standard',
        ];
        $lines = [
            [
                'coa_id' => (int) $asset['asset_coa_id'],
                'line_description' => 'Perolehan aset ' . (string) $asset['asset_code'],
                'debit' => (float) $asset['acquisition_cost'],
                'credit' => 0.0,
            ],
            [
                'coa_id' => (int) $asset['offset_coa_id'],
                'line_description' => 'Lawan perolehan aset ' . (string) $asset['asset_code'],
                'debit' => 0.0,
                'credit' => (float) $asset['acquisition_cost'],
            ],
        ];
        $this->db->beginTransaction();
        try {
            $journalId = $journalModel->create($header, $lines);
            $up = $this->db->prepare("UPDATE asset_items SET linked_journal_id = :journal_id, acquisition_sync_status = 'POSTED', updated_by = :updated_by, updated_at = NOW() WHERE id = :id");
            $up->bindValue(':journal_id', $journalId, PDO::PARAM_INT);
            $up->bindValue(':updated_by', $userId, PDO::PARAM_INT);
            $up->bindValue(':id', $assetId, PDO::PARAM_INT);
            $up->execute();
            $this->createAccountingEvent($assetId, 'ACQUISITION', (string) $asset['acquisition_date'], (float) $asset['acquisition_cost'], $journalId, 'POSTED', 'Posting jurnal perolehan aset.', $userId, null, null);
            $this->db->commit();
            return $journalId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function postDepreciationForDate(string $depreciationDate, array $filters, int $userId): array
    {
        $targetDate = (new DateTimeImmutable($depreciationDate))->format('Y-m-t');
        $period = $this->findPeriodByDate($targetDate);
        if (!$period) {
            throw new RuntimeException('Periode akuntansi untuk penyusutan tidak ditemukan.');
        }
        $sql = "SELECT d.*, a.asset_code, a.asset_name, a.business_unit_id, c.depreciation_expense_coa_id, c.accumulated_depreciation_coa_id
                FROM asset_depreciations d
                INNER JOIN asset_items a ON a.id = d.asset_id
                INNER JOIN asset_categories c ON c.id = a.category_id
                WHERE d.depreciation_date = :depreciation_date
                  AND d.status = 'CALCULATED'
                  AND d.depreciation_amount > 0
                  AND c.depreciation_expense_coa_id IS NOT NULL
                  AND c.accumulated_depreciation_coa_id IS NOT NULL";
        $params = [':depreciation_date' => $targetDate];
        if (!empty($filters['unit_id'])) {
            $sql .= ' AND a.business_unit_id = :unit_id';
            $params[':unit_id'] = (int) $filters['unit_id'];
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k=>$v) {
            $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($rows === []) {
            throw new RuntimeException('Tidak ada penyusutan yang siap diposting untuk periode tersebut.');
        }
        $grouped=[];
        foreach($rows as $row){
            $key=(int)$row['depreciation_expense_coa_id'].'-'.(int)$row['accumulated_depreciation_coa_id'];
            if(!isset($grouped[$key])){
                $grouped[$key]=['expense'=>(int)$row['depreciation_expense_coa_id'],'accum'=>(int)$row['accumulated_depreciation_coa_id'],'amount'=>0.0];
            }
            $grouped[$key]['amount'] += (float)$row['depreciation_amount'];
        }
        $lines=[]; $total=0.0;
        foreach($grouped as $g){
            $amt=round($g['amount'],2); if($amt<=0) continue;
            $lines[]=['coa_id'=>$g['expense'],'line_description'=>'Beban penyusutan aset periode '.format_id_date($targetDate),'debit'=>$amt,'credit'=>0.0];
            $lines[]=['coa_id'=>$g['accum'],'line_description'=>'Akumulasi penyusutan aset periode '.format_id_date($targetDate),'debit'=>0.0,'credit'=>$amt];
            $total += $amt;
        }
        $journalModel = new JournalModel($this->db);
        $header=[
            'journal_date'=>$targetDate,
            'description'=>'Posting penyusutan aset periode '.date('F Y', strtotime($targetDate)),
            'period_id'=>(int)$period['id'],
            'business_unit_id'=>!empty($filters['unit_id']) ? (int)$filters['unit_id'] : null,
            'total_debit'=>$total,
            'total_credit'=>$total,
            'created_by'=>$userId,
            'updated_by'=>$userId,
            'print_template'=>'standard',
        ];
        $this->db->beginTransaction();
        try {
            $journalId=$journalModel->create($header,$lines);
            $upd=$this->db->prepare("UPDATE asset_depreciations SET status = 'POSTED', linked_journal_id = :journal_id, updated_at = NOW() WHERE id = :id");
            foreach($rows as $row){
                $upd->bindValue(':journal_id',$journalId,PDO::PARAM_INT);
                $upd->bindValue(':id',(int)$row['id'],PDO::PARAM_INT);
                $upd->execute();
                $this->createAccountingEvent((int)$row['asset_id'],'DEPRECIATION',$targetDate,(float)$row['depreciation_amount'],$journalId,'POSTED','Posting penyusutan periodik.', $userId, (int)$row['period_year'], (int)$row['period_month']);
            }
            $this->db->commit();
            return ['journal_id'=>$journalId,'count'=>count($rows)];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function createAccountingEvent(int $assetId, string $eventType, string $eventDate, float $amount, ?int $journalId, string $status, string $description, int $userId, ?int $periodYear, ?int $periodMonth): void
    {
        $stmt = $this->db->prepare('INSERT INTO asset_accounting_events (asset_id, event_type, event_date, period_year, period_month, amount, journal_id, status, description, created_by, created_at) VALUES (:asset_id, :event_type, :event_date, :period_year, :period_month, :amount, :journal_id, :status, :description, :created_by, NOW())');
        $stmt->bindValue(':asset_id', $assetId, PDO::PARAM_INT);
        $stmt->bindValue(':event_type', $eventType, PDO::PARAM_STR);
        $stmt->bindValue(':event_date', $eventDate, PDO::PARAM_STR);
        if ($periodYear !== null) { $stmt->bindValue(':period_year', $periodYear, PDO::PARAM_INT); } else { $stmt->bindValue(':period_year', null, PDO::PARAM_NULL); }
        if ($periodMonth !== null) { $stmt->bindValue(':period_month', $periodMonth, PDO::PARAM_INT); } else { $stmt->bindValue(':period_month', null, PDO::PARAM_NULL); }
        $stmt->bindValue(':amount', $amount);
        if ($journalId !== null) { $stmt->bindValue(':journal_id', $journalId, PDO::PARAM_INT); } else { $stmt->bindValue(':journal_id', null, PDO::PARAM_NULL); }
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':created_by', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

}
