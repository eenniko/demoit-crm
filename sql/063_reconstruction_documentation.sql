INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.76', 'Document the full CRM reconstruction workflow, current behavior, route inventory, SQL recovery bundle and acceptance checks.', 'documentation, all modules', 'No business schema changes - version registration only.', NOW());
