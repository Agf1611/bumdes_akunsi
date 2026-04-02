<?php

declare(strict_types=1);

function audit_log(string $module, string $action, string $description, array $options = []): void
{
    try {
        if (!Database::isConnected(db_config()) || !audit_logs_table_exists()) {
            return;
        }

        $pdo = Database::getInstance(db_config());
        $user = audit_actor($options);
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (
                user_id, username, full_name, module_name, action_name,
                entity_type, entity_id, severity_level, description,
                before_data, after_data, context_data,
                ip_address, user_agent, created_at
            ) VALUES (
                :user_id, :username, :full_name, :module_name, :action_name,
                :entity_type, :entity_id, :severity_level, :description,
                :before_data, :after_data, :context_data,
                :ip_address, :user_agent, NOW()
            )'
        );

        $stmt->bindValue(':user_id', $user['id'] > 0 ? $user['id'] : null, $user['id'] > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':username', $user['username'], PDO::PARAM_STR);
        $stmt->bindValue(':full_name', $user['full_name'], PDO::PARAM_STR);
        $stmt->bindValue(':module_name', audit_trim($module, 60), PDO::PARAM_STR);
        $stmt->bindValue(':action_name', audit_trim($action, 60), PDO::PARAM_STR);
        $stmt->bindValue(':entity_type', audit_trim((string) ($options['entity_type'] ?? ''), 60), PDO::PARAM_STR);
        $stmt->bindValue(':entity_id', audit_trim((string) ($options['entity_id'] ?? ''), 80), PDO::PARAM_STR);
        $stmt->bindValue(':severity_level', audit_normalize_severity((string) ($options['severity'] ?? 'info')), PDO::PARAM_STR);
        $stmt->bindValue(':description', audit_trim($description, 255), PDO::PARAM_STR);
        $stmt->bindValue(':before_data', audit_json_encode($options['before'] ?? null), PDO::PARAM_STR);
        $stmt->bindValue(':after_data', audit_json_encode($options['after'] ?? null), PDO::PARAM_STR);
        $stmt->bindValue(':context_data', audit_json_encode($options['context'] ?? null), PDO::PARAM_STR);
        $stmt->bindValue(':ip_address', audit_trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 45), PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', audit_trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 255), PDO::PARAM_STR);
        $stmt->execute();
    } catch (Throwable $e) {
        log_error($e);
    }
}

function audit_logs_table_exists(): bool
{
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    try {
        if (!Database::isConnected(db_config())) {
            $exists = false;
            return $exists;
        }

        $pdo = Database::getInstance(db_config());
        $stmt = $pdo->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' LIMIT 1");
        $exists = (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        $exists = false;
    }

    return $exists;
}

function audit_actor(array $options = []): array
{
    $overrideId = (int) ($options['user_id'] ?? 0);
    $overrideUsername = trim((string) ($options['username'] ?? ''));
    $overrideFullName = trim((string) ($options['full_name'] ?? ''));

    $authUser = Auth::user();
    return [
        'id' => $overrideId > 0 ? $overrideId : (int) ($authUser['id'] ?? 0),
        'username' => $overrideUsername !== '' ? $overrideUsername : (string) ($authUser['username'] ?? ''),
        'full_name' => $overrideFullName !== '' ? $overrideFullName : (string) ($authUser['full_name'] ?? ''),
    ];
}

function audit_json_encode(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }

    $sanitized = audit_sanitize_payload($value);
    $json = json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return is_string($json) ? $json : null;
}

function audit_sanitize_payload(mixed $value): mixed
{
    if (!is_array($value)) {
        return $value;
    }

    $maskedKeys = [
        'password',
        'password_hash',
        'password_confirmation',
        '_token',
        'csrf_token',
        'remember_token',
        'token',
    ];

    $result = [];
    foreach ($value as $key => $item) {
        $keyString = is_string($key) ? $key : (string) $key;
        if (in_array($keyString, $maskedKeys, true)) {
            $result[$key] = '[MASKED]';
            continue;
        }
        $result[$key] = is_array($item) ? audit_sanitize_payload($item) : $item;
    }

    return $result;
}

function audit_trim(string $value, int $limit): string
{
    $value = trim($value);
    if (mb_strlen($value) <= $limit) {
        return $value;
    }

    return mb_substr($value, 0, max(0, $limit - 1)) . '...';
}

function audit_normalize_severity(string $value): string
{
    $value = strtolower(trim($value));
    return in_array($value, ['info', 'warning', 'danger'], true) ? $value : 'info';
}

function audit_decode_json(?string $json): mixed
{
    $json = trim((string) $json);
    if ($json === '') {
        return null;
    }

    try {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return $json;
    }
}

function audit_badge_class(string $severity): string
{
    return match (audit_normalize_severity($severity)) {
        'warning' => 'text-bg-warning text-dark',
        'danger' => 'text-bg-danger',
        default => 'text-bg-info text-dark',
    };
}

function audit_datetime(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '-';
    }

    try {
        $dt = new DateTimeImmutable($value);
        return $dt->format('d-m-Y H:i:s');
    } catch (Throwable) {
        return $value;
    }
}
