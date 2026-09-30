<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';
require_once __DIR__ . '/ModuleService.php';

/** Patients module (doc 01 §10 item 8 — first business module built on the module activation framework). */
class PatientService
{
    public const MODULE_KEY = 'patients';

    public static function isActiveForClient(int $clientId): bool
    {
        return ModuleService::isActiveForClient($clientId, self::MODULE_KEY);
    }

    public static function listForClient(int $clientId): array
    {
        $stmt = db()->prepare('SELECT * FROM patients WHERE client_id = :client_id ORDER BY full_name');
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    public static function find(int $clientId, int $patientId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM patients WHERE id = :id AND client_id = :client_id');
        $stmt->execute(['id' => $patientId, 'client_id' => $clientId]);
        $patient = $stmt->fetch();

        return $patient === false ? null : $patient;
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function create(int $clientId, array $data, int $actorUserId): array
    {
        $fullName = trim((string) ($data['full_name'] ?? ''));

        if ($fullName === '') {
            return [false, 'Full name is required.'];
        }

        $insert = db()->prepare(
            'INSERT INTO patients (client_id, full_name, personal_code, birth_date, phone, email, notes, status, created_by)
             VALUES (:client_id, :full_name, :personal_code, :birth_date, :phone, :email, :notes, \'active\', :created_by)'
        );
        $insert->execute([
            'client_id' => $clientId,
            'full_name' => $fullName,
            'personal_code' => self::nullableTrim($data['personal_code'] ?? null),
            'birth_date' => self::nullableTrim($data['birth_date'] ?? null),
            'phone' => self::nullableTrim($data['phone'] ?? null),
            'email' => self::nullableTrim($data['email'] ?? null),
            'notes' => self::nullableTrim($data['notes'] ?? null),
            'created_by' => $actorUserId,
        ]);

        AuditLogService::log($actorUserId, $clientId, 'patient.created', 'patients', (string) db()->lastInsertId());

        return [true, 'Patient created.'];
    }

    public static function toggleStatus(int $clientId, int $patientId, int $actorUserId): void
    {
        $stmt = db()->prepare('SELECT status FROM patients WHERE id = :id AND client_id = :client_id');
        $stmt->execute(['id' => $patientId, 'client_id' => $clientId]);
        $patient = $stmt->fetch();

        if ($patient === false) {
            return;
        }

        $newStatus = $patient['status'] === 'active' ? 'inactive' : 'active';
        $update = db()->prepare('UPDATE patients SET status = :status WHERE id = :id AND client_id = :client_id');
        $update->execute(['status' => $newStatus, 'id' => $patientId, 'client_id' => $clientId]);

        AuditLogService::log($actorUserId, $clientId, 'patient.status_changed', 'patients', (string) $patientId, $patient['status'], $newStatus);
    }

    private static function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
