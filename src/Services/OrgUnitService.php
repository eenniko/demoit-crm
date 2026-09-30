<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Organisation units for the A-F hierarchy, scoped to a client (doc 02 §8, doc 03 §2). */
class OrgUnitService
{
    public static function listForClient(int $clientId): array
    {
        $stmt = db()->prepare(
            'SELECT ou.id, ou.org_level, ou.name, ou.status, ou.parent_org_unit_id,
                    parent.name AS parent_name,
                    manager.username AS manager_username
             FROM system_org_units ou
             LEFT JOIN system_org_units parent ON parent.id = ou.parent_org_unit_id
             LEFT JOIN system_users manager ON manager.id = ou.manager_user_id
             WHERE ou.client_id = :client_id
             ORDER BY ou.org_level, ou.name'
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function create(int $clientId, array $data, int $actorUserId): array
    {
        $orgLevel = strtoupper(trim((string) ($data['org_level'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));
        $parentId = !empty($data['parent_org_unit_id']) ? (int) $data['parent_org_unit_id'] : null;
        $managerUserId = !empty($data['manager_user_id']) ? (int) $data['manager_user_id'] : null;

        if (!in_array($orgLevel, ['A', 'B', 'C', 'D', 'E', 'F'], true)) {
            return [false, 'Organisation level must be one of A-F.'];
        }

        if ($name === '') {
            return [false, 'Name is required.'];
        }

        $insert = db()->prepare(
            'INSERT INTO system_org_units (client_id, parent_org_unit_id, org_level, name, manager_user_id, status)
             VALUES (:client_id, :parent_org_unit_id, :org_level, :name, :manager_user_id, \'active\')'
        );
        $insert->execute([
            'client_id' => $clientId,
            'parent_org_unit_id' => $parentId,
            'org_level' => $orgLevel,
            'name' => $name,
            'manager_user_id' => $managerUserId,
        ]);

        AuditLogService::log($actorUserId, $clientId, 'org_unit.created', 'system_org_units', (string) db()->lastInsertId());

        return [true, 'Organisation unit created.'];
    }
}
