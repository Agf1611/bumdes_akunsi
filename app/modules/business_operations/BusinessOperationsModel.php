<?php

declare(strict_types=1);

final class BusinessOperationsModel
{
    public function __construct(private PDO $db)
    {
    }

    public function isReady(): bool
    {
        foreach (['business_employees', 'business_activities', 'business_budgets', 'budget_rabs', 'budget_rab_items'] as $table) {
            if (!$this->tableExists($table)) {
                return false;
            }
        }
        return true;
    }

    public function units(): array
    {
        return business_unit_options(true);
    }

    public function accounts(): array
    {
        if (!$this->tableExists('coa_accounts')) {
            return [];
        }
        $stmt = $this->db->query("SELECT id, account_code, account_name, account_type FROM coa_accounts WHERE is_header = 0 AND is_active = 1 ORDER BY account_code ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listEmployees(array $filters): array
    {
        $sql = 'SELECT e.*, bu.unit_code, bu.unit_name
                FROM business_employees e
                LEFT JOIN business_units bu ON bu.id = e.business_unit_id
                WHERE 1=1';
        [$sql, $params] = $this->applyCommonFilters($sql, $filters, 'e', ['employee_name', 'position_title', 'phone', 'email', 'notes']);
        $sql .= ' ORDER BY e.status ASC, e.employee_name ASC, e.id DESC';
        return $this->fetchAll($sql, $params);
    }

    public function listActivities(array $filters): array
    {
        $sql = 'SELECT a.*, bu.unit_code, bu.unit_name
                FROM business_activities a
                LEFT JOIN business_units bu ON bu.id = a.business_unit_id
                WHERE 1=1';
        [$sql, $params] = $this->applyCommonFilters($sql, $filters, 'a', ['activity_name', 'activity_type', 'target_period', 'notes']);
        $sql .= ' ORDER BY a.status ASC, a.updated_at DESC, a.id DESC';
        return $this->fetchAll($sql, $params);
    }

    public function listBudgets(array $filters): array
    {
        $sql = 'SELECT b.*, bu.unit_code, bu.unit_name, ca.account_code, ca.account_name
                FROM business_budgets b
                LEFT JOIN business_units bu ON bu.id = b.business_unit_id
                LEFT JOIN coa_accounts ca ON ca.id = b.account_id
                WHERE 1=1';
        [$sql, $params] = $this->applyCommonFilters($sql, $filters, 'b', ['category', 'notes']);
        if ((int) ($filters['year'] ?? 0) > 0) {
            $sql .= ' AND b.budget_year = :year';
            $params[':year'] = (int) $filters['year'];
        }
        $sql .= ' ORDER BY b.budget_year DESC, COALESCE(b.budget_month, 0) DESC, b.budget_type ASC, b.category ASC';
        return $this->fetchAll($sql, $params);
    }

    public function listPlans(array $filters): array
    {
        $sql = 'SELECT p.*, bu.unit_code, bu.unit_name, COUNT(i.id) AS item_count
                FROM budget_rabs p
                LEFT JOIN business_units bu ON bu.id = p.business_unit_id
                LEFT JOIN budget_rab_items i ON i.budget_rab_id = p.id
                WHERE 1=1';
        [$sql, $params] = $this->applyCommonFilters($sql, $filters, 'p', ['plan_no', 'plan_title', 'activity_name', 'notes']);
        if ((int) ($filters['year'] ?? 0) > 0) {
            $sql .= ' AND YEAR(p.plan_date) = :year';
            $params[':year'] = (int) $filters['year'];
        }
        $sql .= ' GROUP BY p.id ORDER BY p.plan_date DESC, p.id DESC';
        return $this->fetchAll($sql, $params);
    }

    public function find(string $type, int $id): ?array
    {
        $table = $this->tableFor($type);
        $stmt = $this->db->prepare('SELECT * FROM ' . $table . ' WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function planItems(int $planId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM budget_rab_items WHERE budget_rab_id = :id ORDER BY id ASC');
        $stmt->bindValue(':id', $planId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveEmployee(?int $id, array $data): int
    {
        if ($id === null) {
            $sql = 'INSERT INTO business_employees (business_unit_id, employee_name, position_title, phone, email, status, notes, created_at, updated_at)
                    VALUES (:business_unit_id, :employee_name, :position_title, :phone, :email, :status, :notes, NOW(), NOW())';
        } else {
            $sql = 'UPDATE business_employees SET business_unit_id = :business_unit_id, employee_name = :employee_name,
                    position_title = :position_title, phone = :phone, email = :email, status = :status, notes = :notes, updated_at = NOW()
                    WHERE id = :id';
        }
        $stmt = $this->db->prepare($sql);
        $this->bindNullableInt($stmt, ':business_unit_id', $data['business_unit_id']);
        $stmt->bindValue(':employee_name', $data['employee_name'], PDO::PARAM_STR);
        $stmt->bindValue(':position_title', $data['position_title'], PDO::PARAM_STR);
        $stmt->bindValue(':phone', $data['phone'], PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
        $stmt->bindValue(':notes', $data['notes'], PDO::PARAM_STR);
        if ($id !== null) {
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $id ?? (int) $this->db->lastInsertId();
    }

    public function saveActivity(?int $id, array $data): int
    {
        if ($id === null) {
            $sql = 'INSERT INTO business_activities (business_unit_id, activity_name, activity_type, target_period, target_value, status, notes, created_at, updated_at)
                    VALUES (:business_unit_id, :activity_name, :activity_type, :target_period, :target_value, :status, :notes, NOW(), NOW())';
        } else {
            $sql = 'UPDATE business_activities SET business_unit_id = :business_unit_id, activity_name = :activity_name,
                    activity_type = :activity_type, target_period = :target_period, target_value = :target_value,
                    status = :status, notes = :notes, updated_at = NOW() WHERE id = :id';
        }
        $stmt = $this->db->prepare($sql);
        $this->bindNullableInt($stmt, ':business_unit_id', $data['business_unit_id']);
        $stmt->bindValue(':activity_name', $data['activity_name'], PDO::PARAM_STR);
        $stmt->bindValue(':activity_type', $data['activity_type'], PDO::PARAM_STR);
        $stmt->bindValue(':target_period', $data['target_period'], PDO::PARAM_STR);
        $stmt->bindValue(':target_value', $data['target_value'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
        $stmt->bindValue(':notes', $data['notes'], PDO::PARAM_STR);
        if ($id !== null) {
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $id ?? (int) $this->db->lastInsertId();
    }

    public function saveBudget(?int $id, array $data): int
    {
        if ($id === null) {
            $sql = 'INSERT INTO business_budgets (business_unit_id, budget_year, budget_month, budget_type, category, account_id, amount, status, notes, created_at, updated_at)
                    VALUES (:business_unit_id, :budget_year, :budget_month, :budget_type, :category, :account_id, :amount, :status, :notes, NOW(), NOW())';
        } else {
            $sql = 'UPDATE business_budgets SET business_unit_id = :business_unit_id, budget_year = :budget_year,
                    budget_month = :budget_month, budget_type = :budget_type, category = :category, account_id = :account_id,
                    amount = :amount, status = :status, notes = :notes, updated_at = NOW() WHERE id = :id';
        }
        $stmt = $this->db->prepare($sql);
        $this->bindNullableInt($stmt, ':business_unit_id', $data['business_unit_id']);
        $stmt->bindValue(':budget_year', $data['budget_year'], PDO::PARAM_INT);
        $this->bindNullableInt($stmt, ':budget_month', $data['budget_month']);
        $stmt->bindValue(':budget_type', $data['budget_type'], PDO::PARAM_STR);
        $stmt->bindValue(':category', $data['category'], PDO::PARAM_STR);
        $this->bindNullableInt($stmt, ':account_id', $data['account_id']);
        $stmt->bindValue(':amount', $data['amount'], PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
        $stmt->bindValue(':notes', $data['notes'], PDO::PARAM_STR);
        if ($id !== null) {
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $id ?? (int) $this->db->lastInsertId();
    }

    public function savePlan(?int $id, array $data, array $items): int
    {
        $this->db->beginTransaction();
        try {
            $total = 0.0;
            foreach ($items as $item) {
                $total += (float) $item['total_amount'];
            }

            if ($id === null) {
                $sql = 'INSERT INTO budget_rabs (business_unit_id, plan_no, plan_date, plan_title, activity_name, status, notes, total_amount, created_at, updated_at)
                        VALUES (:business_unit_id, :plan_no, :plan_date, :plan_title, :activity_name, :status, :notes, :total_amount, NOW(), NOW())';
            } else {
                $sql = 'UPDATE budget_rabs SET business_unit_id = :business_unit_id, plan_no = :plan_no,
                        plan_date = :plan_date, plan_title = :plan_title, activity_name = :activity_name,
                        status = :status, notes = :notes, total_amount = :total_amount, updated_at = NOW() WHERE id = :id';
            }
            $stmt = $this->db->prepare($sql);
            $this->bindNullableInt($stmt, ':business_unit_id', $data['business_unit_id']);
            $stmt->bindValue(':plan_no', $data['plan_no'], PDO::PARAM_STR);
            $stmt->bindValue(':plan_date', $data['plan_date'], PDO::PARAM_STR);
            $stmt->bindValue(':plan_title', $data['plan_title'], PDO::PARAM_STR);
            $stmt->bindValue(':activity_name', $data['activity_name'], PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
            $stmt->bindValue(':notes', $data['notes'], PDO::PARAM_STR);
            $stmt->bindValue(':total_amount', (string) $total, PDO::PARAM_STR);
            if ($id !== null) {
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            }
            $stmt->execute();
            $planId = $id ?? (int) $this->db->lastInsertId();

            $delete = $this->db->prepare('DELETE FROM budget_rab_items WHERE budget_rab_id = :id');
            $delete->bindValue(':id', $planId, PDO::PARAM_INT);
            $delete->execute();

            $insert = $this->db->prepare('INSERT INTO budget_rab_items (budget_rab_id, item_name, quantity, unit_name, unit_price, total_amount, notes)
                VALUES (:budget_rab_id, :item_name, :quantity, :unit_name, :unit_price, :total_amount, :notes)');
            foreach ($items as $item) {
                $insert->bindValue(':budget_rab_id', $planId, PDO::PARAM_INT);
                $insert->bindValue(':item_name', $item['item_name'], PDO::PARAM_STR);
                $insert->bindValue(':quantity', $item['quantity'], PDO::PARAM_STR);
                $insert->bindValue(':unit_name', $item['unit_name'], PDO::PARAM_STR);
                $insert->bindValue(':unit_price', $item['unit_price'], PDO::PARAM_STR);
                $insert->bindValue(':total_amount', $item['total_amount'], PDO::PARAM_STR);
                $insert->bindValue(':notes', $item['notes'], PDO::PARAM_STR);
                $insert->execute();
            }

            $this->db->commit();
            return $planId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function delete(string $type, int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM ' . $this->tableFor($type) . ' WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function nextPlanNo(): string
    {
        $prefix = 'RAB/' . date('Y') . '/' . date('m') . '/';
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM budget_rabs WHERE plan_no LIKE :prefix');
        $stmt->bindValue(':prefix', $prefix . '%', PDO::PARAM_STR);
        $stmt->execute();
        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
    }

    public function report(array $filters): array
    {
        $year = (int) ($filters['year'] ?? date('Y'));
        $month = (int) ($filters['month'] ?? 0);
        $unitId = (int) ($filters['unit_id'] ?? 0);

        $budgetSql = 'SELECT budget_type, SUM(amount) AS total FROM business_budgets WHERE budget_year = :year';
        $params = [':year' => $year];
        if ($month > 0) {
            $budgetSql .= ' AND (budget_month IS NULL OR budget_month = :month)';
            $params[':month'] = $month;
        }
        if ($unitId > 0) {
            $budgetSql .= ' AND business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }
        $budgetSql .= " AND status <> 'CLOSED' GROUP BY budget_type";
        $budgets = $this->fetchAll($budgetSql, $params);

        $planSql = 'SELECT COUNT(*) AS count_rows, COALESCE(SUM(total_amount), 0) AS total FROM budget_rabs WHERE YEAR(plan_date) = :year';
        $planParams = [':year' => $year];
        if ($month > 0) {
            $planSql .= ' AND MONTH(plan_date) = :month';
            $planParams[':month'] = $month;
        }
        if ($unitId > 0) {
            $planSql .= ' AND business_unit_id = :unit_id';
            $planParams[':unit_id'] = $unitId;
        }
        $planStmt = $this->db->prepare($planSql);
        foreach ($planParams as $key => $value) {
            $planStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $planStmt->execute();
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC) ?: ['count_rows' => 0, 'total' => 0];

        $realizationSql = "SELECT COALESCE(SUM(CASE
                    WHEN ca.account_type = 'EXPENSE' THEN jl.debit
                    WHEN ca.account_type = 'ASSET' AND ca.account_category = 'FIXED_ASSET' THEN jl.debit
                    ELSE 0
                END), 0) AS total
                FROM journal_lines jl
                INNER JOIN journal_headers jh ON jh.id = jl.journal_id
                INNER JOIN coa_accounts ca ON ca.id = jl.coa_id
                WHERE YEAR(jh.journal_date) = :year";
        $realParams = [':year' => $year];
        if ($month > 0) {
            $realizationSql .= ' AND MONTH(jh.journal_date) = :month';
            $realParams[':month'] = $month;
        }
        if ($unitId > 0) {
            $realizationSql .= ' AND jh.business_unit_id = :unit_id';
            $realParams[':unit_id'] = $unitId;
        }
        $realStmt = $this->db->prepare($realizationSql);
        foreach ($realParams as $key => $value) {
            $realStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $realStmt->execute();
        $realization = (float) $realStmt->fetchColumn();

        return [
            'budgets' => $budgets,
            'plan' => $plan,
            'realization' => $realization,
            'plans' => $this->listPlans($filters),
        ];
    }

    private function applyCommonFilters(string $sql, array $filters, string $alias, array $searchColumns): array
    {
        $params = [];
        $unitId = (int) ($filters['unit_id'] ?? 0);
        if ($unitId > 0) {
            $sql .= ' AND ' . $alias . '.business_unit_id = :unit_id';
            $params[':unit_id'] = $unitId;
        }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $parts = [];
            foreach ($searchColumns as $column) {
                $parts[] = $alias . '.' . $column . ' LIKE :search';
            }
            $sql .= ' AND (' . implode(' OR ', $parts) . ')';
            $params[':search'] = '%' . $search . '%';
        }
        return [$sql, $params];
    }

    private function fetchAll(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function tableFor(string $type): string
    {
        return match ($type) {
            'employees' => 'business_employees',
            'business' => 'business_activities',
            'budgets' => 'business_budgets',
            'budget_plans' => 'budget_rabs',
            default => throw new InvalidArgumentException('Jenis data tidak valid.'),
        };
    }

    private function bindNullableInt(PDOStatement $stmt, string $key, mixed $value): void
    {
        $int = (int) $value;
        if ($int <= 0) {
            $stmt->bindValue($key, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($key, $int, PDO::PARAM_INT);
    }

    private function tableExists(string $tableName): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
        $stmt->bindValue(':table', $tableName, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }
}
