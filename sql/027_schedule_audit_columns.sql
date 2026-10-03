INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.40', 'Repair missing schedule audit columns on existing installs.', NOW());
