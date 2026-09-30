<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';
require_once __DIR__ . '/ModuleProvisioningService.php';

/** Module catalogue management (doc 01 §5, doc 02 §7). */
class ModuleService
{
    public static function listAll(): array
    {
        return db()->query('SELECT * FROM system_modules ORDER BY name')->fetchAll();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function create(array $data, int $actorUserId): array
    {
        $moduleKey = trim((string) ($data['module_key'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $isDemo = !empty($data['is_demo_available']) ? 1 : 0;

        if (!preg_match('/^[a-z0-9_]{2,100}$/', $moduleKey)) {
            return [false, 'Module key must be lowercase letters, digits or underscores (2-100 characters).'];
        }

        if ($name === '') {
            return [false, 'Module name is required.'];
        }

        $stmt = db()->prepare('SELECT id FROM system_modules WHERE module_key = :module_key');
        $stmt->execute(['module_key' => $moduleKey]);
        if ($stmt->fetch() !== false) {
            return [false, 'This module key already exists.'];
        }

        $insert = db()->prepare(
            'INSERT INTO system_modules (module_key, name, description, status, is_demo_available)
             VALUES (:module_key, :name, :description, \'active\', :is_demo_available)'
        );
        $insert->execute([
            'module_key' => $moduleKey,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'is_demo_available' => $isDemo,
        ]);

        AuditLogService::log($actorUserId, null, 'module.created', 'system_modules', (string) db()->lastInsertId());

        return [true, 'Module created successfully.'];
    }

    public static function toggleStatus(int $moduleId, int $actorUserId): void
    {
        $stmt = db()->prepare('SELECT status FROM system_modules WHERE id = :id');
        $stmt->execute(['id' => $moduleId]);
        $module = $stmt->fetch();

        if ($module === false) {
            return;
        }

        $newStatus = $module['status'] === 'active' ? 'inactive' : 'active';
        $update = db()->prepare('UPDATE system_modules SET status = :status WHERE id = :id');
        $update->execute(['status' => $newStatus, 'id' => $moduleId]);

        AuditLogService::log($actorUserId, null, 'module.status_changed', 'system_modules', (string) $moduleId, $module['status'], $newStatus);
    }

    /** All catalogue modules with their activation status for one client (doc 02 §7). */
    public static function listForClient(int $clientId): array
    {
        $stmt = db()->prepare(
            'SELECT m.id, m.module_key, m.name, m.status AS catalogue_status,
                    cm.status AS client_status
             FROM system_modules m
             LEFT JOIN system_client_modules cm ON cm.module_id = m.id AND cm.client_id = :client_id
             ORDER BY m.name'
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    /** Modules the client may currently see (catalogue active AND activated for this client). */
    public static function listActiveForClient(int $clientId): array
    {
        $stmt = db()->prepare(
            "SELECT m.id, m.module_key, m.name
             FROM system_modules m
             INNER JOIN system_client_modules cm ON cm.module_id = m.id
             WHERE cm.client_id = :client_id AND cm.status = 'active' AND m.status = 'active'
             ORDER BY m.name"
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    public static function isActiveForClient(int $clientId, string $moduleKey): bool
    {
        foreach (self::listActiveForClient($clientId) as $module) {
            if ($module['module_key'] === $moduleKey) {
                return true;
            }
        }

        return false;
    }

    /** @return array{0: bool, 1: string} */
    public static function toggleClientActivation(int $clientId, int $moduleId, int $actorUserId): array
    {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $moduleStatement = $pdo->prepare(
                'SELECT m.module_key, m.status AS catalogue_status, cm.status AS client_status
                 FROM system_modules m
                 LEFT JOIN system_client_modules cm ON cm.module_id = m.id AND cm.client_id = :client_id
                 WHERE m.id = :module_id
                 FOR UPDATE'
            );
            $moduleStatement->execute(['client_id' => $clientId, 'module_id' => $moduleId]);
            $module = $moduleStatement->fetch();
            if ($module === false) {
                $pdo->rollBack();
                return [false, 'Module not found.'];
            }

            $newStatus = $module['client_status'] === 'active' ? 'inactive' : 'active';
            if ($newStatus === 'active' && $module['catalogue_status'] !== 'active') {
                $pdo->rollBack();
                return [false, 'An inactive catalogue module cannot be activated for a client.'];
            }

            if ($module['client_status'] === null) {
                $activationStatement = $pdo->prepare(
                    "INSERT INTO system_client_modules (client_id, module_id, status)
                     VALUES (:client_id, :module_id, 'active')"
                );
                $activationStatement->execute(['client_id' => $clientId, 'module_id' => $moduleId]);
            } else {
                $activationStatement = $pdo->prepare(
                    "UPDATE system_client_modules
                     SET status = :status, activated_at = CASE WHEN :activation_status = 'active' THEN NOW() ELSE activated_at END
                     WHERE client_id = :client_id AND module_id = :module_id"
                );
                $activationStatement->execute([
                    'status' => $newStatus,
                    'activation_status' => $newStatus,
                    'client_id' => $clientId,
                    'module_id' => $moduleId,
                ]);
            }

            if ($newStatus === 'active') {
                ModuleProvisioningService::provision($clientId, $moduleId, (string) $module['module_key']);
            } else {
                ModuleProvisioningService::markInactive($clientId, $moduleId);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [false, 'Module activation failed: ' . $e->getMessage()];
        }

        AuditLogService::log($actorUserId, $clientId, 'client_module.status_changed', 'system_client_modules', $clientId . ':' . $moduleId, null, $newStatus);

        return [true, $newStatus === 'active'
            ? 'Module activated and its client database provisioned.'
            : 'Module deactivated. Existing client data was retained.'];
    }
}
