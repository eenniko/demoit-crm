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
        ['file' => __DIR__ . '/../../sql/014_bilingual_changelog.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.27' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/015_property_structure.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.28' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_nodes') AND EXISTS (SELECT 1 FROM system_modules WHERE module_key = 'property') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/016_group_panel_modules.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.29' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/017_employment_module.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.30' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts') AND EXISTS (SELECT 1 FROM system_modules WHERE module_key = 'employment') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/018_employment_workloads.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.31' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_workloads') AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts' AND COLUMN_NAME = 'workload_id') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/019_contract_workload_schedule_scope.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.32' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/020_contract_property_path.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.33' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/021_property_sibling_order.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.34' AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_nodes' AND COLUMN_NAME = 'sort_order') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/022_employee_active_contract_column.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.35' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/023_employee_scheduling.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.36' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries') AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'created_by') AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'updated_by') AND EXISTS (SELECT 1 FROM system_modules WHERE module_key = 'schedule') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/024_schedule_save_optimization.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.37' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/025_schedule_save_error_logging.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.38' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/026_schedule_error_code_feedback.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.39' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/027_schedule_audit_columns.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.40' AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'created_by') AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'updated_by') LIMIT 1", 'handler' => 'scheduleAuditColumns'],
        ['file' => __DIR__ . '/../../sql/028_schedule_repair_compatibility.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.41' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/029_schedule_modal_picker.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.42' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/030_schedule_autosave.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.43' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/031_schedule_template_colors.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.44' AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_templates' AND COLUMN_NAME = 'color_hex') LIMIT 1", 'handler' => 'scheduleTemplateColor'],
        ['file' => __DIR__ . '/../../sql/032_schedule_color_allowlist.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.45' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/033_schedule_modal_active_templates.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.46' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/034_schedule_collapsible_groups.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.47' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/035_schedule_viewport_width.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.48' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/036_schedule_workload_under_employee.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.49' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/037_schedule_hour_balances.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.50' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/038_schedule_balance_autosave.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.51' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/039_schedule_four_month_balance.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.52' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/040_schedule_cross_month_hours.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.53' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/041_schedule_month_boundary_balances.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.54' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/042_schedule_contract_day_index.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.55' AND EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND INDEX_NAME = 'uq_employee_schedule_contract_day' AND NON_UNIQUE = 0) AND EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND INDEX_NAME = 'idx_employee_schedule_employee_day') LIMIT 1", 'handler' => 'scheduleContractDayIndex'],
        ['file' => __DIR__ . '/../../sql/043_schedule_temp_label.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.56' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/044_schedule_weekday_exception_hours.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.57' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/045_schedule_single_cell_autosave.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.58' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/046_employment_contract_archive.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.59' AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts' AND COLUMN_NAME = 'archived_at') LIMIT 1", 'handler' => 'employmentContractArchive'],
        ['file' => __DIR__ . '/../../sql/047_schedule_block_templates.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.60' AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_templates' AND COLUMN_NAME = 'template_type' AND COLUMN_TYPE LIKE '%block%') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/048_ui_languages.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.61' AND EXISTS (SELECT 1 FROM system_languages WHERE language_code = 'et' AND is_active = 1) AND EXISTS (SELECT 1 FROM system_languages WHERE language_code = 'ru' AND is_active = 1) AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = 'layout.language') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/049_ui_static_translations.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.62' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = CONCAT('ui.', SHA2('Access level', 256)) AND module_context = 'ui') AND EXISTS (SELECT 1 FROM system_translation_values v INNER JOIN system_translation_keys k ON k.id = v.translation_key_id INNER JOIN system_languages l ON l.id = v.language_id WHERE k.translation_key = CONCAT('ui.', SHA2('Access level', 256)) AND l.language_code = 'et' AND v.status = 'active') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/050_monthly_workload_overrides.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.63' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contract_monthly_workloads') AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = 'panel.contract_default_workload') AND EXISTS (SELECT 1 FROM system_translation_values v INNER JOIN system_translation_keys k ON k.id = v.translation_key_id INNER JOIN system_languages l ON l.id = v.language_id WHERE k.translation_key = 'panel.contract_default_workload' AND l.language_code = 'et' AND v.status = 'active') AND EXISTS (SELECT 1 FROM system_translation_values v INNER JOIN system_translation_keys k ON k.id = v.translation_key_id INNER JOIN system_languages l ON l.id = v.language_id WHERE k.translation_key = 'panel.contract_default_workload' AND l.language_code = 'ru' AND v.status = 'active') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/051_schedule_monthly_workload_modal.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.64' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/052_schedule_weekend_color.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.65' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/053_schedule_weekend_transparency.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.66' LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/054_public_holidays.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.67' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_public_holidays') LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/055_public_holiday_translations.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.68' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = CONCAT('ui.', SHA2('Public holidays XML', 256))) LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/056_public_holiday_tab_translations.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.69' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = CONCAT('ui.', SHA2('Public holidays', 256))) LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/057_schedule_sum_label_translation.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.70' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = CONCAT('ui.', SHA2('Sum h', 256))) LIMIT 1"],
        ['file' => __DIR__ . '/../../sql/058_schedule_printing.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.71' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = CONCAT('ui.', SHA2('Print schedule', 256))) LIMIT 1"],
    ];

    public static function ensureInstalled(): void
    {
        foreach (self::MIGRATIONS as $migration) {
            $applied = isset($migration['marker'])
                ? self::tableExists($migration['marker'])
                : self::rowExists($migration['rowCheck']);

            if (!$applied && is_file($migration['file'])) {
                if (($migration['handler'] ?? null) === 'scheduleAuditColumns') {
                    self::ensureScheduleAuditColumns();
                } elseif (($migration['handler'] ?? null) === 'scheduleTemplateColor') {
                    self::ensureScheduleTemplateColor();
                } elseif (($migration['handler'] ?? null) === 'scheduleContractDayIndex') {
                    self::ensureScheduleContractDayIndex();
                } elseif (($migration['handler'] ?? null) === 'employmentContractArchive') {
                    self::ensureEmploymentContractArchiveColumn();
                }
                self::runSqlFile($migration['file']);
            }
        }
    }

    private static function ensureScheduleAuditColumns(): void
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            "SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries'
               AND COLUMN_NAME IN ('created_by', 'updated_by')"
        );
        $stmt->execute();
        $columns = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

        foreach (['created_by', 'updated_by'] as $column) {
            if (!isset($columns[$column])) {
                $pdo->exec('ALTER TABLE employee_schedule_entries ADD COLUMN ' . $column . ' INT UNSIGNED NULL');
            }
        }
    }

    private static function ensureScheduleTemplateColor(): void
    {
        $stmt = db()->prepare(
            "SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_templates'
               AND COLUMN_NAME = 'color_hex'
             LIMIT 1"
        );
        $stmt->execute();
        if ($stmt->fetchColumn() === false) {
            db()->exec("ALTER TABLE employee_schedule_templates ADD COLUMN color_hex CHAR(7) NOT NULL DEFAULT '#64748B'");
        }
    }

    private static function ensureScheduleContractDayIndex(): void
    {
        $stmt = db()->prepare(
            "SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries'
               AND INDEX_NAME IN ('uq_employee_schedule_day', 'uq_employee_schedule_contract_day', 'idx_employee_schedule_employee_day')"
        );
        $stmt->execute();
        $indexes = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

        if (!isset($indexes['idx_employee_schedule_employee_day'])) {
            db()->exec('ALTER TABLE employee_schedule_entries ADD KEY idx_employee_schedule_employee_day (client_id, employee_id, schedule_date)');
        }
        if (isset($indexes['uq_employee_schedule_day'])) {
            db()->exec('ALTER TABLE employee_schedule_entries DROP INDEX uq_employee_schedule_day');
        }
        if (!isset($indexes['uq_employee_schedule_contract_day'])) {
            db()->exec('ALTER TABLE employee_schedule_entries ADD UNIQUE KEY uq_employee_schedule_contract_day (client_id, contract_id, schedule_date)');
        }
    }

    private static function ensureEmploymentContractArchiveColumn(): void
    {
        $stmt = db()->prepare(
            "SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts'
               AND COLUMN_NAME = 'archived_at'
             LIMIT 1"
        );
        $stmt->execute();
        if ($stmt->fetchColumn() === false) {
            db()->exec('ALTER TABLE employment_contracts ADD COLUMN archived_at DATETIME NULL');
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
