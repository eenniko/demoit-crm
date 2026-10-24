<?php

declare(strict_types=1);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class ManagerDatabase extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $query = str_replace(' FOR UPDATE', '', $query);
        $query = str_replace(
            'ON DUPLICATE KEY UPDATE contract_id = VALUES(contract_id), template_id = VALUES(template_id), updated_by = VALUES(updated_by)',
            'ON CONFLICT(client_id, contract_id, schedule_date) DO UPDATE SET template_id = excluded.template_id, updated_by = excluded.updated_by',
            $query
        );

        return parent::prepare($query, $options);
    }
}

function db(): PDO
{
    static $database;
    if ($database === null) {
        $database = new ManagerDatabase('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $database->sqliteCreateFunction('FIELD', static function (mixed $value, mixed ...$choices): int {
            $index = array_search($value, $choices, true);
            return $index === false ? 0 : $index + 1;
        });
        $database->sqliteCreateFunction('NOW', static fn(): string => date('Y-m-d H:i:s'));
    }
    return $database;
}

class PropertyService
{
    public static function listTreeForClient(int $clientId): array { return []; }
}
class PublicHolidayService
{
    public static function isDayOff(string $date): bool { return false; }
}
class AuditLogService
{
    public static array $events = [];
    public static function log(mixed ...$args): void { self::$events[] = $args; }
}
class RoleService
{
    public static bool $levelF = false;
    public static array $fUsers = [];
    public static array $admins = [];
    public static function isSelfServiceUser(int $userId, int $clientId): bool
    {
        return (self::$levelF && $userId === 5) || in_array($userId, self::$fUsers, true);
    }
    public static function hasAnyRole(int $userId, int $clientId, array $roles): bool { return in_array($userId, self::$admins, true); }
}
class ClientContext
{
    public static int $userId = 5;
    public static function isLoggedIn(): bool { return true; }
    public static function userId(): int { return self::$userId; }
    public static function clientId(): int { return 1; }
}
class ModuleService
{
    public static function isActiveForClient(int $clientId, string $module): bool { return true; }
}
class Csrf
{
    public static function validate(?string $token): bool { return $token === 'valid'; }
    public static function field(): string { return '<input type="hidden" name="csrf_token" value="valid">'; }
}
function render_page(string $title, string $view, array $vars = []): void { echo $view; }

$source = file_get_contents(__DIR__ . '/../src/Services/SubstituteService.php');
$start = $source === false ? false : strpos($source, 'class SubstituteService');
if ($start === false) {
    throw new RuntimeException('Cannot load substitute service.');
}
eval(substr($source, $start));
$source = file_get_contents(__DIR__ . '/../src/Services/ScheduleService.php');
$start = $source === false ? false : strpos($source, 'class ScheduleService');
if ($start === false) {
    throw new RuntimeException('Cannot load schedule service.');
}
eval(substr($source, $start));
$router = file_get_contents(__DIR__ . '/../public/index.php');
if ($router === false) {
    throw new RuntimeException('Cannot load router.');
}
foreach (['require_schedule_modules', 'handle_panel_schedule', 'handle_panel_schedule_workload'] as $function) {
    $start = strpos($router, 'function ' . $function . '(');
    $end = $start === false ? false : strpos($router, "\nfunction ", $start + 1);
    if ($start === false || $end === false) {
        throw new RuntimeException('Cannot load handler.');
    }
    eval(substr($router, $start, $end - $start));
}

$pdo = db();
$pdo->exec("
    CREATE TABLE employment_contracts (id INTEGER PRIMARY KEY, client_id INTEGER, employee_id INTEGER,
        property_node_id INTEGER, department_id INTEGER, contract_type TEXT, start_date TEXT, end_date TEXT,
        workload_id INTEGER, workload_percent REAL, archived_at TEXT, manager_user_id INTEGER);
    CREATE TABLE system_users (id INTEGER, client_id INTEGER, username TEXT, full_name TEXT, status TEXT);
    CREATE TABLE property_nodes (id INTEGER, client_id INTEGER, name TEXT);
    CREATE TABLE employment_departments (id INTEGER, client_id INTEGER, name TEXT);
    CREATE TABLE system_substitutes (id INTEGER PRIMARY KEY AUTOINCREMENT, client_id INTEGER,
        original_user_id INTEGER, substitute_user_id INTEGER, substitute_type TEXT, status TEXT,
        starts_at TEXT, ends_at TEXT, delegated_permissions TEXT, reason TEXT,
        approved_by INTEGER, approved_at TEXT);
    CREATE TABLE employment_contract_monthly_workloads (client_id INTEGER, contract_id INTEGER,
        workload_month TEXT, workload_id INTEGER, workload_percent REAL, created_by INTEGER, updated_by INTEGER);
    CREATE TABLE employee_schedule_templates (id INTEGER, client_id INTEGER, template_type TEXT,
        code TEXT, name TEXT, start_time TEXT, duration_minutes INTEGER, color_hex TEXT, status TEXT);
    CREATE TABLE employee_schedule_entries (client_id INTEGER, employee_id INTEGER, contract_id INTEGER,
        schedule_date TEXT, template_id INTEGER, created_by INTEGER, updated_by INTEGER,
        UNIQUE(client_id, contract_id, schedule_date));
    INSERT INTO property_nodes VALUES (1,1,'Location'), (2,2,'Other client');
    INSERT INTO employee_schedule_templates VALUES
        (1,1,'shift','D','Day','08:00:00',480,'#008000','active'),
        (2,1,'shift','E','Evening','16:00:00',480,'#0000FF','active'),
        (3,1,'exception','HP','Vacation','08:00:00',480,'#FF0000','active');
    INSERT INTO system_users VALUES (5,1,'manager','Manager','active'), (6,1,'staff','Staff','active'),
        (7,1,'unassigned','Unassigned','active'), (8,1,'ordinary','Ordinary','active'),
        (9,1,'inactive','Inactive','inactive'), (10,1,'futuremanager','Future manager','active'),
        (11,1,'archivemanager','Archive manager','active'), (12,1,'inactive_manager','Inactive manager','active'),
        (5,2,'duplicate','Other client','active'), (6,2,'staff','Other staff','active');
");
$insert = $pdo->prepare('INSERT INTO employment_contracts VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
$contracts = [
    [10,1,5,1,null,'primary','2026-01-01',null,1,100,null,99],
    [11,1,5,1,null,'temporary','2026-10-01',null,1,100,null,99],
    [20,1,6,1,null,'primary','2026-10-01','2026-10-31',1,100,null,5],
    [21,1,6,1,null,'temporary','2026-10-01',null,1,100,null,99],
    [22,1,7,1,null,'temporary','2026-10-01',null,1,100,null,5],
    [30,1,7,1,null,'primary','2026-01-01',null,1,100,null,99],
    [40,2,5,2,null,'primary','2026-01-01',null,1,100,null,99],
    [41,2,6,2,null,'primary','2026-01-01',null,1,100,null,5],
    [50,1,8,1,null,'primary','2026-01-01',null,1,100,null,99],
    [51,1,6,1,null,'primary','2026-11-01',null,1,100,null,10],
    [52,1,10,1,null,'primary','2026-01-01',null,1,100,null,99],
    [53,1,11,1,null,'primary','2026-01-01',null,1,100,null,99],
    [54,1,6,1,null,'primary','2026-01-01',null,1,100,'2026-10-01',11],
    [55,1,12,1,null,'primary','2026-01-01',null,1,100,null,99],
    [56,1,9,1,null,'primary','2026-01-01',null,1,100,null,12],
    [60,1,6,1,null,'temporary','2026-11-01',null,1,100,null,5],
];
foreach ($contracts as $row) {
    $insert->execute($row);
}
function contractIds(int $userId, string $month): array
{
    $ids = array_column(ScheduleService::listScheduleContracts(1, $userId, false, ScheduleService::monthInfo($month)), 'contract_id');
    sort($ids);
    return $ids;
}
check(contractIds(5, '2026-10') === [10,11,20,22], 'Manager must see own and assigned primary/temporary, not unassigned contracts.');
check(contractIds(5, '2026-09') === [], 'No selected-month management must not grant own schedule.');
check(contractIds(8, '2026-10') === [], 'Ordinary account received self-edit rights.');
check(contractIds(10, '2026-10') === [], 'Future assignment granted early self access.');
check(contractIds(11, '2026-10') === [], 'Archived assignment granted self access.');
check(contractIds(12, '2026-10') === [], 'Inactive subordinate granted self access.');
$pdo->exec("INSERT INTO employee_schedule_entries VALUES
    (1,5,10,'2026-09-15',1,99,99), (1,5,10,'2026-10-15',1,99,99),
    (1,6,20,'2026-10-15',1,99,99), (1,6,21,'2026-10-15',2,99,99)");
$month = ScheduleService::monthInfo('2026-10');
$data = ScheduleService::monthData(1,5,false,$month);
$rows = array_column($data['contracts'], null, 'contract_id');
check($rows[10]['planned_hours'] === 8.0 && $rows[20]['planned_hours'] === 8.0, 'Own/subordinate totals wrong or unassigned temporary hours leaked.');
check($rows[10]['trimester_balance_hours'] === -336.0, 'Own Tri-OT must use selected-month management scope across earlier months.');
foreach ([10,11,20,22] as $id) {
    [$success, $message] = ScheduleService::saveMonth(1,5,false,'2026-10',[$id => ['2026-10-16' => $id === 11 ? 2 : 1]]);
    check($success, 'Allowed schedule did not save: ' . $message);
}
check((int) $pdo->query("SELECT COUNT(*) FROM employee_schedule_entries WHERE updated_by=5")->fetchColumn() === 4, 'Allowed writes not persisted.');
$pdo->exec("DELETE FROM employee_schedule_entries WHERE contract_id = 10 AND schedule_date BETWEEN '2026-10-30' AND '2026-11-01'");
[$success, $message] = ScheduleService::saveMonth(1,5,false,'2026-10',[10 => ['2026-10-30' => 3]],3);
check($success, 'Consecutive exception spanning a month was not saved: ' . $message);
$repeatedDates = $pdo->query("SELECT schedule_date,template_id FROM employee_schedule_entries WHERE contract_id=10 AND schedule_date BETWEEN '2026-10-30' AND '2026-11-01' ORDER BY schedule_date")->fetchAll();
check($repeatedDates === [
    ['schedule_date' => '2026-10-30', 'template_id' => 3],
    ['schedule_date' => '2026-10-31', 'template_id' => 3],
    ['schedule_date' => '2026-11-01', 'template_id' => 3],
], 'Repeated exception did not create identical consecutive daily values across months.');
$beforeRepeatFailure = $pdo->query('SELECT * FROM employee_schedule_entries ORDER BY client_id,contract_id,schedule_date')->fetchAll();
[$success] = ScheduleService::saveMonth(1,5,false,'2026-10',[10 => ['2026-10-30' => 1]],3);
check(!$success, 'A shift template was accepted for repeat days.');
check($pdo->query('SELECT * FROM employee_schedule_entries ORDER BY client_id,contract_id,schedule_date')->fetchAll() === $beforeRepeatFailure, 'Invalid repeated shift modified schedule entries.');
[$success] = ScheduleService::saveMonth(1,5,false,'2026-10',[10 => ['2026-10-30' => 3]],367);
check(!$success, 'An exception range longer than 366 days was accepted.');
check($pdo->query('SELECT * FROM employee_schedule_entries ORDER BY client_id,contract_id,schedule_date')->fetchAll() === $beforeRepeatFailure, 'Invalid repeat day count modified schedule entries.');
[$success] = ScheduleService::saveMonth(1,5,false,'2026-10',[20 => ['2026-10-31' => 3]],2);
check(!$success, 'Repeated exception was allowed beyond contract end.');
check($pdo->query('SELECT * FROM employee_schedule_entries ORDER BY client_id,contract_id,schedule_date')->fetchAll() === $beforeRepeatFailure, 'Out-of-contract repeated exception was partially saved.');
$pdo->exec("INSERT INTO employee_schedule_entries VALUES (1,5,11,'2026-11-02',1,99,99)");
$beforeOverlapFailure = $pdo->query('SELECT * FROM employee_schedule_entries ORDER BY client_id,contract_id,schedule_date')->fetchAll();
[$success] = ScheduleService::saveMonth(1,5,false,'2026-10',[10 => ['2026-10-31' => 3]],3);
check(!$success, 'Cross-month repeated exception ignored an adjacent-month overlap.');
check($pdo->query('SELECT * FROM employee_schedule_entries ORDER BY client_id,contract_id,schedule_date')->fetchAll() === $beforeOverlapFailure, 'Overlapping repeated exception was partially persisted.');
$pdo->exec("DELETE FROM employee_schedule_entries WHERE contract_id=11 AND schedule_date='2026-11-02'");
$pdo->exec("UPDATE employment_contracts SET manager_user_id=99 WHERE id=22");
$pdo->exec("UPDATE employment_contracts SET manager_user_id=99 WHERE id=60");
[$success] = ScheduleService::saveMonth(1,5,false,'2026-10',[10 => ['2026-10-31' => 3]],2);
check(!$success, 'Cross-month repeated exception bypassed next-month manager authorization.');
$pdo->exec("UPDATE employment_contracts SET manager_user_id=5 WHERE id=60");
$pdo->exec("UPDATE employment_contracts SET manager_user_id=5 WHERE id=22");
$scheduleView = file_get_contents(__DIR__ . '/../views/panel/schedule/index.php');
check(is_string($scheduleView)
    && str_contains($scheduleView, 'id="scheduleExceptionDays"')
    && str_contains($scheduleView, 'max="366"')
    && str_contains($scheduleView, 'repeatDays')
    && str_contains($scheduleView, 'data-schedule-type="<?= e($type) ?>"')
    && str_contains($scheduleView, 'assignmentToSave.repeatDays || 1'),
    'Exception picker must expose validated consecutive-day input and pass the selected count.');
$before = $pdo->query('SELECT * FROM employee_schedule_entries ORDER BY client_id,contract_id,schedule_date')->fetchAll();
foreach ([21,30,40,41,50,54,56] as $id) {
    [$success] = ScheduleService::saveMonth(1,5,false,'2026-10',[10 => ['2026-10-17' => 1], $id => ['2026-10-17' => 1]]);
    check(!$success, 'Unauthorized contract saved.');
    check($pdo->query('SELECT * FROM employee_schedule_entries ORDER BY client_id,contract_id,schedule_date')->fetchAll() === $before, 'Denied mixed write was not rolled back.');
}
[$success] = ScheduleService::saveMonth(1,5,false,'2026-10',[11 => ['2026-10-15' => 1]]);
check(!$success, 'Own primary/temporary overlapping shift accepted.');
$pdo->exec("INSERT INTO employment_contract_monthly_workloads VALUES (1,10,'2026-10-01',1,50,99,99)");
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
$_GET = [];
$_POST = ['csrf_token' => 'valid', 'month' => '2026-10', 'contract_id' => '10', 'workload_id' => ''];
http_response_code(200);
ob_start();
handle_panel_schedule_workload('POST');
$response = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
check(http_response_code() === 200 && $response['success'], 'Manager own workload reset denied.');
check((int) $pdo->query('SELECT COUNT(*) FROM employment_contract_monthly_workloads')->fetchColumn() === 0, 'Workload reset did not persist.');
$_POST['contract_id'] = '20';
http_response_code(200);
ob_start();
handle_panel_schedule_workload('POST');
$response = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
check(http_response_code() === 200 && $response['success'], 'Assigned employee workload reset denied.');
$_POST['contract_id'] = '30';
http_response_code(200);
ob_start();
handle_panel_schedule_workload('POST');
$response = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
check(http_response_code() === 403 && !$response['success'], 'Unassigned workload accepted.');
$_POST = ['csrf_token' => 'valid', 'month' => '2026-10', 'assignments' => [10 => ['2026-10-18' => 1]]];
http_response_code(200);
ob_start();
handle_panel_schedule('POST');
$response = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
check(http_response_code() === 200 && $response['success'], 'Manager own AJAX save denied.');
$_POST['assignments'] = [30 => ['2026-10-18' => 1]];
ob_start();
handle_panel_schedule('POST');
$response = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
check(http_response_code() === 422 && !$response['success'], 'Unassigned AJAX write accepted.');
$_POST['assignments'] = [10 => ['2026-10-19' => 1]];
$_POST['csrf_token'] = 'invalid';
$before = $pdo->query('SELECT * FROM employee_schedule_entries ORDER BY client_id,contract_id,schedule_date')->fetchAll();
ob_start();
handle_panel_schedule('POST');
$response = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
check(http_response_code() === 422 && !$response['success'], 'Invalid manager CSRF accepted.');
check($pdo->query('SELECT * FROM employee_schedule_entries ORDER BY client_id,contract_id,schedule_date')->fetchAll() === $before, 'Invalid CSRF changed data.');
RoleService::$levelF = true;
ob_start();
handle_panel_schedule('POST');
$response = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
check(http_response_code() === 403 && !$response['success'], 'Level F manager can edit.');
$pdo->exec('UPDATE employment_contracts SET manager_user_id=99 WHERE client_id=1 AND manager_user_id=5');
check(contractIds(5,'2026-10') === [], 'Revoked manager assignment retained own access.');
check(AuditLogService::$events !== [], 'Successful saves not audited.');

echo "PASS: actual manager SELECT, own/assigned primary and temporary writes, client and contract isolation, selected-month qualification, Tri-OT, AJAX/workload guards, rollback, overlaps, revocation and Level F denial on isolated SQLite.\n";
