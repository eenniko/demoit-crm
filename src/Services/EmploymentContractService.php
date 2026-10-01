<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';
require_once __DIR__ . '/PropertyService.php';

class EmploymentContractService
{
    public static function listForClient(int $clientId): array
    {
        $stmt = db()->prepare(
            "SELECT c.id, c.employee_id, c.contract_type, c.start_date, c.end_date,
                    employee.username, employee.full_name,
                    property.name AS property_name, property.node_type AS property_type,
                    title.name AS job_title, department.name AS department_name,
                    manager.full_name AS manager_name, manager.username AS manager_username,
                    CASE
                        WHEN c.start_date > CURRENT_DATE THEN 'scheduled'
                        WHEN c.end_date IS NOT NULL AND c.end_date < CURRENT_DATE THEN 'ended'
                        ELSE 'current'
                    END AS period_status
             FROM employment_contracts c
             INNER JOIN system_users employee ON employee.id = c.employee_id AND employee.client_id = c.client_id
             INNER JOIN property_nodes property ON property.id = c.property_node_id AND property.client_id = c.client_id
             INNER JOIN employment_job_titles title ON title.id = c.job_title_id AND title.client_id = c.client_id
             LEFT JOIN employment_departments department ON department.id = c.department_id AND department.client_id = c.client_id
             LEFT JOIN system_users manager ON manager.id = c.manager_user_id AND manager.client_id = c.client_id
             WHERE c.client_id = :client_id
             ORDER BY c.start_date DESC, employee.full_name, c.id DESC"
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    public static function findForClient(int $clientId, int $contractId): ?array
    {
        $stmt = db()->prepare(
            'SELECT c.id, c.employee_id, c.property_node_id, c.job_title_id, c.department_id, c.manager_user_id,
                c.contract_type, c.start_date, c.end_date, employee.username, employee.full_name
             FROM employment_contracts c
             INNER JOIN system_users employee ON employee.id = c.employee_id AND employee.client_id = c.client_id
             WHERE c.client_id = :client_id AND c.id = :id'
        );
        $stmt->execute(['client_id' => $clientId, 'id' => $contractId]);
        $contract = $stmt->fetch();

        return $contract !== false ? $contract : null;
    }

    public static function formOptions(int $clientId, ?array $contract = null): array
    {
        $users = db()->prepare(
            "SELECT id, username, full_name FROM system_users
             WHERE client_id = :client_id AND status = 'active' ORDER BY full_name, username"
        );
        $users->execute(['client_id' => $clientId]);
        $userRows = $users->fetchAll();
        $managerId = (int) ($contract['manager_user_id'] ?? 0);
        if ($managerId > 0 && !in_array($managerId, array_column($userRows, 'id'))) {
            $manager = db()->prepare('SELECT id, username, full_name FROM system_users WHERE client_id = :client_id AND id = :id');
            $manager->execute(['client_id' => $clientId, 'id' => $managerId]);
            $existingManager = $manager->fetch();
            if ($existingManager !== false) {
                $userRows[] = $existingManager;
            }
        }
        $titles = db()->prepare("SELECT id, name FROM employment_job_titles WHERE client_id = :client_id AND status = 'active' ORDER BY name");
        $titles->execute(['client_id' => $clientId]);
        $jobTitles = $titles->fetchAll();
        $currentTitleId = (int) ($contract['job_title_id'] ?? 0);
        if ($currentTitleId > 0 && !in_array($currentTitleId, array_column($jobTitles, 'id'))) {
            $currentTitle = db()->prepare('SELECT id, name FROM employment_job_titles WHERE client_id = :client_id AND id = :id');
            $currentTitle->execute(['client_id' => $clientId, 'id' => $currentTitleId]);
            $existingTitle = $currentTitle->fetch();
            if ($existingTitle !== false) {
                $jobTitles[] = $existingTitle;
            }
        }
        $departments = db()->prepare("SELECT id, name FROM employment_departments WHERE client_id = :client_id AND status = 'active' ORDER BY name");
        $departments->execute(['client_id' => $clientId]);
        $departmentRows = $departments->fetchAll();
        $currentDepartmentId = (int) ($contract['department_id'] ?? 0);
        if ($currentDepartmentId > 0 && !in_array($currentDepartmentId, array_column($departmentRows, 'id'))) {
            $currentDepartment = db()->prepare('SELECT id, name FROM employment_departments WHERE client_id = :client_id AND id = :id');
            $currentDepartment->execute(['client_id' => $clientId, 'id' => $currentDepartmentId]);
            $existingDepartment = $currentDepartment->fetch();
            if ($existingDepartment !== false) {
                $departmentRows[] = $existingDepartment;
            }
        }

        return [
            'employees' => $userRows,
            'managers' => $userRows,
            'properties' => PropertyService::listTreeForClient($clientId),
            'jobTitles' => $jobTitles,
            'departments' => $departmentRows,
        ];
    }

    public static function dateRangesOverlap(string $firstStart, ?string $firstEnd, string $secondStart, ?string $secondEnd): bool
    {
        return ($firstEnd === null || $secondStart <= $firstEnd)
            && ($secondEnd === null || $firstStart <= $secondEnd);
    }

    public static function dateRangeContains(string $outerStart, ?string $outerEnd, string $innerStart, ?string $innerEnd): bool
    {
        return $innerStart >= $outerStart
            && ($outerEnd === null || ($innerEnd !== null && $innerEnd <= $outerEnd));
    }

    /** @return array{0: bool, 1: string} */
    public static function save(int $clientId, ?int $contractId, array $data, int $actorUserId): array
    {
        $existing = $contractId !== null ? self::findForClient($clientId, $contractId) : null;
        if ($contractId !== null && $existing === null) {
            return [false, 'Contract not found for this client.'];
        }

        $employeeId = $existing !== null
            ? (int) $existing['employee_id']
            : self::parseId($data['employee_id'] ?? null, true);
        $propertyId = self::parseId($data['property_node_id'] ?? null, true);
        $jobTitleId = self::parseId($data['job_title_id'] ?? null, true);
        $departmentId = self::parseId($data['department_id'] ?? null, false);
        $managerId = self::parseId($data['manager_user_id'] ?? null, false);
        $type = $existing !== null
            ? (string) $existing['contract_type']
            : (is_string($data['contract_type'] ?? null) ? $data['contract_type'] : '');
        $startDate = self::parseDate($data['start_date'] ?? null);
        $endDate = self::parseOptionalDate($data['end_date'] ?? null);

        if ($employeeId === 0 || $propertyId === 0 || $jobTitleId === 0 || $departmentId === 0 || $managerId === 0) {
            return [false, 'Choose valid employee, location, job title, department and manager values.'];
        }
        if (!in_array($type, ['primary', 'temporary'], true)) {
            return [false, 'Choose a valid contract type.'];
        }
        if ($startDate === null || (($data['end_date'] ?? '') !== '' && $endDate === null)) {
            return [false, 'Enter valid contract dates. The start date is required.'];
        }
        if ($endDate !== null && $endDate < $startDate) {
            return [false, 'The end date cannot be earlier than the start date.'];
        }
        if ($managerId !== null && $managerId === $employeeId) {
            return [false, 'An employee cannot be their own manager.'];
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();

            $employee = $pdo->prepare('SELECT status FROM system_users WHERE client_id = :client_id AND id = :id FOR UPDATE');
            $employee->execute(['client_id' => $clientId, 'id' => $employeeId]);
            $employeeStatus = $employee->fetchColumn();
            if ($employeeStatus === false || ($employeeStatus !== 'active' && $existing === null)) {
                $pdo->rollBack();
                return [false, 'Choose an active employee from this client.'];
            }

            if (!self::referenceExists($pdo, 'property_nodes', $clientId, $propertyId, null)) {
                $pdo->rollBack();
                return [false, 'Choose a property location from this client.'];
            }
            if (!self::referenceExists($pdo, 'employment_job_titles', $clientId, $jobTitleId, $existing['job_title_id'] ?? null)) {
                $pdo->rollBack();
                return [false, 'Choose an active job title.'];
            }
            if ($departmentId !== null && !self::referenceExists($pdo, 'employment_departments', $clientId, $departmentId, $existing['department_id'] ?? null)) {
                $pdo->rollBack();
                return [false, 'Choose an active department.'];
            }
            if ($managerId !== null && !self::referenceExists($pdo, 'system_users', $clientId, $managerId, $existing['manager_user_id'] ?? null)) {
                $pdo->rollBack();
                return [false, 'Choose an active manager from this client.'];
            }

            if ($type === 'primary' && self::hasOverlappingPrimary($pdo, $clientId, $employeeId, $startDate, $endDate, $contractId)) {
                $pdo->rollBack();
                return [false, 'This employee already has a primary contract covering part of this period. Temporary contracts may overlap.'];
            }
            if ($type === 'primary' && self::hasInvalidTemporaryAssignments($pdo, $clientId, $employeeId, $propertyId, $startDate, $endDate, $existing)) {
                $pdo->rollBack();
                $error = 'The primary contract dates and location must continue to cover each linked temporary workplace at a different property location.';
                return [false, $error];
            }
            if ($type === 'temporary' && !self::hasDifferentPrimaryLocation($pdo, $clientId, $employeeId, $propertyId, $startDate, $endDate)) {
                $pdo->rollBack();
                return [false, 'A temporary contract must fit within a primary contract period and use a different property location.'];
            }

            $values = [
                'client_id' => $clientId,
                'employee_id' => $employeeId,
                'property_node_id' => $propertyId,
                'job_title_id' => $jobTitleId,
                'department_id' => $departmentId,
                'manager_user_id' => $managerId,
                'contract_type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
            if ($existing === null) {
                $stmt = $pdo->prepare(
                    'INSERT INTO employment_contracts
                        (client_id, employee_id, property_node_id, job_title_id, department_id, manager_user_id,
                         contract_type, start_date, end_date, created_by)
                     VALUES
                        (:client_id, :employee_id, :property_node_id, :job_title_id, :department_id, :manager_user_id,
                         :contract_type, :start_date, :end_date, :created_by)'
                );
                $stmt->execute($values + ['created_by' => $actorUserId]);
                $contractId = (int) $pdo->lastInsertId();
                $action = 'created';
            } else {
                unset($values['employee_id']);
                $stmt = $pdo->prepare(
                    'UPDATE employment_contracts
                     SET property_node_id = :property_node_id, job_title_id = :job_title_id,
                         department_id = :department_id, manager_user_id = :manager_user_id,
                         contract_type = :contract_type, start_date = :start_date, end_date = :end_date
                     WHERE client_id = :client_id AND id = :id'
                );
                $stmt->execute($values + ['id' => $contractId]);
                $action = 'updated';
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [false, 'Could not save the contract. Check the selected values and try again.'];
        }

        AuditLogService::log($actorUserId, $clientId, "employment.contract.{$action}", 'employment_contracts', (string) $contractId);

        return [true, $action === 'created' ? 'Contract added.' : 'Contract updated.'];
    }

    private static function hasOverlappingPrimary(PDO $pdo, int $clientId, int $employeeId, string $startDate, ?string $endDate, ?int $excludeId): bool
    {
        $sql = "SELECT id, start_date, end_date FROM employment_contracts
                WHERE client_id = :client_id AND employee_id = :employee_id AND contract_type = 'primary'";
        $params = ['client_id' => $clientId, 'employee_id' => $employeeId];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        foreach ($stmt->fetchAll() as $contract) {
            if (self::dateRangesOverlap($startDate, $endDate, $contract['start_date'], $contract['end_date'])) {
                return true;
            }
        }

        return false;
    }

    private static function hasDifferentPrimaryLocation(PDO $pdo, int $clientId, int $employeeId, int $propertyId, string $startDate, ?string $endDate): bool
    {
        $stmt = $pdo->prepare(
            "SELECT property_node_id, start_date, end_date FROM employment_contracts
             WHERE client_id = :client_id AND employee_id = :employee_id AND contract_type = 'primary'"
        );
        $stmt->execute(['client_id' => $clientId, 'employee_id' => $employeeId]);
        $overlapping = [];
        foreach ($stmt->fetchAll() as $contract) {
            if (self::dateRangeContains($contract['start_date'], $contract['end_date'], $startDate, $endDate)) {
                $overlapping[] = (int) $contract['property_node_id'];
            }
        }

        return $overlapping !== [] && !in_array($propertyId, $overlapping, true);
    }

    private static function hasInvalidTemporaryAssignments(PDO $pdo, int $clientId, int $employeeId, int $propertyId, string $startDate, ?string $endDate, ?array $existingPrimary): bool
    {
        $stmt = $pdo->prepare(
            "SELECT property_node_id, start_date, end_date FROM employment_contracts
             WHERE client_id = :client_id AND employee_id = :employee_id AND contract_type = 'temporary'"
        );
        $stmt->execute(['client_id' => $clientId, 'employee_id' => $employeeId]);
        foreach ($stmt->fetchAll() as $contract) {
            $linkedToExisting = $existingPrimary === null
                ? self::dateRangesOverlap($startDate, $endDate, $contract['start_date'], $contract['end_date'])
                : self::dateRangesOverlap($existingPrimary['start_date'], $existingPrimary['end_date'], $contract['start_date'], $contract['end_date']);
            if ($linkedToExisting && (
                !self::dateRangeContains($startDate, $endDate, $contract['start_date'], $contract['end_date'])
                || $propertyId === (int) $contract['property_node_id']
            )) {
                return true;
            }
        }

        return false;
    }

    private static function referenceExists(PDO $pdo, string $table, int $clientId, int $id, mixed $existingId): bool
    {
        $allowedTables = ['property_nodes', 'employment_job_titles', 'employment_departments', 'system_users'];
        if (!in_array($table, $allowedTables, true)) {
            return false;
        }
        $column = $table === 'property_nodes' ? 'id' : 'status';
        $stmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE client_id = :client_id AND id = :id");
        $stmt->execute(['client_id' => $clientId, 'id' => $id]);
        $status = $stmt->fetchColumn();
        if ($status === false) {
            return false;
        }
        if ($table === 'property_nodes') {
            return $status !== false;
        }

        return $status === 'active' || (string) $existingId === (string) $id;
    }

    private static function parseId(mixed $value, bool $required): ?int
    {
        if ($value === null || $value === '') {
            return $required ? 0 : null;
        }
        if (!is_string($value) && !is_int($value)) {
            return 0;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? $id : 0;
    }

    private static function parseDate(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d') === $value
                ? $value
                : null;
    }

    private static function parseOptionalDate(mixed $value): ?string
    {
        return $value === '' || $value === null ? null : self::parseDate($value);
    }
}