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
