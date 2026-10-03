INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.37', 'Avoid redundant deletes for empty schedule cells during monthly roster saves.', NOW());
