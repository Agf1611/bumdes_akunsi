<?php

declare(strict_types=1);

final class AuditLogModel
{
    private const MAX_ACTIVITY_ROWS = 100;

    public function __construct(private PDO $db)
    {
    }

    public function getList(array $filters = []): array
    {
        $sql = 'SELECT * FROM audit_logs WHERE 1=1';
        $params = [];

        if (($filters['module_name'] ?? '') !== '') {
            $sql .= ' AND module_name = :module_name';
            $params[':module_name'] = (string) $filters['module_name'];
        }
        if (($filters['action_name'] ?? '') !== '') {
            $sql .= ' AND action_name = :action_name';
            $params[':action_name'] = (string) $filters['action_name'];
        }
        if (($filters['severity_level'] ?? '') !== '') {
            $sql .= ' AND severity_level = :severity_level';
            $params[':severity_level'] = (string) $filters['severity_level'];
        }
        if (($filters['username'] ?? '') !== '') {
            $sql .= ' AND username LIKE :username';
            $params[':username'] = '%' . (string) $filters['username'] . '%';
        }
        if (($filters['date_from'] ?? '') !== '') {
            $sql .= ' AND DATE(created_at) >= :date_from';
            $params[':date_from'] = (string) $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $sql .= ' AND DATE(created_at) <= :date_to';
            $params[':date_to'] = (string) $filters['date_to'];
        }

        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . self::MAX_ACTIVITY_ROWS;
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getModuleOptions(): array
    {
        $stmt = $this->db->query('SELECT DISTINCT module_name FROM audit_logs WHERE module_name <> "" ORDER BY module_name ASC');
        return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
    }

    public function getActionOptions(): array
    {
        $stmt = $this->db->query('SELECT DISTINCT action_name FROM audit_logs WHERE action_name <> "" ORDER BY action_name ASC');
        return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
    }

    public function getSummary(array $filters = []): array
    {
        $rows = $this->getList($filters);
        $summary = [
            'total' => count($rows),
            'warning' => 0,
            'danger' => 0,
            'today' => 0,
        ];
        $today = date('Y-m-d');
        foreach ($rows as $row) {
            $severity = (string) ($row['severity_level'] ?? 'info');
            if ($severity === 'warning') {
                $summary['warning']++;
            }
            if ($severity === 'danger') {
                $summary['danger']++;
            }
            if (str_starts_with((string) ($row['created_at'] ?? ''), $today)) {
                $summary['today']++;
            }
        }

        return $summary;
    }

    public function maxRows(): int
    {
        return self::MAX_ACTIVITY_ROWS;
    }
}
