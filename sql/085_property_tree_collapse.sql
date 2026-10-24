INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.98', 'Property hierarchy branches can be expanded and collapsed and start collapsed on each page load.', 'property', 'No schema changes.', NOW());
