INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.80', 'Restrict Level F schedule access to printing location and department groups containing their own valid primary or temporary contract for the selected month.', 'schedule, roles', 'No business schema changes - version registration only.', NOW());
