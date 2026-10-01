<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

class EmploymentCatalogService
{
    public static function listForClient(int $clientId, string $catalog): array
    {
        $table = self::tableName($catalog);
        $stmt = db()->prepare("SELECT id, name, status FROM {$table} WHERE client_id = :client_id ORDER BY status, name");
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    /** @return array{0: bool, 1: string} */
    public static function save(int $clientId, string $catalog, ?int $id, string $name, int $actorUserId): array
    {
        $table = self::tableName($catalog);
        $name = trim($name);
        if ($name === '' || preg_match('//u', $name) !== 1 || preg_match_all('/./us', $name) > 191) {
            return [false, 'Enter a valid name of at most 191 characters.'];
        }

        try {
            if ($id === null) {
                $stmt = db()->prepare("INSERT INTO {$table} (client_id, name) VALUES (:client_id, :name)");
                $stmt->execute(['client_id' => $clientId, 'name' => $name]);
                $id = (int) db()->lastInsertId();
                $action = 'created';
            } else {
                $stmt = db()->prepare("UPDATE {$table} SET name = :name WHERE id = :id AND client_id = :client_id");
                $stmt->execute(['name' => $name, 'id' => $id, 'client_id' => $clientId]);
                if ($stmt->rowCount() === 0 && !self::exists($clientId, $table, $id)) {
                    return [false, 'Entry not found for this client.'];
                }
                $action = 'renamed';
            }
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return [false, 'An entry with this name already exists.'];
            }
            return [false, 'Could not save this entry.'];
        }

        AuditLogService::log($actorUserId, $clientId, "employment.{$catalog}.{$action}", $table, (string) $id);

        return [true, $id !== null && $action === 'renamed' ? 'Name updated.' : 'Entry added.'];
    }

    /** @return array{0: bool, 1: string} */
    public static function toggleStatus(int $clientId, string $catalog, int $id, int $actorUserId): array
    {
        $table = self::tableName($catalog);
        $stmt = db()->prepare("SELECT status FROM {$table} WHERE id = :id AND client_id = :client_id");
        $stmt->execute(['id' => $id, 'client_id' => $clientId]);
        $status = $stmt->fetchColumn();
        if ($status === false) {
            return [false, 'Entry not found for this client.'];
        }

        $newStatus = $status === 'active' ? 'inactive' : 'active';
        $update = db()->prepare("UPDATE {$table} SET status = :status WHERE id = :id AND client_id = :client_id");
        $update->execute(['status' => $newStatus, 'id' => $id, 'client_id' => $clientId]);
        AuditLogService::log($actorUserId, $clientId, "employment.{$catalog}.status_changed", $table, (string) $id, (string) $status, $newStatus);

        return [true, $newStatus === 'active' ? 'Entry activated.' : 'Entry hidden. Existing contracts are unchanged.'];
    }

    private static function exists(int $clientId, string $table, int $id): bool
    {
        $stmt = db()->prepare("SELECT 1 FROM {$table} WHERE id = :id AND client_id = :client_id");
        $stmt->execute(['id' => $id, 'client_id' => $clientId]);

        return $stmt->fetchColumn() !== false;
    }

    private static function tableName(string $catalog): string
    {
        return match ($catalog) {
            'titles' => 'employment_job_titles',
            'departments' => 'employment_departments',
            default => throw new InvalidArgumentException('Unknown employment catalog.'),
        };
    }
}