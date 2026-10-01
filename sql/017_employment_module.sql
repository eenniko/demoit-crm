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