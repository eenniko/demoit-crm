INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.39', 'Expose safe SQLSTATE diagnostics to client administrators for schedule save failures.', NOW());
