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
