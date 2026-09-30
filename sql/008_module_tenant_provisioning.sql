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