-- DemoIT CRM 1.98: empty-database recovery bundle.
-- Generated from the ordered SchemaInstaller registry on 2026-10-24.
-- Select an EMPTY database first. No CREATE DATABASE/USE or business data included.
-- NOT a production upgrade or a production data backup. Stop on the first SQL error.
-- Includes exact original migration SQL and conditional equivalents of PHP repair handlers.
-- See 13-paigaldus-varundus-ja-taastamine.md. Do not run this on every HTTP request.

-- BEGIN MIGRATION: 001_core_schema.sql
-- DemoIT CRM — Core database schema (v1.3)
-- Compatible with MySQL, runnable directly in phpMyAdmin.
-- Idempotent: safe to re-run (uses IF NOT EXISTS / INSERT IGNORE).
-- Charset/engine: utf8mb4 + InnoDB throughout.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Global key/value settings (system-wide config)
CREATE TABLE IF NOT EXISTS system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(191) NOT NULL,
    setting_value TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_system_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Clients (tenants). Client code 13666 is reserved for the system/admin environment.
CREATE TABLE IF NOT EXISTS system_clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_code VARCHAR(20) NOT NULL,
    company_name VARCHAR(191) NOT NULL,
    registry_code VARCHAR(100) NULL,
    address VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(191) NULL,
    contact_person_name VARCHAR(191) NULL,
    contact_person_id_code VARCHAR(100) NULL,
    contact_person_phone VARCHAR(50) NULL,
    contact_person_email VARCHAR(191) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_system_clients_code (client_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Modules catalogue
CREATE TABLE IF NOT EXISTS system_modules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_key VARCHAR(100) NOT NULL,
    name VARCHAR(191) NOT NULL,
    description TEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    is_demo_available TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_system_modules_key (module_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Module version history
CREATE TABLE IF NOT EXISTS system_module_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id INT UNSIGNED NOT NULL,
    version VARCHAR(20) NOT NULL,
    released_at DATETIME NULL,
    changelog TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_module_version (module_id, version),
    CONSTRAINT fk_module_versions_module FOREIGN KEY (module_id) REFERENCES system_modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Languages. English is mandatory and must stay active + default.
CREATE TABLE IF NOT EXISTS system_languages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    language_code VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_system_languages_code (language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Translation keys (one per translatable string, scoped to a module/context)
CREATE TABLE IF NOT EXISTS system_translation_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    translation_key VARCHAR(191) NOT NULL,
    module_context VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_translation_key (translation_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Translation values per language. Missing values fall back to English at read time.
CREATE TABLE IF NOT EXISTS system_translation_values (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    translation_key_id INT UNSIGNED NOT NULL,
    language_id INT UNSIGNED NOT NULL,
    value TEXT NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_translation_value (translation_key_id, language_id),
    CONSTRAINT fk_translation_values_key FOREIGN KEY (translation_key_id) REFERENCES system_translation_keys (id) ON DELETE CASCADE,
    CONSTRAINT fk_translation_values_language FOREIGN KEY (language_id) REFERENCES system_languages (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Users. Every user belongs to exactly one client (system-level users belong to reserved client 13666).
CREATE TABLE IF NOT EXISTS system_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(191) NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(191) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_client_username (client_id, username),
    CONSTRAINT fk_users_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Roles (system-level or client-level, optionally tied to an A-F organisation level)
CREATE TABLE IF NOT EXISTS system_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(100) NOT NULL,
    name VARCHAR(191) NOT NULL,
    scope ENUM('system','client') NOT NULL,
    org_level ENUM('A','B','C','D','E','F') NULL,
    is_system_critical TINYINT(1) NOT NULL DEFAULT 0,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roles_key (role_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Permission types
CREATE TABLE IF NOT EXISTS system_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(100) NOT NULL,
    name VARCHAR(191) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_permissions_key (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Role <-> permission grants, optionally scoped to one module
CREATE TABLE IF NOT EXISTS system_role_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_permission_module (role_id, permission_id, module_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES system_roles (id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES system_permissions (id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_module FOREIGN KEY (module_id) REFERENCES system_modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Organisation units (A-F hierarchy: departments/groups/teams per client)
CREATE TABLE IF NOT EXISTS system_org_units (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    parent_org_unit_id INT UNSIGNED NULL,
    org_level ENUM('A','B','C','D','E','F') NOT NULL,
    name VARCHAR(191) NOT NULL,
    manager_user_id INT UNSIGNED NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_org_units_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_org_units_parent FOREIGN KEY (parent_org_unit_id) REFERENCES system_org_units (id) ON DELETE SET NULL,
    CONSTRAINT fk_org_units_manager FOREIGN KEY (manager_user_id) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. User <-> role assignments, scoped to a client and optionally an org unit
CREATE TABLE IF NOT EXISTS system_user_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    org_unit_id INT UNSIGNED NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES system_roles (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_org_unit FOREIGN KEY (org_unit_id) REFERENCES system_org_units (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Temporary substitutes (asendajad)
CREATE TABLE IF NOT EXISTS system_substitutes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    org_unit_id INT UNSIGNED NULL,
    original_user_id INT UNSIGNED NOT NULL,
    substitute_user_id INT UNSIGNED NOT NULL,
    substitute_type VARCHAR(50) NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    delegated_permissions TEXT NULL COMMENT 'JSON list of permission keys',
    reason TEXT NULL,
    status ENUM('pending','approved','active','ended','rejected') NOT NULL DEFAULT 'pending',
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_substitutes_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_substitutes_org_unit FOREIGN KEY (org_unit_id) REFERENCES system_org_units (id) ON DELETE SET NULL,
    CONSTRAINT fk_substitutes_original_user FOREIGN KEY (original_user_id) REFERENCES system_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_substitutes_substitute_user FOREIGN KEY (substitute_user_id) REFERENCES system_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_substitutes_approved_by FOREIGN KEY (approved_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Audit log (append-only; never physically delete rows)
CREATE TABLE IF NOT EXISTS system_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    client_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    object_type VARCHAR(100) NULL,
    object_id VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    approved_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_user (user_id),
    KEY idx_audit_client (client_id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_approved_by FOREIGN KEY (approved_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. System version log (mirrors CHANGELOG.md)
CREATE TABLE IF NOT EXISTS system_version_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(20) NOT NULL,
    description TEXT NOT NULL,
    affected_modules TEXT NULL,
    database_changes TEXT NULL,
    released_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_version_logs_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Initial setup lock (single row, id = 1)
CREATE TABLE IF NOT EXISTS system_initial_setup (
    id INT UNSIGNED NOT NULL PRIMARY KEY,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    completed_by INT UNSIGNED NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_initial_setup_completed_by FOREIGN KEY (completed_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Seed data (safe to re-run: INSERT IGNORE / ON DUPLICATE KEY UPDATE)
-- ---------------------------------------------------------------------------

-- English is the mandatory default language.
INSERT IGNORE INTO system_languages (language_code, name, is_active, is_default)
VALUES ('en', 'English', 1, 1);

-- Reserved system/admin client. Holds no business data, only system-level users.
INSERT IGNORE INTO system_clients (client_code, company_name, status)
VALUES ('13666', 'DemoIT – System Administration', 'active');

-- Single row that tracks whether the initial secure setup has been completed.
INSERT IGNORE INTO system_initial_setup (id, is_completed)
VALUES (1, 0);

-- Baseline permission types (doc 03 §3).
INSERT IGNORE INTO system_permissions (permission_key, name) VALUES
    ('view', 'View'),
    ('create', 'Create'),
    ('edit', 'Edit'),
    ('deactivate', 'Deactivate'),
    ('approve', 'Approve'),
    ('export', 'Export'),
    ('manage_subordinates', 'Manage subordinates'),
    ('configure_module', 'Configure module'),
    ('view_audit_log', 'View audit log');

-- Baseline system-level roles (doc 03 §1).
INSERT IGNORE INTO system_roles (role_key, name, scope, is_system_critical) VALUES
    ('system_admin', 'System Administrator', 'system', 1),
    ('developer', 'Developer', 'system', 0),
    ('client_manager', 'Client Manager', 'system', 0),
    ('client_support', 'Client Support', 'system', 0);

-- Baseline client-level roles, including the A-F organisation hierarchy (doc 03 §1-2).
INSERT IGNORE INTO system_roles (role_key, name, scope, org_level, is_system_critical) VALUES
    ('client_admin', 'Client Administrator', 'client', NULL, 1),
    ('level_a', 'Level A', 'client', 'A', 0),
    ('level_b', 'Level B', 'client', 'B', 0),
    ('level_c', 'Level C', 'client', 'C', 0),
    ('level_d', 'Level D', 'client', 'D', 0),
    ('level_e', 'Level E', 'client', 'E', 0),
    ('level_f', 'Level F', 'client', 'F', 0),
    ('temp_substitute', 'Temporary Substitute', 'client', NULL, 0),
    ('viewer', 'Viewer', 'client', NULL, 0);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.3', 'Initial core database schema created (system_* tables) and seeded with baseline languages, roles and permissions.', NOW());

-- END MIGRATION: 001_core_schema.sql

-- BEGIN MIGRATION: 002_client_modules.sql
-- DemoIT CRM — Client <-> module activation (v1.6)
-- Idempotent, phpMyAdmin-runnable.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS system_client_modules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    activated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_client_module (client_id, module_id),
    CONSTRAINT fk_client_modules_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_client_modules_module FOREIGN KEY (module_id) REFERENCES system_modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.6', 'Added system_client_modules table for per-client module activation.', NOW());

-- END MIGRATION: 002_client_modules.sql

-- BEGIN MIGRATION: 003_notifications_support.sql
-- DemoIT CRM — Notifications & support tickets (v1.12)
-- Idempotent, phpMyAdmin-runnable.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS system_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL COMMENT 'NULL = broadcast to all users of the client',
    title VARCHAR(191) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_client (client_id),
    KEY idx_notifications_user (user_id),
    CONSTRAINT fk_notifications_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_created_by FOREIGN KEY (created_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS system_support_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    created_by_user_id INT UNSIGNED NOT NULL,
    assigned_to_user_id INT UNSIGNED NULL,
    subject VARCHAR(191) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open','in_progress','closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_tickets_client (client_id),
    KEY idx_tickets_status (status),
    CONSTRAINT fk_tickets_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_tickets_created_by FOREIGN KEY (created_by_user_id) REFERENCES system_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_tickets_assigned_to FOREIGN KEY (assigned_to_user_id) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.12', 'Added system_notifications and system_support_tickets tables.', NOW());

-- END MIGRATION: 003_notifications_support.sql

-- BEGIN MIGRATION: 004_module_patients.sql
-- DemoIT CRM — "Patients" business module (v1.13)
-- Idempotent, phpMyAdmin-runnable.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(191) NOT NULL,
    personal_code VARCHAR(50) NULL,
    birth_date DATE NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(191) NULL,
    notes TEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_patients_client (client_id),
    CONSTRAINT fk_patients_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_patients_created_by FOREIGN KEY (created_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Register the module in the catalogue (clients still need it activated individually via /admin/clients/view).
INSERT IGNORE INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('patients', 'Patients', 'Patient records management.', 'active', 1);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.13', 'Added first business module: Patients (patients table + module catalogue entry).', NOW());

-- END MIGRATION: 004_module_patients.sql

-- BEGIN MIGRATION: 005_module_organisation.sql
-- DemoIT CRM — "Organisation & Substitutes" module registration (v1.18)
-- Idempotent, phpMyAdmin-runnable. Tables already exist (system_org_units, system_substitutes from 001_core_schema.sql);
-- this migration only registers the module in the catalogue so clients can activate it like any other module.

SET NAMES utf8mb4;

INSERT IGNORE INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('organisation', 'Organisation & Substitutes', 'A-F organisation hierarchy and temporary substitute management.', 'active', 1);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.18', 'Registered Organisation & Substitutes as an activatable module instead of always-on panel menu items.', NOW());

-- END MIGRATION: 005_module_organisation.sql

-- BEGIN MIGRATION: 006_employee_users.sql
ALTER TABLE system_users
    ADD COLUMN phone VARCHAR(50) NULL AFTER email;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.19', 'Employee user management: phone, editing, default Level F access and account e-mail delivery.', NOW());
-- END MIGRATION: 006_employee_users.sql

-- BEGIN MIGRATION: 007_legacy_employee_import.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.20', 'Legacy db_user_data SQL upload and employee import added.', NOW());
-- END MIGRATION: 007_legacy_employee_import.sql

-- BEGIN MIGRATION: 008_module_tenant_provisioning.sql
-- Shared-table tenant provisioning for activatable modules.

CREATE TABLE IF NOT EXISTS system_client_module_provisions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NOT NULL,
    schema_version VARCHAR(30) NOT NULL,
    status ENUM('ready','inactive') NOT NULL DEFAULT 'ready',
    provisioned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_client_module_provision (client_id, module_id),
    CONSTRAINT fk_module_provisions_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_module_provisions_module FOREIGN KEY (module_id) REFERENCES system_modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employee_module_settings (
    client_id INT UNSIGNED PRIMARY KEY,
    default_role_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_employee_settings_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_settings_default_role FOREIGN KEY (default_role_id) REFERENCES system_roles (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('employees', 'Employees', 'Employee accounts, access levels and legacy employee import.', 'active', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), status = 'active';

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.21', 'Added transactional per-client module provisioning and the Employees module tenant settings.', NOW());
-- END MIGRATION: 008_module_tenant_provisioning.sql

-- BEGIN MIGRATION: 009_employee_module_documentation.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.22', 'Added the Employees module administrator manual and normative AI development guide.', NOW());
-- END MIGRATION: 009_employee_module_documentation.sql

-- BEGIN MIGRATION: 010_readme_version_history.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.23', 'Added a GitHub README version history with a link to the full changelog.', NOW());
-- END MIGRATION: 010_readme_version_history.sql

-- BEGIN MIGRATION: 011_signed_in_display_name.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.24', 'Show the signed-in person name in greetings, falling back to username.', NOW());
-- END MIGRATION: 011_signed_in_display_name.sql

-- BEGIN MIGRATION: 012_employee_edit_org_unit.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.25', 'Hide organisation unit selection on employee edit while preserving the existing assignment.', NOW());
-- END MIGRATION: 012_employee_edit_org_unit.sql

-- BEGIN MIGRATION: 013_employee_create_org_unit.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.26', 'Hide organisation unit selection on employee creation.', NOW());
-- END MIGRATION: 013_employee_create_org_unit.sql

-- BEGIN MIGRATION: 014_bilingual_changelog.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.27', 'Added a full English translation to the bilingual changelog.', NOW());
-- END MIGRATION: 014_bilingual_changelog.sql

-- BEGIN MIGRATION: 015_property_structure.sql
CREATE TABLE IF NOT EXISTS property_nodes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL,
    node_type ENUM('building', 'wing', 'floor', 'room') NOT NULL,
    name VARCHAR(191) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_property_nodes_client_id (client_id, id),
    KEY idx_property_nodes_parent (client_id, parent_id),
    CONSTRAINT fk_property_nodes_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_property_nodes_parent FOREIGN KEY (client_id, parent_id) REFERENCES property_nodes (client_id, id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('property', 'Property structure', 'Client buildings, wings, floors and rooms.', 'active', 1);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.28', 'Added an independent property structure module for buildings, wings, floors and rooms.', NOW());
-- END MIGRATION: 015_property_structure.sql

-- BEGIN MIGRATION: 016_group_panel_modules.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.29', 'Grouped activated module links under one client panel Modules heading.', NOW());
-- END MIGRATION: 016_group_panel_modules.sql

-- BEGIN MIGRATION: 017_employment_module.sql
SET NAMES utf8mb4;

SET @employment_user_index_sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE system_users ADD UNIQUE KEY uq_system_users_client_id_id (client_id, id)',
        'SELECT 1'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'system_users'
      AND INDEX_NAME = 'uq_system_users_client_id_id'
);
PREPARE employment_user_index_stmt FROM @employment_user_index_sql;
EXECUTE employment_user_index_stmt;
DEALLOCATE PREPARE employment_user_index_stmt;

CREATE TABLE IF NOT EXISTS employment_job_titles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    name VARCHAR(191) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employment_job_titles_client_id (client_id, id),
    UNIQUE KEY uq_employment_job_titles_name (client_id, name),
    CONSTRAINT fk_employment_job_titles_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employment_departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    name VARCHAR(191) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employment_departments_client_id (client_id, id),
    UNIQUE KEY uq_employment_departments_name (client_id, name),
    CONSTRAINT fk_employment_departments_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employment_contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    property_node_id INT UNSIGNED NOT NULL,
    job_title_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NULL,
    manager_user_id INT UNSIGNED NULL,
    contract_type ENUM('primary', 'temporary') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    archived_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employment_contracts_client_id (client_id, id),
    KEY idx_employment_contracts_employee_period (client_id, employee_id, contract_type, start_date, end_date),
    KEY idx_employment_contracts_property (client_id, property_node_id),
    CONSTRAINT fk_employment_contracts_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_employment_contracts_employee FOREIGN KEY (client_id, employee_id) REFERENCES system_users (client_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_employment_contracts_property FOREIGN KEY (client_id, property_node_id) REFERENCES property_nodes (client_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_employment_contracts_job_title FOREIGN KEY (client_id, job_title_id) REFERENCES employment_job_titles (client_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_employment_contracts_department FOREIGN KEY (client_id, department_id) REFERENCES employment_departments (client_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_employment_contracts_manager FOREIGN KEY (client_id, manager_user_id) REFERENCES system_users (client_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_employment_contracts_created_by FOREIGN KEY (created_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('employment', 'Employment', 'Job titles, departments and employee contracts.', 'active', 1);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.30', 'Added employment job titles, departments and primary/temporary employee contracts.', NOW());
-- END MIGRATION: 017_employment_module.sql

-- BEGIN MIGRATION: 018_employment_workloads.sql
CREATE TABLE IF NOT EXISTS employment_workloads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    name VARCHAR(191) NOT NULL,
    workload_percent DECIMAL(5,2) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employment_workloads_client_id (client_id, id),
    UNIQUE KEY uq_employment_workloads_name (client_id, name),
    CONSTRAINT fk_employment_workloads_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO employment_workloads (client_id, name, workload_percent, status)
SELECT id, 'Full-time (100%)', 100.00, 'active' FROM system_clients;

SET @employment_workload_id_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE employment_contracts ADD COLUMN workload_id INT UNSIGNED NULL AFTER job_title_id',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts' AND COLUMN_NAME = 'workload_id'
);
PREPARE employment_workload_id_stmt FROM @employment_workload_id_sql;
EXECUTE employment_workload_id_stmt;
DEALLOCATE PREPARE employment_workload_id_stmt;

SET @employment_workload_percent_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE employment_contracts ADD COLUMN workload_percent DECIMAL(5,2) NOT NULL DEFAULT 100.00 AFTER workload_id',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts' AND COLUMN_NAME = 'workload_percent'
);
PREPARE employment_workload_percent_stmt FROM @employment_workload_percent_sql;
EXECUTE employment_workload_percent_stmt;
DEALLOCATE PREPARE employment_workload_percent_stmt;

UPDATE employment_contracts c
INNER JOIN employment_workloads w ON w.client_id = c.client_id AND w.name = 'Full-time (100%)'
SET c.workload_id = w.id
WHERE c.workload_id IS NULL;

SET @employment_workload_not_null_sql = (
    SELECT IF(IS_NULLABLE = 'YES',
        'ALTER TABLE employment_contracts MODIFY COLUMN workload_id INT UNSIGNED NOT NULL',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts' AND COLUMN_NAME = 'workload_id'
);
PREPARE employment_workload_not_null_stmt FROM @employment_workload_not_null_sql;
EXECUTE employment_workload_not_null_stmt;
DEALLOCATE PREPARE employment_workload_not_null_stmt;

SET @employment_workload_fk_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE employment_contracts ADD CONSTRAINT fk_employment_contracts_workload FOREIGN KEY (client_id, workload_id) REFERENCES employment_workloads (client_id, id) ON DELETE RESTRICT',
        'SELECT 1')
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts' AND CONSTRAINT_NAME = 'fk_employment_contracts_workload'
);
PREPARE employment_workload_fk_stmt FROM @employment_workload_fk_sql;
EXECUTE employment_workload_fk_stmt;
DEALLOCATE PREPARE employment_workload_fk_stmt;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.31', 'Added configurable workload percentages and monthly required-hours calculation to employment contracts.', NOW());
-- END MIGRATION: 018_employment_workloads.sql

-- BEGIN MIGRATION: 019_contract_workload_schedule_scope.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.32', 'Keep workload on employment contracts and defer monthly active-workday hours calculation to scheduling.', NOW());
-- END MIGRATION: 019_contract_workload_schedule_scope.sql

-- BEGIN MIGRATION: 020_contract_property_path.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.33', 'Display full client property hierarchy paths in the employment contract list.', NOW());
-- END MIGRATION: 020_contract_property_path.sql

-- BEGIN MIGRATION: 021_property_sibling_order.sql
SET @property_sort_order_column_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE property_nodes ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER name',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_nodes' AND COLUMN_NAME = 'sort_order'
);
PREPARE property_sort_order_column_stmt FROM @property_sort_order_column_sql;
EXECUTE property_sort_order_column_stmt;
DEALLOCATE PREPARE property_sort_order_column_stmt;

UPDATE property_nodes SET sort_order = id WHERE sort_order = 0;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.34', 'Added persistent sibling ordering to the property structure hierarchy.', NOW());
-- END MIGRATION: 021_property_sibling_order.sql

-- BEGIN MIGRATION: 022_employee_active_contract_column.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.35', 'Show currently valid contract types in the employee list.', NOW());
-- END MIGRATION: 022_employee_active_contract_column.sql

-- BEGIN MIGRATION: 023_employee_scheduling.sql
CREATE TABLE IF NOT EXISTS employee_schedule_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    template_type ENUM('shift', 'exception') NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    color_hex CHAR(7) NOT NULL DEFAULT '#0000FF',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employee_schedule_templates_client_id (client_id, id),
    UNIQUE KEY uq_employee_schedule_templates_code (client_id, template_type, code),
    CONSTRAINT fk_employee_schedule_templates_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employee_schedule_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    contract_id INT UNSIGNED NOT NULL,
    schedule_date DATE NOT NULL,
    template_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_employee_schedule_employee_day (client_id, employee_id, schedule_date),
    UNIQUE KEY uq_employee_schedule_contract_day (client_id, contract_id, schedule_date),
    KEY idx_employee_schedule_contract_month (client_id, contract_id, schedule_date),
    CONSTRAINT fk_employee_schedule_entries_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_employee_schedule_entries_employee FOREIGN KEY (client_id, employee_id) REFERENCES system_users (client_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_employee_schedule_entries_contract FOREIGN KEY (client_id, contract_id) REFERENCES employment_contracts (client_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_employee_schedule_entries_template FOREIGN KEY (client_id, template_id) REFERENCES employee_schedule_templates (client_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_employee_schedule_entries_created_by FOREIGN KEY (created_by) REFERENCES system_users (id) ON DELETE SET NULL,
    CONSTRAINT fk_employee_schedule_entries_updated_by FOREIGN KEY (updated_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO employee_schedule_templates (client_id, template_type, code, name, start_time, duration_minutes, color_hex, status)
SELECT c.id, defaults.template_type, defaults.code, defaults.name, defaults.start_time, defaults.duration_minutes, defaults.color_hex, 'active'
FROM system_clients c
CROSS JOIN (
    SELECT 'shift' AS template_type, '12Ö' AS code, '12-hour night shift' AS name, '20:00:00' AS start_time, 720 AS duration_minutes, '#0000FF' AS color_hex
    UNION ALL SELECT 'shift', '12P', '12-hour day shift', '08:00:00', 720, '#008000'
    UNION ALL SELECT 'shift', '24H', '24-hour shift', '08:00:00', 1440, '#EE82EE'
    UNION ALL SELECT 'shift', '8P', '8-hour day shift', '08:00:00', 480, '#FFA500'
    UNION ALL SELECT 'exception', 'HP', 'HP', '08:00:00', 480, '#FF0000'
    UNION ALL SELECT 'exception', 'K', 'K', '08:00:00', 480, '#FF0000'
    UNION ALL SELECT 'exception', 'LHP', 'LHP', '08:00:00', 480, '#FF0000'
    UNION ALL SELECT 'exception', 'LIP', 'LIP', '08:00:00', 480, '#FF0000'
    UNION ALL SELECT 'exception', 'P', 'P', '08:00:00', 480, '#FF0000'
    UNION ALL SELECT 'exception', 'TV', 'TV', '08:00:00', 480, '#FF0000'
    UNION ALL SELECT 'exception', 'X', 'X', '08:00:00', 480, '#FF0000'
) AS defaults;

INSERT IGNORE INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('schedule', 'Schedule', 'Monthly employee schedules, shifts and exceptions.', 'active', 1);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.36', 'Added monthly employee schedules with configurable shifts, exceptions and manager-scoped floor rosters.', NOW());
-- END MIGRATION: 023_employee_scheduling.sql

-- BEGIN MIGRATION: 024_schedule_save_optimization.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.37', 'Avoid redundant deletes for empty schedule cells during monthly roster saves.', NOW());

-- END MIGRATION: 024_schedule_save_optimization.sql

-- BEGIN MIGRATION: 025_schedule_save_error_logging.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.38', 'Log server-side details for monthly schedule save failures.', NOW());

-- END MIGRATION: 025_schedule_save_error_logging.sql

-- BEGIN MIGRATION: 026_schedule_error_code_feedback.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.39', 'Expose safe SQLSTATE diagnostics to client administrators for schedule save failures.', NOW());

-- END MIGRATION: 026_schedule_error_code_feedback.sql

-- BEGIN MIGRATION: 027_schedule_audit_columns.sql
-- PHP repair handler equivalent: employee_schedule_entries.created_by
SET @crm_repair_sql = (
    SELECT IF(COUNT(*) = 0, 'ALTER TABLE employee_schedule_entries ADD COLUMN created_by INT UNSIGNED NULL', 'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'created_by'
);
PREPARE crm_repair_stmt FROM @crm_repair_sql;
EXECUTE crm_repair_stmt;
DEALLOCATE PREPARE crm_repair_stmt;

-- PHP repair handler equivalent: employee_schedule_entries.updated_by
SET @crm_repair_sql = (
    SELECT IF(COUNT(*) = 0, 'ALTER TABLE employee_schedule_entries ADD COLUMN updated_by INT UNSIGNED NULL', 'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'updated_by'
);
PREPARE crm_repair_stmt FROM @crm_repair_sql;
EXECUTE crm_repair_stmt;
DEALLOCATE PREPARE crm_repair_stmt;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.40', 'Repair missing schedule audit columns on existing installs.', NOW());

-- END MIGRATION: 027_schedule_audit_columns.sql

-- BEGIN MIGRATION: 028_schedule_repair_compatibility.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.41', 'Use the PHP installer for conditional schedule audit-column repairs.', NOW());

-- END MIGRATION: 028_schedule_repair_compatibility.sql

-- BEGIN MIGRATION: 029_schedule_modal_picker.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.42', 'Added modal-based shift and exception selection to the monthly schedule.', NOW());

-- END MIGRATION: 029_schedule_modal_picker.sql

-- BEGIN MIGRATION: 030_schedule_autosave.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.43', 'Autosave monthly schedule changes after a modal selection.', NOW());

-- END MIGRATION: 030_schedule_autosave.sql

-- BEGIN MIGRATION: 031_schedule_template_colors.sql
-- PHP repair handler equivalent: employee_schedule_templates.color_hex
SET @crm_repair_sql = (
    SELECT IF(COUNT(*) = 0, 'ALTER TABLE employee_schedule_templates ADD COLUMN color_hex CHAR(7) NOT NULL DEFAULT ''#64748B''', 'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_templates' AND COLUMN_NAME = 'color_hex'
);
PREPARE crm_repair_stmt FROM @crm_repair_sql;
EXECUTE crm_repair_stmt;
DEALLOCATE PREPARE crm_repair_stmt;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.44', 'Add configurable colors to employee schedule templates.', NOW());

-- END MIGRATION: 031_schedule_template_colors.sql

-- BEGIN MIGRATION: 032_schedule_color_allowlist.sql
UPDATE employee_schedule_templates
SET color_hex = CASE UPPER(color_hex)
    WHEN '#2563EB' THEN '#0000FF'
    WHEN '#0F766E' THEN '#008000'
    WHEN '#7C3AED' THEN '#4B0082'
    WHEN '#D97706' THEN '#FFA500'
    WHEN '#475569' THEN '#000000'
    WHEN '#64748B' THEN '#000000'
    ELSE '#0000FF'
END
WHERE UPPER(color_hex) NOT IN (
    '#FF0000', '#FFA500', '#FFFF00', '#008000', '#0000FF',
    '#4B0082', '#EE82EE', '#000000', '#FFFFFF'
);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.45', 'Restrict schedule template colors to the named rainbow, black and white choices.', NOW());

-- END MIGRATION: 032_schedule_color_allowlist.sql

-- BEGIN MIGRATION: 033_schedule_modal_active_templates.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.46', 'List active schedule templates only in the shift picker modal.', NOW());

-- END MIGRATION: 033_schedule_modal_active_templates.sql

-- BEGIN MIGRATION: 034_schedule_collapsible_groups.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.47', 'Add independent collapsible employee groups to the monthly schedule.', NOW());

-- END MIGRATION: 034_schedule_collapsible_groups.sql

-- BEGIN MIGRATION: 035_schedule_viewport_width.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.48', 'Fit the monthly schedule grid to available viewport width.', NOW());

-- END MIGRATION: 035_schedule_viewport_width.sql

-- BEGIN MIGRATION: 036_schedule_workload_under_employee.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.49', 'Show workload percentage beneath employee name in the schedule grid.', NOW());

-- END MIGRATION: 036_schedule_workload_under_employee.sql

-- BEGIN MIGRATION: 037_schedule_hour_balances.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.50', 'Show planned, required, monthly overtime and trimester-to-date hours in the schedule.', NOW());

-- END MIGRATION: 037_schedule_hour_balances.sql

-- BEGIN MIGRATION: 038_schedule_balance_autosave.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.51', 'Refresh all four schedule hour summaries after autosave.', NOW());

-- END MIGRATION: 038_schedule_balance_autosave.sql

-- BEGIN MIGRATION: 039_schedule_four_month_balance.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.52', 'Use four-month cumulative periods for Tri-OT balances.', NOW());

-- END MIGRATION: 039_schedule_four_month_balance.sql

-- BEGIN MIGRATION: 040_schedule_cross_month_hours.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.53', 'Allocate shift minutes across month boundaries with four-month-period end handling.', NOW());

-- END MIGRATION: 040_schedule_cross_month_hours.sql

-- BEGIN MIGRATION: 041_schedule_month_boundary_balances.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.54', 'Allocate shift minutes across calendar months with four-month period-end handling.', NOW());

-- END MIGRATION: 041_schedule_month_boundary_balances.sql

-- BEGIN MIGRATION: 042_schedule_contract_day_index.sql
-- PHP repair handler equivalent: employee_schedule_entries.idx_employee_schedule_employee_day
SET @crm_repair_sql = (
    SELECT IF(COUNT(*) = 0, 'ALTER TABLE employee_schedule_entries ADD KEY idx_employee_schedule_employee_day (client_id, employee_id, schedule_date)', 'SELECT 1')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND INDEX_NAME = 'idx_employee_schedule_employee_day'
);
PREPARE crm_repair_stmt FROM @crm_repair_sql;
EXECUTE crm_repair_stmt;
DEALLOCATE PREPARE crm_repair_stmt;

-- PHP repair handler equivalent: employee_schedule_entries.uq_employee_schedule_day
SET @crm_repair_sql = (
    SELECT IF(COUNT(*) > 0, 'ALTER TABLE employee_schedule_entries DROP INDEX uq_employee_schedule_day', 'SELECT 1')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND INDEX_NAME = 'uq_employee_schedule_day'
);
PREPARE crm_repair_stmt FROM @crm_repair_sql;
EXECUTE crm_repair_stmt;
DEALLOCATE PREPARE crm_repair_stmt;

-- PHP repair handler equivalent: employee_schedule_entries.uq_employee_schedule_contract_day
SET @crm_repair_sql = (
    SELECT IF(COUNT(*) = 0, 'ALTER TABLE employee_schedule_entries ADD UNIQUE KEY uq_employee_schedule_contract_day (client_id, contract_id, schedule_date)', 'SELECT 1')
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND INDEX_NAME = 'uq_employee_schedule_contract_day'
);
PREPARE crm_repair_stmt FROM @crm_repair_sql;
EXECUTE crm_repair_stmt;
DEALLOCATE PREPARE crm_repair_stmt;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.55', 'Allow one schedule entry per employee contract and date.', NOW());

-- END MIGRATION: 042_schedule_contract_day_index.sql

-- BEGIN MIGRATION: 043_schedule_temp_label.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.56', 'Remove the temporary label from the planned-hours breakdown.', NOW());

-- END MIGRATION: 043_schedule_temp_label.sql

-- BEGIN MIGRATION: 044_schedule_weekday_exception_hours.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.57', 'Count exception-template duration as Planned on weekdays only.', NOW());

-- END MIGRATION: 044_schedule_weekday_exception_hours.sql

-- BEGIN MIGRATION: 045_schedule_single_cell_autosave.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.58', 'Submit only the selected schedule assignment during autosave.', NOW());

-- END MIGRATION: 045_schedule_single_cell_autosave.sql

-- BEGIN MIGRATION: 046_employment_contract_archive.sql
-- PHP repair handler equivalent: employment_contracts.archived_at
SET @crm_repair_sql = (
    SELECT IF(COUNT(*) = 0, 'ALTER TABLE employment_contracts ADD COLUMN archived_at DATETIME NULL', 'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts' AND COLUMN_NAME = 'archived_at'
);
PREPARE crm_repair_stmt FROM @crm_repair_sql;
EXECUTE crm_repair_stmt;
DEALLOCATE PREPARE crm_repair_stmt;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.59', 'Add reversible soft-archive metadata to employment contracts.', NOW());

-- END MIGRATION: 046_employment_contract_archive.sql

-- BEGIN MIGRATION: 047_schedule_block_templates.sql
ALTER TABLE employee_schedule_templates
    MODIFY COLUMN template_type ENUM('shift', 'exception', 'block') NOT NULL;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.60', 'Add Block schedule templates that prevent overlapping entries without adding planned hours.', NOW());
-- END MIGRATION: 047_schedule_block_templates.sql

-- BEGIN MIGRATION: 048_ui_languages.sql
INSERT INTO system_languages (language_code, name, is_active, is_default)
VALUES ('et', 'Eesti', 1, 0), ('ru', 'Русский', 1, 0)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = 1;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context) VALUES
    ('layout.language', 'layout'),
    ('layout.signed_in_as', 'layout'),
    ('layout.log_out', 'layout'),
    ('layout.client_login', 'layout'),
    ('admin.dashboard', 'admin'),
    ('admin.client_solutions', 'admin'),
    ('admin.client_list', 'admin'),
    ('admin.add_client', 'admin'),
    ('admin.modules', 'admin'),
    ('admin.all_modules', 'admin'),
    ('admin.add_module', 'admin'),
    ('admin.dev_languages', 'admin'),
    ('admin.languages', 'admin'),
    ('admin.translations', 'admin'),
    ('admin.missing_translations', 'admin'),
    ('admin.users_permissions', 'admin'),
    ('admin.roles', 'admin'),
    ('admin.system_logs', 'admin'),
    ('admin.notifications', 'admin'),
    ('admin.support', 'admin'),
    ('admin.system_settings', 'admin'),
    ('panel.dashboard', 'panel'),
    ('panel.client_management', 'panel'),
    ('panel.modules', 'panel'),
    ('panel.active_modules', 'panel'),
    ('panel.employees_permissions', 'panel'),
    ('panel.patients', 'panel'),
    ('panel.property_structure', 'panel'),
    ('panel.employment', 'panel'),
    ('panel.work_schedule', 'panel'),
    ('panel.organisation', 'panel'),
    ('panel.substitutes', 'panel'),
    ('panel.reports_statistics', 'panel'),
    ('panel.settings', 'panel'),
    ('panel.support', 'panel'),
    ('panel.notifications', 'panel'),
    ('panel.my_account', 'panel');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_key_row.id, languages.id,
       CASE languages.language_code
           WHEN 'en' THEN seed.english_value
           WHEN 'et' THEN seed.estonian_value
           WHEN 'ru' THEN seed.russian_value
       END,
       'active'
FROM (
    SELECT 'layout.language' AS translation_key, 'Language' AS english_value, 'Keel' AS estonian_value, 'Язык' AS russian_value
    UNION ALL SELECT 'layout.signed_in_as', 'Signed in as', 'Sisse logitud:', 'Вы вошли как'
    UNION ALL SELECT 'layout.log_out', 'Log out', 'Logi välja', 'Выйти'
    UNION ALL SELECT 'layout.client_login', 'Client login', 'Kliendi sisselogimine', 'Вход клиента'
    UNION ALL SELECT 'admin.dashboard', 'Dashboard', 'Töölaud', 'Панель управления'
    UNION ALL SELECT 'admin.client_solutions', 'Client solutions', 'Kliendilahendused', 'Клиентские решения'
    UNION ALL SELECT 'admin.client_list', 'Client list', 'Kliendid', 'Список клиентов'
    UNION ALL SELECT 'admin.add_client', 'Add client', 'Lisa klient', 'Добавить клиента'
    UNION ALL SELECT 'admin.modules', 'Modules', 'Moodulid', 'Модули'
    UNION ALL SELECT 'admin.all_modules', 'All modules', 'Kõik moodulid', 'Все модули'
    UNION ALL SELECT 'admin.add_module', 'Add module', 'Lisa moodul', 'Добавить модуль'
    UNION ALL SELECT 'admin.dev_languages', 'Dev module: languages', 'Arendusmoodul: keeled', 'Модуль разработки: языки'
    UNION ALL SELECT 'admin.languages', 'Languages', 'Keeled', 'Языки'
    UNION ALL SELECT 'admin.translations', 'Translations', 'Tõlked', 'Переводы'
    UNION ALL SELECT 'admin.missing_translations', 'Missing translations', 'Puuduvad tõlked', 'Отсутствующие переводы'
    UNION ALL SELECT 'admin.users_permissions', 'Users & permissions', 'Kasutajad ja õigused', 'Пользователи и права'
    UNION ALL SELECT 'admin.roles', 'Roles', 'Rollid', 'Роли'
    UNION ALL SELECT 'admin.system_logs', 'System logs', 'Süsteemi logid', 'Системные журналы'
    UNION ALL SELECT 'admin.notifications', 'Notifications', 'Teavitused', 'Уведомления'
    UNION ALL SELECT 'admin.support', 'Support', 'Tugi', 'Поддержка'
    UNION ALL SELECT 'admin.system_settings', 'System settings', 'Süsteemi seaded', 'Настройки системы'
    UNION ALL SELECT 'panel.dashboard', 'Dashboard', 'Töölaud', 'Панель управления'
    UNION ALL SELECT 'panel.client_management', 'Client management', 'Kliendihaldus', 'Управление клиентом'
    UNION ALL SELECT 'panel.modules', 'Modules', 'Moodulid', 'Модули'
    UNION ALL SELECT 'panel.active_modules', 'Active modules', 'Aktiivsed moodulid', 'Активные модули'
    UNION ALL SELECT 'panel.employees_permissions', 'Employees & permissions', 'Töötajad ja õigused', 'Сотрудники и права'
    UNION ALL SELECT 'panel.patients', 'Patients', 'Patsiendid', 'Пациенты'
    UNION ALL SELECT 'panel.property_structure', 'Property structure', 'Kinnistu struktuur', 'Структура объекта'
    UNION ALL SELECT 'panel.employment', 'Employment', 'Töölepingud', 'Трудовые договоры'
    UNION ALL SELECT 'panel.work_schedule', 'Work schedule', 'Töögraafik', 'Рабочий график'
    UNION ALL SELECT 'panel.organisation', 'Organisation', 'Organisatsioon', 'Организация'
    UNION ALL SELECT 'panel.substitutes', 'Substitutes', 'Asendajad', 'Замещения'
    UNION ALL SELECT 'panel.reports_statistics', 'Reports & statistics', 'Aruanded ja statistika', 'Отчёты и статистика'
    UNION ALL SELECT 'panel.settings', 'Settings', 'Seaded', 'Настройки'
    UNION ALL SELECT 'panel.support', 'Support', 'Tugi', 'Поддержка'
    UNION ALL SELECT 'panel.notifications', 'Notifications', 'Teavitused', 'Уведомления'
    UNION ALL SELECT 'panel.my_account', 'My account', 'Minu konto', 'Моя учётная запись'
) AS seed
INNER JOIN system_translation_keys translation_key_row ON translation_key_row.translation_key = seed.translation_key
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.61', 'Add session language selection and database-backed shared UI translations for English, Estonian and Russian.', NOW());
-- END MIGRATION: 048_ui_languages.sql

-- BEGIN MIGRATION: 049_ui_static_translations.sql
DROP TEMPORARY TABLE IF EXISTS ui_translation_seed;

CREATE TEMPORARY TABLE ui_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL,
    PRIMARY KEY (english_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO ui_translation_seed (english_value, estonian_value, russian_value) VALUES
('© DemoIT CRM', '© DemoIT CRM', '© DemoIT CRM'),
('A modular business management system. Clients activate only the modules they need, and active modules share data and workflows.', 'Modulaarne ettevõtte haldussüsteem. Kliendid aktiveerivad vajalikud moodulid ning aktiivsed moodulid jagavad andmeid ja töövooge.', 'Модульная система управления бизнесом. Клиенты активируют нужные модули, а активные модули обмениваются данными и рабочими процессами.'),
('A temporary password will be generated automatically.', 'Ajutine parool luuakse automaatselt.', 'Временный пароль будет создан автоматически.'),
('Access level', 'Juurdepääsutase', 'Уровень доступа'),
('Action', 'Toiming', 'Действие'),
('Activated for this client', 'Selle kliendi jaoks aktiveeritud', 'Активировано для этого клиента'),
('Active contract', 'Aktiivne leping', 'Действующий договор'),
('Active modules', 'Aktiivsed moodulid', 'Активные модули'),
('Active users', 'Aktiivsed kasutajad', 'Активные пользователи'),
('Add', 'Lisa', 'Добавить'),
('Add / update a setting', 'Lisa või muuda seadistust', 'Добавить или изменить настройку'),
('Add building', 'Lisa hoone', 'Добавить здание'),
('Add client', 'Lisa klient', 'Добавить клиента'),
('Add contract', 'Lisa leping', 'Добавить договор'),
('Add employee', 'Lisa töötaja', 'Добавить сотрудника'),
('Add inside', 'Lisa siseüksus', 'Добавить внутреннее подразделение'),
('Add key', 'Lisa võti', 'Добавить ключ'),
('Add language', 'Lisa keel', 'Добавить язык'),
('Add location', 'Lisa asukoht', 'Добавить местоположение'),
('Add module', 'Lisa moodul', 'Добавить модуль'),
('Add organisation unit', 'Lisa organisatsiooniüksus', 'Добавить организационное подразделение'),
('Add patient', 'Lisa patsient', 'Добавить пациента'),
('Add template', 'Lisa mall', 'Добавить шаблон'),
('Add translation', 'Lisa tõlge', 'Добавить перевод'),
('Add translation key', 'Lisa tõlkevõti', 'Добавить ключ перевода'),
('Add unit', 'Lisa üksus', 'Добавить подразделение'),
('Address', 'Aadress', 'Адрес'),
('All modules', 'Kõik moodulid', 'Все модули'),
('All users of this client (broadcast)', 'Kõik selle kliendi kasutajad (teavitus kõigile)', 'Все пользователи этого клиента (рассылка всем)'),
('Approve', 'Kinnita', 'Одобрить'),
('Available in demo mode', 'Demorežiimis saadaval', 'Доступно в деморежиме'),
('Back', 'Tagasi', 'Назад'),
('Back to home', 'Avalehele', 'На главную'),
('Back to list', 'Tagasi loendisse', 'Назад к списку'),
('Back to translations', 'Tagasi tõlgete juurde', 'Назад к переводам'),
('Block', 'Blokk', 'Блок'),
('Cancel', 'Tühista', 'Отмена'),
('Catalogue', 'Kataloog', 'Каталог'),
('Change my password', 'Muuda minu parooli', 'Изменить мой пароль'),
('Change password', 'Muuda parooli', 'Изменить пароль'),
('Choose shift, exception or block', 'Vali vahetus, erand või blokk', 'Выберите смену, исключение или блок'),
('Client', 'Klient', 'Клиент'),
('Client code', 'Kliendikood', 'Код клиента'),
('Client created. Temporary password for the first user (shown only once):', 'Klient on loodud. Esimese kasutaja ajutine parool (kuvatakse ainult üks kord):', 'Клиент создан. Временный пароль первого пользователя (показывается только один раз):'),
('Client details', 'Kliendi andmed', 'Данные клиента'),
('Client list', 'Klientide loend', 'Список клиентов'),
('Client login', 'Kliendi sisselogimine', 'Вход клиента'),
('Client management', 'Kliendihaldus', 'Управление клиентом'),
('Client:', 'Klient:', 'Клиент:'),
('Close', 'Sulge', 'Закрыть'),
('Code', 'Kood', 'Код'),
('Collapse group', 'Ahenda rühm', 'Свернуть группу'),
('Color', 'Värv', 'Цвет'),
('Company name', 'Ettevõtte nimi', 'Название компании'),
('Confirm new password', 'Kinnita uus parool', 'Подтвердите новый пароль'),
('Contact', 'Kontakt', 'Контакт'),
('Contact e-mail', 'Kontakti e-post', 'Электронная почта контакта'),
('Contact phone', 'Kontakti telefon', 'Телефон контакта'),
('Contract', 'Leping', 'Договор'),
('Contract type', 'Lepingu tüüp', 'Тип договора'),
('Contracts', 'Lepingud', 'Договоры'),
('Create administrator', 'Loo administraator', 'Создать администратора'),
('Create client', 'Loo klient', 'Создать клиента'),
('Create employee', 'Loo töötaja', 'Создать сотрудника'),
('Create key', 'Loo võti', 'Создать ключ'),
('Create language', 'Loo keel', 'Создать язык'),
('Create module', 'Loo moodul', 'Создать модуль'),
('Create patient', 'Loo patsient', 'Создать пациента'),
('Create the first system administrator account. This can only be done once.', 'Loo esimene süsteemiadministraatori konto. Seda saab teha ainult ühe korra.', 'Создайте первую учётную запись системного администратора. Это можно сделать только один раз.'),
('Create unit', 'Loo üksus', 'Создать подразделение'),
('Created', 'Loodud', 'Создано'),
('Created by', 'Looja', 'Автор'),
('Current password', 'Praegune parool', 'Текущий пароль'),
('Dashboard', 'Töölaud', 'Панель управления'),
('Date of birth', 'Sünnikuupäev', 'Дата рождения'),
('Day off', 'Vaba päev', 'Выходной'),
('Default', 'Vaikimisi', 'По умолчанию'),
('Demo', 'Demo', 'Демо'),
('DemoIT CRM', 'DemoIT CRM', 'DemoIT CRM'),
('Department', 'Osakond', 'Отдел'),
('Departments', 'Osakonnad', 'Отделы'),
('Description', 'Kirjeldus', 'Описание'),
('Display name', 'Kuvatav nimi', 'Отображаемое имя'),
('Down', 'Alla', 'Вниз'),
('Duration', 'Kestus', 'Длительность'),
('Duration (hours)', 'Kestus (tundides)', 'Длительность (часы)'),
('Edit', 'Muuda', 'Изменить'),
('Edit employee', 'Muuda töötajat', 'Изменить сотрудника'),
('Edit name', 'Muuda nime', 'Изменить название'),
('E-mail', 'E-post', 'Электронная почта'),
('Employee', 'Töötaja', 'Сотрудник'),
('Employee code', 'Töötaja kood', 'Код сотрудника'),
('Employee code / username', 'Töötaja kood / kasutajanimi', 'Код сотрудника / имя пользователя'),
('Employee name', 'Töötaja nimi', 'Имя сотрудника'),
('Employees & permissions', 'Töötajad ja õigused', 'Сотрудники и права'),
('Employment contracts', 'Töölepingud', 'Трудовые договоры'),
('End date', 'Lõppkuupäev', 'Дата окончания'),
('Ends at', 'Lõpeb', 'Заканчивается'),
('English (base)', 'Inglise keel (põhikeel)', 'Английский (основной)'),
('English value', 'Ingliskeelne väärtus', 'Значение на английском'),
('Exception', 'Erand', 'Исключение'),
('Filter', 'Filter', 'Фильтр'),
('First user (client administrator)', 'Esimene kasutaja (kliendiadministraator)', 'Первый пользователь (администратор клиента)'),
('Floor / department', 'Korrus / osakond', 'Этаж / отдел'),
('Full name', 'Täisnimi', 'Полное имя'),
('Import employees', 'Impordi töötajad', 'Импортировать сотрудников'),
('Import legacy database', 'Impordi pärandandmebaas', 'Импортировать устаревшую базу данных'),
('Import legacy employees', 'Impordi pärandtöötajad', 'Импортировать сотрудников из старой системы'),
('Initial system setup', 'Süsteemi algseadistus', 'Первоначальная настройка системы'),
('Inside', 'Siseüksus', 'Внутреннее подразделение'),
('IP', 'IP', 'IP'),
('Job title', 'Ametinimetus', 'Должность'),
('Job title / department', 'Ametinimetus / osakond', 'Должность / отдел'),
('Job titles', 'Ametinimetused', 'Должности'),
('Key', 'Võti', 'Ключ'),
('Language code', 'Keele kood', 'Код языка'),
('Languages', 'Keeled', 'Языки'),
('Leave blank for an open-ended contract.', 'Tähtajatu lepingu korral jäta tühjaks.', 'Для бессрочного договора оставьте поле пустым.'),
('Legacy database SQL file', 'Pärandandmebaasi SQL-fail', 'SQL-файл старой базы данных'),
('Level', 'Tase', 'Уровень'),
('Location', 'Asukoht', 'Местоположение'),
('Log in', 'Logi sisse', 'Войти'),
('Manage role names', 'Halda rollinimesid', 'Управление названиями ролей'),
('Manager', 'Juht', 'Руководитель'),
('Manager (optional)', 'Juht (valikuline)', 'Руководитель (необязательно)'),
('Mark read', 'Märgi loetuks', 'Отметить как прочитанное'),
(CONCAT('Maximum file size: 5 MB. Only db_user_data INSERT rows are read', CHAR(59), ' uploaded SQL is never executed.'), 'Faili maksimaalne suurus: 5 MB. Loetakse ainult db_user_data INSERT-ridu, üleslaaditud SQL-i ei käivitata.', 'Максимальный размер файла: 5 МБ. Читаются только строки INSERT таблицы db_user_data, загруженный SQL не выполняется.'),
('Message', 'Sõnum', 'Сообщение'),
('Min h', 'Min h', 'Мин. ч'),
('Minimum 8 characters.', 'Vähemalt 8 märki.', 'Не менее 8 символов.'),
('Missing translations', 'Puuduvad tõlked', 'Отсутствующие переводы'),
('Missing translations —', 'Puuduvad tõlked —', 'Отсутствующие переводы —'),
('Module', 'Moodul', 'Модуль'),
('Module / context', 'Moodul / kontekst', 'Модуль / контекст'),
('Module key', 'Mooduli võti', 'Ключ модуля'),
('Modules', 'Moodulid', 'Модули'),
('Month', 'Kuu', 'Месяц'),
('Monthly schedule', 'Kuugraafik', 'Месячный график'),
('My account', 'Minu konto', 'Моя учётная запись'),
('Name', 'Nimi', 'Название'),
('New', 'Uus', 'Новый'),
('New password', 'Uus parool', 'Новый пароль'),
('New support ticket', 'Uus tugipäring', 'Новая заявка в поддержку'),
('New ticket', 'Uus päring', 'Новая заявка'),
('No active contract', 'Aktiivne leping puudub', 'Нет действующего договора'),
('No buildings yet.', 'Hooneid pole veel lisatud.', 'Здания ещё не добавлены.'),
('No clients yet.', 'Kliente pole veel lisatud.', 'Клиенты ещё не добавлены.'),
('No contracts have been added.', 'Lepinguid pole lisatud.', 'Договоры ещё не добавлены.'),
('No department', 'Osakond puudub', 'Без отдела'),
('No eligible substitutes found (must be in the same organisation unit, a subordinate, or the direct manager).', 'Sobivaid asendajaid ei leitud (asendaja peab kuuluma samasse organisatsiooniüksusesse, olema alluv või otsene juht).', 'Подходящие заместители не найдены (заместитель должен быть из того же подразделения, подчинённым или непосредственным руководителем).'),
('No entries yet.', 'Kirjeid pole veel.', 'Записей пока нет.'),
('No log entries found.', 'Logikirjeid ei leitud.', 'Записи журнала не найдены.'),
('No manager', 'Juht puudub', 'Без руководителя'),
('No missing translations for this language.', 'Selles keeles puuduvaid tõlkeid pole.', 'Для этого языка нет непереведённых строк.'),
('No modules are activated for your account yet. Please contact support.', 'Sinu kontole pole veel mooduleid aktiveeritud. Võta ühendust kasutajatoega.', 'Для вашей учётной записи ещё не активированы модули. Обратитесь в службу поддержки.'),
('No modules yet.', 'Mooduleid pole veel.', 'Модули ещё не добавлены.'),
('No notifications sent yet.', 'Teavitusi pole veel saadetud.', 'Уведомления ещё не отправлялись.'),
('No notifications yet.', 'Teavitusi pole veel.', 'Уведомлений пока нет.'),
('No open tickets.', 'Avatud päringuid pole.', 'Открытых заявок нет.'),
('No organisation units yet.', 'Organisatsiooniüksusi pole veel.', 'Организационные подразделения ещё не добавлены.'),
('No patients yet.', 'Patsiente pole veel.', 'Пациенты ещё не добавлены.'),
('No primary contracts managed by you overlap this month.', 'Sinu juhitavatel põhilepingutel pole sel kuul kattuvat perioodi.', 'В этом месяце нет пересекающихся периодов основных договоров под вашим управлением.'),
('No shift templates available.', 'Vahetusemalle pole saadaval.', 'Шаблоны смен недоступны.'),
('No substitute requests yet.', 'Asendajapäringuid pole veel.', 'Запросов на замещение пока нет.'),
('No support tickets yet.', 'Tugipäringuid pole veel.', 'Заявок в поддержку пока нет.'),
('No users yet.', 'Kasutajaid pole veel.', 'Пользователей пока нет.'),
('None', 'Puudub', 'Нет'),
('None (top level)', 'Puudub (kõrgeim tase)', 'Нет (верхний уровень)'),
('Notes', 'Märkused', 'Примечания'),
('Nothing was saved. Choose a non-overlapping schedule value.', 'Midagi ei salvestatud. Vali ajaliselt mittekattuv graafikuväärtus.', 'Изменения не сохранены. Выберите значение графика без пересечения по времени.'),
('Notifications', 'Teavitused', 'Уведомления'),
('Object', 'Objekt', 'Объект'),
('Off', 'Vaba', 'Выходной'),
('Open admin panel', 'Ava adminipaneel', 'Открыть панель администратора'),
('Open C-panel', 'Ava kliendipaneel', 'Открыть панель клиента'),
('Open support tickets', 'Ava tugipäringud', 'Открытые заявки в поддержку'),
('Organisation', 'Organisatsioon', 'Организация'),
('Organisation units', 'Organisatsiooniüksused', 'Организационные подразделения'),
('Original user', 'Algne kasutaja', 'Исходный пользователь'),
('OT', 'OT', 'Сверхурочно'),
('Page not found.', 'Lehte ei leitud.', 'Страница не найдена.'),
('Parent unit', 'Ülemüksus', 'Родительское подразделение'),
('Parent unit (optional)', 'Ülemüksus (valikuline)', 'Родительское подразделение (необязательно)'),
('Password', 'Parool', 'Пароль'),
('Password reset. New temporary password (shown only once):', 'Parool lähtestati. Uus ajutine parool (kuvatakse ainult üks kord):', 'Пароль сброшен. Новый временный пароль (показывается только один раз):'),
('Patients', 'Patsiendid', 'Пациенты'),
('Percentage', 'Protsent', 'Процент'),
('Period', 'Periood', 'Период'),
('Personal code', 'Isikukood', 'Личный код'),
('Personal ID code', 'Isikukood', 'Персональный идентификационный код'),
('Phone', 'Telefon', 'Телефон'),
('Planned h', 'Planeeritud h', 'План ч'),
('Primary workplace', 'Põhitöökoht', 'Основное место работы'),
('Property location', 'Kinnistu asukoht', 'Местоположение объекта'),
('Property structure', 'Kinnistu struktuur', 'Структура объекта'),
('Reason', 'Põhjus', 'Причина'),
('Recently sent', 'Hiljuti saadetud', 'Недавно отправленные'),
('Recipient', 'Saaja', 'Получатель'),
('Registry code', 'Registrikood', 'Регистрационный код'),
('Reject', 'Lükka tagasi', 'Отклонить'),
('Rename how roles are displayed across the system. Technical role keys stay unchanged.', 'Määra, kuidas rollinimesid süsteemis kuvatakse. Tehnilised rollivõtmed ei muutu.', 'Измените отображаемые в системе названия ролей. Технические ключи ролей останутся без изменений.'),
('Reports & statistics', 'Aruanded ja statistika', 'Отчёты и статистика'),
('Reports, statistics and notifications will be added as later modules go live.', 'Aruanded, statistika ja teavitused lisatakse järgmiste moodulite käivitamisel.', 'Отчёты, статистика и уведомления будут добавлены при запуске следующих модулей.'),
('Representative', 'Esindaja', 'Представитель'),
('Request substitute', 'Taotle asendajat', 'Запросить замещение'),
('Reset', 'Lähtesta', 'Сбросить'),
('Reset password', 'Lähtesta parool', 'Сбросить пароль'),
('Roles', 'Rollid', 'Роли'),
('Save', 'Salvesta', 'Сохранить'),
('Save changes', 'Salvesta muudatused', 'Сохранить изменения'),
('Save contract', 'Salvesta leping', 'Сохранить договор'),
('Save location', 'Salvesta asukoht', 'Сохранить местоположение'),
('Save setting', 'Salvesta seadistus', 'Сохранить настройку'),
('Schedule conflict', 'Graafiku konflikt', 'Конфликт в графике'),
('Scope', 'Ulatus', 'Область'),
('Select a schedule value.', 'Vali graafikuväärtus.', 'Выберите значение графика.'),
('Select a user', 'Vali kasutaja', 'Выберите пользователя'),
('Select employee', 'Vali töötaja', 'Выберите сотрудника'),
('Select job title', 'Vali ametinimetus', 'Выберите должность'),
('Select location', 'Vali asukoht', 'Выберите местоположение'),
('Select workload', 'Vali koormus', 'Выберите нагрузку'),
('Send', 'Saada', 'Отправить'),
('Send notification to', 'Saada teavitus kasutajale', 'Отправить уведомление'),
('Shift', 'Vahetus', 'Смена'),
('Shift settings', 'Vahetuste seaded', 'Настройки смен'),
('Show', 'Näita', 'Показать'),
('Showing the latest entries.', 'Kuvatakse viimaseid kirjeid.', 'Показаны последние записи.'),
('Signed in as', 'Sisse logitud:', 'Вы вошли как'),
('Something went wrong', 'Midagi läks valesti', 'Что-то пошло не так'),
('Start date', 'Alguskuupäev', 'Дата начала'),
('Start time', 'Algusaeg', 'Время начала'),
('Starts', 'Algab', 'Начало'),
('Starts at', 'Algab kell', 'Начинается в'),
('Status', 'Olek', 'Статус'),
('Subject', 'Teema', 'Тема'),
('Submit request', 'Saada taotlus', 'Отправить запрос'),
('Submit ticket', 'Saada tugipäring', 'Отправить заявку'),
('Substitute', 'Asendaja', 'Заместитель'),
('Support', 'Tugi', 'Поддержка'),
('Support tickets', 'Tugipäringud', 'Заявки в поддержку'),
('System logs', 'Süsteemi logid', 'Системные журналы'),
('System settings', 'Süsteemi seaded', 'Настройки системы'),
('Temporary substitutes', 'Ajutised asendajad', 'Временные замещения'),
('Temporary workplace', 'Ajutine töökoht', 'Временное место работы'),
('The inactive account can be activated after its details are checked.', 'Mitteaktiivse konto saab aktiveerida pärast andmete kontrollimist.', 'Неактивную учётную запись можно активировать после проверки данных.'),
('Title', 'Pealkiri', 'Название'),
('Translation key', 'Tõlkevõti', 'Ключ перевода'),
('Translations', 'Tõlked', 'Переводы'),
('Tri-OT', 'Tri-OT', 'Три-OT'),
('Type', 'Tüüp', 'Тип'),
('Up', 'Üles', 'Вверх'),
('Update', 'Uuenda', 'Обновить'),
('Update password', 'Uuenda parool', 'Обновить пароль'),
('Upload a phpMyAdmin SQL dump containing the', 'Laadi üles phpMyAdmini SQL-tõmmis, mis sisaldab', 'Загрузите дамп SQL из phpMyAdmin, содержащий'),
('User', 'Kasutaja', 'Пользователь'),
('User being substituted', 'Asendatav kasutaja', 'Замещаемый пользователь'),
('Username', 'Kasutajanimi', 'Имя пользователя'),
('Users', 'Kasutajad', 'Пользователи'),
('Value', 'Väärtus', 'Значение'),
('Welcome,', 'Tere tulemast,', 'Добро пожаловать,'),
('When', 'Millal', 'Когда'),
('View', 'Vaata', 'Просмотр'),
('Work schedule', 'Töögraafik', 'Рабочий график'),
('Workload', 'Koormus', 'Нагрузка'),
('Workload (%)', 'Koormus (%)', 'Нагрузка (%)'),
('Workloads', 'Koormused', 'Нагрузки'),
('You do not have permission to view this page.', 'Sul puudub selle lehe vaatamise õigus.', 'У вас нет прав для просмотра этой страницы.'),
('Action contains…', 'Toiming sisaldab…', 'Действие содержит…'),
('e.g. appointments', 'nt vastuvõtud', 'например, приёмы'),
('e.g. dashboard.welcome_title', 'nt dashboard.welcome_title', 'например, dashboard.welcome_title'),
('e.g. Estonian', 'nt eesti keel', 'например, эстонский'),
('e.g. et', 'nt et', 'например, et'),
('Employment', 'Töölepingud', 'Трудовые договоры'),
('Duration in hours', 'Kestus tundides', 'Длительность в часах'),
('Workload percentage', 'Koormuse protsent', 'Процент нагрузки'),
('Template color', 'Malli värv', 'Цвет шаблона'),
('Username contains…', 'Kasutajanimi sisaldab…', 'Имя пользователя содержит…');

INSERT IGNORE INTO ui_translation_seed (english_value, estonian_value, russian_value) VALUES
('Too many failed attempts. Please try again later.', 'Liiga palju ebaõnnestunud katseid. Proovi hiljem uuesti.', 'Слишком много неудачных попыток. Повторите позже.'),
('Invalid client code, username or password.', 'Kliendikood, kasutajanimi või parool on vale.', 'Неверный код клиента, имя пользователя или пароль.'),
('This account or client is not active.', 'See konto või klient ei ole aktiivne.', 'Эта учётная запись или клиент не активны.'),
('Login successful.', 'Sisselogimine õnnestus.', 'Вход выполнен.'),
('Company name is required.', 'Ettevõtte nimi on kohustuslik.', 'Необходимо указать название компании.'),
('Client details updated.', 'Kliendi andmed on uuendatud.', 'Данные клиента обновлены.'),
('Client code, company name and username are required.', 'Kliendikood, ettevõtte nimi ja kasutajanimi on kohustuslikud.', 'Необходимо указать код клиента, название компании и имя пользователя.'),
('Client code 13666 is reserved and cannot be assigned to a regular client.', 'Kliendikood 13666 on reserveeritud ja seda ei saa tavakliendile määrata.', 'Код клиента 13666 зарезервирован и не может быть назначен обычному клиенту.'),
('Client code may only contain letters, digits, "-" and "_" (2-20 characters).', 'Kliendikood võib sisaldada ainult tähti, numbreid, märke "-" ja "_" (2–20 märki).', 'Код клиента может содержать только буквы, цифры, символы "-" и "_" (2–20 символов).'),
('This client code is already in use.', 'See kliendikood on juba kasutusel.', 'Этот код клиента уже используется.'),
('Username already exists for this client.', 'See kasutajanimi on selle kliendi jaoks juba olemas.', 'Это имя пользователя уже существует у данного клиента.'),
('Client created successfully.', 'Klient on loodud.', 'Клиент создан.'),
('Enter a valid name of at most 191 characters.', 'Sisesta korrektne nimi, kuni 191 märki.', 'Введите корректное название длиной не более 191 символа.'),
('Enter a workload percentage from 0.01 to 100.00.', 'Sisesta koormus vahemikus 0,01–100,00%.', 'Введите нагрузку от 0,01 до 100,00%.'),
('Entry not found for this client.', 'Selle kliendi kirjet ei leitud.', 'Запись клиента не найдена.'),
('An entry with this name already exists.', 'Sellise nimega kirje on juba olemas.', 'Запись с таким названием уже существует.'),
('Could not save this entry.', 'Kirjet ei saanud salvestada.', 'Не удалось сохранить запись.'),
('Entry added.', 'Kirje on lisatud.', 'Запись добавлена.'),
('Workload updated.', 'Koormus on uuendatud.', 'Нагрузка обновлена.'),
('Name updated.', 'Nimi on uuendatud.', 'Название обновлено.'),
('Entry activated.', 'Kirje on aktiveeritud.', 'Запись активирована.'),
('Entry hidden. Existing contracts are unchanged.', 'Kirje on peidetud. Olemasolevaid lepinguid ei muudeta.', 'Запись скрыта. Существующие договоры не изменены.'),
('Temporary contract not found for this client.', 'Selle kliendi ajutist lepingut ei leitud.', 'Временный договор клиента не найден.'),
('A temporary contract with schedule entries cannot be archived.', 'Graafikukannetega ajutist lepingut ei saa arhiveerida.', 'Временный договор с записями в графике нельзя архивировать.'),
('Could not change temporary contract archive status.', 'Ajutise lepingu arhiiviolekut ei saanud muuta.', 'Не удалось изменить статус архивации временного договора.'),
('Temporary contract archived.', 'Ajutine leping arhiveeriti.', 'Временный договор архивирован.'),
('Temporary contract restored.', 'Ajutine leping taastati.', 'Временный договор восстановлен.'),
('Contract not found for this client.', 'Selle kliendi lepingut ei leitud.', 'Договор клиента не найден.'),
('Restore the archived contract before editing it.', 'Enne muutmist taasta arhiveeritud leping.', 'Перед редактированием восстановите архивированный договор.'),
('Choose valid employee, location, job title, workload, department and manager values.', 'Vali kehtiv töötaja, asukoht, ametinimetus, koormus, osakond ja juht.', 'Выберите действительные значения сотрудника, местоположения, должности, нагрузки, отдела и руководителя.'),
('Choose a valid contract type.', 'Vali kehtiv lepingu tüüp.', 'Выберите допустимый тип договора.'),
('Enter valid contract dates. The start date is required.', 'Sisesta kehtivad lepingu kuupäevad. Alguskuupäev on kohustuslik.', 'Укажите корректные даты договора. Дата начала обязательна.'),
('The end date cannot be earlier than the start date.', 'Lõppkuupäev ei saa olla alguskuupäevast varasem.', 'Дата окончания не может быть раньше даты начала.'),
('An employee cannot be their own manager.', 'Töötaja ei saa olla iseenda juht.', 'Сотрудник не может быть собственным руководителем.'),
('Choose an active employee from this client.', 'Vali selle kliendi aktiivne töötaja.', 'Выберите активного сотрудника этого клиента.'),
('Choose a property location from this client.', 'Vali selle kliendi kinnistu asukoht.', 'Выберите местоположение объекта этого клиента.'),
('Choose an active job title.', 'Vali aktiivne ametinimetus.', 'Выберите активную должность.'),
('Choose an active workload.', 'Vali aktiivne koormus.', 'Выберите активную нагрузку.'),
('Choose an active department.', 'Vali aktiivne osakond.', 'Выберите активный отдел.'),
('Choose an active manager from this client.', 'Vali selle kliendi aktiivne juht.', 'Выберите активного руководителя этого клиента.'),
('This employee already has a primary contract covering part of this period. Temporary contracts may overlap.', 'Töötajal on juba osa sellest perioodist kattev põhileping. Ajutised lepingud võivad kattuda.', 'У сотрудника уже есть основной договор, покрывающий часть этого периода. Временные договоры могут пересекаться.'),
('The primary contract dates and location must continue to cover each linked temporary workplace at a different property location.', 'Põhileping peab kogu perioodi vältel katma seotud ajutise töökoha, mis asub teises kinnistu asukohas.', 'Основной договор должен покрывать весь период связанного временного рабочего места в другом местоположении объекта.'),
('An employee can have only one temporary contract on the same floor in each month.', 'Töötajal võib samal korrusel olla kuus ainult üks ajutine leping.', 'У сотрудника может быть только один временный договор на одном этаже в течение месяца.'),
('A temporary contract must fit within a primary contract period and use a different property location.', 'Ajutine leping peab jääma põhilepingu perioodi sisse ja asuma teises kinnistu asukohas.', 'Временный договор должен укладываться в период основного договора и относиться к другому местоположению объекта.'),
('Could not save the contract. Check the selected values and try again.', 'Lepingut ei saanud salvestada. Kontrolli valitud väärtusi ja proovi uuesti.', 'Не удалось сохранить договор. Проверьте выбранные значения и попробуйте снова.'),
('Contract added.', 'Leping on lisatud.', 'Договор добавлен.'),
('Contract updated.', 'Leping on uuendatud.', 'Договор обновлён.'),
('Initial setup has already been completed.', 'Algseadistus on juba lõpetatud.', 'Первоначальная настройка уже завершена.'),
('Password must be at least 8 characters.', 'Parool peab olema vähemalt 8 märki pikk.', 'Пароль должен содержать не менее 8 символов.'),
('Please fill in a valid name, e-mail and username.', 'Sisesta korrektne nimi, e-post ja kasutajanimi.', 'Укажите корректные имя, электронную почту и имя пользователя.'),
('First system administrator created successfully.', 'Esimene süsteemiadministraator on loodud.', 'Первый системный администратор создан.'),
('Language code must be 2-10 lowercase letters (e.g. "en", "et").', 'Keelekood peab olema 2–10 väiketähte (nt "en", "et").', 'Код языка должен содержать 2–10 строчных букв (например, "en", "et").'),
('Language name is required.', 'Keele nimi on kohustuslik.', 'Необходимо указать название языка.'),
('This language code already exists.', 'See keelekood on juba olemas.', 'Этот код языка уже существует.'),
('Language not found.', 'Keelt ei leitud.', 'Язык не найден.'),
('English is mandatory and cannot be deactivated.', 'Inglise keel on kohustuslik ja seda ei saa välja lülitada.', 'Английский язык обязателен и не может быть отключён.'),
('Language created successfully.', 'Keel on loodud.', 'Язык создан.'),
('Language updated.', 'Keele olek on uuendatud.', 'Язык обновлён.'),
('The upload does not contain any db_user_data rows.', 'Üleslaaditud failis pole db_user_data ridu.', 'В загруженном файле нет строк db_user_data.'),
('Employees module is not provisioned for this client.', 'Selle kliendi töötajate moodul pole ette valmistatud.', 'Модуль сотрудников для этого клиента не подготовлен.'),
('Module key must be lowercase letters, digits or underscores (2-100 characters).', 'Mooduli võti peab sisaldama väiketähti, numbreid või alakriipse (2–100 märki).', 'Ключ модуля должен содержать строчные буквы, цифры или подчёркивания (2–100 символов).'),
('Module name is required.', 'Mooduli nimi on kohustuslik.', 'Необходимо указать название модуля.'),
('This module key already exists.', 'See mooduli võti on juba olemas.', 'Этот ключ модуля уже существует.'),
('Module created successfully.', 'Moodul on loodud.', 'Модуль создан.'),
('Module not found.', 'Moodulit ei leitud.', 'Модуль не найден.'),
('An inactive catalogue module cannot be activated for a client.', 'Mitteaktiivset kataloogimoodulit ei saa kliendile aktiveerida.', 'Неактивный модуль каталога нельзя активировать для клиента.'),
('Module activated.', 'Moodul on aktiveeritud.', 'Модуль активирован.'),
('Module deactivated.', 'Moodul on välja lülitatud.', 'Модуль отключён.'),
('Title and message are required.', 'Pealkiri ja sõnum on kohustuslikud.', 'Необходимо указать заголовок и сообщение.'),
('Notification sent.', 'Teavitus on saadetud.', 'Уведомление отправлено.'),
('Organisation level must be one of A-F.', 'Organisatsiooni tase peab olema A–F.', 'Уровень организации должен быть от A до F.'),
('Name is required.', 'Nimi on kohustuslik.', 'Необходимо указать название.'),
('Organisation unit created.', 'Organisatsiooniüksus on loodud.', 'Организационное подразделение создано.'),
('Full name is required.', 'Täisnimi on kohustuslik.', 'Необходимо указать полное имя.'),
('Patient created.', 'Patsient on lisatud.', 'Пациент добавлен.'),
('Parent not found for this client.', 'Selle kliendi ülemüksust ei leitud.', 'Родительское подразделение клиента не найдено.'),
('Choose a valid location type for the selected parent.', 'Vali valitud ülemüksuse jaoks sobiv asukohatüüp.', 'Выберите допустимый тип местоположения для выбранного родительского подразделения.'),
('Could not save the property location.', 'Kinnistu asukohta ei saanud salvestada.', 'Не удалось сохранить местоположение объекта.'),
('Location added.', 'Asukoht on lisatud.', 'Местоположение добавлено.'),
('Invalid move direction.', 'Liikumissuund ei sobi.', 'Недопустимое направление перемещения.'),
('Location not found for this client.', 'Selle kliendi asukohta ei leitud.', 'Местоположение клиента не найдено.'),
('Location not found among its siblings.', 'Asukohta ei leitud samal tasemel üksuste seas.', 'Местоположение не найдено среди соседних элементов.'),
('Location is already at this end of the list.', 'Asukoht on juba loendi selles otsas.', 'Местоположение уже находится на этом краю списка.'),
('Could not change the location order.', 'Asukohtade järjekorda ei saanud muuta.', 'Не удалось изменить порядок местоположений.'),
('Location order updated.', 'Asukohtade järjekord on uuendatud.', 'Порядок местоположений обновлён.'),
('Could not rename the property location.', 'Kinnistu asukohta ei saanud ümber nimetada.', 'Не удалось переименовать местоположение объекта.'),
('Location renamed.', 'Asukoht on ümber nimetatud.', 'Местоположение переименовано.'),
('Role name cannot be empty.', 'Rolli nimi ei tohi olla tühi.', 'Название роли не может быть пустым.'),
('Role not found.', 'Rolli ei leitud.', 'Роль не найдена.'),
('Role name updated.', 'Rolli nimi on uuendatud.', 'Название роли обновлено.'),
('Enter a valid type, code, color, start time and duration.', 'Sisesta kehtiv tüüp, kood, värv, algusaeg ja kestus.', 'Укажите допустимые тип, код, цвет, время начала и длительность.'),
('Duration must be from 0.25 to 24 hours.', 'Kestus peab olema 0,25–24 tundi.', 'Длительность должна составлять от 0,25 до 24 часов.'),
('Schedule template not found for this client.', 'Selle kliendi graafikumalli ei leitud.', 'Шаблон графика клиента не найден.'),
('This code already exists for this type.', 'See kood on selle tüübi jaoks juba olemas.', 'Этот код уже существует для данного типа.'),
('Could not save the schedule template.', 'Graafikumalli ei saanud salvestada.', 'Не удалось сохранить шаблон графика.'),
('Schedule template added.', 'Graafikumall on lisatud.', 'Шаблон графика добавлен.'),
('Schedule template updated.', 'Graafikumall on uuendatud.', 'Шаблон графика обновлён.'),
('Template activated.', 'Mall on aktiveeritud.', 'Шаблон активирован.'),
('Template hidden. Existing schedule entries are unchanged.', 'Mall on peidetud. Olemasolevaid graafikukandeid ei muudeta.', 'Шаблон скрыт. Существующие записи графика не изменены.'),
('Choose a valid month and schedule values.', 'Vali kehtiv kuu ja graafikuväärtused.', 'Выберите допустимый месяц и значения графика.'),
('Schedule contains an employee or contract outside your assignments.', 'Graafik sisaldab töötajat või lepingut väljaspool sinu õigusi.', 'График содержит сотрудника или договор вне ваших полномочий.'),
('Schedule contains an invalid day.', 'Graafik sisaldab vigast kuupäeva.', 'График содержит недопустимую дату.'),
('A shift cannot be assigned outside the employee contract period.', 'Vahetust ei saa määrata väljapoole töötaja lepingu perioodi.', 'Нельзя назначить смену вне периода договора сотрудника.'),
('Schedule contains an invalid shift value.', 'Graafik sisaldab vigast vahetuse väärtust.', 'График содержит недопустимое значение смены.'),
('Choose a valid schedule template.', 'Vali kehtiv graafikumall.', 'Выберите допустимый шаблон графика.'),
('Choose an active schedule template.', 'Vali aktiivne graafikumall.', 'Выберите активный шаблон графика.'),
('Schedule saved.', 'Graafik on salvestatud.', 'График сохранён.'),
('Original user, substitute, start and end dates are required.', 'Algne kasutaja, asendaja ning algus- ja lõppkuupäev on kohustuslikud.', 'Необходимо указать исходного пользователя, заместителя, даты начала и окончания.'),
('End date must be after the start date.', 'Lõppkuupäev peab olema alguskuupäevast hilisem.', 'Дата окончания должна быть позже даты начала.'),
('Selected substitute is not eligible for this user (must be same unit, subordinate, or direct manager).', 'Valitud asendaja ei sobi sellele kasutajale (peab olema samast üksusest, alluv või otsene juht).', 'Выбранный заместитель не подходит (должен быть из того же подразделения, подчинённым или непосредственным руководителем).');
INSERT IGNORE INTO ui_translation_seed (english_value, estonian_value, russian_value) VALUES
('Request not found or already processed.', 'Päringut ei leitud või see on juba töödeldud.', 'Запрос не найден или уже обработан.'),
('Substitute request submitted for approval.', 'Asendajapäring on kinnitamiseks saadetud.', 'Запрос на замещение отправлен на утверждение.'),
('Substitute request approved.', 'Asendajapäring on kinnitatud.', 'Запрос на замещение утверждён.'),
('Substitute request rejected.', 'Asendajapäring on tagasi lükatud.', 'Запрос на замещение отклонён.'),
('Subject and message are required.', 'Teema ja sõnum on kohustuslikud.', 'Необходимо указать тему и сообщение.'),
('Support ticket submitted.', 'Tugipäring on saadetud.', 'Заявка в поддержку отправлена.'),
('Translation key and English value are required.', 'Tõlkevõti ja ingliskeelne väärtus on kohustuslikud.', 'Необходимо указать ключ перевода и значение на английском.'),
('This translation key already exists.', 'See tõlkevõti on juba olemas.', 'Этот ключ перевода уже существует.'),
('Translation key created.', 'Tõlkevõti on loodud.', 'Ключ перевода создан.'),
('Employee name, employee code and e-mail are required.', 'Töötaja nimi, töötaja kood ja e-post on kohustuslikud.', 'Необходимо указать имя сотрудника, код сотрудника и электронную почту.'),
('Enter a valid e-mail address.', 'Sisesta kehtiv e-posti aadress.', 'Введите действительный адрес электронной почты.'),
('Invalid role selected.', 'Valitud roll ei sobi.', 'Выбрана недопустимая роль.'),
('This username already exists for the client.', 'See kasutajanimi on kliendil juba olemas.', 'Это имя пользователя уже существует у клиента.'),
('Role not found.', 'Rolli ei leitud.', 'Роль не найдена.'),
('Employee name and a valid e-mail address are required.', 'Töötaja nimi ja kehtiv e-posti aadress on kohustuslikud.', 'Необходимо указать имя сотрудника и действительный адрес электронной почты.'),
('Employee not found.', 'Töötajat ei leitud.', 'Сотрудник не найден.'),
('Cannot remove the only active client administrator role. Assign another administrator first.', 'Ainsa aktiivse kliendiadministraatori rolli ei saa eemaldada. Määra esmalt teine administraator.', 'Нельзя удалить роль единственного активного администратора клиента. Сначала назначьте другого администратора.'),
('User not found.', 'Kasutajat ei leitud.', 'Пользователь не найден.'),
('Cannot deactivate the only active client administrator. Assign another administrator first.', 'Ainsat aktiivset kliendiadministraatorit ei saa välja lülitada. Määra esmalt teine administraator.', 'Нельзя отключить единственного активного администратора клиента. Сначала назначьте другого администратора.'),
('Employee status updated.', 'Töötaja olek on uuendatud.', 'Статус сотрудника обновлён.'),
('New password must be at least 8 characters.', 'Uus parool peab olema vähemalt 8 märki pikk.', 'Новый пароль должен содержать не менее 8 символов.'),
('Current password is incorrect.', 'Praegune parool on vale.', 'Текущий пароль неверен.'),
('Password reset and e-mailed.', 'Parool lähtestati ja saadeti e-postiga.', 'Пароль сброшен и отправлен по электронной почте.'),
('Password reset, but e-mail delivery failed.', 'Parool lähtestati, kuid e-kirja saatmine ebaõnnestus.', 'Пароль сброшен, но отправить электронное письмо не удалось.'),
('Password updated.', 'Parool on uuendatud.', 'Пароль обновлён.'),
('Invalid session token, please try again.', 'Seansi tunnus on kehtetu. Proovi uuesti.', 'Недействительный токен сеанса. Повторите попытку.'),
('New password and confirmation do not match.', 'Uus parool ja kinnitus ei kattu.', 'Новый пароль и подтверждение не совпадают.'),
('The SQL file must not exceed 5 MB.', 'SQL-faili suurus ei tohi ületada 5 MB.', 'Размер SQL-файла не должен превышать 5 МБ.'),
('Select a .sql file.', 'Vali .sql-fail.', 'Выберите файл .sql.'),
('The uploaded file could not be verified.', 'Üleslaaditud faili ei õnnestunud kontrollida.', 'Не удалось проверить загруженный файл.'),
('The uploaded file could not be read.', 'Üleslaaditud faili ei õnnestunud lugeda.', 'Не удалось прочитать загруженный файл.'),
('The uploaded SQL file is too large.', 'Üleslaaditud SQL-fail on liiga suur.', 'Загруженный SQL-файл слишком большой.'),
('The SQL file was only partially uploaded. Please try again.', 'SQL-fail laaditi üles ainult osaliselt. Proovi uuesti.', 'SQL-файл загружен не полностью. Повторите попытку.'),
('Select a legacy SQL file.', 'Vali pärandsüsteemi SQL-fail.', 'Выберите SQL-файл старой системы.'),
('Choose a valid month.', 'Vali kehtiv kuu.', 'Выберите допустимый месяц.'),
('Invalid request, please try again.', 'Päring on kehtetu. Proovi uuesti.', 'Недопустимый запрос. Повторите попытку.'),
('Saving schedule...', 'Graafikut salvestatakse...', 'Сохранение графика...'),
('The server returned an unexpected response.', 'Server tagastas ootamatu vastuse.', 'Сервер вернул неожиданный ответ.'),
('Client creation failed: ', 'Kliendi loomine ebaõnnestus: ', 'Не удалось создать клиента: '),
('Setup failed: ', 'Algseadistus ebaõnnestus: ', 'Не удалось выполнить первоначальную настройку: '),
('Legacy employee import failed: ', 'Pärandtöötajate import ebaõnnestus: ', 'Не удалось импортировать сотрудников из старой системы: '),
('Module activation failed: ', 'Mooduli aktiveerimine ebaõnnestus: ', 'Не удалось активировать модуль: '),
('Failed to create translation key: ', 'Tõlkevõtme loomine ebaõnnestus: ', 'Не удалось создать ключ перевода: '),
('Failed to create user: ', 'Kasutaja loomine ebaõnnestus: ', 'Не удалось создать пользователя: '),
('Failed to update employee: ', 'Töötaja uuendamine ebaõnnestus: ', 'Не удалось обновить сотрудника: '),
('This employee already has another shift during that time on ', 'Töötajal on sellel ajal teine vahetus kuupäeval ', 'У сотрудника уже есть другая смена в этот период, дата '),
('Client created. Temporary password for the first user (shown only once): ', 'Klient on loodud. Esimese kasutaja ajutine parool (kuvatakse ainult üks kord): ', 'Клиент создан. Временный пароль первого пользователя (показывается только один раз): '),
('Password reset. New temporary password (shown only once): ', 'Parool lähtestati. Uus ajutine parool (kuvatakse ainult üks kord): ', 'Пароль сброшен. Новый временный пароль (показывается только один раз): '),
('Legacy import completed. Imported: ', 'Pärandandmete import lõpetatud. Imporditud: ', 'Импорт старых данных завершён. Импортировано: '),
('Legacy import completed. Imported: ', 'Pärandandmete import lõpetatud. Imporditud: ', 'Импорт старых данных завершён. Импортировано: '),
('Login successful.', 'Sisselogimine õnnestus.', 'Вход выполнен.'),
('Client created successfully.', 'Klient on loodud.', 'Клиент создан.'),
('First system administrator created successfully.', 'Esimene süsteemiadministraator on loodud.', 'Первый системный администратор создан.'),
('Module created successfully.', 'Moodul on loodud.', 'Модуль создан.'),
('Module deactivated.', 'Moodul on välja lülitatud.', 'Модуль отключён.'),
('Request not found or already processed.', 'Päringut ei leitud või see on juba töödeldud.', 'Запрос не найден или уже обработан.'),
('Selected substitute is not eligible for this user (must be same unit, subordinate, or direct manager).', 'Valitud asendaja ei sobi sellele kasutajale (peab olema samast üksusest, alluv või otsene juht).', 'Выбранный заместитель не подходит (должен быть из того же подразделения, подчинённым или непосредственным руководителем).');

INSERT IGNORE INTO ui_translation_seed (english_value, estonian_value, russian_value) VALUES
('The application could not start. Please check the database connection settings in', 'Rakendust ei saanud käivitada. Kontrolli andmebaasiühenduse seadeid failis', 'Не удалось запустить приложение. Проверьте настройки подключения к базе данных в файле'),
(', or try again shortly.', ', või proovi veidi aja pärast uuesti.', ', или повторите попытку позже.'),
('weekdays this month (Monday to Friday).', 'tööpäeva sel kuul (esmaspäevast reedeni).', 'рабочих дней в этом месяце (с понедельника по пятницу).'),
('table.', 'tabeli.', 'таблицу.'),
('The legacy user ID becomes the employee code and username. Imported accounts are inactive and receive Level F access.', 'Pärandsüsteemi kasutaja ID-st saab töötaja kood ja kasutajanimi. Imporditud kontod on mitteaktiivsed ning saavad F-taseme õigused.', 'Идентификатор пользователя старой системы становится кодом сотрудника и именем пользователя. Импортированные учётные записи неактивны и получают уровень F.'),
('Existing usernames are skipped. Passwords are not imported.', 'Olemasolevad kasutajanimed jäetakse vahele. Paroole ei impordita.', 'Существующие имена пользователей пропускаются. Пароли не импортируются.'),
('The legacy user ID becomes the employee code and username. Imported accounts are inactive and receive Level F access. Existing usernames are skipped. Passwords are not imported.', 'Pärandsüsteemi kasutaja ID-st saab töötaja kood ja kasutajanimi. Imporditud kontod on mitteaktiivsed ning saavad F-taseme õigused. Olemasolevad kasutajanimed jäetakse vahele. Paroole ei impordita.', 'Идентификатор пользователя старой системы становится кодом сотрудника и именем пользователя. Импортированные учётные записи неактивны и получают уровень F. Существующие имена пользователей пропускаются.'),
('table. The legacy user ID becomes the employee code and username. Imported accounts are inactive and receive Level F access. Existing usernames are skipped. Passwords are not imported.', 'tabelit. Pärandsüsteemi kasutaja ID-st saab töötaja kood ja kasutajanimi. Imporditud kontod on mitteaktiivsed ning saavad F-taseme õigused. Olemasolevad kasutajanimed jäetakse vahele. Paroole ei impordita.', 'таблицу. Идентификатор пользователя старой системы становится кодом сотрудника и именем пользователя. Импортированные учётные записи неактивны и получают уровень F. Существующие имена пользователей пропускаются. Пароли не импортируются.');
INSERT IGNORE INTO ui_translation_seed (english_value, estonian_value, russian_value) VALUES
('inactive', 'mitteaktiivne', 'неактивный'),
('Active', 'Aktiivne', 'Активный'),
('Inactive', 'Mitteaktiivne', 'Неактивный'),
('Current', 'Kehtiv', 'Действующий'),
('Scheduled', 'Kavandatud', 'Запланированный'),
('Ended', 'Lõppenud', 'Завершённый'),
('Archived', 'Arhiveeritud', 'Архивированный'),
('Primary', 'Põhi', 'Основной'),
('Temporary', 'Ajutine', 'Временный'),
('Yes', 'Jah', 'Да'),
('No', 'Ei', 'Нет'),
('Red', 'Punane', 'Красный'),
('Orange', 'Oranž', 'Оранжевый'),
('Yellow', 'Kollane', 'Жёлтый'),
('Green', 'Roheline', 'Зелёный'),
('Blue', 'Sinine', 'Синий'),
('Indigo', 'Indigo', 'Индиго'),
('Violet', 'Lilla', 'Фиолетовый'),
('Black', 'Must', 'Чёрный'),
('White', 'Valge', 'Белый'),
('Mon', 'E', 'Пн'),
('Tue', 'T', 'Вт'),
('Wed', 'K', 'Ср'),
('Thu', 'N', 'Чт'),
('Fri', 'R', 'Пт'),
('Sat', 'L', 'Сб'),
('Sun', 'P', 'Вс'),
('Monday', 'Esmaspäev', 'Понедельник'),
('Tuesday', 'Teisipäev', 'Вторник'),
('Wednesday', 'Kolmapäev', 'Среда'),
('Thursday', 'Neljapäev', 'Четверг'),
('Friday', 'Reede', 'Пятница'),
('Saturday', 'Laupäev', 'Суббота'),
('Sunday', 'Pühapäev', 'Воскресенье');

INSERT IGNORE INTO ui_translation_seed (english_value, estonian_value, russian_value) VALUES
('Could not save the monthly schedule. Check the values and try again.', 'Graafikut ei saanud salvestada. Kontrolli väärtusi ja proovi uuesti.', 'Не удалось сохранить график. Проверьте значения и попробуйте снова.'),
('Employee updated.', 'Töötaja andmed on uuendatud.', 'Данные сотрудника обновлены.'),
('Enter a name of at most 191 characters.', 'Sisesta nimi pikkusega kuni 191 märki.', 'Введите название длиной не более 191 символа.'),
('Setting saved.', 'Seadistus on salvestatud.', 'Настройка сохранена.'),
('The SQL file upload failed.', 'SQL-faili üleslaadimine ebaõnnestus.', 'Не удалось загрузить SQL-файл.');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM ui_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code
           WHEN 'en' THEN seed.english_value
           WHEN 'et' THEN seed.estonian_value
           WHEN 'ru' THEN seed.russian_value
       END,
       'active'
FROM ui_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE ui_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.62', 'Populate database translations for visible English UI text in Estonian and Russian.', NOW());
-- END MIGRATION: 049_ui_static_translations.sql

-- BEGIN MIGRATION: 050_monthly_workload_overrides.sql
CREATE TABLE IF NOT EXISTS employment_contract_monthly_workloads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    contract_id INT UNSIGNED NOT NULL,
    workload_month DATE NOT NULL,
    workload_id INT UNSIGNED NOT NULL,
    workload_percent DECIMAL(5,2) NOT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employment_contract_monthly_workload (client_id, contract_id, workload_month),
    KEY idx_employment_contract_monthly_workload_month (client_id, workload_month),
    CONSTRAINT fk_employment_contract_monthly_workloads_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_employment_contract_monthly_workloads_contract FOREIGN KEY (client_id, contract_id) REFERENCES employment_contracts (client_id, id) ON DELETE CASCADE,
    CONSTRAINT fk_employment_contract_monthly_workloads_workload FOREIGN KEY (client_id, workload_id) REFERENCES employment_workloads (client_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_employment_contract_monthly_workloads_created_by FOREIGN KEY (created_by) REFERENCES system_users (id) ON DELETE SET NULL,
    CONSTRAINT fk_employment_contract_monthly_workloads_updated_by FOREIGN KEY (updated_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context) VALUES
    ('panel.monthly_workload', 'panel'),
    ('panel.contract_default_workload', 'panel'),
    ('panel.monthly_workload_saved', 'panel'),
    ('panel.monthly_workload_reset', 'panel'),
    ('panel.choose_active_primary_contract', 'panel'),
    ('panel.primary_contract_month_coverage', 'panel'),
    ('panel.choose_month_workload', 'panel'),
    ('panel.monthly_workload_save_error', 'panel');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_key_row.id, languages.id, seed.value, 'active'
FROM (
    SELECT 'panel.monthly_workload' AS translation_key, 'en' AS language_code, 'Monthly workload' AS value
    UNION ALL SELECT 'panel.monthly_workload', 'et', 'Kuu koormus'
    UNION ALL SELECT 'panel.monthly_workload', 'ru', 'Нагрузка на месяц'
    UNION ALL SELECT 'panel.contract_default_workload', 'en', 'Contract default'
    UNION ALL SELECT 'panel.contract_default_workload', 'et', 'Lepingu koormus'
    UNION ALL SELECT 'panel.contract_default_workload', 'ru', 'Нагрузка по договору'
    UNION ALL SELECT 'panel.monthly_workload_saved', 'en', 'Monthly workload saved.'
    UNION ALL SELECT 'panel.monthly_workload_saved', 'et', 'Kuu koormus salvestati.'
    UNION ALL SELECT 'panel.monthly_workload_saved', 'ru', 'Нагрузка на месяц сохранена.'
    UNION ALL SELECT 'panel.monthly_workload_reset', 'en', 'Monthly workload reset to the contract default.'
    UNION ALL SELECT 'panel.monthly_workload_reset', 'et', 'Kuu koormus lähtestati lepingu koormusele.'
    UNION ALL SELECT 'panel.monthly_workload_reset', 'ru', 'Нагрузка на месяц сброшена до значения по договору.'
    UNION ALL SELECT 'panel.choose_active_primary_contract', 'en', 'Choose an active primary contract.'
    UNION ALL SELECT 'panel.choose_active_primary_contract', 'et', 'Vali aktiivne põhileping.'
    UNION ALL SELECT 'panel.choose_active_primary_contract', 'ru', 'Выберите действующий основной договор.'
    UNION ALL SELECT 'panel.primary_contract_month_coverage', 'en', 'The primary contract does not cover this month.'
    UNION ALL SELECT 'panel.primary_contract_month_coverage', 'et', 'Põhileping ei kehti sel kuul.'
    UNION ALL SELECT 'panel.primary_contract_month_coverage', 'ru', 'Основной договор не действует в этом месяце.'
    UNION ALL SELECT 'panel.choose_month_workload', 'en', 'Choose a valid month and workload.'
    UNION ALL SELECT 'panel.choose_month_workload', 'et', 'Vali kehtiv kuu ja koormus.'
    UNION ALL SELECT 'panel.choose_month_workload', 'ru', 'Выберите допустимый месяц и нагрузку.'
    UNION ALL SELECT 'panel.monthly_workload_save_error', 'en', 'Could not save the monthly workload.'
    UNION ALL SELECT 'panel.monthly_workload_save_error', 'et', 'Kuu koormust ei saanud salvestada.'
    UNION ALL SELECT 'panel.monthly_workload_save_error', 'ru', 'Не удалось сохранить нагрузку на месяц.'
) AS seed
INNER JOIN system_translation_keys translation_key_row ON translation_key_row.translation_key = seed.translation_key
INNER JOIN system_languages languages ON languages.language_code = seed.language_code AND languages.is_active = 1;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.63', 'Add primary-contract monthly workload overrides for schedule required-hours and Tri-OT calculations.', NOW());

-- END MIGRATION: 050_monthly_workload_overrides.sql

-- BEGIN MIGRATION: 051_schedule_monthly_workload_modal.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.64', 'Open monthly workload overrides in a modal from the schedule employee row.', NOW());
-- END MIGRATION: 051_schedule_monthly_workload_modal.sql

-- BEGIN MIGRATION: 052_schedule_weekend_color.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.65', 'Use a 50/50 red-and-white mix for schedule weekend-day backgrounds.', NOW());

-- END MIGRATION: 052_schedule_weekend_color.sql

-- BEGIN MIGRATION: 053_schedule_weekend_transparency.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.66', 'Make the schedule weekend background 50% transparent.', NOW());

-- END MIGRATION: 053_schedule_weekend_transparency.sql

-- BEGIN MIGRATION: 054_public_holidays.sql
CREATE TABLE IF NOT EXISTS system_public_holidays (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    holiday_date DATE NOT NULL,
    title VARCHAR(255) NOT NULL,
    notes TEXT NULL,
    kind VARCHAR(100) NOT NULL,
    kind_id TINYINT UNSIGNED NOT NULL,
    source_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_system_public_holidays_source (source_key),
    KEY idx_system_public_holidays_day_off (holiday_date, kind_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.67', 'Import Estonian public holidays and treat statutory holidays as schedule non-working days.', NOW());
-- END MIGRATION: 054_public_holidays.sql

-- BEGIN MIGRATION: 055_public_holiday_translations.sql
CREATE TEMPORARY TABLE public_holiday_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL,
    PRIMARY KEY (english_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO public_holiday_translation_seed (english_value, estonian_value, russian_value) VALUES
('Public holidays XML', 'Riigipühade XML', 'XML государственных праздников'),
('Import new entries', 'Impordi uued kirjed', 'Импортировать новые записи'),
('Only missing entries are added. Existing records are never changed.', 'Lisatakse ainult puuduvad kirjed. Olemasolevaid kirjeid ei muudeta.', 'Добавляются только отсутствующие записи. Существующие записи не изменяются.'),
('Added %d new entries, %d were already present.', 'Lisati %d uut kirjet, %d olid juba olemas.', 'Добавлено новых записей: %d, уже существовало: %d.'),
('The XML upload failed.', 'XML-faili üleslaadimine ebaõnnestus.', 'Не удалось загрузить XML-файл.'),
('Select a valid public-holiday XML file.', 'Vali kehtiv riigipühade XML-fail.', 'Выберите корректный XML-файл государственных праздников.'),
('The uploaded file must have an .xml extension.', 'Üleslaaditava faili laiend peab olema .xml.', 'Расширение загружаемого файла должно быть .xml.'),
('The XML file must be no larger than 2 MB.', 'XML-faili suurus ei tohi ületada 2 MB.', 'Размер XML-файла не должен превышать 2 МБ.'),
('The uploaded XML file could not be read.', 'Üleslaaditud XML-faili ei õnnestunud lugeda.', 'Не удалось прочитать загруженный XML-файл.'),
('The XML file is empty, too large, or contains a forbidden declaration.', 'XML-fail on tühi, liiga suur või sisaldab keelatud deklaratsiooni.', 'XML-файл пуст, слишком велик или содержит запрещённое объявление.'),
('The file is not a valid public-holiday XML document.', 'Fail ei ole korrektne riigipühade XML-dokument.', 'Файл не является корректным XML-документом государственных праздников.'),
('The XML file must contain between 1 and 10,000 entries.', 'XML-fail peab sisaldama 1 kuni 10 000 kirjet.', 'XML-файл должен содержать от 1 до 10 000 записей.'),
('The XML contains an entry with invalid date, title, kind, or kind ID.', 'XML-fail sisaldab vigase kuupäeva, nimetuse, liigi või liigi ID-ga kirjet.', 'XML-файл содержит запись с неверной датой, названием, типом или идентификатором типа.'),
('Could not import the public holidays.', 'Riigipühi ei õnnestunud importida.', 'Не удалось импортировать государственные праздники.'),
('Invalid session token, please try again.', 'Vigane seansitunnus. Proovi uuesti.', 'Недействительный токен сеанса. Повторите попытку.');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM public_holiday_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code
           WHEN 'en' THEN seed.english_value
           WHEN 'et' THEN seed.estonian_value
           WHEN 'ru' THEN seed.russian_value
       END,
       'active'
FROM public_holiday_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE public_holiday_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.68', 'Translate public-holiday XML import controls and messages.', NOW());
-- END MIGRATION: 055_public_holiday_translations.sql

-- BEGIN MIGRATION: 056_public_holiday_tab_translations.sql
CREATE TEMPORARY TABLE public_holiday_tab_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL,
    PRIMARY KEY (english_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO public_holiday_tab_translation_seed (english_value, estonian_value, russian_value) VALUES
('Public holidays', 'Riigipühad', 'Государственные праздники'),
('Date', 'Kuupäev', 'Дата'),
('Category', 'Liik', 'Категория'),
('No public holidays have been imported yet.', 'Riigipühi pole veel imporditud.', 'Государственные праздники ещё не импортированы.');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM public_holiday_tab_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code
           WHEN 'en' THEN seed.english_value
           WHEN 'et' THEN seed.estonian_value
           WHEN 'ru' THEN seed.russian_value
       END,
       'active'
FROM public_holiday_tab_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE public_holiday_tab_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.69', 'Add translations for the public holidays schedule tab.', NOW());
-- END MIGRATION: 056_public_holiday_tab_translations.sql

-- BEGIN MIGRATION: 057_schedule_sum_label_translation.sql
CREATE TEMPORARY TABLE schedule_sum_label_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL,
    PRIMARY KEY (english_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO schedule_sum_label_translation_seed (english_value, estonian_value, russian_value) VALUES
('Sum h', 'Kokku h', 'Всего ч');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM schedule_sum_label_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code
           WHEN 'en' THEN seed.english_value
           WHEN 'et' THEN seed.estonian_value
           WHEN 'ru' THEN seed.russian_value
       END,
       'active'
FROM schedule_sum_label_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE schedule_sum_label_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.70', 'Rename the schedule planned-hours column label to Sum.', NOW());
-- END MIGRATION: 057_schedule_sum_label_translation.sql

-- BEGIN MIGRATION: 058_schedule_printing.sql
CREATE TEMPORARY TABLE schedule_print_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL,
    PRIMARY KEY (english_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO schedule_print_translation_seed (english_value, estonian_value, russian_value) VALUES
('Print schedule', 'Prindi graafik', 'Печать графика'),
('Choose locations to print', 'Vali prinditavad asukohad', 'Выберите места для печати'),
('Select all', 'Vali kõik', 'Выбрать все'),
('Clear selection', 'Tühista valik', 'Снять выделение'),
('Print / Save as PDF', 'Prindi / salvesta PDF-ina', 'Печать / сохранить в PDF'),
('No locations available for this month.', 'Selles kuus pole prinditavaid asukohti.', 'В этом месяце нет мест для печати.');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM schedule_print_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code
           WHEN 'en' THEN seed.english_value
           WHEN 'et' THEN seed.estonian_value
           WHEN 'ru' THEN seed.russian_value
       END,
       'active'
FROM schedule_print_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE schedule_print_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.71', 'Add monthly schedule printing with location selection.', NOW());
-- END MIGRATION: 058_schedule_printing.sql

-- BEGIN MIGRATION: 059_schedule_print_row_height.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.72', 'Equalize employee row heights in the schedule print view.', NOW());
-- END MIGRATION: 059_schedule_print_row_height.sql

-- BEGIN MIGRATION: 060_schedule_tab_visibility.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.73', 'Keep schedule settings tabs visible when selected.', NOW());
-- END MIGRATION: 060_schedule_tab_visibility.sql

-- BEGIN MIGRATION: 061_schedule_print_admin_tabs.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.74', 'Keep administrator schedule tabs visible on the print page.', NOW());
-- END MIGRATION: 061_schedule_print_admin_tabs.sql

-- BEGIN MIGRATION: 062_documentation_alignment.sql
INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.75', 'Align the AI prompt and schedule guide with current behavior.', NOW());
-- END MIGRATION: 062_documentation_alignment.sql

-- BEGIN MIGRATION: 063_reconstruction_documentation.sql
INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.76', 'Document the full CRM reconstruction workflow, current behavior, route inventory, SQL recovery bundle and acceptance checks.', 'documentation, all modules', 'No business schema changes - version registration only.', NOW());

-- END MIGRATION: 063_reconstruction_documentation.sql

-- BEGIN MIGRATION: 064_documentation_migration_delimiter_fix.sql
INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.77', 'Fix the documentation migration string delimiter that prevented application startup.', 'schema installer, documentation', 'No business schema changes - correct migration 063 and register the fix.', NOW());

-- END MIGRATION: 064_documentation_migration_delimiter_fix.sql

-- BEGIN MIGRATION: 065_client_management_read_only.sql
INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.78', 'Display client details as plain text without form controls for non-administrators, retaining the administrator form and server-side POST restriction.', 'client management', 'No business schema changes - version registration only.', NOW());

-- END MIGRATION: 065_client_management_read_only.sql

-- BEGIN MIGRATION: 066_level_f_self_service_scope.sql
INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.79', 'Restrict Level F employee and employment lists to the signed-in account and deny employment catalog access.', 'employees, employment, roles', 'No business schema changes - version registration only.', NOW());

-- END MIGRATION: 066_level_f_self_service_scope.sql

-- BEGIN MIGRATION: 067_level_f_schedule_print_scope.sql
INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.80', 'Restrict Level F schedule access to printing location and department groups containing their own valid primary or temporary contract for the selected month.', 'schedule, roles', 'No business schema changes - version registration only.', NOW());

-- END MIGRATION: 067_level_f_schedule_print_scope.sql

-- BEGIN MIGRATION: 068_level_f_hidden_modules_property.sql
INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.81', 'Hide Active modules and Property structure for Level F and deny direct page access while retaining schedule printing dependencies.', 'modules, property, roles', 'No business schema changes - version registration only.', NOW());

-- END MIGRATION: 068_level_f_hidden_modules_property.sql

-- BEGIN MIGRATION: 069_manager_own_schedule_scope.sql
INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.82', 'Allow managers assigned to active employee contracts in the selected month to view and edit their own schedule alongside assigned employee contracts.', 'schedule', 'No business schema changes - version registration only.', NOW());

-- END MIGRATION: 069_manager_own_schedule_scope.sql

-- BEGIN MIGRATION: 070_schedule_manager_substitutes.sql
CREATE TEMPORARY TABLE schedule_substitute_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO schedule_substitute_translation_seed (english_value, estonian_value, russian_value) VALUES
('Schedule substitutes', 'Graafikuasendajad', 'Заместители руководителя графика'),
('Manager being substituted', 'Asendatav manager', 'Замещаемый руководитель'),
('Assign substitute', 'Määra asendaja', 'Назначить заместителя'),
('End substitute rights', 'Lõpeta asendaja õigused', 'Прекратить права заместителя'),
('Manager', 'Manager', 'Руководитель'),
('Substitute', 'Asendaja', 'Заместитель'),
('Starts at', 'Algusaeg', 'Начало'),
('Ends at', 'Lõppaeg', 'Окончание'),
('Period', 'Periood', 'Период'),
('Reason', 'Põhjus', 'Причина'),
('Status', 'Olek', 'Статус'),
('active', 'aktiivne', 'активно'),
('scheduled', 'planeeritud', 'запланировано'),
('ended', 'lõppenud', 'завершено'),
('Substitutes can edit current and future months only while the assignment is active.', 'Asendajad saavad muuta jooksvat kuud ja tulevasi kuid ainult aktiivse asendusperioodi jooksul.', 'Заместители могут изменять текущий и будущие месяцы только в период активного замещения.'),
('Account roles are unchanged. No employment contract is required for the substitute.', 'Konto rollid ei muutu. Asendajal ei pea olema töölepingut.', 'Роли аккаунта не меняются. Заместителю не требуется трудовой договор.'),
('If the substitute is not listed, ask the client administrator to add and activate the account under Employees & permissions.', 'Kui asendajat pole loendis, palu kliendiadministraatoril lisada ja aktiveerida konto Töötajad ja õigused all.', 'Если заместителя нет в списке, попросите администратора клиента добавить и активировать аккаунт в разделе Сотрудники и права.'),
('Schedule substitute assigned. Existing account roles are unchanged.', 'Graafikuasendaja määratud. Konto olemasolevad rollid ei muutu.', 'Заместитель графика назначен. Существующие роли аккаунта не меняются.'),
('Schedule substitute rights ended.', 'Graafikuasendaja õigused lõpetatud.', 'Права заместителя графика прекращены.'),
('You are not allowed to assign a schedule substitute.', 'Sul pole õigust graafikuasendajat määrata.', 'У вас нет права назначать заместителя графика.'),
('Choose different users and a valid future-ending substitute period.', 'Vali erinevad kasutajad ja korrektne tulevikus lõppev asendusperiood.', 'Выберите разных пользователей и корректный период замещения с окончанием в будущем.'),
('A print-only Level F user cannot delegate manager editing rights.', 'Ainult printimisõigusega F kasutaja ei saa manageri muutmisõigust delegeerida.', 'Пользователь F с правом только печати не может передавать права редактирования руководителя.'),
('Both users must have active accounts in this client. Add the substitute under Employees & permissions first.', 'Mõlemal kasutajal peab olema selles kliendis aktiivne konto. Lisa asendaja esmalt Töötajad ja õigused alla.', 'Оба пользователя должны иметь активные аккаунты у этого клиента. Сначала добавьте заместителя в Сотрудники и права.'),
('The original user must manage another active employee contract during the substitute period.', 'Asendatav kasutaja peab asendusperioodil olema teise aktiivse töötaja lepingul manager.', 'Замещаемый пользователь должен руководить другим активным сотрудником по договору в период замещения.'),
('This substitute already has an overlapping assignment for this manager.', 'Sellel asendajal on juba selle manageri kattuv asendusperiood.', 'У этого заместителя уже есть пересекающееся назначение для данного руководителя.'),
('Schedule substitute assignment not found or not allowed.', 'Graafikuasendust ei leitud või toiming pole lubatud.', 'Назначение заместителя графика не найдено или действие не разрешено.'),
('Use the Schedule substitutes page to assign schedule editing rights.', 'Graafiku muutmisõiguse määramiseks kasuta Graafikuasendajad lehte.', 'Для назначения прав изменения графика используйте страницу заместителей графика.');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM schedule_substitute_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code WHEN 'en' THEN seed.english_value WHEN 'et' THEN seed.estonian_value WHEN 'ru' THEN seed.russian_value END,
       'active'
FROM schedule_substitute_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE schedule_substitute_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.83', 'Add time-limited manager-assigned schedule shift substitution for current and future months without changing base account roles or requiring substitute employment contracts.', 'schedule, substitutes, translations', 'Translation seeds and version registration only - existing system_substitutes stores dedicated schedule assignments.', NOW());

-- END MIGRATION: 070_schedule_manager_substitutes.sql

-- BEGIN MIGRATION: 071_support_conversations.sql
-- PHP repair handler equivalent: system_support_tickets.recipient_type
SET @crm_repair_sql = (
    SELECT IF(COUNT(*) = 0, 'ALTER TABLE system_support_tickets ADD COLUMN recipient_type ENUM(''system'', ''client_admin'', ''manager'') NOT NULL DEFAULT ''system''', 'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_support_tickets' AND COLUMN_NAME = 'recipient_type'
);
PREPARE crm_repair_stmt FROM @crm_repair_sql;
EXECUTE crm_repair_stmt;
DEALLOCATE PREPARE crm_repair_stmt;

-- PHP repair handler equivalent: system_support_tickets.closed_at
SET @crm_repair_sql = (
    SELECT IF(COUNT(*) = 0, 'ALTER TABLE system_support_tickets ADD COLUMN closed_at DATETIME NULL, ADD KEY idx_support_closed (closed_at, id)', 'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_support_tickets' AND COLUMN_NAME = 'closed_at'
);
PREPARE crm_repair_stmt FROM @crm_repair_sql;
EXECUTE crm_repair_stmt;
DEALLOCATE PREPARE crm_repair_stmt;

-- PHP repair handler equivalent: system_support_tickets.closed_by_user_id
SET @crm_repair_sql = (
    SELECT IF(COUNT(*) = 0, 'ALTER TABLE system_support_tickets ADD COLUMN closed_by_user_id INT UNSIGNED NULL, ADD CONSTRAINT fk_support_closed_by FOREIGN KEY (closed_by_user_id) REFERENCES system_users (id) ON DELETE SET NULL', 'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_support_tickets' AND COLUMN_NAME = 'closed_by_user_id'
);
PREPARE crm_repair_stmt FROM @crm_repair_sql;
EXECUTE crm_repair_stmt;
DEALLOCATE PREPARE crm_repair_stmt;

CREATE TABLE IF NOT EXISTS system_support_replies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_support_replies_thread (ticket_id, created_at, id),
    CONSTRAINT fk_support_replies_ticket FOREIGN KEY (ticket_id) REFERENCES system_support_tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_support_replies_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS system_support_participants (
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    added_by_user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ticket_id, user_id),
    KEY idx_support_participant_user (user_id, ticket_id),
    CONSTRAINT fk_support_participants_ticket FOREIGN KEY (ticket_id) REFERENCES system_support_tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_support_participants_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_support_participants_added_by FOREIGN KEY (added_by_user_id) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE system_support_tickets SET closed_at = updated_at WHERE status = 'closed' AND closed_at IS NULL;

CREATE TEMPORARY TABLE support_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO support_translation_seed (english_value, estonian_value, russian_value) VALUES
('Recipient', 'Vastaja', 'Получатель'),
('System support', 'Süsteemi administraator', 'Администратор системы'),
('Client administrator', 'Kliendi administraator', 'Администратор клиента'),
('Employee manager', 'Töötaja manager', 'Руководитель сотрудника'),
('Choose recipient', 'Vali vastaja', 'Выберите получателя'),
('No available recipients.', 'Sobivaid vastajaid ei ole.', 'Нет доступных получателей.'),
('Open tickets', 'Avatud päringud', 'Открытые обращения'),
('Recently closed tickets', 'Viimati suletud päringud', 'Недавно закрытые обращения'),
('The 10 most recently closed tickets are shown.', 'Kuvatakse 10 viimati suletud päringut.', 'Показаны 10 последних закрытых обращений.'),
('No open tickets.', 'Avatud päringuid ei ole.', 'Нет открытых обращений.'),
('No closed tickets.', 'Suletud päringuid ei ole.', 'Нет закрытых обращений.'),
('Closed', 'Suletud', 'Закрыто'),
('open', 'avatud', 'открыто'),
('in_progress', 'töös', 'в работе'),
('closed', 'suletud', 'закрыто'),
('Support ticket', 'Toe päring', 'Обращение в поддержку'),
('Conversation', 'Vestlus', 'Переписка'),
('Participants', 'Osalejad', 'Участники'),
('Add participant', 'Lisa osaleja', 'Добавить участника'),
('Choose participant', 'Vali osaleja', 'Выберите участника'),
('Invited participants', 'Kutsutud osalejad', 'Приглашённые участники'),
('Reply', 'Vastus', 'Ответ'),
('Send reply', 'Saada vastus', 'Отправить ответ'),
('Close ticket', 'Sulge päring', 'Закрыть обращение'),
('Back to support', 'Tagasi toe lehele', 'Вернуться к поддержке'),
('Only the creator, recipient and invited participants can see this conversation.', 'Vestlust näevad ainult looja, vastaja ja kutsutud osalejad.', 'Переписку видят только автор, получатель и приглашённые участники.'),
('System support tickets are visible to the system support team.', 'Süsteemi administraatorile saadetud päringuid näeb süsteemi toe meeskond.', 'Обращения администратору системы видит команда системной поддержки.'),
('This ticket is closed and read-only.', 'See päring on suletud ja ainult loetav.', 'Это обращение закрыто и доступно только для чтения.'),
('Reply added.', 'Vastus lisatud.', 'Ответ добавлен.'),
('Participant added.', 'Osaleja lisatud.', 'Участник добавлен.'),
('Support ticket closed.', 'Toe päring suletud.', 'Обращение закрыто.'),
('Choose a ticket recipient.', 'Vali päringu vastaja.', 'Выберите получателя обращения.'),
('Choose an available recipient for this ticket.', 'Vali selle päringu jaoks sobiv vastaja.', 'Выберите доступного получателя для этого обращения.'),
('Subject must not exceed 191 characters.', 'Teema võib olla kuni 191 märki pikk.', 'Тема не должна превышать 191 символ.'),
('Message is required.', 'Sõnum on kohustuslik.', 'Сообщение обязательно.'),
('Message must not exceed 65535 bytes.', 'Sõnum võib olla kuni 65535 baiti pikk.', 'Сообщение не должно превышать 65535 байт.'),
('Support ticket access denied.', 'Toe päringu juurdepääs keelatud.', 'Доступ к обращению запрещён.'),
('Support ticket not found or access denied.', 'Toe päringut ei leitud või juurdepääs on keelatud.', 'Обращение не найдено или доступ запрещён.'),
('Invalid ticket action.', 'Vigane päringu toiming.', 'Недопустимое действие с обращением.'),
('This action requires POST.', 'See toiming nõuab POST-päringut.', 'Для этого действия требуется POST-запрос.'),
('Only the creator or recipient can invite participants or close this ticket.', 'Osalejaid saab kutsuda ja päringut sulgeda ainult looja või vastaja.', 'Только автор или получатель может приглашать участников или закрывать обращение.'),
('Choose an active employee who is not already a participant.', 'Vali aktiivne töötaja, kes ei ole veel osaleja.', 'Выберите активного сотрудника, который ещё не является участником.');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM support_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code WHEN 'en' THEN seed.english_value WHEN 'et' THEN seed.estonian_value WHEN 'ru' THEN seed.russian_value END,
       'active'
FROM support_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE support_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.84', 'Private recipient-directed support conversations with replies, invited participants and a read-only latest-ten closed archive.', 'support, translations', 'Ticket recipient and closure columns, reply and participant tables, legacy closed timestamp backfill and translations.', NOW());

-- END MIGRATION: 071_support_conversations.sql

-- BEGIN MIGRATION: 072_estonian_time_zone.sql
INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.85', 'Align PHP and every application MySQL session with Europe/Tallinn, including daylight saving transitions.', 'core, notifications, support, audit', 'Version registration only - historical timestamps are not shifted without provenance verification.', NOW());

-- END MIGRATION: 072_estonian_time_zone.sql

-- BEGIN MIGRATION: 073_notification_archive.sql
CREATE TABLE IF NOT EXISTS system_notification_reads (
    notification_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notification_id, user_id),
    CONSTRAINT fk_notification_reads_notification FOREIGN KEY (notification_id) REFERENCES system_notifications (id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_reads_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TEMPORARY TABLE notification_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO notification_translation_seed (english_value, estonian_value, russian_value) VALUES
('Last 30 days', 'Viimased 30 päeva', 'Последние 30 дней'),
('Archive', 'Arhiiv', 'Архив'),
('Notifications older than 30 days.', 'Üle 30 päeva vanused teavitused.', 'Уведомления старше 30 дней.'),
('Notifications from the last 30 days.', 'Viimase 30 päeva teavitused.', 'Уведомления за последние 30 дней.'),
('Unread notifications from the last 24 hours', 'Viimase 24 tunni lugemata teavitused', 'Непрочитанные уведомления за последние 24 часа'),
('Previous', 'Eelmine', 'Назад'),
('Next', 'Järgmine', 'Далее');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui' FROM notification_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_keys.id, languages.id,
       CASE languages.language_code WHEN 'en' THEN seed.english_value WHEN 'et' THEN seed.estonian_value WHEN 'ru' THEN seed.russian_value END,
       'active'
FROM notification_translation_seed seed
INNER JOIN system_translation_keys translation_keys ON translation_keys.translation_key = CONCAT('ui.', SHA2(seed.english_value, 256))
INNER JOIN system_languages languages ON languages.language_code IN ('en', 'et', 'ru') AND languages.is_active = 1;

DROP TEMPORARY TABLE notification_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.86', 'Add 30-day notification inbox, paginated older archive and per-user unread badge for the last 24 hours.', 'notifications, translations', 'Per-user notification read receipts and translation seeds.', NOW());

-- END MIGRATION: 073_notification_archive.sql

-- BEGIN MIGRATION: 074_support_unopened_count.sql
CREATE TABLE IF NOT EXISTS system_support_reads (
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ticket_id, user_id),
    CONSTRAINT fk_support_reads_ticket FOREIGN KEY (ticket_id) REFERENCES system_support_tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_support_reads_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('Unopened active support tickets', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE l.language_code
           WHEN 'en' THEN 'Unopened active support tickets'
           WHEN 'et' THEN 'Avamata aktiivsed toe päringud'
           WHEN 'ru' THEN 'Неоткрытые активные обращения в поддержку'
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key = CONCAT('ui.', SHA2('Unopened active support tickets', 256));

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.87', 'Count active support tickets never opened by the recipient or participant, treating creators as already read.', 'support, translations', 'Per-user first-open support receipts and badge translations.', NOW());

-- END MIGRATION: 074_support_unopened_count.sql

-- BEGIN MIGRATION: 075_support_reply_unread.sql
CREATE TABLE IF NOT EXISTS system_support_reply_reads (
    ticket_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    last_reply_id INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (ticket_id, user_id),
    CONSTRAINT fk_support_reply_reads_ticket FOREIGN KEY (ticket_id) REFERENCES system_support_tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_support_reply_reads_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('Unread active support tickets', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE l.language_code
           WHEN 'en' THEN 'Unread active support tickets'
           WHEN 'et' THEN 'Lugemata aktiivsed toe päringud'
           WHEN 'ru' THEN 'Непрочитанные активные обращения в поддержку'
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key = CONCAT('ui.', SHA2('Unread active support tickets', 256));

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.88', 'Count active support tickets with unseen replies as unread for other participants including creators.', 'support, translations', 'Per-user reply ID read watermarks and badge translations.', NOW());

-- END MIGRATION: 075_support_reply_unread.sql

-- BEGIN MIGRATION: 076_support_unread_rows.sql
INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('New reply', 256)), 'ui'),
       (CONCAT('ui.', SHA2('Unread', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE
           WHEN k.translation_key = CONCAT('ui.', SHA2('New reply', 256)) THEN
               CASE l.language_code WHEN 'en' THEN 'New reply' WHEN 'et' THEN 'Uus vastus' WHEN 'ru' THEN 'Новый ответ' END
           ELSE
               CASE l.language_code WHEN 'en' THEN 'Unread' WHEN 'et' THEN 'Lugemata' WHEN 'ru' THEN 'Не прочитано' END
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key IN (CONCAT('ui.', SHA2('New reply', 256)), CONCAT('ui.', SHA2('Unread', 256)));

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.89', 'Highlight unread support ticket rows in bold with new reply or unread labels using the menu badge read rules.', 'support, translations', 'Translation seeds and version registration only.', NOW());

-- END MIGRATION: 076_support_unread_rows.sql

-- BEGIN MIGRATION: 077_property_type_translations.sql
INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('Building', 256)), 'ui'),
       (CONCAT('ui.', SHA2('Wing', 256)), 'ui'),
       (CONCAT('ui.', SHA2('Floor', 256)), 'ui'),
       (CONCAT('ui.', SHA2('Room', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE l.language_code WHEN 'en' THEN labels.en WHEN 'et' THEN labels.et WHEN 'ru' THEN labels.ru END,
       'active'
FROM (
    SELECT 'Building' AS en, 'Hoone' AS et, 'Здание' AS ru
    UNION ALL SELECT 'Wing', 'Korpus', 'Корпус'
    UNION ALL SELECT 'Floor', 'Korrus', 'Этаж'
    UNION ALL SELECT 'Room', 'Ruum', 'Помещение'
) labels
INNER JOIN system_translation_keys k ON k.translation_key = CONCAT('ui.', SHA2(labels.en, 256))
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1;

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.90', 'Added Estonian and Russian property type translations for list and creation views.', 'property, translations', 'Translation seeds and version registration only.', NOW());

-- END MIGRATION: 077_property_type_translations.sql

-- BEGIN MIGRATION: 078_new_job_title_translation.sql
INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('New job title', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE l.language_code WHEN 'en' THEN 'New job title' WHEN 'et' THEN 'Uus ametinimetus' WHEN 'ru' THEN 'Новая должность' END,
       'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key = CONCAT('ui.', SHA2('New job title', 256));

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.91', 'Added Estonian and Russian translations for the New job title form label.', 'employment, translations', 'Translation seeds and version registration only.', NOW());

-- END MIGRATION: 078_new_job_title_translation.sql

-- BEGIN MIGRATION: 079_employment_ui_translations.sql
DROP TEMPORARY TABLE IF EXISTS employment_translation_seed;

CREATE TEMPORARY TABLE employment_translation_seed (
    english_value VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
    estonian_value TEXT NOT NULL,
    russian_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO employment_translation_seed (english_value, estonian_value, russian_value) VALUES
('New department', 'Uus osakond', 'Новый отдел'),
('New workload', 'Uus koormus', 'Новая нагрузка'),
('Job title name', 'Ametinimetuse nimi', 'Название должности'),
('Department name', 'Osakonna nimi', 'Название отдела'),
('Workload name', 'Koormuse nimi', 'Название нагрузки'),
('Hide', 'Peida', 'Скрыть'),
('Activate', 'Aktiveeri', 'Активировать'),
('Edit contract', 'Muuda lepingut', 'Изменить договор'),
('Open-ended', 'Tähtajatu', 'Бессрочный'),
('Restore', 'Taasta', 'Восстановить');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
SELECT CONCAT('ui.', SHA2(english_value, 256)), 'ui'
FROM employment_translation_seed;

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE l.language_code WHEN 'en' THEN s.english_value WHEN 'et' THEN s.estonian_value WHEN 'ru' THEN s.russian_value END,
       'active'
FROM employment_translation_seed s
INNER JOIN system_translation_keys k ON k.translation_key = CONCAT('ui.', SHA2(s.english_value, 256))
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1;

DROP TEMPORARY TABLE IF EXISTS employment_translation_seed;

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.92', 'Completed missing employment catalogue, contract form and action label translations in Estonian and Russian.', 'employment, translations', 'Translation seeds and version registration only.', NOW());

-- END MIGRATION: 079_employment_ui_translations.sql

-- BEGIN MIGRATION: 080_schedule_consecutive_exception_translations.sql
INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('Consecutive days (including selected day)', 256)), 'ui'),
       (CONCAT('ui.', SHA2('The exception cannot extend beyond this contract.', 256)), 'ui');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE
           WHEN k.translation_key = CONCAT('ui.', SHA2('Consecutive days (including selected day)', 256)) THEN
               CASE l.language_code
                   WHEN 'en' THEN 'Consecutive days (including selected day)'
                   WHEN 'et' THEN 'Päevade arv (koos valitud päevaga)'
                   WHEN 'ru' THEN 'Количество дней (включая выбранный день)'
               END
           ELSE
               CASE l.language_code
                   WHEN 'en' THEN 'The exception cannot extend beyond this contract.'
                   WHEN 'et' THEN 'Erand ei saa ulatuda töölepingu kehtivusajast kaugemale.'
                   WHEN 'ru' THEN 'Исключение не может выходить за пределы срока договора.'
               END
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key IN (
    CONCAT('ui.', SHA2('Consecutive days (including selected day)', 256)),
    CONCAT('ui.', SHA2('The exception cannot extend beyond this contract.', 256))
);

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.93', 'Added consecutive multi-day exception assignments from a selected schedule date, with cross-month contract and permission validation.', 'schedule, translations', 'Translation seeds and version registration only.', NOW());

-- END MIGRATION: 080_schedule_consecutive_exception_translations.sql

-- BEGIN MIGRATION: 081_client_domain_embed.sql
CREATE TABLE IF NOT EXISTS system_client_domain_embeds (
    client_id INT UNSIGNED NOT NULL PRIMARY KEY,
    customer_domain VARCHAR(253) NOT NULL,
    embed_token CHAR(64) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_client_domain_embeds_domain (customer_domain),
    UNIQUE KEY uq_client_domain_embeds_token (embed_token),
    CONSTRAINT fk_client_domain_embeds_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_client_domain_embeds_user FOREIGN KEY (created_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('client_domain_embed', 'Customer domain embed', 'Download an iframe page that displays this client workspace on its own domain.', 'active', 0);

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES (CONCAT('ui.', SHA2('Customer domain embed', 256)), 'ui'),
       (CONCAT('ui.', SHA2('Download an iframe page that displays this client workspace on its own domain.', 256)), 'ui');

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES ('embed.company_workspace', 'embed'),
       ('embed.signin_fallback', 'embed'),
       ('embed.open_new_tab', 'embed'),
       ('embed.customer_domain', 'embed'),
       ('embed.setup_title', 'embed'),
       ('embed.setup_help', 'embed'),
       ('embed.domain_label', 'embed'),
       ('embed.domain_hint', 'embed'),
       ('embed.save_domain', 'embed'),
       ('embed.download_page', 'embed'),
       ('embed.download_hint', 'embed'),
       ('embed.not_configured', 'embed'),
       ('embed.domain_saved', 'embed'),
       ('embed.invalid_domain', 'embed'),
       ('embed.domain_in_use', 'embed'),
       ('embed.module_inactive', 'embed');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE k.translation_key
           WHEN 'embed.company_workspace' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi tööruum' WHEN 'ru' THEN 'Рабочая область клиента' ELSE 'Client workspace' END
           WHEN 'embed.signin_fallback' THEN CASE l.language_code WHEN 'et' THEN 'Kui sisselogimine manustatud aknas ei tööta, ava CRM eraldi vahekaardil.' WHEN 'ru' THEN 'Если вход во встроенном окне не работает, откройте CRM в отдельной вкладке.' ELSE 'If sign-in is blocked in this embedded window, open the CRM in a separate tab.' END
           WHEN 'embed.open_new_tab' THEN CASE l.language_code WHEN 'et' THEN 'Ava uuel vahekaardil' WHEN 'ru' THEN 'Открыть в новой вкладке' ELSE 'Open in a new tab' END
           WHEN 'embed.customer_domain' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi domeeni leht' WHEN 'ru' THEN 'Страница на домене клиента' ELSE 'Customer domain page' END
           WHEN 'embed.setup_title' THEN CASE l.language_code WHEN 'et' THEN 'CRM-i kuvamine kliendi domeenil' WHEN 'ru' THEN 'Отображение CRM на домене клиента' ELSE 'Display CRM on a customer domain' END
           WHEN 'embed.setup_help' THEN CASE l.language_code WHEN 'et' THEN 'See moodul genereerib staatilise HTML-faili, mis kuvab selle kliendi tööruumi iframe’is. Rakendus ja andmed jäävad DemoIT CRM-i serverisse.' WHEN 'ru' THEN 'Этот модуль создаёт статический HTML-файл, который показывает рабочую область клиента во фрейме. Приложение и данные остаются на сервере DemoIT CRM.' ELSE 'This module generates a static HTML file that displays this client workspace in an iframe. The application and data remain on the DemoIT CRM server.' END
           WHEN 'embed.domain_label' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi domeen' WHEN 'ru' THEN 'Домен клиента' ELSE 'Customer domain' END
           WHEN 'embed.domain_hint' THEN CASE l.language_code WHEN 'et' THEN 'Sisesta domeeninimi ilma https://, tee või pordita. Kliendi veebileht peab kasutama HTTPS-i.' WHEN 'ru' THEN 'Введите доменное имя без https://, пути или порта. Сайт клиента должен использовать HTTPS.' ELSE 'Enter the domain without https://, a path or port. The customer website must use HTTPS.' END
           WHEN 'embed.save_domain' THEN CASE l.language_code WHEN 'et' THEN 'Salvesta domeen' WHEN 'ru' THEN 'Сохранить домен' ELSE 'Save domain' END
           WHEN 'embed.download_page' THEN CASE l.language_code WHEN 'et' THEN 'Laadi HTML-leht alla' WHEN 'ru' THEN 'Скачать HTML-страницу' ELSE 'Download HTML page' END
           WHEN 'embed.download_hint' THEN CASE l.language_code WHEN 'et' THEN 'Laadi fail alla ja paigalda see kliendi domeeni veebijuure või sobiva alamlehe index.html-failina. Domeeni või tokeni muutmisel laadi uus fail alla.' WHEN 'ru' THEN 'Скачайте файл и разместите его как index.html в корне сайта клиента или в нужном каталоге. После изменения домена или токена скачайте новый файл.' ELSE 'Download the file and place it as index.html in the customer website root or a suitable directory. Download a new file after changing the domain or token.' END
           WHEN 'embed.domain_saved' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi domeen salvestati. Muudatuse rakendamiseks laadi uus leht alla.' WHEN 'ru' THEN 'Домен клиента сохранён. Для применения изменений скачайте новую страницу.' ELSE 'Customer domain saved. Download a new page to apply the change.' END
           WHEN 'embed.invalid_domain' THEN CASE l.language_code WHEN 'et' THEN 'Sisesta korrektne domeeninimi ilma skeemi, tee või pordita.' WHEN 'ru' THEN 'Введите корректное доменное имя без схемы, пути или порта.' ELSE 'Enter a valid domain name without a scheme, path or port.' END
           WHEN 'embed.domain_in_use' THEN CASE l.language_code WHEN 'et' THEN 'See domeen on juba teise kliendi kasutuses.' WHEN 'ru' THEN 'Этот домен уже настроен для другого клиента.' ELSE 'This domain is already configured for another client.' END
           WHEN 'embed.module_inactive' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi domeeni moodul ei ole selle kliendi jaoks aktiivne.' WHEN 'ru' THEN 'Модуль домена клиента не активирован для этого клиента.' ELSE 'The customer-domain module is not active for this client.' END
           ELSE CASE l.language_code WHEN 'et' THEN 'Domeen pole veel seadistatud.' WHEN 'ru' THEN 'Домен ещё не настроен.' ELSE 'No domain has been configured yet.' END
       END,
       'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key LIKE 'embed.%';

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE k.translation_key
           WHEN CONCAT('ui.', SHA2('Customer domain embed', 256)) THEN
               CASE l.language_code
                   WHEN 'et' THEN 'Kliendi domeeni embed'
                   WHEN 'ru' THEN 'Встраивание на домен клиента'
                   ELSE 'Customer domain embed'
               END
           ELSE
               CASE l.language_code
                   WHEN 'et' THEN 'Laadi alla iframe-leht, mis kuvab selle kliendi tööruumi tema enda domeenil.'
                   WHEN 'ru' THEN 'Скачайте страницу iframe, которая отображает рабочую область этого клиента на его домене.'
                   ELSE 'Download an iframe page that displays this client workspace on its own domain.'
               END
       END,
       'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key IN (
    CONCAT('ui.', SHA2('Customer domain embed', 256)),
    CONCAT('ui.', SHA2('Download an iframe page that displays this client workspace on its own domain.', 256))
);

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.94', 'Added downloadable customer-domain iframe pages with tenant-pinned login.', 'authentication, client domain embed', 'Added system_client_domain_embeds, module catalogue seed and English/Estonian/Russian translations.', NOW());

-- END MIGRATION: 081_client_domain_embed.sql

-- BEGIN MIGRATION: 082_client_administrator_management.sql
INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES ('admin.client_admins', 'admin'),
       ('admin.client_admin_add', 'admin'),
       ('admin.client_admin_temp_password', 'admin'),
       ('admin.client_admin_none', 'admin'),
       ('admin.client_admin_username', 'admin'),
       ('admin.client_admin_name', 'admin'),
       ('admin.client_admin_contact', 'admin'),
       ('admin.client_admin_status', 'admin'),
       ('admin.client_admin_edit', 'admin'),
       ('admin.client_admin_reset', 'admin'),
       ('admin.client_admin_back', 'admin'),
       ('admin.client_admin_email', 'admin'),
       ('admin.client_admin_phone', 'admin'),
       ('admin.client_admin_temp_password_note', 'admin'),
       ('admin.client_admin_create', 'admin'),
       ('admin.client_admin_edit_title', 'admin'),
       ('admin.client_admin_save', 'admin');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE k.translation_key
           WHEN 'admin.client_admins' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi administraatorid' WHEN 'ru' THEN 'Администраторы клиента' ELSE 'Client administrators' END
           WHEN 'admin.client_admin_add' THEN CASE l.language_code WHEN 'et' THEN 'Lisa administraator' WHEN 'ru' THEN 'Добавить администратора' ELSE 'Add administrator' END
           WHEN 'admin.client_admin_temp_password' THEN CASE l.language_code WHEN 'et' THEN 'Ajutine parool (kuvatakse ainult üks kord):' WHEN 'ru' THEN 'Временный пароль (показывается только один раз):' ELSE 'Temporary password (shown only once):' END
           WHEN 'admin.client_admin_none' THEN CASE l.language_code WHEN 'et' THEN 'Kliendi administraatoreid pole veel lisatud.' WHEN 'ru' THEN 'Администраторы клиента ещё не добавлены.' ELSE 'No client administrators yet.' END
           WHEN 'admin.client_admin_username' THEN CASE l.language_code WHEN 'et' THEN 'Kasutajanimi' WHEN 'ru' THEN 'Имя пользователя' ELSE 'Username' END
           WHEN 'admin.client_admin_name' THEN CASE l.language_code WHEN 'et' THEN 'Täisnimi' WHEN 'ru' THEN 'Полное имя' ELSE 'Full name' END
           WHEN 'admin.client_admin_contact' THEN CASE l.language_code WHEN 'et' THEN 'Kontakt' WHEN 'ru' THEN 'Контакт' ELSE 'Contact' END
           WHEN 'admin.client_admin_status' THEN CASE l.language_code WHEN 'et' THEN 'Olek' WHEN 'ru' THEN 'Статус' ELSE 'Status' END
           WHEN 'admin.client_admin_edit' THEN CASE l.language_code WHEN 'et' THEN 'Muuda' WHEN 'ru' THEN 'Изменить' ELSE 'Edit' END
           WHEN 'admin.client_admin_reset' THEN CASE l.language_code WHEN 'et' THEN 'Lähtesta parool' WHEN 'ru' THEN 'Сбросить пароль' ELSE 'Reset password' END
           WHEN 'admin.client_admin_back' THEN CASE l.language_code WHEN 'et' THEN 'Tagasi kliendi juurde' WHEN 'ru' THEN 'Назад к клиенту' ELSE 'Back to client' END
           WHEN 'admin.client_admin_email' THEN CASE l.language_code WHEN 'et' THEN 'E-post' WHEN 'ru' THEN 'Электронная почта' ELSE 'E-mail' END
           WHEN 'admin.client_admin_phone' THEN CASE l.language_code WHEN 'et' THEN 'Telefon' WHEN 'ru' THEN 'Телефон' ELSE 'Phone' END
           WHEN 'admin.client_admin_temp_password_note' THEN CASE l.language_code WHEN 'et' THEN 'Ajutine parool luuakse automaatselt ja kuvatakse pärast loomist ühe korra.' WHEN 'ru' THEN 'Временный пароль будет создан автоматически и показан один раз после создания.' ELSE 'A temporary password will be generated and shown once after creation.' END
           WHEN 'admin.client_admin_create' THEN CASE l.language_code WHEN 'et' THEN 'Loo administraator' WHEN 'ru' THEN 'Создать администратора' ELSE 'Create administrator' END
           WHEN 'admin.client_admin_edit_title' THEN CASE l.language_code WHEN 'et' THEN 'Muuda kliendi administraatorit' WHEN 'ru' THEN 'Изменить администратора клиента' ELSE 'Edit client administrator' END
           ELSE CASE l.language_code WHEN 'et' THEN 'Salvesta muudatused' WHEN 'ru' THEN 'Сохранить изменения' ELSE 'Save changes' END
       END,
       'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key LIKE 'admin.client_admin_%';

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.95', 'Added client administrator-only management to client details.', 'clients, users, translations', 'Added English, Estonian and Russian translations for client administrator management. Existing user and role schema is reused.', NOW());

-- END MIGRATION: 082_client_administrator_management.sql

-- BEGIN MIGRATION: 083_employee_list_pagination.sql
CREATE TABLE IF NOT EXISTS employee_list_settings (
    client_id INT UNSIGNED NOT NULL PRIMARY KEY,
    page_size SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    CONSTRAINT fk_employee_list_settings_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES ('employees.search_name', 'employees'),
       ('employees.search', 'employees'),
       ('employees.clear_search', 'employees'),
       ('employees.list_settings', 'employees'),
       ('employees.per_page', 'employees'),
       ('employees.save_settings', 'employees'),
       ('employees.shared_settings', 'employees'),
       ('employees.results', 'employees'),
       ('employees.no_results', 'employees'),
       ('employees.pagination', 'employees'),
       ('employees.page_size_invalid', 'employees'),
       ('employees.page_size_saved', 'employees');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE k.translation_key
           WHEN 'employees.search_name' THEN CASE l.language_code WHEN 'et' THEN 'Otsi nime järgi' WHEN 'ru' THEN 'Поиск по имени' ELSE 'Search by name' END
           WHEN 'employees.search' THEN CASE l.language_code WHEN 'et' THEN 'Otsi' WHEN 'ru' THEN 'Найти' ELSE 'Search' END
           WHEN 'employees.clear_search' THEN CASE l.language_code WHEN 'et' THEN 'Tühjenda otsing' WHEN 'ru' THEN 'Сбросить поиск' ELSE 'Clear search' END
           WHEN 'employees.list_settings' THEN CASE l.language_code WHEN 'et' THEN 'Töötajate loendi seaded' WHEN 'ru' THEN 'Настройки списка сотрудников' ELSE 'Employee list settings' END
           WHEN 'employees.per_page' THEN CASE l.language_code WHEN 'et' THEN 'Töötajaid lehel' WHEN 'ru' THEN 'Сотрудников на странице' ELSE 'Employees per page' END
           WHEN 'employees.save_settings' THEN CASE l.language_code WHEN 'et' THEN 'Salvesta seaded' WHEN 'ru' THEN 'Сохранить настройки' ELSE 'Save settings' END
           WHEN 'employees.shared_settings' THEN CASE l.language_code WHEN 'et' THEN 'Kehtib kõigile selle kliendi kasutajatele, ka otsingutulemustele.' WHEN 'ru' THEN 'Применяется ко всем пользователям клиента, включая результаты поиска.' ELSE 'Applies to all users of this client, including search results.' END
           WHEN 'employees.results' THEN CASE l.language_code WHEN 'et' THEN 'Tulemusi' WHEN 'ru' THEN 'Результатов' ELSE 'Results' END
           WHEN 'employees.no_results' THEN CASE l.language_code WHEN 'et' THEN 'Töötajaid ei leitud.' WHEN 'ru' THEN 'Сотрудники не найдены.' ELSE 'No employees found.' END
           WHEN 'employees.pagination' THEN CASE l.language_code WHEN 'et' THEN 'Töötajate loendi leheküljed' WHEN 'ru' THEN 'Страницы списка сотрудников' ELSE 'Employee list pages' END
           WHEN 'employees.page_size_invalid' THEN CASE l.language_code WHEN 'et' THEN 'Sisesta lehe suurus vahemikus 1 kuni 100.' WHEN 'ru' THEN 'Укажите размер страницы от 1 до 100.' ELSE 'Enter a page size between 1 and 100.' END
           ELSE CASE l.language_code WHEN 'et' THEN 'Töötajate loendi seaded on kliendile salvestatud.' WHEN 'ru' THEN 'Настройки списка сотрудников сохранены для клиента.' ELSE 'Employee list settings saved for this client.' END
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.module_context = 'employees' AND k.translation_key IN (
    'employees.search_name', 'employees.search', 'employees.clear_search', 'employees.list_settings',
    'employees.per_page', 'employees.save_settings', 'employees.shared_settings', 'employees.results',
    'employees.no_results', 'employees.pagination', 'employees.page_size_invalid', 'employees.page_size_saved'
);

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.96', 'Added employee name search and numbered pagination with client administrator-managed shared page size.', 'employees, translations', 'Added employee_list_settings and English, Estonian and Russian translations.', NOW());

-- END MIGRATION: 083_employee_list_pagination.sql

-- BEGIN MIGRATION: 084_contract_list_pagination.sql
CREATE TABLE IF NOT EXISTS employment_contract_list_settings (
    client_id INT UNSIGNED NOT NULL PRIMARY KEY,
    page_size SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    CONSTRAINT fk_contract_list_settings_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context)
VALUES ('employment.list_settings', 'employment'),
       ('employment.per_page', 'employment'),
       ('employment.no_results', 'employment'),
       ('employment.pagination', 'employment'),
       ('employment.page_size_saved', 'employment');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT k.id, l.id,
       CASE k.translation_key
           WHEN 'employment.list_settings' THEN CASE l.language_code WHEN 'et' THEN 'Töölepingute loendi seaded' WHEN 'ru' THEN 'Настройки списка трудовых договоров' ELSE 'Contract list settings' END
           WHEN 'employment.per_page' THEN CASE l.language_code WHEN 'et' THEN 'Töölepinguid lehel' WHEN 'ru' THEN 'Договоров на странице' ELSE 'Contracts per page' END
           WHEN 'employment.no_results' THEN CASE l.language_code WHEN 'et' THEN 'Töölepinguid ei leitud.' WHEN 'ru' THEN 'Трудовые договоры не найдены.' ELSE 'No contracts found.' END
           WHEN 'employment.pagination' THEN CASE l.language_code WHEN 'et' THEN 'Töölepingute loendi leheküljed' WHEN 'ru' THEN 'Страницы списка трудовых договоров' ELSE 'Contract list pages' END
           ELSE CASE l.language_code WHEN 'et' THEN 'Töölepingute loendi seaded on kliendile salvestatud.' WHEN 'ru' THEN 'Настройки списка трудовых договоров сохранены для клиента.' ELSE 'Contract list settings saved for this client.' END
       END, 'active'
FROM system_translation_keys k
INNER JOIN system_languages l ON l.language_code IN ('en', 'et', 'ru') AND l.is_active = 1
WHERE k.translation_key IN (
    'employment.list_settings', 'employment.per_page', 'employment.no_results',
    'employment.pagination', 'employment.page_size_saved'
);

INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.97', 'Added contract employee-name search and numbered pagination with client administrator-managed shared page size.', 'employment, translations', 'Added employment_contract_list_settings and English, Estonian and Russian translations.', NOW());

-- END MIGRATION: 084_contract_list_pagination.sql

-- BEGIN MIGRATION: 085_property_tree_collapse.sql
INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.98', 'Property hierarchy branches can be expanded and collapsed and start collapsed on each page load.', 'property', 'No schema changes.', NOW());

-- END MIGRATION: 085_property_tree_collapse.sql
