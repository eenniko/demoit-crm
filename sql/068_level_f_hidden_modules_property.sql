INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.81', 'Hide Active modules and Property structure for Level F and deny direct page access while retaining schedule printing dependencies.', 'modules, property, roles', 'No business schema changes - version registration only.', NOW());
