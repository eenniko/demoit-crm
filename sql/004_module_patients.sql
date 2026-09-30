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
