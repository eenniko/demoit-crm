ALTER TABLE employee_schedule_templates
    MODIFY COLUMN template_type ENUM('shift', 'exception', 'block') NOT NULL;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.60', 'Add Block schedule templates that prevent overlapping entries without adding planned hours.', NOW());