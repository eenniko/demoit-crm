INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.24', 'Show the signed-in person name in greetings, falling back to username.', NOW());