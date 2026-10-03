CREATE TABLE IF NOT EXISTS employee_schedule_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    template_type ENUM('shift', 'exception') NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    color_hex CHAR(7) NOT NULL DEFAULT '#64748B',
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
    UNIQUE KEY uq_employee_schedule_day (client_id, employee_id, schedule_date),
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
    SELECT 'shift' AS template_type, '12Ö' AS code, '12-hour night shift' AS name, '20:00:00' AS start_time, 720 AS duration_minutes, '#2563EB' AS color_hex
    UNION ALL SELECT 'shift', '12P', '12-hour day shift', '08:00:00', 720, '#0F766E'
    UNION ALL SELECT 'shift', '24H', '24-hour shift', '08:00:00', 1440, '#7C3AED'
    UNION ALL SELECT 'shift', '8P', '8-hour day shift', '08:00:00', 480, '#D97706'
    UNION ALL SELECT 'exception', 'HP', 'HP', '08:00:00', 480, '#475569'
    UNION ALL SELECT 'exception', 'K', 'K', '08:00:00', 480, '#475569'
    UNION ALL SELECT 'exception', 'LHP', 'LHP', '08:00:00', 480, '#475569'
    UNION ALL SELECT 'exception', 'LIP', 'LIP', '08:00:00', 480, '#475569'
    UNION ALL SELECT 'exception', 'P', 'P', '08:00:00', 480, '#475569'
    UNION ALL SELECT 'exception', 'TV', 'TV', '08:00:00', 480, '#475569'
    UNION ALL SELECT 'exception', 'X', 'X', '08:00:00', 480, '#475569'
) AS defaults;

INSERT IGNORE INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('schedule', 'Schedule', 'Monthly employee schedules, shifts and exceptions.', 'active', 1);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.36', 'Added monthly employee schedules with configurable shifts, exceptions and manager-scoped floor rosters.', NOW());