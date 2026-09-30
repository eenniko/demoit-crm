<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Client support tickets (doc 01 §4 "Tugi"). */
class SupportService
{
    public static function listForClient(int $clientId): array
    {
        $stmt = db()->prepare(
            'SELECT t.*, u.username AS created_by_username
             FROM system_support_tickets t
             INNER JOIN system_users u ON u.id = t.created_by_user_id
             WHERE t.client_id = :client_id
             ORDER BY t.created_at DESC'
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    public static function listAllOpen(): array
    {
        $stmt = db()->query(
            "SELECT t.*, u.username AS created_by_username, c.client_code
             FROM system_support_tickets t
             INNER JOIN system_users u ON u.id = t.created_by_user_id
             INNER JOIN system_clients c ON c.id = t.client_id
             WHERE t.status != 'closed'
             ORDER BY t.created_at DESC"
        );

        return $stmt->fetchAll();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function create(int $clientId, int $userId, array $data): array
    {
        $subject = trim((string) ($data['subject'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($subject === '' || $message === '') {
            return [false, 'Subject and message are required.'];
        }

        $insert = db()->prepare(
            'INSERT INTO system_support_tickets (client_id, created_by_user_id, subject, message, status)
             VALUES (:client_id, :user_id, :subject, :message, \'open\')'
        );
        $insert->execute(['client_id' => $clientId, 'user_id' => $userId, 'subject' => $subject, 'message' => $message]);

        AuditLogService::log($userId, $clientId, 'support_ticket.created', 'system_support_tickets', (string) db()->lastInsertId());

        return [true, 'Support ticket submitted.'];
    }

    public static function updateStatus(int $ticketId, string $status, int $actorUserId): void
    {
        if (!in_array($status, ['open', 'in_progress', 'closed'], true)) {
            return;
        }

        $stmt = db()->prepare('SELECT client_id FROM system_support_tickets WHERE id = :id');
        $stmt->execute(['id' => $ticketId]);
        $clientId = $stmt->fetchColumn();

        $update = db()->prepare('UPDATE system_support_tickets SET status = :status WHERE id = :id');
        $update->execute(['status' => $status, 'id' => $ticketId]);

        AuditLogService::log($actorUserId, $clientId !== false ? (int) $clientId : null, 'support_ticket.status_changed', 'system_support_tickets', (string) $ticketId, null, $status);
    }
}
