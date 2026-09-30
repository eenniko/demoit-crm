<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Imports employee contact rows from the legacy db_user_data SQL dump without executing uploaded SQL. */
class LegacyEmployeeImportService
{
    private const SOURCE_TABLE = 'db_user_data';

    /**
     * @return array{0: bool, 1: string, 2: array{found: int, imported: int, skipped: int}}
     */
    public static function importForClient(int $clientId, string $sql, int $actorUserId): array
    {
        try {
            $rows = self::parse($sql);
        } catch (InvalidArgumentException $e) {
            return [false, $e->getMessage(), ['found' => 0, 'imported' => 0, 'skipped' => 0]];
        }

        if ($rows === []) {
            return [false, 'The upload does not contain any db_user_data rows.', ['found' => 0, 'imported' => 0, 'skipped' => 0]];
        }

        $pdo = db();
        $roleStmt = $pdo->prepare(
            'SELECT settings.default_role_id
             FROM employee_module_settings settings
             WHERE settings.client_id = :client_id'
        );
        $roleStmt->execute(['client_id' => $clientId]);
        $roleId = $roleStmt->fetchColumn();
        if ($roleId === false) {
            return [false, 'Employees module is not provisioned for this client.', ['found' => count($rows), 'imported' => 0, 'skipped' => 0]];
        }

        $existsStmt = $pdo->prepare('SELECT 1 FROM system_users WHERE client_id = :client_id AND username = :username LIMIT 1');
        $insertUser = $pdo->prepare(
            "INSERT INTO system_users (client_id, username, email, phone, password_hash, full_name, status, must_change_password, created_at, updated_at)
             VALUES (:client_id, :username, :email, :phone, :password_hash, :full_name, 'inactive', 1, :created_at, :updated_at)"
        );
        $assignRole = $pdo->prepare(
            "INSERT INTO system_user_roles (user_id, role_id, client_id, status)
             VALUES (:user_id, :role_id, :client_id, 'active')"
        );
        $imported = 0;
        $skipped = 0;
        $seenUsernames = [];

        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $username = trim((string) ($row['db_users_id'] ?? ''));
                $fullName = trim((string) ($row['cn'] ?? ''));
                if ($username === '' || $fullName === '' || isset($seenUsernames[$username])) {
                    $skipped++;
                    continue;
                }
                $seenUsernames[$username] = true;

                $existsStmt->execute(['client_id' => $clientId, 'username' => $username]);
                if ($existsStmt->fetchColumn() !== false) {
                    $skipped++;
                    continue;
                }

                $email = trim((string) ($row['email'] ?? ''));
                $phone = trim((string) ($row['phone'] ?? ''));
                $createdAt = self::validDate((string) ($row['created_at'] ?? '')) ?? date('Y-m-d H:i:s');
                $updatedAt = self::validDate((string) ($row['updated_at'] ?? '')) ?? $createdAt;
                $temporaryPassword = bin2hex(random_bytes(24));

                $insertUser->execute([
                    'client_id' => $clientId,
                    'username' => $username,
                    'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
                    'full_name' => $fullName,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ]);
                $assignRole->execute([
                    'user_id' => (int) $pdo->lastInsertId(),
                    'role_id' => (int) $roleId,
                    'client_id' => $clientId,
                ]);
                $imported++;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            return [false, 'Legacy employee import failed: ' . $e->getMessage(), ['found' => count($rows), 'imported' => 0, 'skipped' => 0]];
        }

        AuditLogService::log(
            $actorUserId,
            $clientId,
            'user.legacy_imported',
            'system_users',
            null,
            null,
            "Imported: {$imported}; skipped: {$skipped}"
        );

        return [true, "Legacy import completed. Imported: {$imported}; skipped: {$skipped}.", [
            'found' => count($rows),
            'imported' => $imported,
            'skipped' => $skipped,
        ]];
    }

    /** @return array<int, array<string, ?string>> */
    public static function parse(string $sql): array
    {
        if (trim($sql) === '') {
            throw new InvalidArgumentException('The uploaded SQL file is empty.');
        }

        $rows = [];
        foreach (self::splitStatements($sql) as $statement) {
            if (!preg_match(
                '/\bINSERT\s+INTO\s+`?' . self::SOURCE_TABLE . '`?\s*\(([^)]+)\)\s*VALUES\s*(.+)$/is',
                $statement,
                $matches
            )) {
                continue;
            }

            $columns = array_map(
                static fn (string $column): string => trim($column, " \t\n\r\0\x0B`"),
                explode(',', $matches[1])
            );
            foreach (self::parseValueTuples($matches[2]) as $values) {
                if (count($columns) !== count($values)) {
                    throw new InvalidArgumentException('The db_user_data column and value counts do not match.');
                }
                $rows[] = array_combine($columns, $values);
            }
        }

        return $rows;
    }

    /** @return string[] */
    private static function splitStatements(string $sql): array
    {
        $statements = [];
        $statement = '';
        $quoted = false;
        $escaped = false;

        for ($index = 0, $length = strlen($sql); $index < $length; $index++) {
            $character = $sql[$index];
            $statement .= $character;
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($quoted && $character === '\\') {
                $escaped = true;
                continue;
            }
            if ($character === "'") {
                if ($quoted && ($sql[$index + 1] ?? '') === "'") {
                    $statement .= "'";
                    $index++;
                    continue;
                }
                $quoted = !$quoted;
                continue;
            }
            if (!$quoted && $character === ';') {
                $statements[] = substr($statement, 0, -1);
                $statement = '';
            }
        }

        if (trim($statement) !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }

    /** @return array<int, array<int, ?string>> */
    private static function parseValueTuples(string $valuesSql): array
    {
        $rows = [];
        $row = [];
        $token = '';
        $inTuple = false;
        $quoted = false;
        $tokenWasQuoted = false;

        for ($index = 0, $length = strlen($valuesSql); $index < $length; $index++) {
            $character = $valuesSql[$index];
            if (!$inTuple) {
                if ($character === '(') {
                    $inTuple = true;
                    $row = [];
                    $token = '';
                    $tokenWasQuoted = false;
                }
                continue;
            }

            if ($quoted) {
                if ($character === '\\' && $index + 1 < $length) {
                    $index++;
                    $token .= self::decodeEscape($valuesSql[$index]);
                } elseif ($character === "'" && ($valuesSql[$index + 1] ?? '') === "'") {
                    $token .= "'";
                    $index++;
                } elseif ($character === "'") {
                    $quoted = false;
                } else {
                    $token .= $character;
                }
                continue;
            }

            if ($character === "'") {
                $quoted = true;
                $tokenWasQuoted = true;
            } elseif ($character === ',') {
                $row[] = self::decodeToken($token, $tokenWasQuoted);
                $token = '';
                $tokenWasQuoted = false;
            } elseif ($character === ')') {
                $row[] = self::decodeToken($token, $tokenWasQuoted);
                $rows[] = $row;
                $inTuple = false;
            } else {
                $token .= $character;
            }
        }

        if ($quoted || $inTuple) {
            throw new InvalidArgumentException('The db_user_data VALUES list is malformed.');
        }

        return $rows;
    }

    private static function decodeToken(string $token, bool $wasQuoted): ?string
    {
        $token = trim($token);
        if (!$wasQuoted && strtoupper($token) === 'NULL') {
            return null;
        }

        return $token;
    }

    private static function decodeEscape(string $character): string
    {
        return match ($character) {
            '0' => "\0",
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'Z' => chr(26),
            default => $character,
        };
    }

    private static function validDate(string $value): ?string
    {
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);

        return $date !== false && $date->format('Y-m-d H:i:s') === $value ? $value : null;
    }
}