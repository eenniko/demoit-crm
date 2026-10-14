INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.79', 'Restrict Level F employee and employment lists to the signed-in account and deny employment catalog access.', 'employees, employment, roles', 'No business schema changes - version registration only.', NOW());
