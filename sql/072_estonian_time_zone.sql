INSERT IGNORE INTO system_version_logs (version, description, affected_modules, database_changes, released_at)
VALUES ('1.85', 'Align PHP and every application MySQL session with Europe/Tallinn, including daylight saving transitions.', 'core, notifications, support, audit', 'Version registration only - historical timestamps are not shifted without provenance verification.', NOW());
