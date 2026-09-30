-- DemoIT CRM — Notifications & support tickets (v1.12)
-- Idempotent, phpMyAdmin-runnable.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS system_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL COMMENT 'NULL = broadcast to all users of the client',
    title VARCHAR(191) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_client (client_id),
    KEY idx_notifications_user (user_id),
    CONSTRAINT fk_notifications_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES system_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_created_by FOREIGN KEY (created_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS system_support_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    created_by_user_id INT UNSIGNED NOT NULL,
    assigned_to_user_id INT UNSIGNED NULL,
    subject VARCHAR(191) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open','in_progress','closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_tickets_client (client_id),
    KEY idx_tickets_status (status),
    CONSTRAINT fk_tickets_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_tickets_created_by FOREIGN KEY (created_by_user_id) REFERENCES system_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_tickets_assigned_to FOREIGN KEY (assigned_to_user_id) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.12', 'Added system_notifications and system_support_tickets tables.', NOW());
