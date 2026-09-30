<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

/** Temporary substitutes (asendajad), doc 02 §9, doc 03 §5-6. */
class SubstituteService
{
    public static function listForClient(int $clientId): array
    {
        $stmt = db()->prepare(
            'SELECT s.*, orig.username AS original_username, sub.username AS substitute_username
             FROM system_substitutes s
             INNER JOIN system_users orig ON orig.id = s.original_user_id
             INNER JOIN system_users sub ON sub.id = s.substitute_user_id
             WHERE s.client_id = :client_id
             ORDER BY s.created_at DESC'
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    /**
     * Eligible substitutes: users in the same org unit, subordinates of that unit,
     * or the direct manager of the parent unit (doc 03 §5).
     */
    public static function eligibleSubstitutes(int $clientId, int $originalUserId): array
    {
        $unitStmt = db()->prepare(
            "SELECT org_unit_id FROM system_user_roles
             WHERE client_id = :client_id AND user_id = :user_id AND status = 'active' AND org_unit_id IS NOT NULL
             LIMIT 1"
        );
        $unitStmt->execute(['client_id' => $clientId, 'user_id' => $originalUserId]);
        $unitId = $unitStmt->fetchColumn();

        $candidateIds = [];

        if ($unitId !== false) {
            $unitId = (int) $unitId;

            // Same unit, same level.
            $sameUnitStmt = db()->prepare(
                "SELECT DISTINCT ur.user_id FROM system_user_roles ur
                 WHERE ur.client_id = :client_id AND ur.org_unit_id = :unit_id AND ur.status = 'active' AND ur.user_id != :user_id"
            );
            $sameUnitStmt->execute(['client_id' => $clientId, 'unit_id' => $unitId, 'user_id' => $originalUserId]);
            $candidateIds = array_merge($candidateIds, array_column($sameUnitStmt->fetchAll(), 'user_id'));

            // Subordinates: users assigned to units whose parent is this unit.
            $subStmt = db()->prepare(
                "SELECT DISTINCT ur.user_id FROM system_user_roles ur
                 INNER JOIN system_org_units ou ON ou.id = ur.org_unit_id
                 WHERE ou.client_id = :client_id AND ou.parent_org_unit_id = :unit_id AND ur.status = 'active'"
            );
            $subStmt->execute(['client_id' => $clientId, 'unit_id' => $unitId]);
            $candidateIds = array_merge($candidateIds, array_column($subStmt->fetchAll(), 'user_id'));

            // Direct manager (the parent unit's manager).
            $managerStmt = db()->prepare(
                'SELECT parent.manager_user_id FROM system_org_units child
                 INNER JOIN system_org_units parent ON parent.id = child.parent_org_unit_id
                 WHERE child.id = :unit_id AND parent.manager_user_id IS NOT NULL'
            );
            $managerStmt->execute(['unit_id' => $unitId]);
            $manager = $managerStmt->fetchColumn();
            if ($manager !== false) {
                $candidateIds[] = (int) $manager;
            }
        }

        $candidateIds = array_values(array_unique(array_filter($candidateIds, fn ($id) => (int) $id !== $originalUserId)));

        if (empty($candidateIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($candidateIds), '?'));
        $stmt = db()->prepare(
            "SELECT id, username, full_name FROM system_users WHERE client_id = ? AND status = 'active' AND id IN ($placeholders)"
        );
        $stmt->execute(array_merge([$clientId], $candidateIds));

        return $stmt->fetchAll();
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function create(int $clientId, array $data, int $requestedByUserId): array
    {
        $originalUserId = (int) ($data['original_user_id'] ?? 0);
        $substituteUserId = (int) ($data['substitute_user_id'] ?? 0);
        $startsAt = trim((string) ($data['starts_at'] ?? ''));
        $endsAt = trim((string) ($data['ends_at'] ?? ''));
        $substituteType = trim((string) ($data['substitute_type'] ?? 'temporary'));
        $reason = trim((string) ($data['reason'] ?? ''));
        $delegatedPermissions = isset($data['delegated_permissions']) && is_array($data['delegated_permissions'])
            ? array_values($data['delegated_permissions'])
            : [];

        if ($originalUserId === 0 || $substituteUserId === 0 || $startsAt === '' || $endsAt === '') {
            return [false, 'Original user, substitute, start and end dates are required.'];
        }

        if ($startsAt >= $endsAt) {
            return [false, 'End date must be after the start date.'];
        }

        $eligible = array_column(self::eligibleSubstitutes($clientId, $originalUserId), 'id');
        if (!in_array($substituteUserId, $eligible, true)) {
            return [false, 'Selected substitute is not eligible for this user (must be same unit, subordinate, or direct manager).'];
        }

        $insert = db()->prepare(
            'INSERT INTO system_substitutes
                (client_id, original_user_id, substitute_user_id, substitute_type, starts_at, ends_at, delegated_permissions, reason, status)
             VALUES
                (:client_id, :original_user_id, :substitute_user_id, :substitute_type, :starts_at, :ends_at, :delegated_permissions, :reason, \'pending\')'
        );
        $insert->execute([
            'client_id' => $clientId,
            'original_user_id' => $originalUserId,
            'substitute_user_id' => $substituteUserId,
            'substitute_type' => $substituteType,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'delegated_permissions' => json_encode($delegatedPermissions),
            'reason' => $reason !== '' ? $reason : null,
        ]);

        AuditLogService::log($requestedByUserId, $clientId, 'substitute.requested', 'system_substitutes', (string) db()->lastInsertId());

        return [true, 'Substitute request submitted for approval.'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function approve(int $clientId, int $substituteId, int $approverUserId): array
    {
        $stmt = db()->prepare('SELECT status FROM system_substitutes WHERE id = :id AND client_id = :client_id');
        $stmt->execute(['id' => $substituteId, 'client_id' => $clientId]);
        $row = $stmt->fetch();

        if ($row === false || $row['status'] !== 'pending') {
            return [false, 'Request not found or already processed.'];
        }

        $update = db()->prepare(
            'UPDATE system_substitutes SET status = \'approved\', approved_by = :approved_by, approved_at = NOW() WHERE id = :id'
        );
        $update->execute(['approved_by' => $approverUserId, 'id' => $substituteId]);

        AuditLogService::log($approverUserId, $clientId, 'substitute.approved', 'system_substitutes', (string) $substituteId);

        return [true, 'Substitute request approved.'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    public static function reject(int $clientId, int $substituteId, int $approverUserId): array
    {
        $stmt = db()->prepare('SELECT status FROM system_substitutes WHERE id = :id AND client_id = :client_id');
        $stmt->execute(['id' => $substituteId, 'client_id' => $clientId]);
        $row = $stmt->fetch();

        if ($row === false || $row['status'] !== 'pending') {
            return [false, 'Request not found or already processed.'];
        }

        $update = db()->prepare(
            'UPDATE system_substitutes SET status = \'rejected\', approved_by = :approved_by, approved_at = NOW() WHERE id = :id'
        );
        $update->execute(['approved_by' => $approverUserId, 'id' => $substituteId]);

        AuditLogService::log($approverUserId, $clientId, 'substitute.rejected', 'system_substitutes', (string) $substituteId);

        return [true, 'Substitute request rejected.'];
    }
}
