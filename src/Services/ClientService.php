<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';
require_once __DIR__ . '/InitialSetupService.php';

/** Client (tenant) creation and listing (doc 01 §6, doc 02 §4). */
class ClientService
{
    public static function listActive(): array
    {
        $stmt = db()->query(
            "SELECT id, client_code, company_name, email, status, created_at
             FROM system_clients
             WHERE client_code != '" . InitialSetupService::RESERVED_ADMIN_CLIENT_CODE . "'
             ORDER BY created_at DESC"
        );

        return $stmt->fetchAll();
    }

    public static function find(int $clientId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM system_clients WHERE id = :id');
        $stmt->execute(['id' => $clientId]);
        $client = $stmt->fetch();

        return $client === false ? null : $client;
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function updateOwnDetails(int $clientId, array $data, int $actorUserId): array
    {
        $companyName = trim((string) ($data['company_name'] ?? ''));

        if ($companyName === '') {
            return [false, 'Company name is required.'];
        }

        $update = db()->prepare(
            'UPDATE system_clients SET
                company_name = :company_name, address = :address, phone = :phone, email = :email,
                contact_person_name = :contact_person_name, contact_person_phone = :contact_person_phone,
                contact_person_email = :contact_person_email
             WHERE id = :id'
        );
        $update->execute([
            'company_name' => $companyName,
            'address' => self::nullableTrim($data['address'] ?? null),
            'phone' => self::nullableTrim($data['phone'] ?? null),
            'email' => self::nullableTrim($data['email'] ?? null),
            'contact_person_name' => self::nullableTrim($data['contact_person_name'] ?? null),
            'contact_person_phone' => self::nullableTrim($data['contact_person_phone'] ?? null),
            'contact_person_email' => self::nullableTrim($data['contact_person_email'] ?? null),
            'id' => $clientId,
        ]);

        AuditLogService::log($actorUserId, $clientId, 'client.details_updated', 'system_clients', (string) $clientId);

        return [true, 'Client details updated.'];
    }

    /**
     * @return array{0: bool, 1: string, 2: ?string} success, message, temporary password (if created)
     */
    public static function createClient(array $data, int $createdByUserId): array
    {
        $clientCode = trim((string) ($data['client_code'] ?? ''));
        $companyName = trim((string) ($data['company_name'] ?? ''));
        $username = trim((string) ($data['username'] ?? ''));

        if ($clientCode === '' || $companyName === '' || $username === '') {
            return [false, 'Client code, company name and username are required.', null];
        }

        if ($clientCode === InitialSetupService::RESERVED_ADMIN_CLIENT_CODE) {
            return [false, 'Client code 13666 is reserved and cannot be assigned to a regular client.', null];
        }

        if (!preg_match('/^[A-Za-z0-9_-]{2,20}$/', $clientCode)) {
            return [false, 'Client code may only contain letters, digits, "-" and "_" (2-20 characters).', null];
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $existsStmt = $pdo->prepare('SELECT id FROM system_clients WHERE client_code = :code FOR UPDATE');
            $existsStmt->execute(['code' => $clientCode]);
            if ($existsStmt->fetch() !== false) {
                $pdo->rollBack();

                return [false, 'This client code is already in use.', null];
            }

            $clientStmt = $pdo->prepare(
                'INSERT INTO system_clients
                    (client_code, company_name, registry_code, address, phone, email,
                     contact_person_name, contact_person_id_code, contact_person_phone, contact_person_email, status)
                 VALUES
                    (:client_code, :company_name, :registry_code, :address, :phone, :email,
                     :contact_person_name, :contact_person_id_code, :contact_person_phone, :contact_person_email, \'active\')'
            );
            $clientStmt->execute([
                'client_code' => $clientCode,
                'company_name' => $companyName,
                'registry_code' => self::nullableTrim($data['registry_code'] ?? null),
                'address' => self::nullableTrim($data['address'] ?? null),
                'phone' => self::nullableTrim($data['phone'] ?? null),
                'email' => self::nullableTrim($data['email'] ?? null),
                'contact_person_name' => self::nullableTrim($data['contact_person_name'] ?? null),
                'contact_person_id_code' => self::nullableTrim($data['contact_person_id_code'] ?? null),
                'contact_person_phone' => self::nullableTrim($data['contact_person_phone'] ?? null),
                'contact_person_email' => self::nullableTrim($data['contact_person_email'] ?? null),
            ]);
            $clientId = (int) $pdo->lastInsertId();

            $userExistsStmt = $pdo->prepare('SELECT id FROM system_users WHERE client_id = :client_id AND username = :username');
            $userExistsStmt->execute(['client_id' => $clientId, 'username' => $username]);
            if ($userExistsStmt->fetch() !== false) {
                $pdo->rollBack();

                return [false, 'Username already exists for this client.', null];
            }

            $tempPassword = self::generateTempPassword();

            $userStmt = $pdo->prepare(
                'INSERT INTO system_users (client_id, username, email, password_hash, full_name, status, must_change_password)
                 VALUES (:client_id, :username, :email, :password_hash, :full_name, \'active\', 1)'
            );
            $userStmt->execute([
                'client_id' => $clientId,
                'username' => $username,
                'email' => self::nullableTrim($data['contact_person_email'] ?? null),
                'password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT),
                'full_name' => self::nullableTrim($data['contact_person_name'] ?? null),
            ]);
            $userId = (int) $pdo->lastInsertId();

            $roleStmt = $pdo->prepare("SELECT id FROM system_roles WHERE role_key = 'client_admin'");
            $roleStmt->execute();
            $role = $roleStmt->fetch();

            if ($role === false) {
                throw new RuntimeException('Role client_admin is missing. Run sql/001_core_schema.sql first.');
            }

            $assignStmt = $pdo->prepare(
                'INSERT INTO system_user_roles (user_id, role_id, client_id, status) VALUES (:user_id, :role_id, :client_id, \'active\')'
            );
            $assignStmt->execute([
                'user_id' => $userId,
                'role_id' => (int) $role['id'],
                'client_id' => $clientId,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();

            return [false, 'Client creation failed: ' . $e->getMessage(), null];
        }

        AuditLogService::log($createdByUserId, $clientId, 'client.created', 'system_clients', (string) $clientId);
        AuditLogService::log($createdByUserId, $clientId, 'client.first_user_created', 'system_users', (string) $userId);

        return [true, 'Client created successfully.', $tempPassword];
    }

    private static function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function generateTempPassword(): string
    {
        return substr(bin2hex(random_bytes(8)), 0, 12) . 'Aa1!';
    }
}
