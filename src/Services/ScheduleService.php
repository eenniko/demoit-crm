<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';
require_once __DIR__ . '/PropertyService.php';

class ScheduleService
{
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

    public static function listTemplates(int $clientId): array
    {
        $stmt = db()->prepare(
            'SELECT id, template_type, code, name, start_time, duration_minutes, color_hex, status
             FROM employee_schedule_templates
             WHERE client_id = :client_id
             ORDER BY FIELD(template_type, \'shift\', \'exception\'), status, code'
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
        $color = is_string($data['color_hex'] ?? null) ? strtoupper($data['color_hex']) : '#64748B';
        if (!in_array($type, ['shift', 'exception'], true)
            || !preg_match('/^[\p{L}\p{N}_-]{1,20}$/u', $code)
            || ($name !== '' && (preg_match('//u', $name) !== 1 || preg_match_all('/./us', $name) > 100))
            || !preg_match('/^(?:0?\.\d{1,2}|\d{1,2}(?:\.\d{1,2})?|24(?:\.0{1,2})?)$/', $duration)
            || !preg_match('/^#[0-9A-F]{6}$/', $color)
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
        $contracts = self::listPrimaryContracts($clientId, $managerId, $isClientAdmin, $month);
        $templates = self::listTemplates($clientId);
        $entries = self::getMonthEntries($clientId, $contracts, $month);
        foreach ($contracts as &$contract) {
            $plannedMinutes = 0;
            foreach ($entries[(int) $contract['contract_id']] ?? [] as $entry) {
                if ($entry['template_type'] === 'shift') {
                    $plannedMinutes += (int) $entry['duration_minutes'];
                }
            }
            $contract['planned_hours'] = round($plannedMinutes / 60, 2);
            $contract['required_hours'] = round((float) $contract['workload_percent'] * (int) $contract['active_workdays'] * 8 / 100, 2);
        }
        unset($contract);

        return ['contracts' => $contracts, 'templates' => $templates, 'entries' => $entries];
    }

    public static function templateLabel(array $template): string
    {
        $minutes = (int) $template['duration_minutes'];
        $duration = $minutes % 60 === 0 ? (string) intdiv($minutes, 60) : number_format($minutes / 60, 2, '.', '');
        return $template['code'] . ' · ' . substr($template['start_time'], 0, 5) . ' · ' . $duration . 'h';
    }

    public static function listPrimaryContracts(int $clientId, int $managerId, bool $isClientAdmin, array $month, bool $forUpdate = false): array
    {
        $sql =
            "SELECT c.id AS contract_id, c.employee_id, c.property_node_id, c.department_id,
                    c.start_date, c.end_date, c.workload_percent,
                    employee.username, employee.full_name, department.name AS department_name,
                    property.name AS property_name
             FROM employment_contracts c
             INNER JOIN system_users employee ON employee.id = c.employee_id AND employee.client_id = c.client_id
             INNER JOIN property_nodes property ON property.id = c.property_node_id AND property.client_id = c.client_id
             LEFT JOIN employment_departments department ON department.id = c.department_id AND department.client_id = c.client_id
             WHERE c.client_id = :client_id AND c.contract_type = 'primary'
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
            $contracts = self::listPrimaryContracts($clientId, $actorUserId, $isClientAdmin, $month, true);
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
                    $existingByDay[(int) $entry['employee_id'] . '|' . $entry['schedule_date']] = $entry;
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

                    $dayKey = (int) $contract['employee_id'] . '|' . $date;
                    $existing = $existingByDay[$dayKey] ?? false;
                    if ($existing !== false && (int) $existing['contract_id'] !== (int) $contract['contract_id']) {
                        $pdo->rollBack();
                        return [false, 'This employee already has a schedule entry for this day under another contract.'];
                    }

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
                        return [false, 'Choose a valid shift or exception.'];
                    }
                    $selectedTemplateStatus = $templateStatus[$templateId] ?? false;
                    $retainingHidden = $existing !== false && (int) $existing['template_id'] === $templateId;
                    if ($selectedTemplateStatus === false || ($selectedTemplateStatus !== 'active' && !$retainingHidden)) {
                        $pdo->rollBack();
                        return [false, 'Choose an active shift or exception.'];
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
        return is_string($color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1
            ? strtoupper($color)
            : '#64748B';
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