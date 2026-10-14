<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';
require_once __DIR__ . '/RoleService.php';

/** Temporary substitutes (asendajad), doc 02 §9, doc 03 §5-6. */
class SubstituteService
{
    public static function activeScheduleManagers(int $clientId, int $userId): array
    {
        $stmt = db()->prepare(
            "SELECT DISTINCT s.original_user_id
             FROM system_substitutes s
             INNER JOIN system_users original ON original.id = s.original_user_id AND original.client_id = s.client_id
             INNER JOIN system_users replacement ON replacement.id = s.substitute_user_id AND replacement.client_id = s.client_id
             WHERE s.client_id = :client_id AND s.substitute_user_id = :user_id
               AND s.substitute_type = 'schedule_manager' AND s.status = 'approved'
               AND s.starts_at <= :active_now_start AND s.ends_at > :active_now_end
               AND original.status = 'active' AND replacement.status = 'active'"
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute(['client_id' => $clientId, 'user_id' => $userId,
            'active_now_start' => $now, 'active_now_end' => $now]);

        return array_values(array_filter(
            array_map('intval', array_column($stmt->fetchAll(), 'original_user_id')),
            static fn(int $managerId): bool => !RoleService::isSelfServiceUser($managerId, $clientId)
        ));
    }

    public static function canAssignScheduleSubstitute(int $clientId, int $userId): bool
    {
        if (RoleService::hasAnyRole($userId, $clientId, ['client_admin'])) {
            return true;
        }
        if (RoleService::isSelfServiceUser($userId, $clientId)) {
            return false;
        }
        $stmt = db()->prepare(
            "SELECT 1 FROM employment_contracts c
             INNER JOIN system_users employee ON employee.id = c.employee_id AND employee.client_id = c.client_id
             WHERE c.client_id = :client_id AND c.manager_user_id = :manager_id
               AND c.employee_id <> :own_id AND c.archived_at IS NULL
               AND c.contract_type IN ('primary', 'temporary') AND employee.status = 'active'
               AND (c.end_date IS NULL OR c.end_date >= CURRENT_DATE) LIMIT 1"
        );
        $stmt->execute(['client_id' => $clientId, 'manager_id' => $userId, 'own_id' => $userId]);

        return $stmt->fetchColumn() !== false;
    }

    public static function scheduleAssignments(int $clientId, int $userId, bool $isAdmin): array
    {
        $sql = "SELECT s.*, original.full_name AS original_name, original.username AS original_username,
                    replacement.full_name AS substitute_name, replacement.username AS substitute_username
                FROM system_substitutes s
                INNER JOIN system_users original ON original.id = s.original_user_id AND original.client_id = s.client_id
                INNER JOIN system_users replacement ON replacement.id = s.substitute_user_id AND replacement.client_id = s.client_id
                WHERE s.client_id = :client_id AND s.substitute_type = 'schedule_manager'";
        $params = ['client_id' => $clientId];
        if (!$isAdmin) {
            $sql .= ' AND (s.original_user_id = :original_id OR s.substitute_user_id = :replacement_id)';
            $params['original_id'] = $userId;
            $params['replacement_id'] = $userId;
        }
        $stmt = db()->prepare($sql . ' ORDER BY s.starts_at DESC, s.id DESC');
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function assignScheduleSubstitute(int $clientId, int $actorId, array $data): array
    {
        $isAdmin = RoleService::hasAnyRole($actorId, $clientId, ['client_admin']);
        if (!self::canAssignScheduleSubstitute($clientId, $actorId)) {
            return [false, 'You are not allowed to assign a schedule substitute.'];
        }
        $managerId = $isAdmin ? filter_var($data['original_user_id'] ?? null, FILTER_VALIDATE_INT) : $actorId;
        $replacementId = filter_var($data['substitute_user_id'] ?? null, FILTER_VALIDATE_INT);
        $start = is_string($data['starts_at'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $data['starts_at']) === 1
            ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $data['starts_at']) : false;
        $end = is_string($data['ends_at'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $data['ends_at']) === 1
            ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $data['ends_at']) : false;
        if (!$managerId || !$replacementId || $managerId === $replacementId
            || $start === false || $end === false
            || $start->format('Y-m-d\TH:i') !== $data['starts_at']
            || $end->format('Y-m-d\TH:i') !== $data['ends_at']
            || $end <= $start || $end <= new DateTimeImmutable()) {
            return [false, 'Choose different users and a valid future-ending substitute period.'];
        }
        if (RoleService::isSelfServiceUser($managerId, $clientId)) {
            return [false, 'A print-only Level F user cannot delegate manager editing rights.'];
        }
        $users = db()->prepare(
            "SELECT id FROM system_users WHERE client_id = :client_id AND status = 'active'
             AND id IN (:manager_id, :replacement_id)"
        );
        $users->execute(['client_id' => $clientId, 'manager_id' => $managerId, 'replacement_id' => $replacementId]);
        if (count($users->fetchAll()) !== 2) {
            return [false, 'Both users must have active accounts in this client. Add the substitute under Employees & permissions first.'];
        }
        $managed = db()->prepare(
            "SELECT 1 FROM employment_contracts c
             INNER JOIN system_users employee ON employee.id = c.employee_id AND employee.client_id = c.client_id
             WHERE c.client_id = :client_id AND c.manager_user_id = :manager_id AND c.employee_id <> :own_id
               AND c.archived_at IS NULL AND employee.status = 'active'
               AND c.contract_type IN ('primary', 'temporary')
               AND c.start_date <= :end_date AND (c.end_date IS NULL OR c.end_date >= :start_date) LIMIT 1"
        );
        $managed->execute(['client_id' => $clientId, 'manager_id' => $managerId, 'own_id' => $managerId,
            'start_date' => $start->format('Y-m-d'), 'end_date' => $end->format('Y-m-d')]);
        if ($managed->fetchColumn() === false) {
            return [false, 'The original user must manage another active employee contract during the substitute period.'];
        }
        $overlap = db()->prepare(
            "SELECT 1 FROM system_substitutes WHERE client_id = :client_id
             AND original_user_id = :manager_id AND substitute_user_id = :replacement_id
             AND substitute_type = 'schedule_manager' AND status = 'approved'
             AND starts_at < :ends_at AND ends_at > :starts_at LIMIT 1"
        );
        $params = ['client_id' => $clientId, 'manager_id' => $managerId, 'replacement_id' => $replacementId,
            'starts_at' => $start->format('Y-m-d H:i:s'), 'ends_at' => $end->format('Y-m-d H:i:s')];
        $overlap->execute($params);
        if ($overlap->fetchColumn() !== false) {
            return [false, 'This substitute already has an overlapping assignment for this manager.'];
        }
        $insert = db()->prepare(
            "INSERT INTO system_substitutes
                (client_id, original_user_id, substitute_user_id, substitute_type, starts_at, ends_at,
                 delegated_permissions, reason, status, approved_by, approved_at)
             VALUES (:client_id, :manager_id, :replacement_id, 'schedule_manager', :starts_at, :ends_at,
                 :permissions, :reason, 'approved', :actor_id, NOW())"
        );
        $insert->execute($params + ['permissions' => json_encode(['schedule.shifts']),
            'reason' => is_string($data['reason'] ?? null) ? trim($data['reason']) : null, 'actor_id' => $actorId]);
        AuditLogService::log($actorId, $clientId, 'schedule.substitute_assigned', 'system_substitutes', (string) db()->lastInsertId());

        return [true, 'Schedule substitute assigned. Existing account roles are unchanged.'];
    }

    public static function endScheduleSubstitute(int $clientId, int $actorId, int $id): array
    {
        $isAdmin = RoleService::hasAnyRole($actorId, $clientId, ['client_admin']);
        $sql = "UPDATE system_substitutes SET status = 'ended'
                WHERE id = :id AND client_id = :client_id AND substitute_type = 'schedule_manager' AND status = 'approved'";
        $params = ['id' => $id, 'client_id' => $clientId];
        if (!$isAdmin) {
            $sql .= ' AND original_user_id = :actor_id';
            $params['actor_id'] = $actorId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() !== 1) {
            return [false, 'Schedule substitute assignment not found or not allowed.'];
        }
        AuditLogService::log($actorId, $clientId, 'schedule.substitute_ended', 'system_substitutes', (string) $id);

        return [true, 'Schedule substitute rights ended.'];
    }

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
        if (strtolower($substituteType) === 'schedule_manager') {
            return [false, 'Use the Schedule substitutes page to assign schedule editing rights.'];
        }
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
