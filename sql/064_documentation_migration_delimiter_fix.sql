INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.77', 'Fix the documentation migration string delimiter that prevented application startup.', 'schema installer, documentation', 'No business schema changes - correct migration 063 and register the fix.', NOW());
