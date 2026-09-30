<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

/** Central audit logging service. Every write must go through here (doc 04 §9). */
class AuditLogService
{
    public static function log(
        ?int $userId,
        ?int $clientId,
        string $action,
        ?string $objectType = null,
        ?string $objectId = null,
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        $stmt = db()->prepare(
            'INSERT INTO system_audit_logs
                (user_id, client_id, action, object_type, object_id, old_value, new_value, ip_address, user_agent, created_at)
             VALUES
                (:user_id, :client_id, :action, :object_type, :object_id, :old_value, :new_value, :ip_address, :user_agent, NOW())'
        );

        $stmt->execute([
            'user_id' => $userId,
            'client_id' => $clientId,
            'action' => $action,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    /**
     * Recent audit log entries with optional filters (doc 02 §10, doc 01 §5 "Süsteemi logid").
     * @param array{action?: string, client_code?: string, username?: string} $filters
     */
    public static function listRecent(array $filters = [], int $limit = 100): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['action'])) {
            $conditions[] = 'l.action LIKE :action';
            $params['action'] = '%' . $filters['action'] . '%';
        }

        if (!empty($filters['client_code'])) {
            $conditions[] = 'c.client_code = :client_code';
            $params['client_code'] = $filters['client_code'];
        }

        if (!empty($filters['username'])) {
            $conditions[] = 'u.username LIKE :username';
            $params['username'] = '%' . $filters['username'] . '%';
        }

        $where = $conditions === [] ? '' : ('WHERE ' . implode(' AND ', $conditions));

        $stmt = db()->prepare(
            "SELECT l.id, l.action, l.object_type, l.object_id, l.old_value, l.new_value,
                    l.ip_address, l.created_at,
                    u.username, c.client_code
             FROM system_audit_logs l
             LEFT JOIN system_users u ON u.id = l.user_id
             LEFT JOIN system_clients c ON c.id = l.client_id
             {$where}
             ORDER BY l.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
