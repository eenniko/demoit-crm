SET @property_sort_order_column_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE property_nodes ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER name',
        'SELECT 1')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_nodes' AND COLUMN_NAME = 'sort_order'
);
PREPARE property_sort_order_column_stmt FROM @property_sort_order_column_sql;
EXECUTE property_sort_order_column_stmt;
DEALLOCATE PREPARE property_sort_order_column_stmt;

UPDATE property_nodes SET sort_order = id WHERE sort_order = 0;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.34', 'Added persistent sibling ordering to the property structure hierarchy.', NOW());