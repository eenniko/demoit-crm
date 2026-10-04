<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Language catalogue management. English must always stay active and default (doc 02 §6). */
class LanguageService
{
    public static function listAll(): array
    {
        return db()->query('SELECT * FROM system_languages ORDER BY is_default DESC, name')->fetchAll();
    }

    public static function listActive(): array
    {
        return db()->query('SELECT id, language_code, name, is_default FROM system_languages WHERE is_active = 1 ORDER BY is_default DESC, name')->fetchAll();
    }

    public static function currentCode(): string
    {
        $code = is_string($_SESSION['language_code'] ?? null) ? $_SESSION['language_code'] : 'en';
        foreach (self::listActive() as $language) {
            if ($language['language_code'] === $code) {
                return $code;
            }
        }

        foreach (self::listActive() as $language) {
            if ((int) $language['is_default'] === 1) {
                return (string) $language['language_code'];
            }
        }

        return 'en';
    }

    public static function setCurrentCode(string $code): bool
    {
        $code = strtolower(trim($code));
        foreach (self::listActive() as $language) {
            if ($language['language_code'] === $code) {
                $_SESSION['language_code'] = $code;
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function create(array $data, int $actorUserId): array
    {
        $code = strtolower(trim((string) ($data['language_code'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));

        if (!preg_match('/^[a-z]{2,10}$/', $code)) {
            return [false, 'Language code must be 2-10 lowercase letters (e.g. "en", "et").'];
        }

        if ($name === '') {
            return [false, 'Language name is required.'];
        }

        $stmt = db()->prepare('SELECT id FROM system_languages WHERE language_code = :code');
        $stmt->execute(['code' => $code]);
        if ($stmt->fetch() !== false) {
            return [false, 'This language code already exists.'];
        }

        $insert = db()->prepare(
            'INSERT INTO system_languages (language_code, name, is_active, is_default) VALUES (:code, :name, 1, 0)'
        );
        $insert->execute(['code' => $code, 'name' => $name]);

        AuditLogService::log($actorUserId, null, 'language.created', 'system_languages', (string) db()->lastInsertId());

        return [true, 'Language created successfully.'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function toggleActive(int $languageId, int $actorUserId): array
    {
        $stmt = db()->prepare('SELECT language_code, is_active, is_default FROM system_languages WHERE id = :id');
        $stmt->execute(['id' => $languageId]);
        $language = $stmt->fetch();

        if ($language === false) {
            return [false, 'Language not found.'];
        }

        if ($language['language_code'] === 'en') {
            return [false, 'English is mandatory and cannot be deactivated.'];
        }

        $newActive = (int) $language['is_active'] === 1 ? 0 : 1;
        $update = db()->prepare('UPDATE system_languages SET is_active = :active WHERE id = :id');
        $update->execute(['active' => $newActive, 'id' => $languageId]);

        AuditLogService::log($actorUserId, null, 'language.status_changed', 'system_languages', (string) $languageId);

        return [true, 'Language updated.'];
    }
}
