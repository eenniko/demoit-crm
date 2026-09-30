<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Handles the one-time secure creation of the first system administrator (doc 01 §7, doc 04 §5). */
class InitialSetupService
{
    public const RESERVED_ADMIN_CLIENT_CODE = '13666';

    public static function isCompleted(): bool
    {
        $stmt = db()->query('SELECT is_completed FROM system_initial_setup WHERE id = 1');
        $row = $stmt->fetch();

        return $row !== false && (int) $row['is_completed'] === 1;
    }

    /**
     * @return array{0: bool, 1: string} success flag and error/success message
     */
    public static function createFirstAdmin(string $fullName, string $email, string $username, string $password): array
    {
        if (self::isCompleted()) {
            return [false, 'Initial setup has already been completed.'];
        }

        if (strlen($password) < 8) {
            return [false, 'Password must be at least 8 characters.'];
        }

        if ($username === '' || $fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Please fill in a valid name, e-mail and username.'];
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $clientStmt = $pdo->prepare('SELECT id FROM system_clients WHERE client_code = :code FOR UPDATE');
            $clientStmt->execute(['code' => self::RESERVED_ADMIN_CLIENT_CODE]);
            $client = $clientStmt->fetch();

            if ($client === false) {
                throw new RuntimeException('Reserved system client 13666 is missing. Run sql/001_core_schema.sql first.');
            }

            $clientId = (int) $client['id'];

            $userStmt = $pdo->prepare(
                'INSERT INTO system_users (client_id, username, email, password_hash, full_name, status, must_change_password)
                 VALUES (:client_id, :username, :email, :password_hash, :full_name, \'active\', 0)'
            );
            $userStmt->execute([
                'client_id' => $clientId,
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'full_name' => $fullName,
            ]);
            $userId = (int) $pdo->lastInsertId();

            $roleStmt = $pdo->prepare("SELECT id FROM system_roles WHERE role_key = 'system_admin'");
            $roleStmt->execute();
            $role = $roleStmt->fetch();

            if ($role === false) {
                throw new RuntimeException('Role system_admin is missing. Run sql/001_core_schema.sql first.');
            }

            $assignStmt = $pdo->prepare(
                'INSERT INTO system_user_roles (user_id, role_id, client_id, status) VALUES (:user_id, :role_id, :client_id, \'active\')'
            );
            $assignStmt->execute([
                'user_id' => $userId,
                'role_id' => (int) $role['id'],
                'client_id' => $clientId,
            ]);

            $lockStmt = $pdo->prepare(
                'UPDATE system_initial_setup SET is_completed = 1, completed_by = :user_id, completed_at = NOW() WHERE id = 1'
            );
            $lockStmt->execute(['user_id' => $userId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();

            return [false, 'Setup failed: ' . $e->getMessage()];
        }

        AuditLogService::log($userId, $clientId, 'initial_setup.completed', 'system_users', (string) $userId);

        return [true, 'First system administrator created successfully.'];
    }
}
