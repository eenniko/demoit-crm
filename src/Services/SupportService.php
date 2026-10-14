<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';
require_once __DIR__ . '/RoleService.php';

class SupportService
{
    private const SYSTEM_ROLES = ['system_admin', 'developer', 'client_manager', 'client_support'];
    private const SELECT_TICKET = "SELECT t.*, ";
    private const TICKET_DETAILS = "COALESCE(NULLIF(TRIM(u.full_name), ''), u.username) AS creator_name,
               COALESCE(NULLIF(TRIM(a.full_name), ''), a.username) AS recipient_name, c.client_code
        FROM system_support_tickets t
        INNER JOIN system_users u ON u.id = t.created_by_user_id
        INNER JOIN system_clients c ON c.id = t.client_id
        LEFT JOIN system_users a ON a.id = t.assigned_to_user_id";

    private static function isSystemResponder(int $userId, int $clientId): bool
    {
        return RoleService::hasAnyRole($userId, $clientId, self::SYSTEM_ROLES);
    }

    private static function activeUser(int $userId, int $clientId): bool
    {
        $stmt = db()->prepare("SELECT 1 FROM system_users WHERE id = :id AND client_id = :client AND status = 'active'");
        $stmt->execute(['id' => $userId, 'client' => $clientId]);
        return $stmt->fetchColumn() !== false;
    }

    private static function accessCondition(int $userId, int $clientId, bool $systemWorkspace, array &$params): string
    {
        $params = ['creator' => $userId, 'recipient' => $userId, 'participant' => $userId];
        $access = "(t.created_by_user_id = :creator OR t.assigned_to_user_id = :recipient
            OR EXISTS (SELECT 1 FROM system_support_participants p WHERE p.ticket_id = t.id AND p.user_id = :participant)";
        if (self::isSystemResponder($userId, $clientId)) {
            $access .= " OR t.recipient_type = 'system'";
        }
        $access .= ')';
        if (!$systemWorkspace) {
            $params['client'] = $clientId;
            $access .= ' AND t.client_id = :client';
        }
        return $access;
    }

    /** @return array{open: array, closed: array} */
    public static function listVisible(int $clientId, int $userId, bool $systemWorkspace = false): array
    {
        if (!self::activeUser($userId, $clientId)
            || ($systemWorkspace && !self::isSystemResponder($userId, $clientId))) {
            throw new RuntimeException('Support ticket access denied.');
        }
        $params = [];
        $where = self::accessCondition($userId, $clientId, $systemWorkspace, $params);
        $unread = self::unreadCondition($userId, $params);
        $unreadReply = self::unreadReplyCondition($userId, $params, 'list_reply');
        $lists = [];
        foreach (['open', 'closed'] as $segment) {
            $status = $segment === 'closed' ? "= 'closed'" : "!= 'closed'";
            $order = $segment === 'closed' ? 't.closed_at DESC, t.id DESC LIMIT 10' : 't.created_at DESC, t.id DESC';
            $stmt = db()->prepare(self::SELECT_TICKET
                . "CASE WHEN {$unread} THEN 1 ELSE 0 END AS is_unread,
                   CASE WHEN t.status != 'closed' AND {$unreadReply} THEN 1 ELSE 0 END AS has_unread_reply, "
                . self::TICKET_DETAILS . " WHERE {$where} AND t.status {$status} ORDER BY {$order}");
            $stmt->execute($params);
            $lists[$segment] = $stmt->fetchAll();
        }
        return $lists;
    }

    public static function unreadCount(int $clientId, int $userId, bool $systemWorkspace = false): int
    {
        if (!self::activeUser($userId, $clientId)
            || ($systemWorkspace && !self::isSystemResponder($userId, $clientId))) {
            throw new RuntimeException('Support ticket access denied.');
        }
        $params = [];
        $where = self::accessCondition($userId, $clientId, $systemWorkspace, $params);
        $unread = self::unreadCondition($userId, $params);
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM system_support_tickets t
             WHERE {$where} AND {$unread}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private static function unreadCondition(int $userId, array &$params): string
    {
        $params['author'] = $userId;
        $params['reader'] = $userId;
        $reply = self::unreadReplyCondition($userId, $params, 'reply');
        return "t.status != 'closed'
               AND ((t.created_by_user_id != :author
                     AND NOT EXISTS (SELECT 1 FROM system_support_reads r
                                     WHERE r.ticket_id = t.id AND r.user_id = :reader))
                    OR {$reply})";
    }

    private static function unreadReplyCondition(int $userId, array &$params, string $prefix): string
    {
        $params[$prefix . '_author'] = $userId;
        $params[$prefix . '_reader'] = $userId;
        return "EXISTS (SELECT 1 FROM system_support_replies reply
                               WHERE reply.ticket_id = t.id AND reply.user_id != :{$prefix}_author
                                 AND reply.id > COALESCE(
                                     (SELECT rr.last_reply_id FROM system_support_reply_reads rr
                                      WHERE rr.ticket_id = t.id AND rr.user_id = :{$prefix}_reader), 0))";
    }

    public static function markOpened(int $ticketId, int $clientId, int $userId, bool $systemWorkspace = false, int $lastReplyId = 0): bool
    {
        if (self::ticket($ticketId, $clientId, $userId, $systemWorkspace) === null) {
            return false;
        }
        $stmt = db()->prepare(
            'INSERT INTO system_support_reads (ticket_id, user_id, opened_at)
             VALUES (:ticket, :user, :opened)
             ON DUPLICATE KEY UPDATE opened_at = opened_at'
        );
        $stmt->execute(['ticket' => $ticketId, 'user' => $userId, 'opened' => date('Y-m-d H:i:s')]);
        self::recordReplyRead($ticketId, $userId, $lastReplyId);
        return true;
    }

    private static function recordReplyRead(int $ticketId, int $userId, int $lastReplyId): void
    {
        $stmt = db()->prepare(
            'INSERT INTO system_support_reply_reads (ticket_id, user_id, last_reply_id)
             VALUES (:ticket, :user, :reply)
             ON DUPLICATE KEY UPDATE last_reply_id = GREATEST(last_reply_id, VALUES(last_reply_id))'
        );
        $stmt->execute(['ticket' => $ticketId, 'user' => $userId, 'reply' => $lastReplyId]);
    }

    public static function ticket(int $ticketId, int $clientId, int $userId, bool $systemWorkspace = false, bool $lock = false): ?array
    {
        if (!self::activeUser($userId, $clientId)
            || ($systemWorkspace && !self::isSystemResponder($userId, $clientId))) {
            return null;
        }
        $params = [];
        $where = self::accessCondition($userId, $clientId, $systemWorkspace, $params);
        $params['id'] = $ticketId;
        $stmt = db()->prepare(self::SELECT_TICKET . self::TICKET_DETAILS . " WHERE t.id = :id AND {$where}" . ($lock ? ' FOR UPDATE' : ''));
        $stmt->execute($params);
        $ticket = $stmt->fetch();
        if ($ticket === false) {
            return null;
        }
        $ticket['can_manage'] = (int) $ticket['created_by_user_id'] === $userId
            || (int) $ticket['assigned_to_user_id'] === $userId
            || ($ticket['recipient_type'] === 'system' && self::isSystemResponder($userId, $clientId));
        return $ticket;
    }

    /** Managers are offered only from currently valid primary/temporary contracts. */
    public static function recipientOptions(int $clientId, int $userId): array
    {
        $stmt = db()->prepare(
            "SELECT DISTINCT u.id, COALESCE(NULLIF(TRIM(u.full_name), ''), u.username) AS name
             FROM system_users u
             INNER JOIN system_user_roles ur ON ur.user_id = u.id AND ur.client_id = u.client_id AND ur.status = 'active'
             INNER JOIN system_roles r ON r.id = ur.role_id AND r.role_key = 'client_admin'
             WHERE u.client_id = :client AND u.status = 'active' ORDER BY name, u.id"
        );
        $stmt->execute(['client' => $clientId]);
        $admins = $stmt->fetchAll();
        $stmt = db()->prepare(
            "SELECT DISTINCT u.id, COALESCE(NULLIF(TRIM(u.full_name), ''), u.username) AS name
             FROM employment_contracts ec
             INNER JOIN system_users u ON u.id = ec.manager_user_id AND u.client_id = ec.client_id
             WHERE ec.client_id = :client AND ec.employee_id = :employee AND ec.archived_at IS NULL
               AND ec.contract_type IN ('primary', 'temporary') AND ec.start_date <= :today_start
               AND (ec.end_date IS NULL OR ec.end_date >= :today_end) AND u.status = 'active'
             ORDER BY name, u.id"
        );
        $stmt->execute(['client' => $clientId, 'employee' => $userId, 'today_start' => date('Y-m-d'), 'today_end' => date('Y-m-d')]);
        return ['client_admin' => $admins, 'manager' => $stmt->fetchAll()];
    }

    /** @return array{0: bool, 1: string, 2?: int} */
    public static function create(int $clientId, int $userId, array $data): array
    {
        if (!self::activeUser($userId, $clientId)) {
            return [false, 'Support ticket access denied.'];
        }
        $subject = trim((string) ($data['subject'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        if ($subject === '' || $message === '') {
            return [false, 'Subject and message are required.'];
        }
        if (mb_strlen($subject) > 191) {
            return [false, 'Subject must not exceed 191 characters.'];
        }
        if (strlen($message) > 65535) {
            return [false, 'Message must not exceed 65535 bytes.'];
        }
        $type = (string) ($data['recipient_type'] ?? '');
        $recipient = (int) ($data['recipient_user_id'] ?? 0);
        if (!in_array($type, ['system', 'client_admin', 'manager'], true)) {
            return [false, 'Choose a ticket recipient.'];
        }
        if ($type !== 'system') {
            $options = self::recipientOptions($clientId, $userId);
            if (!in_array($recipient, array_map('intval', array_column($options[$type], 'id')), true)) {
                return [false, 'Choose an available recipient for this ticket.'];
            }
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO system_support_tickets (client_id, created_by_user_id, assigned_to_user_id, recipient_type, subject, message, status)
                 VALUES (:client, :creator, :recipient, :type, :subject, :message, 'open')"
            );
            $stmt->execute(['client' => $clientId, 'creator' => $userId, 'recipient' => $type === 'system' ? null : $recipient,
                'type' => $type, 'subject' => $subject, 'message' => $message]);
            $id = (int) $pdo->lastInsertId();
            AuditLogService::log($userId, $clientId, 'support_ticket.created', 'system_support_tickets', (string) $id);
            $pdo->commit();
            return [true, 'Support ticket submitted.', $id];
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public static function replies(int $ticketId, int $clientId, int $userId, bool $systemWorkspace = false): array
    {
        if (self::ticket($ticketId, $clientId, $userId, $systemWorkspace) === null) {
            throw new RuntimeException('Support ticket access denied.');
        }
        $stmt = db()->prepare(
            "SELECT r.*, COALESCE(NULLIF(TRIM(u.full_name), ''), u.username) AS author_name
             FROM system_support_replies r INNER JOIN system_users u ON u.id = r.user_id
             WHERE r.ticket_id = :id ORDER BY r.created_at, r.id"
        );
        $stmt->execute(['id' => $ticketId]);
        return $stmt->fetchAll();
    }

    public static function participants(int $ticketId, int $clientId, int $userId, bool $systemWorkspace = false): array
    {
        if (self::ticket($ticketId, $clientId, $userId, $systemWorkspace) === null) {
            throw new RuntimeException('Support ticket access denied.');
        }
        $stmt = db()->prepare(
            "SELECT p.user_id, p.created_at, COALESCE(NULLIF(TRIM(u.full_name), ''), u.username) AS name
             FROM system_support_participants p INNER JOIN system_users u ON u.id = p.user_id
             WHERE p.ticket_id = :id ORDER BY p.created_at, p.user_id"
        );
        $stmt->execute(['id' => $ticketId]);
        return $stmt->fetchAll();
    }

    public static function inviteOptions(int $ticketId, int $clientId, int $userId, bool $systemWorkspace = false): array
    {
        $ticket = self::ticket($ticketId, $clientId, $userId, $systemWorkspace);
        if ($ticket === null || !$ticket['can_manage'] || $ticket['status'] === 'closed') {
            return [];
        }
        $stmt = db()->prepare(
            "SELECT u.id, COALESCE(NULLIF(TRIM(u.full_name), ''), u.username) AS name
             FROM system_users u WHERE u.client_id = :client AND u.status = 'active'
               AND u.id != :creator AND u.id != :recipient
               AND NOT EXISTS (SELECT 1 FROM system_support_participants p WHERE p.ticket_id = :ticket AND p.user_id = u.id)
             ORDER BY name, u.id"
        );
        $stmt->execute(['client' => $ticket['client_id'], 'creator' => $ticket['created_by_user_id'],
            'recipient' => (int) $ticket['assigned_to_user_id'], 'ticket' => $ticketId]);
        return $stmt->fetchAll();
    }

    /** The ticket lock serializes replies/invitations with closure. */
    public static function act(int $ticketId, int $clientId, int $userId, string $action, array $data, bool $systemWorkspace = false): array
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $ticket = self::ticket($ticketId, $clientId, $userId, $systemWorkspace, true);
            $error = null;
            if ($ticket === null) {
                $error = 'Support ticket not found or access denied.';
            } elseif ($ticket['status'] === 'closed') {
                $error = 'This ticket is closed and read-only.';
            } elseif (!in_array($action, ['reply', 'invite', 'close'], true)) {
                $error = 'Invalid ticket action.';
            } elseif ($action !== 'reply' && !$ticket['can_manage']) {
                $error = 'Only the creator or recipient can invite participants or close this ticket.';
            }
            $body = trim((string) ($data['message'] ?? ''));
            $invitee = (int) ($data['user_id'] ?? 0);
            if ($error === null && $action === 'reply' && $body === '') {
                $error = 'Message is required.';
            }
            if ($error === null && $action === 'reply' && strlen($body) > 65535) {
                $error = 'Message must not exceed 65535 bytes.';
            }
            if ($error === null && $action === 'invite') {
                $options = self::inviteOptions($ticketId, $clientId, $userId, $systemWorkspace);
                if (!in_array($invitee, array_map('intval', array_column($options, 'id')), true)) {
                    $error = 'Choose an active employee who is not already a participant.';
                }
            }
            if ($error !== null) {
                $pdo->rollBack();
                return [false, $error];
            }
            $now = date('Y-m-d H:i:s');
            if ($action === 'reply') {
                $stmt = $pdo->prepare('INSERT INTO system_support_replies (ticket_id, user_id, message, created_at) VALUES (:ticket, :user, :message, :created)');
                $stmt->execute(['ticket' => $ticketId, 'user' => $userId, 'message' => $body, 'created' => $now]);
                self::recordReplyRead($ticketId, $userId, (int) $pdo->lastInsertId());
                $stmt = $pdo->prepare(
                    'INSERT INTO system_support_reads (ticket_id, user_id, opened_at)
                     VALUES (:ticket, :user, :opened)
                     ON DUPLICATE KEY UPDATE opened_at = opened_at'
                );
                $stmt->execute(['ticket' => $ticketId, 'user' => $userId, 'opened' => $now]);
                $stmt = $pdo->prepare("UPDATE system_support_tickets SET status = 'in_progress', updated_at = :updated WHERE id = :id");
                $stmt->execute(['updated' => $now, 'id' => $ticketId]);
                $message = 'Reply added.';
            } elseif ($action === 'invite') {
                $stmt = $pdo->prepare('INSERT INTO system_support_participants (ticket_id, user_id, added_by_user_id, created_at) VALUES (:ticket, :user, :actor, :created)');
                $stmt->execute(['ticket' => $ticketId, 'user' => $invitee, 'actor' => $userId, 'created' => $now]);
                $message = 'Participant added.';
            } else {
                $stmt = $pdo->prepare("UPDATE system_support_tickets SET status = 'closed', closed_at = :closed, closed_by_user_id = :actor, updated_at = :updated WHERE id = :id");
                $stmt->execute(['closed' => $now, 'actor' => $userId, 'updated' => $now, 'id' => $ticketId]);
                $message = 'Support ticket closed.';
            }
            AuditLogService::log($userId, (int) $ticket['client_id'], 'support_ticket.' . $action,
                'system_support_tickets', (string) $ticketId, null, $action === 'invite' ? (string) $invitee : null);
            $pdo->commit();
            return [true, $message];
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
