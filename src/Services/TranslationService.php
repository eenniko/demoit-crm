<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Translation keys/values with English fallback (doc 02 §6, doc 04 §7). */
class TranslationService
{
    /** All keys with their value for a given language (NULL if missing -> falls back to English at read time). */
    public static function listForLanguage(int $languageId): array
    {
        $stmt = db()->prepare(
            'SELECT k.id AS key_id, k.translation_key, k.module_context,
                    en.value AS en_value,
                    tv.value AS value, tv.status AS value_status
             FROM system_translation_keys k
             LEFT JOIN system_translation_values en
                    ON en.translation_key_id = k.id AND en.language_id = (SELECT id FROM system_languages WHERE language_code = \'en\')
             LEFT JOIN system_translation_values tv
                    ON tv.translation_key_id = k.id AND tv.language_id = :language_id
             ORDER BY k.translation_key'
        );
        $stmt->execute(['language_id' => $languageId]);

        return $stmt->fetchAll();
    }

    /** Keys that have no active value for the given (non-English) language. */
    public static function missingForLanguage(int $languageId): array
    {
        $stmt = db()->prepare(
            'SELECT k.id AS key_id, k.translation_key, k.module_context, en.value AS en_value
             FROM system_translation_keys k
             LEFT JOIN system_translation_values en
                    ON en.translation_key_id = k.id AND en.language_id = (SELECT id FROM system_languages WHERE language_code = \'en\')
             LEFT JOIN system_translation_values tv
                    ON tv.translation_key_id = k.id AND tv.language_id = :language_id AND tv.status = \'active\'
             WHERE tv.id IS NULL
             ORDER BY k.translation_key'
        );
        $stmt->execute(['language_id' => $languageId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function createKey(string $translationKey, ?string $moduleContext, string $englishValue, int $actorUserId): array
    {
        $translationKey = trim($translationKey);
        $englishValue = trim($englishValue);

        if ($translationKey === '' || $englishValue === '') {
            return [false, 'Translation key and English value are required.'];
        }

        $pdo = db();
        $existsStmt = $pdo->prepare('SELECT id FROM system_translation_keys WHERE translation_key = :key');
        $existsStmt->execute(['key' => $translationKey]);
        if ($existsStmt->fetch() !== false) {
            return [false, 'This translation key already exists.'];
        }

        $pdo->beginTransaction();

        try {
            $insertKey = $pdo->prepare(
                'INSERT INTO system_translation_keys (translation_key, module_context) VALUES (:key, :context)'
            );
            $insertKey->execute(['key' => $translationKey, 'context' => $moduleContext !== '' ? $moduleContext : null]);
            $keyId = (int) $pdo->lastInsertId();

            $englishLanguageId = (int) $pdo->query("SELECT id FROM system_languages WHERE language_code = 'en'")->fetchColumn();

            $insertValue = $pdo->prepare(
                'INSERT INTO system_translation_values (translation_key_id, language_id, value, status, updated_by)
                 VALUES (:key_id, :language_id, :value, \'active\', :updated_by)'
            );
            $insertValue->execute([
                'key_id' => $keyId,
                'language_id' => $englishLanguageId,
                'value' => $englishValue,
                'updated_by' => $actorUserId,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();

            return [false, 'Failed to create translation key: ' . $e->getMessage()];
        }

        AuditLogService::log($actorUserId, null, 'translation.key_created', 'system_translation_keys', (string) $keyId);

        return [true, 'Translation key created.'];
    }

    public static function upsertValue(int $keyId, int $languageId, string $value, int $actorUserId): void
    {
        $stmt = db()->prepare(
            'INSERT INTO system_translation_values (translation_key_id, language_id, value, status, updated_by)
             VALUES (:key_id, :language_id, :value, \'active\', :updated_by)
             ON DUPLICATE KEY UPDATE value = VALUES(value), status = \'active\', updated_by = VALUES(updated_by)'
        );
        $stmt->execute([
            'key_id' => $keyId,
            'language_id' => $languageId,
            'value' => $value,
            'updated_by' => $actorUserId,
        ]);

        AuditLogService::log($actorUserId, null, 'translation.value_updated', 'system_translation_values', $keyId . ':' . $languageId, null, $value);
    }
}
