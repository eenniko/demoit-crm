<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Notifications: targeted at one user, or broadcast to an entire client (doc 01 §5 "Teavitused"). */
class NotificationService
{
    public static function listForUser(int $clientId, int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT * FROM system_notifications
             WHERE client_id = :client_id AND (user_id = :user_id OR user_id IS NULL)
             ORDER BY created_at DESC
             LIMIT 50'
        );
        $stmt->execute(['client_id' => $clientId, 'user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function unreadCount(int $clientId, int $userId): int
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM system_notifications
             WHERE client_id = :client_id AND (user_id = :user_id OR user_id IS NULL) AND is_read = 0"
        );
        $stmt->execute(['client_id' => $clientId, 'user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public static function markRead(int $notificationId, int $userId): void
    {
        // Broadcast notifications (user_id IS NULL) are marked read only for the targeted-copy pattern is out of scope;
        // here we only allow marking a user's own targeted notification as read.
        $stmt = db()->prepare('UPDATE system_notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $notificationId, 'user_id' => $userId]);
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
