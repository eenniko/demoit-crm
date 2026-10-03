SET @has_schedule_created_by = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'created_by'
);
SET @schedule_ddl = IF(@has_schedule_created_by = 0,
    'ALTER TABLE employee_schedule_entries ADD COLUMN created_by INT UNSIGNED NULL',
    'SELECT 1'
);
PREPARE schedule_stmt FROM @schedule_ddl;
EXECUTE schedule_stmt;
DEALLOCATE PREPARE schedule_stmt;

SET @has_schedule_updated_by = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'updated_by'
);
SET @schedule_ddl = IF(@has_schedule_updated_by = 0,
    'ALTER TABLE employee_schedule_entries ADD COLUMN updated_by INT UNSIGNED NULL',
    'SELECT 1'
);
PREPARE schedule_stmt FROM @schedule_ddl;
EXECUTE schedule_stmt;
DEALLOCATE PREPARE schedule_stmt;

SET @has_schedule_created_by_fk = (
    SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_employee_schedule_entries_created_by'
);
SET @schedule_ddl = IF(@has_schedule_created_by_fk = 0,
    'ALTER TABLE employee_schedule_entries ADD CONSTRAINT fk_employee_schedule_entries_created_by FOREIGN KEY (created_by) REFERENCES system_users (id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE schedule_stmt FROM @schedule_ddl;
EXECUTE schedule_stmt;
DEALLOCATE PREPARE schedule_stmt;

SET @has_schedule_updated_by_fk = (
    SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_employee_schedule_entries_updated_by'
);
SET @schedule_ddl = IF(@has_schedule_updated_by_fk = 0,
    'ALTER TABLE employee_schedule_entries ADD CONSTRAINT fk_employee_schedule_entries_updated_by FOREIGN KEY (updated_by) REFERENCES system_users (id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE schedule_stmt FROM @schedule_ddl;
EXECUTE schedule_stmt;
DEALLOCATE PREPARE schedule_stmt;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.40', 'Repair missing schedule audit columns and foreign keys on existing installs.', NOW());
