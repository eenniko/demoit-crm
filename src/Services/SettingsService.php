<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Global key/value system settings (doc 01 §5 "Süsteemi seaded"). */
class SettingsService
{
    public static function all(): array
    {
        return db()->query('SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key')->fetchAll();
    }

    public static function set(string $key, string $value, int $actorUserId): void
    {
        $key = trim($key);

        if ($key === '') {
            return;
        }

        $stmt = db()->prepare(
            'INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute(['key' => $key, 'value' => $value]);

        AuditLogService::log($actorUserId, null, 'setting.updated', 'system_settings', $key, null, $value);
    }
}
