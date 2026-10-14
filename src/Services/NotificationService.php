<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Notifications: targeted at one user, or broadcast to an entire client (doc 01 §5 "Teavitused"). */
class NotificationService
{
    public static function listForUser(int $clientId, int $userId, bool $archive = false, int $page = 1): array
    {
        if ($page < 1 || $page > 100000) {
            throw new InvalidArgumentException('Invalid notification page.');
        }
        $offset = ($page - 1) * 50;
        $comparison = $archive ? '<' : '>=';
        $stmt = db()->prepare(
            "SELECT n.*, CASE WHEN r.user_id IS NOT NULL OR (n.user_id IS NOT NULL AND n.is_read = 1)
                        THEN 1 ELSE 0 END AS is_read
             FROM system_notifications n
             LEFT JOIN system_notification_reads r ON r.notification_id = n.id AND r.user_id = :reader
             WHERE n.client_id = :client_id AND (n.user_id = :user_id OR n.user_id IS NULL)
               AND n.created_at {$comparison} :cutoff AND n.created_at <= :now
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT 51 OFFSET {$offset}"
        );
        $now = new DateTimeImmutable('now');
        $stmt->execute(['client_id' => $clientId, 'user_id' => $userId, 'reader' => $userId,
            'cutoff' => $now->modify('-30 days')->format('Y-m-d H:i:s'), 'now' => $now->format('Y-m-d H:i:s')]);

        return $stmt->fetchAll();
    }

    public static function unreadCount(int $clientId, int $userId): int
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM system_notifications n
             WHERE n.client_id = :client_id AND (n.user_id = :user_id OR n.user_id IS NULL)
               AND (n.user_id IS NULL OR n.is_read = 0)
               AND n.created_at >= :cutoff AND n.created_at <= :now
               AND NOT EXISTS (SELECT 1 FROM system_notification_reads r
                               WHERE r.notification_id = n.id AND r.user_id = :reader)"
        );
        $now = new DateTimeImmutable('now');
        $stmt->execute(['client_id' => $clientId, 'user_id' => $userId, 'reader' => $userId,
            'cutoff' => $now->sub(new DateInterval('PT24H'))->format('Y-m-d H:i:s'), 'now' => $now->format('Y-m-d H:i:s')]);

        return (int) $stmt->fetchColumn();
    }

    public static function markRead(int $notificationId, int $userId, int $clientId): bool
    {
        $stmt = db()->prepare(
            'SELECT id FROM system_notifications
             WHERE id = :id AND client_id = :client AND (user_id = :user OR user_id IS NULL)'
        );
        $stmt->execute(['id' => $notificationId, 'client' => $clientId, 'user' => $userId]);
        if ($stmt->fetchColumn() === false) {
            return false;
        }
        $stmt = db()->prepare(
            'INSERT INTO system_notification_reads (notification_id, user_id, read_at)
             VALUES (:notification, :user, :read_at)
             ON DUPLICATE KEY UPDATE read_at = read_at'
        );
        $stmt->execute(['notification' => $notificationId, 'user' => $userId, 'read_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function send(int $clientId, array $data, int $actorUserId): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        $userId = !empty($data['user_id']) ? (int) $data['user_id'] : null;

        if ($title === '' || $message === '') {
            return [false, 'Title and message are required.'];
        }

        $insert = db()->prepare(
            'INSERT INTO system_notifications (client_id, user_id, title, message, created_by)
             VALUES (:client_id, :user_id, :title, :message, :created_by)'
        );
        $insert->execute([
            'client_id' => $clientId,
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'created_by' => $actorUserId,
        ]);

        AuditLogService::log($actorUserId, $clientId, 'notification.sent', 'system_notifications', (string) db()->lastInsertId());

        return [true, 'Notification sent.'];
    }

    public static function listSentForClient(int $clientId): array
    {
        $stmt = db()->prepare('SELECT * FROM system_notifications WHERE client_id = :client_id ORDER BY created_at DESC LIMIT 50');
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }
}
