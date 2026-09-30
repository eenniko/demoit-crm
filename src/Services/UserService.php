<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';
require_once __DIR__ . '/EmailService.php';

/** User + role management scoped to a single client (doc 03). */
class UserService
{
    public static function listForClient(int $clientId): array
    {
        $stmt = db()->prepare(
                'SELECT u.id, u.username, u.full_name, u.email, u.phone, u.status, u.must_change_password,
                    GROUP_CONCAT(r.role_key SEPARATOR \',\') AS role_keys,
                    GROUP_CONCAT(r.name SEPARATOR \', \') AS roles
             FROM system_users u
             LEFT JOIN system_user_roles ur ON ur.user_id = u.id AND ur.client_id = u.client_id AND ur.status = \'active\'
             LEFT JOIN system_roles r ON r.id = ur.role_id
             WHERE u.client_id = :client_id
             GROUP BY u.id, u.username, u.full_name, u.email, u.phone, u.status, u.must_change_password
             ORDER BY u.username'
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function createForClient(int $clientId, array $data, int $actorUserId): array
    {
        $username = trim((string) ($data['username'] ?? ''));
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $defaultRoleKey = self::defaultEmployeeRoleKey($clientId);
        $roleKey = trim((string) ($data['role_key'] ?? $defaultRoleKey));
        $orgUnitId = !empty($data['org_unit_id']) ? (int) $data['org_unit_id'] : null;

        if ($username === '' || $fullName === '' || $email === '') {
            return [false, 'Employee name, employee code and e-mail are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Enter a valid e-mail address.'];
        }

        if (!self::isAssignableClientRole($roleKey)) {
            return [false, 'Invalid role selected.'];
        }

        $pdo = db();

        $existsStmt = $pdo->prepare('SELECT id FROM system_users WHERE client_id = :client_id AND username = :username');
        $existsStmt->execute(['client_id' => $clientId, 'username' => $username]);
        if ($existsStmt->fetch() !== false) {
            return [false, 'This username already exists for the client.'];
        }

        $roleStmt = $pdo->prepare('SELECT id FROM system_roles WHERE role_key = :role_key AND scope = \'client\'');
        $roleStmt->execute(['role_key' => $roleKey]);
        $role = $roleStmt->fetch();

        if ($role === false) {
            return [false, 'Role not found.'];
        }

        $tempPassword = substr(bin2hex(random_bytes(8)), 0, 12) . 'Aa1!';

        $pdo->beginTransaction();

        try {
            $insertUser = $pdo->prepare(
                'INSERT INTO system_users (client_id, username, email, phone, password_hash, full_name, status, must_change_password)
                 VALUES (:client_id, :username, :email, :phone, :password_hash, :full_name, \'inactive\', 1)'
            );
            $insertUser->execute([
                'client_id' => $clientId,
                'username' => $username,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT),
                'full_name' => $fullName,
            ]);
            $userId = (int) $pdo->lastInsertId();

            $assignStmt = $pdo->prepare(
                'INSERT INTO system_user_roles (user_id, role_id, client_id, org_unit_id, status) VALUES (:user_id, :role_id, :client_id, :org_unit_id, \'active\')'
            );
            $assignStmt->execute([
                'user_id' => $userId,
                'role_id' => (int) $role['id'],
                'client_id' => $clientId,
                'org_unit_id' => $orgUnitId,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();

            return [false, 'Failed to create user: ' . $e->getMessage()];
        }

        AuditLogService::log($actorUserId, $clientId, 'user.created', 'system_users', (string) $userId);

        return [true, "Employee created as inactive with Level F access. Activate the account to e-mail the login details."];
    }

    public static function findForClient(int $clientId, int $userId): ?array
    {
        $stmt = db()->prepare(
            'SELECT u.id, u.username, u.full_name, u.email, u.phone, ur.role_id, ur.org_unit_id
             FROM system_users u
             LEFT JOIN system_user_roles ur ON ur.user_id = u.id AND ur.client_id = u.client_id AND ur.status = \'active\'
             WHERE u.id = :id AND u.client_id = :client_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId, 'client_id' => $clientId]);
        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    /** @return array{0: bool, 1: string} */
    public static function updateForClient(int $clientId, int $userId, array $data, int $actorUserId): array
    {
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $roleKey = trim((string) ($data['role_key'] ?? 'level_f'));

        if ($fullName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Employee name and a valid e-mail address are required.'];
        }
        if (!self::isAssignableClientRole($roleKey)) {
            return [false, 'Invalid role selected.'];
        }

        $user = self::findForClient($clientId, $userId);
        if ($user === null) {
            return [false, 'Employee not found.'];
        }
        $orgUnitId = array_key_exists('org_unit_id', $data)
            ? (!empty($data['org_unit_id']) ? (int) $data['org_unit_id'] : null)
            : $user['org_unit_id'];

        $currentRoleStmt = db()->prepare(
            "SELECT r.role_key FROM system_user_roles ur
             INNER JOIN system_roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id AND ur.client_id = :client_id AND ur.status = 'active'"
        );
        $currentRoleStmt->execute(['user_id' => $userId, 'client_id' => $clientId]);
        if ($currentRoleStmt->fetchColumn() === 'client_admin' && $roleKey !== 'client_admin' && self::isLastActiveClientAdmin($clientId, $userId)) {
            return [false, 'Cannot remove the only active client administrator role. Assign another administrator first.'];
        }

        $roleStmt = db()->prepare("SELECT id FROM system_roles WHERE role_key = :role_key AND scope = 'client'");
        $roleStmt->execute(['role_key' => $roleKey]);
        $roleId = $roleStmt->fetchColumn();
        if ($roleId === false) {
            return [false, 'Role not found.'];
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $updateUser = $pdo->prepare('UPDATE system_users SET full_name = :full_name, email = :email, phone = :phone WHERE id = :id AND client_id = :client_id');
            $updateUser->execute(['full_name' => $fullName, 'email' => $email, 'phone' => $phone !== '' ? $phone : null, 'id' => $userId, 'client_id' => $clientId]);

            $deactivateRoles = $pdo->prepare("UPDATE system_user_roles SET status = 'inactive' WHERE user_id = :user_id AND client_id = :client_id");
            $deactivateRoles->execute(['user_id' => $userId, 'client_id' => $clientId]);
            $existingAssignment = $pdo->prepare(
                'SELECT id FROM system_user_roles WHERE user_id = :user_id AND role_id = :role_id AND client_id = :client_id ORDER BY id DESC LIMIT 1'
            );
            $existingAssignment->execute(['user_id' => $userId, 'role_id' => (int) $roleId, 'client_id' => $clientId]);
            $assignmentId = $existingAssignment->fetchColumn();
            if ($assignmentId !== false) {
                $assignRole = $pdo->prepare("UPDATE system_user_roles SET org_unit_id = :org_unit_id, status = 'active' WHERE id = :id");
                $assignRole->execute(['org_unit_id' => $orgUnitId, 'id' => (int) $assignmentId]);
            } else {
                $assignRole = $pdo->prepare(
                    "INSERT INTO system_user_roles (user_id, role_id, client_id, org_unit_id, status)
                     VALUES (:user_id, :role_id, :client_id, :org_unit_id, 'active')"
                );
                $assignRole->execute(['user_id' => $userId, 'role_id' => (int) $roleId, 'client_id' => $clientId, 'org_unit_id' => $orgUnitId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            return [false, 'Failed to update employee: ' . $e->getMessage()];
        }

        AuditLogService::log($actorUserId, $clientId, 'user.updated', 'system_users', (string) $userId);
        return [true, 'Employee updated.'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function toggleStatus(int $clientId, int $userId, int $actorUserId): array
    {
        $stmt = db()->prepare('SELECT status, username, full_name, email, password_hash FROM system_users WHERE id = :id AND client_id = :client_id');
        $stmt->execute(['id' => $userId, 'client_id' => $clientId]);
        $user = $stmt->fetch();

        if ($user === false) {
            return [false, 'User not found.'];
        }

        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';

        if ($newStatus === 'inactive' && self::isLastActiveClientAdmin($clientId, $userId)) {
            return [false, 'Cannot deactivate the only active client administrator. Assign another administrator first.'];
        }

        $update = db()->prepare('UPDATE system_users SET status = :status WHERE id = :id');
        $update->execute(['status' => $newStatus, 'id' => $userId]);

        AuditLogService::log($actorUserId, $clientId, 'user.status_changed', 'system_users', (string) $userId, $user['status'], $newStatus);

        $message = 'Employee status updated.';
        if ($newStatus === 'active') {
            $tempPassword = substr(bin2hex(random_bytes(8)), 0, 12) . 'Aa1!';
            $passwordUpdate = db()->prepare('UPDATE system_users SET password_hash = :password_hash, must_change_password = 1 WHERE id = :id');
            $passwordUpdate->execute(['password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT), 'id' => $userId]);
            $sent = EmailService::sendTemporaryPassword((string) $user['email'], (string) $user['full_name'], (string) $user['username'], $tempPassword);
            $message = $sent
                ? 'Employee activated and login details e-mailed.'
                : "Employee activated, but e-mail delivery failed. Temporary password: {$tempPassword}";
        }

        return [true, $message];
    }

    /** True if $userId is the client's only currently active client_admin. */
    private static function isLastActiveClientAdmin(int $clientId, int $userId): bool
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) AS cnt
             FROM system_user_roles ur
             INNER JOIN system_roles r ON r.id = ur.role_id
             INNER JOIN system_users u ON u.id = ur.user_id
             WHERE ur.client_id = :client_id AND r.role_key = 'client_admin'
               AND ur.status = 'active' AND u.status = 'active' AND u.id != :user_id"
        );
        $stmt->execute(['client_id' => $clientId, 'user_id' => $userId]);
        $otherActiveAdmins = (int) $stmt->fetchColumn();

        $isThisUserAdminStmt = db()->prepare(
            "SELECT COUNT(*) FROM system_user_roles ur
             INNER JOIN system_roles r ON r.id = ur.role_id
             WHERE ur.client_id = :client_id AND ur.user_id = :user_id AND r.role_key = 'client_admin' AND ur.status = 'active'"
        );
        $isThisUserAdminStmt->execute(['client_id' => $clientId, 'user_id' => $userId]);
        $isThisUserAdmin = (int) $isThisUserAdminStmt->fetchColumn() > 0;

        return $isThisUserAdmin && $otherActiveAdmins === 0;
    }

    private static function isAssignableClientRole(string $roleKey): bool
    {
        return in_array($roleKey, [
            'client_admin', 'level_a', 'level_b', 'level_c', 'level_d', 'level_e', 'level_f', 'viewer', 'temp_substitute',
        ], true);
    }

    private static function defaultEmployeeRoleKey(int $clientId): string
    {
        $statement = db()->prepare(
            'SELECT r.role_key
             FROM employee_module_settings settings
             INNER JOIN system_roles r ON r.id = settings.default_role_id
             WHERE settings.client_id = :client_id'
        );
        $statement->execute(['client_id' => $clientId]);
        $roleKey = $statement->fetchColumn();

        if ($roleKey === false) {
            throw new RuntimeException('Employees module is not provisioned for this client.');
        }

        return (string) $roleKey;
    }

    /**
     * Admin-triggered reset: generates a new temporary password for a user (doc 01 §7 "ajutine parool").
     * @return array{0: bool, 1: string, 2: ?string} success, message, temporary password
     */
    public static function resetPassword(int $clientId, int $userId, int $actorUserId): array
    {
        $stmt = db()->prepare('SELECT id, username, full_name, email FROM system_users WHERE id = :id AND client_id = :client_id');
        $stmt->execute(['id' => $userId, 'client_id' => $clientId]);

        $user = $stmt->fetch();
        if ($user === false) {
            return [false, 'User not found.', null];
        }

        $tempPassword = substr(bin2hex(random_bytes(8)), 0, 12) . 'Aa1!';

        $update = db()->prepare(
            'UPDATE system_users SET password_hash = :password_hash, must_change_password = 1 WHERE id = :id'
        );
        $update->execute(['password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT), 'id' => $userId]);

        AuditLogService::log($actorUserId, $clientId, 'user.password_reset', 'system_users', (string) $userId);

        $sent = EmailService::sendTemporaryPassword((string) $user['email'], (string) $user['full_name'], (string) $user['username'], $tempPassword);
        return [true, $sent ? 'Password reset and e-mailed.' : 'Password reset, but e-mail delivery failed.', $tempPassword];
    }

    /**
     * Self-service password change: requires the current password (doc 04 §4/§12 security rules).
     * @return array{0: bool, 1: string}
     */
    public static function changeOwnPassword(int $userId, string $currentPassword, string $newPassword): array
    {
        if (strlen($newPassword) < 8) {
            return [false, 'New password must be at least 8 characters.'];
        }

        $stmt = db()->prepare('SELECT password_hash FROM system_users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if ($user === false || !password_verify($currentPassword, $user['password_hash'])) {
            return [false, 'Current password is incorrect.'];
        }

        $update = db()->prepare(
            'UPDATE system_users SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id'
        );
        $update->execute(['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]);

        AuditLogService::log($userId, null, 'user.password_changed_self', 'system_users', (string) $userId);

        return [true, 'Password updated.'];
    }
}
