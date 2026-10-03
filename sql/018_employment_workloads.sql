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