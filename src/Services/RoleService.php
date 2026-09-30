<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Reads a user's active roles, scoped to a client (doc 03 §1). */
class RoleService
{
    /** @return string[] role_key values */
    public static function rolesFor(int $userId, int $clientId): array
    {
        $stmt = db()->prepare(
            'SELECT r.role_key
             FROM system_user_roles ur
             INNER JOIN system_roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id AND ur.client_id = :client_id AND ur.status = \'active\''
        );
        $stmt->execute(['user_id' => $userId, 'client_id' => $clientId]);

        return array_column($stmt->fetchAll(), 'role_key');
    }

    public static function hasAnyRole(int $userId, int $clientId, array $roleKeys): bool
    {
        return count(array_intersect(self::rolesFor($userId, $clientId), $roleKeys)) > 0;
    }

    /** Roles that can be assigned to client-side users (doc 03 §1). */
    public static function listAssignableClientRoles(): array
    {
        $stmt = db()->query("SELECT id, role_key, name FROM system_roles WHERE scope = 'client' ORDER BY name");

        return $stmt->fetchAll();
    }

    /** All roles (system + client scope), for admin management (doc 01 §5 "Roles"). */
    public static function listAll(): array
    {
        return db()->query('SELECT * FROM system_roles ORDER BY scope, org_level, name')->fetchAll();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function updateName(int $roleId, string $newName, int $actorUserId): array
    {
        $newName = trim($newName);

        if ($newName === '') {
            return [false, 'Role name cannot be empty.'];
        }

        $stmt = db()->prepare('SELECT name FROM system_roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);
        $role = $stmt->fetch();

        if ($role === false) {
            return [false, 'Role not found.'];
        }

        $update = db()->prepare('UPDATE system_roles SET name = :name WHERE id = :id');
        $update->execute(['name' => $newName, 'id' => $roleId]);

        AuditLogService::log($actorUserId, null, 'role.renamed', 'system_roles', (string) $roleId, $role['name'], $newName);

        return [true, 'Role name updated.'];
    }
}
