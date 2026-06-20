<?php

declare(strict_types=1);

function asset_cash_usage_empty(float $profitBeforeAssetPurchase = 0.0): array
{
    return [
        'profit_before_asset_purchase' => $profitBeforeAssetPurchase,
        'asset_cash_outflow' => 0.0,
        'after_asset_purchase_indicator' => $profitBeforeAssetPurchase,
        'asset_acquisition_total' => 0.0,
        'unlinked_asset_total' => 0.0,
        'unlinked_asset_count' => 0,
        'linked_asset_total' => 0.0,
        'linked_asset_count' => 0,
        'cash_account_count' => 0,
        'warnings' => [],
    ];
}

function asset_cash_usage_summary(PDO $db, string $dateFrom, string $dateTo, int $unitId = 0, float $profitBeforeAssetPurchase = 0.0): array
{
    $summary = asset_cash_usage_empty($profitBeforeAssetPurchase);
    if ($dateFrom === '' || $dateTo === '' || $dateTo < $dateFrom) {
        $summary['warnings'][] = 'Rentang tanggal belum valid untuk membaca belanja aset.';
        return $summary;
    }

    if (!asset_cash_usage_table_exists($db, 'asset_items') || !asset_cash_usage_table_exists($db, 'journal_headers') || !asset_cash_usage_table_exists($db, 'journal_lines') || !asset_cash_usage_table_exists($db, 'coa_accounts')) {
        $summary['warnings'][] = 'Data aset atau jurnal belum lengkap untuk membaca belanja aset.';
        return $summary;
    }

    $assetTotals = asset_cash_usage_asset_totals($db, $dateFrom, $dateTo, $unitId);
    $summary = array_replace($summary, $assetTotals);

    $cashAccountIds = asset_cash_usage_cash_account_ids($db);
    $summary['cash_account_count'] = count($cashAccountIds);
    if ($cashAccountIds === []) {
        $summary['warnings'][] = 'Belanja aset dari kas/bank belum dapat dibaca karena akun kas/bank belum terdeteksi.';
        return $summary;
    }

    $summary['asset_cash_outflow'] = asset_cash_usage_cash_outflow($db, $cashAccountIds, $dateFrom, $dateTo, $unitId);
    $summary['after_asset_purchase_indicator'] = $profitBeforeAssetPurchase - (float) $summary['asset_cash_outflow'];
    if ((int) $summary['unlinked_asset_count'] > 0) {
        $summary['warnings'][] = 'Ada ' . number_format((int) $summary['unlinked_asset_count'], 0, ',', '.') . ' aset perolehan belum tertaut jurnal; nilainya ditampilkan sebagai catatan, tidak mengurangi indikator sisa.';
    }

    return $summary;
}

function asset_cash_usage_asset_totals(PDO $db, string $dateFrom, string $dateTo, int $unitId): array
{
    $sql = "SELECT
                COALESCE(SUM(acquisition_cost), 0) AS asset_acquisition_total,
                COALESCE(SUM(CASE WHEN linked_journal_id IS NOT NULL AND linked_journal_id > 0 THEN acquisition_cost ELSE 0 END), 0) AS linked_asset_total,
                COALESCE(SUM(CASE WHEN linked_journal_id IS NOT NULL AND linked_journal_id > 0 THEN 1 ELSE 0 END), 0) AS linked_asset_count,
                COALESCE(SUM(CASE WHEN linked_journal_id IS NULL OR linked_journal_id = 0 THEN acquisition_cost ELSE 0 END), 0) AS unlinked_asset_total,
                COALESCE(SUM(CASE WHEN linked_journal_id IS NULL OR linked_journal_id = 0 THEN 1 ELSE 0 END), 0) AS unlinked_asset_count
            FROM asset_items
            WHERE entry_mode = 'ACQUISITION'
              AND acquisition_date >= :date_from
              AND acquisition_date <= :date_to";
    if ($unitId > 0) {
        $sql .= ' AND business_unit_id = :unit_id';
    }

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':date_from', $dateFrom, PDO::PARAM_STR);
    $stmt->bindValue(':date_to', $dateTo, PDO::PARAM_STR);
    if ($unitId > 0) {
        $stmt->bindValue(':unit_id', $unitId, PDO::PARAM_INT);
    }
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'asset_acquisition_total' => (float) ($row['asset_acquisition_total'] ?? 0),
        'linked_asset_total' => (float) ($row['linked_asset_total'] ?? 0),
        'linked_asset_count' => (int) ($row['linked_asset_count'] ?? 0),
        'unlinked_asset_total' => (float) ($row['unlinked_asset_total'] ?? 0),
        'unlinked_asset_count' => (int) ($row['unlinked_asset_count'] ?? 0),
    ];
}

function asset_cash_usage_cash_outflow(PDO $db, array $cashAccountIds, string $dateFrom, string $dateTo, int $unitId): float
{
    if ($cashAccountIds === []) {
        return 0.0;
    }

    $cashPlaceholders = implode(', ', array_fill(0, count($cashAccountIds), '?'));
    $assetUnitFilter = $unitId > 0 ? ' AND a.business_unit_id = ?' : '';
    $journalUnitFilter = $unitId > 0 ? ' AND h.business_unit_id = ?' : '';

    $sql = "SELECT COALESCE(SUM(LEAST(asset_journals.linked_asset_total, cash_journals.cash_outflow)), 0) AS asset_cash_outflow
            FROM (
                SELECT a.linked_journal_id AS journal_id, COALESCE(SUM(a.acquisition_cost), 0) AS linked_asset_total
                FROM asset_items a
                WHERE a.entry_mode = 'ACQUISITION'
                  AND a.linked_journal_id IS NOT NULL
                  AND a.linked_journal_id > 0
                  AND a.acquisition_date >= ?
                  AND a.acquisition_date <= ?
                  {$assetUnitFilter}
                GROUP BY a.linked_journal_id
            ) asset_journals
            INNER JOIN (
                SELECT h.id AS journal_id, COALESCE(SUM(CASE WHEN l.credit > l.debit THEN l.credit - l.debit ELSE 0 END), 0) AS cash_outflow
                FROM journal_headers h
                INNER JOIN journal_lines l ON l.journal_id = h.id
                WHERE h.journal_date >= ?
                  AND h.journal_date <= ?
                  " . journal_posted_sql($db, 'h') . "
                  AND l.coa_id IN ({$cashPlaceholders})
                  {$journalUnitFilter}
                GROUP BY h.id
                HAVING cash_outflow > 0
            ) cash_journals ON cash_journals.journal_id = asset_journals.journal_id";

    $params = [$dateFrom, $dateTo];
    if ($unitId > 0) {
        $params[] = $unitId;
    }
    $params[] = $dateFrom;
    $params[] = $dateTo;
    foreach ($cashAccountIds as $cashAccountId) {
        $params[] = (int) $cashAccountId;
    }
    if ($unitId > 0) {
        $params[] = $unitId;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return (float) ($stmt->fetchColumn() ?: 0);
}

function asset_cash_usage_cash_account_ids(PDO $db): array
{
    $select = 'id, account_name, account_category';
    $conditions = ["account_type = 'ASSET'", 'is_active = 1', 'is_header = 0'];
    $cashSignals = [];
    if (asset_cash_usage_column_exists($db, 'coa_accounts', 'is_cash')) {
        $cashSignals[] = 'is_cash = 1';
    }
    if (asset_cash_usage_column_exists($db, 'coa_accounts', 'is_bank')) {
        $cashSignals[] = 'is_bank = 1';
    }
    $cashSignals[] = "(account_category = 'CURRENT_ASSET' AND (LOWER(account_name) LIKE '%kas%' OR LOWER(account_name) LIKE '%bank%'))";

    $sql = 'SELECT ' . $select . '
            FROM coa_accounts
            WHERE ' . implode(' AND ', $conditions) . '
              AND (' . implode(' OR ', $cashSignals) . ')
            ORDER BY account_code ASC, id ASC';
    $stmt = $db->query($sql);
    $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

    return array_values(array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), array_filter($rows, static fn (array $row): bool => (int) ($row['id'] ?? 0) > 0)));
}

function asset_cash_usage_table_exists(PDO $db, string $tableName): bool
{
    try {
        $stmt = $db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name LIMIT 1');
        $stmt->bindValue(':table_name', $tableName, PDO::PARAM_STR);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        return false;
    }
}

function asset_cash_usage_column_exists(PDO $db, string $tableName, string $columnName): bool
{
    try {
        $stmt = $db->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name LIMIT 1');
        $stmt->bindValue(':table_name', $tableName, PDO::PARAM_STR);
        $stmt->bindValue(':column_name', $columnName, PDO::PARAM_STR);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        return false;
    }
}
