<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';
require_once __DIR__ . '/PropertyService.php';

class ScheduleService
{
    public const TEMPLATE_COLORS = [
        '#FF0000' => 'Red',
        '#FFA500' => 'Orange',
        '#FFFF00' => 'Yellow',
        '#008000' => 'Green',
        '#0000FF' => 'Blue',
        '#4B0082' => 'Indigo',
        '#EE82EE' => 'Violet',
        '#000000' => 'Black',
        '#FFFFFF' => 'White',
    ];

    public static function monthInfo(string $month): ?array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return null;
        }
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01');
        if ($start === false) {
            return null;
        }
        $end = $start->modify('last day of this month');

        return [
            'month' => $month,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'days' => (int) $start->format('t'),
            'active_workdays' => self::countWeekdays($start, $end),
        ];
    }

    public static function countWeekdays(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        $count = 0;
        for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
            if ((int) $day->format('N') <= 5) {
                $count++;
            }
        }

        return $count;
    }

    public static function requiredHoursForMonth(float $workloadPercent, int $workingDays): float
    {
        if ($workloadPercent <= 0 || $workloadPercent > 100 || $workingDays < 0 || $workingDays > 31) {
            throw new InvalidArgumentException('Invalid monthly workload inputs.');
        }

        return $workingDays * 8 * ($workloadPercent / 100);
    }

    public static function listTemplates(int $clientId): array
    {
        $stmt = db()->prepare(
            'SELECT id, template_type, code, name, start_time, duration_minutes, color_hex, status
             FROM employee_schedule_templates
             WHERE client_id = :client_id
             ORDER BY FIELD(template_type, \'shift\', \'exception\', \'block\'), status, code'
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    public static function saveTemplate(int $clientId, ?int $templateId, array $data, int $actorUserId): array
    {
        $type = is_string($data['template_type'] ?? null) ? $data['template_type'] : '';
        $code = trim(is_string($data['code'] ?? null) ? $data['code'] : '');
        $name = trim(is_string($data['name'] ?? null) ? $data['name'] : '');
        $startTime = is_string($data['start_time'] ?? null) ? $data['start_time'] : '';
        $duration = is_string($data['duration_hours'] ?? null) ? $data['duration_hours'] : '';
        $color = is_string($data['color_hex'] ?? null) ? strtoupper(trim($data['color_hex'])) : '#0000FF';
        if (!in_array($type, ['shift', 'exception', 'block'], true)
            || !preg_match('/^[\p{L}\p{N}_-]{1,20}$/u', $code)
            || ($name !== '' && (preg_match('//u', $name) !== 1 || preg_match_all('/./us', $name) > 100))
            || !preg_match('/^(?:0?\.\d{1,2}|\d{1,2}(?:\.\d{1,2})?|24(?:\.0{1,2})?)$/', $duration)
            || !isset(self::TEMPLATE_COLORS[$color])
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $startTime)) {
            return [false, 'Enter a valid type, code, color, start time and duration.'];
        }
        $durationMinutes = (int) round((float) $duration * 60);
        if ($durationMinutes < 15 || $durationMinutes > 1440) {
            return [false, 'Duration must be from 0.25 to 24 hours.'];
        }
        $name = $name !== '' ? $name : $code;

        try {
            if ($templateId === null) {
                $stmt = db()->prepare(
                    'INSERT INTO employee_schedule_templates
                                (client_id, template_type, code, name, start_time, duration_minutes, color_hex)
                            VALUES (:client_id, :template_type, :code, :name, :start_time, :duration_minutes, :color_hex)'
                );
                $stmt->execute([
                    'client_id' => $clientId,
                    'template_type' => $type,
                    'code' => $code,
                    'name' => $name,
                    'start_time' => $startTime . ':00',
                    'duration_minutes' => $durationMinutes,
                    'color_hex' => $color,
                ]);
                $templateId = (int) db()->lastInsertId();
                $action = 'created';
            } else {
                $stmt = db()->prepare(
                    'UPDATE employee_schedule_templates
                     SET template_type = :template_type, code = :code, name = :name,
                         start_time = :start_time, duration_minutes = :duration_minutes, color_hex = :color_hex
                     WHERE client_id = :client_id AND id = :id'
                );
                $stmt->execute([
                    'client_id' => $clientId,
                    'id' => $templateId,
                    'template_type' => $type,
                    'code' => $code,
                    'name' => $name,
                    'start_time' => $startTime . ':00',
                    'duration_minutes' => $durationMinutes,
                    'color_hex' => $color,
                ]);
                if ($stmt->rowCount() === 0 && !self::templateExists($clientId, $templateId)) {
                    return [false, 'Schedule template not found for this client.'];
                }
                $action = 'updated';
            }
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return [false, 'This code already exists for this type.'];
            }
            return [false, 'Could not save the schedule template.'];
        }

        AuditLogService::log($actorUserId, $clientId, "schedule.template.{$action}", 'employee_schedule_templates', (string) $templateId);

        return [true, $action === 'created' ? 'Schedule template added.' : 'Schedule template updated.'];
    }

    public static function toggleTemplate(int $clientId, int $templateId, int $actorUserId): array
    {
        $stmt = db()->prepare('SELECT status FROM employee_schedule_templates WHERE client_id = :client_id AND id = :id');
        $stmt->execute(['client_id' => $clientId, 'id' => $templateId]);
        $status = $stmt->fetchColumn();
        if ($status === false) {
            return [false, 'Schedule template not found for this client.'];
        }
        $newStatus = $status === 'active' ? 'inactive' : 'active';
        $update = db()->prepare('UPDATE employee_schedule_templates SET status = :status WHERE client_id = :client_id AND id = :id');
        $update->execute(['status' => $newStatus, 'client_id' => $clientId, 'id' => $templateId]);
        AuditLogService::log($actorUserId, $clientId, 'schedule.template.status_changed', 'employee_schedule_templates', (string) $templateId, (string) $status, $newStatus);

        return [true, $newStatus === 'active' ? 'Template activated.' : 'Template hidden. Existing schedule entries are unchanged.'];
    }

    public static function monthData(int $clientId, int $managerId, bool $isClientAdmin, array $month): array
    {
        $contracts = self::listScheduleContracts($clientId, $managerId, $isClientAdmin, $month);
        $monthlyWorkloadOverrides = self::monthlyWorkloadOverrides(
            $clientId,
            array_map(static fn (array $contract): int => (int) $contract['contract_id'], $contracts),
            $month['month']
        );
        foreach ($contracts as &$contract) {
            $contract['base_workload_id'] = (int) ($contract['workload_id'] ?? 0);
            $contract['base_workload_percent'] = (float) $contract['workload_percent'];
            $override = $monthlyWorkloadOverrides[(int) $contract['contract_id']] ?? null;
            $contract['monthly_workload_id'] = $override !== null ? (int) $override['workload_id'] : null;
            if ($contract['contract_type'] === 'primary' && $override !== null) {
                $contract['workload_percent'] = (float) $override['workload_percent'];
            }
        }
        unset($contract);
        $templates = self::listTemplates($clientId);
        $entries = self::getMonthEntries($clientId, $contracts, $month);
        $plannedMinutes = self::plannedWorkMinutes($clientId, $managerId, $isClientAdmin, $contracts, $month);
        $temporaryMinutesByEmployee = [];
        foreach ($contracts as $contract) {
            if ($contract['contract_type'] === 'temporary') {
                $employeeId = (int) $contract['employee_id'];
                $temporaryMinutesByEmployee[$employeeId] = ($temporaryMinutesByEmployee[$employeeId] ?? 0)
                    + ($plannedMinutes['byContract'][(int) $contract['contract_id']] ?? 0);
            }
        }
        foreach ($contracts as &$contract) {
            $employeeId = (int) $contract['employee_id'];
            $contractMinutes = $plannedMinutes['byContract'][(int) $contract['contract_id']] ?? 0;
            if ($contract['contract_type'] === 'temporary') {
                $contract['planned_hours'] = round($contractMinutes / 60, 2);
                $contract['temporary_planned_hours'] = $contract['planned_hours'];
                $contract['required_hours'] = null;
                $contract['monthly_balance_hours'] = null;
                $contract['trimester_balance_hours'] = null;
                continue;
            }

            $contract['planned_hours'] = round(($plannedMinutes['byEmployee'][$employeeId] ?? 0) / 60, 2);
            $contract['temporary_planned_hours'] = round(($temporaryMinutesByEmployee[$employeeId] ?? 0) / 60, 2);
            $contract['required_hours'] = round(self::requiredHoursForMonth((float) $contract['workload_percent'], (int) $contract['active_workdays']), 2);
            $contract['monthly_balance_hours'] = round($contract['planned_hours'] - $contract['required_hours'], 2);
        }
        unset($contract);
        $trimesterBalances = self::trimesterBalances($clientId, $managerId, $isClientAdmin, $contracts, $month);
        foreach ($contracts as &$contract) {
            if ($contract['contract_type'] === 'primary') {
                $contract['trimester_balance_hours'] = $trimesterBalances[(int) $contract['employee_id']] ?? 0.0;
            }
        }
        unset($contract);

        return ['contracts' => $contracts, 'templates' => $templates, 'entries' => $entries];
    }

    private static function trimesterBalances(int $clientId, int $managerId, bool $isClientAdmin, array $visibleContracts, array $selectedMonth): array
    {
        $employeeIds = array_values(array_unique(array_map(
            static fn (array $contract): int => (int) $contract['employee_id'],
            $visibleContracts
        )));
        if ($employeeIds === []) {
            return [];
        }

        $selectedMonthNumber = (int) substr($selectedMonth['month'], 5, 2);
        $trimesterStartMonth = intdiv($selectedMonthNumber - 1, 4) * 4 + 1;
        $periodStart = new DateTimeImmutable(substr($selectedMonth['month'], 0, 4) . '-' . sprintf('%02d', $trimesterStartMonth) . '-01');
        $selectedMonthStart = new DateTimeImmutable($selectedMonth['month'] . '-01');
        $balances = [];

        while ($periodStart <= $selectedMonthStart) {
            $period = self::monthInfo($periodStart->format('Y-m'));
            $placeholders = [];
            $params = ['client_id' => $clientId, 'month_start' => $period['start'], 'month_end' => $period['end']];
            foreach ($employeeIds as $index => $employeeId) {
                $key = 'employee_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $employeeId;
            }
            $sql =
                "SELECT id AS contract_id, employee_id, start_date, end_date, workload_percent
                 FROM employment_contracts
                 WHERE client_id = :client_id AND contract_type = 'primary' AND archived_at IS NULL
                   AND employee_id IN (" . implode(', ', $placeholders) . ")
                   AND start_date <= :month_end
                   AND (end_date IS NULL OR end_date >= :month_start)";
            if (!$isClientAdmin) {
                $sql .= ' AND manager_user_id = :manager_id';
                $params['manager_id'] = $managerId;
            }
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $periodContracts = $stmt->fetchAll();

            if ($periodContracts !== []) {
                $periodWorkloadOverrides = self::monthlyWorkloadOverrides(
                    $clientId,
                    array_map(static fn (array $contract): int => (int) $contract['contract_id'], $periodContracts),
                    $period['month']
                );
                $plannedMinutesByEmployee = self::plannedWorkMinutes($clientId, $managerId, $isClientAdmin, $periodContracts, $period)['byEmployee'];
                $requiredHoursByEmployee = [];
                foreach ($periodContracts as $contract) {
                    $contractStart = max($period['start'], $contract['start_date']);
                    $contractEnd = $contract['end_date'] === null ? $period['end'] : min($period['end'], $contract['end_date']);
                    $activeWorkdays = self::countWeekdays(new DateTimeImmutable($contractStart), new DateTimeImmutable($contractEnd));
                    $employeeId = (int) $contract['employee_id'];
                    $override = $periodWorkloadOverrides[(int) $contract['contract_id']] ?? null;
                    $workloadPercent = $override !== null ? (float) $override['workload_percent'] : (float) $contract['workload_percent'];
                    $requiredHoursByEmployee[$employeeId] = ($requiredHoursByEmployee[$employeeId] ?? 0.0)
                        + self::requiredHoursForMonth($workloadPercent, $activeWorkdays);
                }
                foreach ($requiredHoursByEmployee as $employeeId => $requiredHours) {
                    $plannedHours = ($plannedMinutesByEmployee[$employeeId] ?? 0) / 60;
                    $balances[$employeeId] = round(($balances[$employeeId] ?? 0.0) + $plannedHours - $requiredHours, 2);
                }
            }

            $periodStart = $periodStart->modify('first day of next month');
        }

        return $balances;
    }

    private static function monthlyWorkloadOverrides(int $clientId, array $contractIds, string $month): array
    {
        $contractIds = array_values(array_unique(array_map('intval', $contractIds)));
        if ($contractIds === []) {
            return [];
        }

        $placeholders = [];
        $params = ['client_id' => $clientId, 'workload_month' => $month . '-01'];
        foreach ($contractIds as $index => $contractId) {
            $key = 'contract_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $contractId;
        }
        $stmt = db()->prepare(
            'SELECT contract_id, workload_id, workload_percent
             FROM employment_contract_monthly_workloads
             WHERE client_id = :client_id AND workload_month = :workload_month
               AND contract_id IN (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($params);

        $overrides = [];
        foreach ($stmt->fetchAll() as $override) {
            $overrides[(int) $override['contract_id']] = $override;
        }

        return $overrides;
    }

    public static function saveMonthlyWorkload(int $clientId, int $contractId, string $month, ?int $workloadId, int $actorUserId): array
    {
        $monthInfo = self::monthInfo($month);
        if ($monthInfo === null || ($workloadId !== null && $workloadId <= 0)) {
            return [false, 'Choose a valid month and workload.'];
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();
            $contractQuery = $pdo->prepare(
                'SELECT contract_type, start_date, end_date, archived_at
                 FROM employment_contracts
                 WHERE client_id = :client_id AND id = :contract_id
                 FOR UPDATE'
            );
            $contractQuery->execute(['client_id' => $clientId, 'contract_id' => $contractId]);
            $contract = $contractQuery->fetch();
            if ($contract === false || $contract['contract_type'] !== 'primary' || $contract['archived_at'] !== null) {
                $pdo->rollBack();
                return [false, 'Choose an active primary contract.'];
            }
            if ($contract['start_date'] > $monthInfo['end'] || ($contract['end_date'] !== null && $contract['end_date'] < $monthInfo['start'])) {
                $pdo->rollBack();
                return [false, 'The primary contract does not cover this month.'];
            }

            if ($workloadId === null) {
                $delete = $pdo->prepare(
                    'DELETE FROM employment_contract_monthly_workloads
                     WHERE client_id = :client_id AND contract_id = :contract_id AND workload_month = :workload_month'
                );
                $delete->execute([
                    'client_id' => $clientId,
                    'contract_id' => $contractId,
                    'workload_month' => $monthInfo['start'],
                ]);
                $message = 'Monthly workload reset to the contract default.';
            } else {
                $workloadQuery = $pdo->prepare(
                    "SELECT workload_percent FROM employment_workloads
                     WHERE client_id = :client_id AND id = :workload_id AND status = 'active'"
                );
                $workloadQuery->execute(['client_id' => $clientId, 'workload_id' => $workloadId]);
                $workloadPercent = $workloadQuery->fetchColumn();
                if ($workloadPercent === false) {
                    $pdo->rollBack();
                    return [false, 'Choose an active workload.'];
                }

                $save = $pdo->prepare(
                    'INSERT INTO employment_contract_monthly_workloads
                        (client_id, contract_id, workload_month, workload_id, workload_percent, created_by, updated_by)
                     VALUES (:client_id, :contract_id, :workload_month, :workload_id, :workload_percent, :created_by, :updated_by)
                     ON DUPLICATE KEY UPDATE workload_id = VALUES(workload_id), workload_percent = VALUES(workload_percent), updated_by = VALUES(updated_by)'
                );
                $save->execute([
                    'client_id' => $clientId,
                    'contract_id' => $contractId,
                    'workload_month' => $monthInfo['start'],
                    'workload_id' => $workloadId,
                    'workload_percent' => (float) $workloadPercent,
                    'created_by' => $actorUserId,
                    'updated_by' => $actorUserId,
                ]);
                $message = 'Monthly workload saved.';
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [false, 'Could not save the monthly workload.'];
        }

        AuditLogService::log(
            $actorUserId,
            $clientId,
            'schedule.monthly_workload_saved',
            'employment_contract_monthly_workloads',
            $contractId . ':' . $month
        );

        return [true, $message];
    }

    private static function plannedWorkMinutes(int $clientId, int $managerId, bool $isClientAdmin, array $contracts, array $month): array
    {
        $employeeIds = array_values(array_unique(array_map(
            static fn (array $contract): int => (int) $contract['employee_id'],
            $contracts
        )));
        if ($employeeIds === []) {
            return ['byEmployee' => [], 'byContract' => []];
        }

        $utc = new DateTimeZone('UTC');
        $monthStart = new DateTimeImmutable($month['start'] . ' 00:00:00', $utc);
        $monthEndExclusive = $monthStart->modify('first day of next month');
        $previousDay = $monthStart->modify('-1 day');
        $previousMonthIsPeriodEnd = (int) $previousDay->format('n') % 4 === 0;
        $queryStart = $previousMonthIsPeriodEnd ? $monthStart : $previousDay;
        $placeholders = [];
        $params = [
            'client_id' => $clientId,
            'range_start' => $queryStart->format('Y-m-d'),
            'month_end' => $month['end'],
        ];
        foreach ($employeeIds as $index => $employeeId) {
            $key = 'employee_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $employeeId;
        }

        $sql =
            'SELECT e.employee_id, e.contract_id, e.schedule_date, template.template_type, template.start_time, template.duration_minutes
             FROM employee_schedule_entries e
             INNER JOIN employment_contracts contract
                     ON contract.client_id = e.client_id AND contract.id = e.contract_id AND contract.archived_at IS NULL
             INNER JOIN employee_schedule_templates template
                ON template.client_id = e.client_id AND template.id = e.template_id
             WHERE e.client_id = :client_id AND e.schedule_date BETWEEN :range_start AND :month_end
               AND e.employee_id IN (' . implode(', ', $placeholders) . ')
               AND template.template_type IN (\'shift\', \'exception\')';
        if (!$isClientAdmin) {
            $sql .= ' AND contract.manager_user_id = :manager_id';
            $params['manager_id'] = $managerId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $minutesByEmployee = [];
        $minutesByContract = [];

        foreach ($stmt->fetchAll() as $entry) {
            if (!self::templateCountsAsPlanned($entry['template_type'], $entry['schedule_date'])) {
                continue;
            }
            $durationMinutes = (int) $entry['duration_minutes'];
            $minutesInMonth = self::shiftMinutesInMonth($entry['schedule_date'], $entry['start_time'], $durationMinutes, $month);

            if ($minutesInMonth > 0) {
                $employeeId = (int) $entry['employee_id'];
                $minutesByEmployee[$employeeId] = ($minutesByEmployee[$employeeId] ?? 0) + $minutesInMonth;
                $contractId = (int) $entry['contract_id'];
                $minutesByContract[$contractId] = ($minutesByContract[$contractId] ?? 0) + $minutesInMonth;
            }
        }

        return ['byEmployee' => $minutesByEmployee, 'byContract' => $minutesByContract];
    }

    public static function templateCountsAsPlanned(string $templateType, string $scheduleDate): bool
    {
        if ($templateType === 'shift') {
            return true;
        }
        if ($templateType === 'block') {
            return false;
        }
        if ($templateType !== 'exception') {
            return false;
        }

        return (int) (new DateTimeImmutable($scheduleDate))->format('N') <= 5;
    }

    public static function shiftMinutesInMonth(string $scheduleDate, string $startTime, int $durationMinutes, array $month): int
    {
        $utc = new DateTimeZone('UTC');
        $monthStart = new DateTimeImmutable($month['start'] . ' 00:00:00', $utc);
        $monthEndExclusive = $monthStart->modify('first day of next month');
        $shiftStart = new DateTimeImmutable($scheduleDate . ' ' . $startTime, $utc);
        $shiftEnd = $shiftStart->modify('+' . $durationMinutes . ' minutes');
        $isPeriodEndMonth = (int) $monthStart->format('n') % 4 === 0;

        if ($isPeriodEndMonth && $shiftStart >= $monthStart && $shiftStart < $monthEndExclusive && $shiftEnd > $monthEndExclusive) {
            return $durationMinutes;
        }

        $overlapStart = max($shiftStart->getTimestamp(), $monthStart->getTimestamp());
        $overlapEnd = min($shiftEnd->getTimestamp(), $monthEndExclusive->getTimestamp());

        return max(0, intdiv($overlapEnd - $overlapStart, 60));
    }

    public static function formatHours(float $hours): string
    {
        $rounded = round($hours, 2);
        return number_format($rounded, abs($rounded - round($rounded)) < 0.00001 ? 0 : 2, '.', '') . 'h';
    }

    public static function formatBalance(float $hours): string
    {
        $rounded = round($hours, 2);
        return ($rounded > 0 ? '+' : '') . self::formatHours($rounded);
    }

    public static function templateLabel(array $template): string
    {
        $minutes = (int) $template['duration_minutes'];
        $duration = $minutes % 60 === 0 ? (string) intdiv($minutes, 60) : number_format($minutes / 60, 2, '.', '');
        return $template['code'] . ' · ' . substr($template['start_time'], 0, 5) . ' · ' . $duration . 'h';
    }

    public static function listScheduleContracts(int $clientId, int $managerId, bool $isClientAdmin, array $month, bool $forUpdate = false): array
    {
        $sql =
                "SELECT c.id AS contract_id, c.employee_id, c.property_node_id, c.department_id, c.contract_type,
                    c.start_date, c.end_date, c.workload_id, c.workload_percent,
                    employee.username, employee.full_name, department.name AS department_name,
                    property.name AS property_name
             FROM employment_contracts c
             INNER JOIN system_users employee ON employee.id = c.employee_id AND employee.client_id = c.client_id
             INNER JOIN property_nodes property ON property.id = c.property_node_id AND property.client_id = c.client_id
             LEFT JOIN employment_departments department ON department.id = c.department_id AND department.client_id = c.client_id
             WHERE c.client_id = :client_id AND c.archived_at IS NULL AND c.contract_type IN ('primary', 'temporary')
               AND employee.status = 'active'
               AND c.start_date <= :month_end
               AND (c.end_date IS NULL OR c.end_date >= :month_start)";
        $params = ['client_id' => $clientId, 'month_end' => $month['end'], 'month_start' => $month['start']];
        if (!$isClientAdmin) {
            $sql .= ' AND c.manager_user_id = :manager_id';
            $params['manager_id'] = $managerId;
        }
        $sql .= ' ORDER BY property.name, department.name, employee.full_name, c.start_date';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $contracts = $stmt->fetchAll();

        $paths = [];
        foreach (PropertyService::listTreeForClient($clientId) as $node) {
            $paths[(int) $node['id']] = implode(' - ', $node['path']);
        }
        foreach ($contracts as &$contract) {
            $contract['property_path'] = $paths[(int) $contract['property_node_id']] ?? $contract['property_name'];
            $contractStart = max($month['start'], $contract['start_date']);
            $contractEnd = $contract['end_date'] === null ? $month['end'] : min($month['end'], $contract['end_date']);
            $contract['active_workdays'] = self::countWeekdays(
                new DateTimeImmutable($contractStart),
                new DateTimeImmutable($contractEnd)
            );
        }
        unset($contract);
        usort($contracts, static fn (array $first, array $second): int =>
            ($first['property_path'] <=> $second['property_path'])
            ?: (($first['department_name'] ?? '') <=> ($second['department_name'] ?? ''))
            ?: (($first['full_name'] ?: $first['username']) <=> ($second['full_name'] ?: $second['username']))
            ?: ($first['start_date'] <=> $second['start_date'])
        );

        return $contracts;
    }

    public static function getMonthEntries(int $clientId, array $contracts, array $month): array
    {
        $contractIds = array_map(static fn (array $contract): int => (int) $contract['contract_id'], $contracts);
        if ($contractIds === []) {
            return [];
        }
        $placeholders = [];
        $params = ['client_id' => $clientId, 'month_start' => $month['start'], 'month_end' => $month['end']];
        foreach ($contractIds as $index => $contractId) {
            $key = 'contract_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $contractId;
        }
        $stmt = db()->prepare(
            'SELECT e.contract_id, e.employee_id, e.schedule_date, e.template_id,
                    template.template_type, template.code, template.name AS template_name,
                    template.start_time, template.duration_minutes, template.color_hex,
                    template.status AS template_status
             FROM employee_schedule_entries e
             INNER JOIN employee_schedule_templates template
                ON template.client_id = e.client_id AND template.id = e.template_id
             WHERE e.client_id = :client_id AND e.schedule_date BETWEEN :month_start AND :month_end
               AND e.contract_id IN (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($params);
        $entries = [];
        foreach ($stmt->fetchAll() as $entry) {
            $entries[(int) $entry['contract_id']][$entry['schedule_date']] = $entry;
        }

        return $entries;
    }

    /** @return array{0: bool, 1: string} */
    public static function saveMonth(int $clientId, int $actorUserId, bool $isClientAdmin, string $managerMonth, mixed $assignments): array
    {
        $month = self::monthInfo($managerMonth);
        if ($month === null || !is_array($assignments)) {
            return [false, 'Choose a valid month and schedule values.'];
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();
            $contracts = self::listScheduleContracts($clientId, $actorUserId, $isClientAdmin, $month, true);
            $contractById = [];
            foreach ($contracts as $contract) {
                $contractById[(string) $contract['contract_id']] = $contract;
            }
            $employeeIds = array_values(array_unique(array_map(static fn (array $contract): int => (int) $contract['employee_id'], $contracts)));
            $existingByDay = [];
            if ($employeeIds !== []) {
                $employeePlaceholders = [];
                $entryParams = ['client_id' => $clientId, 'month_start' => $month['start'], 'month_end' => $month['end']];
                foreach ($employeeIds as $index => $employeeId) {
                    $key = 'employee_' . $index;
                    $employeePlaceholders[] = ':' . $key;
                    $entryParams[$key] = $employeeId;
                }
                $existingEntries = $pdo->prepare(
                    'SELECT employee_id, schedule_date, contract_id, template_id
                     FROM employee_schedule_entries
                     WHERE client_id = :client_id AND schedule_date BETWEEN :month_start AND :month_end
                       AND employee_id IN (' . implode(', ', $employeePlaceholders) . ')
                     FOR UPDATE'
                );
                $existingEntries->execute($entryParams);
                foreach ($existingEntries->fetchAll() as $entry) {
                    $existingByDay[(int) $entry['contract_id'] . '|' . $entry['schedule_date']] = $entry;
                }
            }
            $templateStatus = [];
            $templates = $pdo->prepare('SELECT id, status FROM employee_schedule_templates WHERE client_id = :client_id FOR UPDATE');
            $templates->execute(['client_id' => $clientId]);
            foreach ($templates->fetchAll() as $template) {
                $templateStatus[(int) $template['id']] = $template['status'];
            }
            $delete = $pdo->prepare(
                'DELETE FROM employee_schedule_entries
                 WHERE client_id = :client_id AND contract_id = :contract_id AND schedule_date = :schedule_date'
            );

            foreach ($assignments as $contractKey => $days) {
                $contractKey = is_string($contractKey) || is_int($contractKey) ? (string) $contractKey : '';
                if (!ctype_digit($contractKey) || !isset($contractById[$contractKey]) || !is_array($days)) {
                    $pdo->rollBack();
                    return [false, 'Schedule contains an employee or contract outside your assignments.'];
                }
                $contract = $contractById[$contractKey];
                foreach ($days as $date => $templateValue) {
                    if (!is_string($date) || !self::isDateInMonth($date, $month['month'])) {
                        $pdo->rollBack();
                        return [false, 'Schedule contains an invalid day.'];
                    }
                    if ($date < $contract['start_date'] || ($contract['end_date'] !== null && $date > $contract['end_date'])) {
                        $pdo->rollBack();
                        return [false, 'A shift cannot be assigned outside the employee contract period.'];
                    }
                    if (!is_string($templateValue) && !is_int($templateValue)) {
                        $pdo->rollBack();
                        return [false, 'Schedule contains an invalid shift value.'];
                    }

                    $dayKey = (int) $contract['contract_id'] . '|' . $date;
                    $existing = $existingByDay[$dayKey] ?? false;

                    if ($templateValue === '' || $templateValue === '0') {
                        if ($existing !== false) {
                            $delete->execute(['client_id' => $clientId, 'contract_id' => (int) $contract['contract_id'], 'schedule_date' => $date]);
                            unset($existingByDay[$dayKey]);
                        }
                        continue;
                    }
                    $templateId = filter_var($templateValue, FILTER_VALIDATE_INT);
                    if ($templateId === false || $templateId <= 0) {
                        $pdo->rollBack();
                        return [false, 'Choose a valid schedule template.'];
                    }
                    $selectedTemplateStatus = $templateStatus[$templateId] ?? false;
                    $retainingHidden = $existing !== false && (int) $existing['template_id'] === $templateId;
                    if ($selectedTemplateStatus === false || ($selectedTemplateStatus !== 'active' && !$retainingHidden)) {
                        $pdo->rollBack();
                        return [false, 'Choose an active schedule template.'];
                    }

                    $upsert = $pdo->prepare(
                        'INSERT INTO employee_schedule_entries
                            (client_id, employee_id, contract_id, schedule_date, template_id, created_by, updated_by)
                         VALUES (:client_id, :employee_id, :contract_id, :schedule_date, :template_id, :created_by, :updated_by)
                         ON DUPLICATE KEY UPDATE contract_id = VALUES(contract_id), template_id = VALUES(template_id), updated_by = VALUES(updated_by)'
                    );
                    $upsert->execute([
                        'client_id' => $clientId,
                        'employee_id' => (int) $contract['employee_id'],
                        'contract_id' => (int) $contract['contract_id'],
                        'schedule_date' => $date,
                        'template_id' => $templateId,
                        'created_by' => $actorUserId,
                        'updated_by' => $actorUserId,
                    ]);
                    $existingByDay[$dayKey] = [
                        'contract_id' => (int) $contract['contract_id'],
                        'template_id' => $templateId,
                    ];
                }
            }
            $overlapDate = self::findOverlappingShiftDate($pdo, $clientId, $employeeIds, $month);
            if ($overlapDate !== null) {
                $pdo->rollBack();
                return [false, 'This employee already has another shift during that time on ' . $overlapDate . '.'];
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Schedule save failed (' . get_class($exception) . ', ' . $exception->getCode() . '): ' . $exception->getMessage());
            $message = 'Could not save the monthly schedule. Check the values and try again.';
            if ($isClientAdmin && $exception instanceof PDOException) {
                $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
                if (preg_match('/^[A-Z0-9]{5}$/', $sqlState) === 1) {
                    $databaseCode = (int) ($exception->errorInfo[1] ?? 0);
                    $message .= ' (SQLSTATE ' . $sqlState . ', database code ' . $databaseCode . ')';
                }
            }
            return [false, $message];
        }

        AuditLogService::log($actorUserId, $clientId, 'schedule.month_saved', 'employee_schedule_entries', $managerMonth);

        return [true, 'Schedule saved.'];
    }

    private static function findOverlappingShiftDate(PDO $pdo, int $clientId, array $employeeIds, array $month): ?string
    {
        if ($employeeIds === []) {
            return null;
        }

        $monthStart = new DateTimeImmutable($month['start']);
        $monthEnd = new DateTimeImmutable($month['end']);
        $params = [
            'client_id' => $clientId,
            'range_start' => $monthStart->modify('-1 day')->format('Y-m-d'),
            'range_end' => $monthEnd->modify('+1 day')->format('Y-m-d'),
        ];
        $placeholders = [];
        foreach ($employeeIds as $index => $employeeId) {
            $key = 'employee_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $employeeId;
        }
        $stmt = $pdo->prepare(
            'SELECT e.employee_id, e.schedule_date, template.template_type, template.start_time, template.duration_minutes
             FROM employee_schedule_entries e
             INNER JOIN employee_schedule_templates template
                ON template.client_id = e.client_id AND template.id = e.template_id
             WHERE e.client_id = :client_id AND e.schedule_date BETWEEN :range_start AND :range_end
               AND e.employee_id IN (' . implode(', ', $placeholders) . ')
               AND template.template_type IN (\'shift\', \'exception\', \'block\')
             ORDER BY e.employee_id, e.schedule_date, template.start_time
             FOR UPDATE'
        );
        $stmt->execute($params);

        return self::findFirstShiftOverlap($stmt->fetchAll());
    }

    public static function findFirstShiftOverlap(array $entries): ?string
    {
        $timezone = new DateTimeZone('UTC');
        $shifts = [];
        foreach ($entries as $entry) {
            $start = new DateTimeImmutable($entry['schedule_date'] . ' ' . $entry['start_time'], $timezone);
            $shifts[] = [
                'employee_id' => (int) $entry['employee_id'],
                'date' => $entry['schedule_date'],
                'template_type' => $entry['template_type'],
                'start' => $start,
                'end' => $start->modify('+' . (int) $entry['duration_minutes'] . ' minutes'),
            ];
        }
        usort($shifts, static fn (array $first, array $second): int =>
            ($first['employee_id'] <=> $second['employee_id']) ?: ($first['start'] <=> $second['start'])
        );
        $lastEndByEmployee = [];
        $lastPlannedEndByEmployee = [];
        $lastBlockEndByEmployee = [];
        foreach ($shifts as $shift) {
            $employeeId = $shift['employee_id'];
            $templateType = $shift['template_type'];
            if ($templateType === 'block') {
                if (isset($lastEndByEmployee[$employeeId]) && $shift['start'] < $lastEndByEmployee[$employeeId]) {
                    return $shift['date'];
                }
                if (!isset($lastEndByEmployee[$employeeId]) || $shift['end'] > $lastEndByEmployee[$employeeId]) {
                    $lastEndByEmployee[$employeeId] = $shift['end'];
                }
                if (!isset($lastBlockEndByEmployee[$employeeId]) || $shift['end'] > $lastBlockEndByEmployee[$employeeId]) {
                    $lastBlockEndByEmployee[$employeeId] = $shift['end'];
                }
                continue;
            }
            if (isset($lastBlockEndByEmployee[$employeeId]) && $shift['start'] < $lastBlockEndByEmployee[$employeeId]) {
                return $shift['date'];
            }
            if (!self::templateCountsAsPlanned($templateType, $shift['date'])) {
                if (!isset($lastEndByEmployee[$employeeId]) || $shift['end'] > $lastEndByEmployee[$employeeId]) {
                    $lastEndByEmployee[$employeeId] = $shift['end'];
                }
                continue;
            }
            if (isset($lastPlannedEndByEmployee[$employeeId]) && $shift['start'] < $lastPlannedEndByEmployee[$employeeId]) {
                return $shift['date'];
            }
            if (!isset($lastEndByEmployee[$employeeId]) || $shift['end'] > $lastEndByEmployee[$employeeId]) {
                $lastEndByEmployee[$employeeId] = $shift['end'];
            }
            if (!isset($lastPlannedEndByEmployee[$employeeId]) || $shift['end'] > $lastPlannedEndByEmployee[$employeeId]) {
                $lastPlannedEndByEmployee[$employeeId] = $shift['end'];
            }
        }

        return null;
    }

    public static function formatTemplate(array $template): string
    {
        $minutes = (int) $template['duration_minutes'];
        $duration = $minutes % 60 === 0
            ? (string) intdiv($minutes, 60)
            : number_format($minutes / 60, 2, '.', '');

        return $template['code'] . ' · ' . substr($template['start_time'], 0, 5) . ' · ' . $duration . 'h';
    }

    public static function safeTemplateColor(mixed $color): string
    {
        $color = is_string($color) ? strtoupper($color) : '';
        return isset(self::TEMPLATE_COLORS[$color]) ? $color : '#0000FF';
    }

    private static function isDateInMonth(string $date, string $month): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && substr($date, 0, 7) === $month
            && DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date;
    }

    private static function templateExists(int $clientId, int $templateId): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM employee_schedule_templates WHERE client_id = :client_id AND id = :id');
        $stmt->execute(['client_id' => $clientId, 'id' => $templateId]);
        return $stmt->fetchColumn() !== false;
    }
}