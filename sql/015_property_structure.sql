CREATE TABLE IF NOT EXISTS property_nodes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL,
    node_type ENUM('building', 'wing', 'floor', 'room') NOT NULL,
    name VARCHAR(191) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_property_nodes_client_id (client_id, id),
    KEY idx_property_nodes_parent (client_id, parent_id),
    CONSTRAINT fk_property_nodes_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_property_nodes_parent FOREIGN KEY (client_id, parent_id) REFERENCES property_nodes (client_id, id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('property', 'Property structure', 'Client buildings, wings, floors and rooms.', 'active', 1);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.28', 'Added an independent property structure module for buildings, wings, floors and rooms.', NOW());