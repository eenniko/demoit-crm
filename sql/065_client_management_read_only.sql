INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.78', 'Display client details as plain text without form controls for non-administrators, retaining the administrator form and server-side POST restriction.', 'client management', 'No business schema changes - version registration only.', NOW());
