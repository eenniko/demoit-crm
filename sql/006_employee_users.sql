ALTER TABLE system_users
    ADD COLUMN phone VARCHAR(50) NULL AFTER email;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.19', 'Employee user management: phone, editing, default Level F access and account e-mail delivery.', NOW());