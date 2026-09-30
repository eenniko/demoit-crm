<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

/**
 * Auto-installs the core SQL schema on first request if it is missing,
 * so the site works immediately after upload without a manual phpMyAdmin step.
 */
class SchemaInstaller
{
    /**
     * Each migration is only (re-)run when not yet applied.
     * 'marker' = a table that must exist (checked without needing rows, for CREATE TABLE migrations).
     * 'rowCheck' = a SQL query that must return a row (for migrations that only insert/update data into existing tables).
     */
    private const MIGRATIONS = [
        ['file' => __DIR__ . '/../../sql/001_core_schema.sql', 'marker' => 'system_initial_setup'],
        ['file' => __DIR__ . '/../../sql/002_client_modules.sql', 'marker' => 'system_client_modules'],
        ['file' => __DIR__ . '/../../sql/003_notifications_support.sql', 'marker' => 'system_notifications'],
        ['file' => __DIR__ . '/../../sql/004_module_patients.sql', 'marker' => 'patients'],
        ['file' => __DIR__ . '/../../sql/005_module_organisation.sql', 'rowCheck' => "SELECT 1 FROM system_modules WHERE module_key = 'organisation' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/006_employee_users.sql', 'rowCheck' => "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_users' AND COLUMN_NAME = 'phone' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/007_legacy_employee_import.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.20' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/008_module_tenant_provisioning.sql', 'rowCheck' => "SELECT 1 FROM system_modules WHERE module_key = 'employees' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_client_module_provisions') AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_module_settings') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/009_employee_module_documentation.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.22' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/010_readme_version_history.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.23' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/011_signed_in_display_name.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.24' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/012_employee_edit_org_unit.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.25' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/013_employee_create_org_unit.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.26' LIMIT 1"],
    ];

    public static function ensureInstalled(): void
    {
        foreach (self::MIGRATIONS as $migration) {
            $applied = isset($migration['marker'])
                ? self::tableExists($migration['marker'])
                : self::rowExists($migration['rowCheck']);

            if (!$applied && is_file($migration['file'])) {
                self::runSqlFile($migration['file']);
            }
        }
    }

    private static function tableExists(string $table): bool
    {
        try {
            db()->query('SELECT 1 FROM ' . $table . ' LIMIT 1');

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function rowExists(string $query): bool
    {
        try {
            return db()->query($query)->fetch() !== false;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function runSqlFile(string $path): void
    {
        $pdo = db();

        foreach (self::splitStatements((string) file_get_contents($path)) as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }

    /** @return string[] */
    private static function splitStatements(string $sql): array
    {
        $withoutComments = implode("\n", array_filter(
            explode("\n", $sql),
            static fn (string $line): bool => !str_starts_with(ltrim($line), '--')
        ));

        return array_map('trim', explode(';', $withoutComments));
    }
}
