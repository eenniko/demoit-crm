<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

/** Creates and tracks the client-specific data required by an activated module. */
class ModuleProvisioningService
{
    private const SCHEMA_VERSIONS = [
        'employees' => '1.0',
        'organisation' => '1.0',
        'patients' => '1.0',
        'property' => '1.0',
    ];

    public static function provision(int $clientId, int $moduleId, string $moduleKey): void
    {
        if ($moduleKey === 'employees') {
            self::provisionEmployees($clientId);
        }

        $statement = db()->prepare(
            "INSERT INTO system_client_module_provisions
                (client_id, module_id, schema_version, status, provisioned_at)
             VALUES (:client_id, :module_id, :schema_version, 'ready', NOW())
             ON DUPLICATE KEY UPDATE
                schema_version = VALUES(schema_version), status = 'ready', provisioned_at = NOW()"
        );
        $statement->execute([
            'client_id' => $clientId,
            'module_id' => $moduleId,
            'schema_version' => self::SCHEMA_VERSIONS[$moduleKey] ?? '1.0',
        ]);
    }

    public static function markInactive(int $clientId, int $moduleId): void
    {
        $statement = db()->prepare(
            "UPDATE system_client_module_provisions
             SET status = 'inactive'
             WHERE client_id = :client_id AND module_id = :module_id"
        );
        $statement->execute(['client_id' => $clientId, 'module_id' => $moduleId]);
    }

    private static function provisionEmployees(int $clientId): void
    {
        $roleStatement = db()->query("SELECT id FROM system_roles WHERE role_key = 'level_f' AND scope = 'client' LIMIT 1");
        $defaultRoleId = $roleStatement->fetchColumn();
        if ($defaultRoleId === false) {
            throw new RuntimeException('Employees module provisioning failed: Level F role is missing.');
        }

        $statement = db()->prepare(
            'INSERT INTO employee_module_settings (client_id, default_role_id)
             VALUES (:client_id, :default_role_id)
             ON DUPLICATE KEY UPDATE default_role_id = default_role_id'
        );
        $statement->execute(['client_id' => $clientId, 'default_role_id' => (int) $defaultRoleId]);
    }
}