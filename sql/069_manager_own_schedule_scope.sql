INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.82', 'Allow managers assigned to active employee contracts in the selected month to view and edit their own schedule alongside assigned employee contracts.', 'schedule', 'No business schema changes - version registration only.', NOW());
