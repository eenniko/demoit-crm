<?php

declare(strict_types=1);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class PrintDatabase extends PDO
{
    public static array $roles = ['level_f'];
    public static array $contracts = [];
    public static array $queries = [];

    public function __construct() {}

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return new PrintStatement($query);
    }
}

class PrintStatement extends PDOStatement
{
    private array $params = [];

    public function __construct(private string $sql) {}

    public function execute(?array $params = null): bool
    {
        $this->params = $params ?? [];
        PrintDatabase::$queries[] = [$this->sql, $this->params];

        return true;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        if (str_contains($this->sql, 'SELECT r.role_key')) {
            return array_map(static fn(string $role): array => ['role_key' => $role], PrintDatabase::$roles);
        }
        check(isset($this->params['client_id']), 'Missing client parameter.');
        if (str_contains($this->sql, 'FROM employee_schedule_templates')
            || str_contains($this->sql, 'FROM employment_contract_monthly_workloads')) {
            return [];
        }
        if (str_contains($this->sql, 'FROM employment_contracts') && !str_contains($this->sql, 'FROM employee_schedule_entries')) {
            $contracts = array_values(array_filter(PrintDatabase::$contracts, fn(array $c): bool =>
                $c['client_id'] === $this->params['client_id']
                && $c['archived_at'] === null && $c['status'] === 'active'
                && $c['start_date'] <= $this->params['month_end']
                && ($c['end_date'] === null || $c['end_date'] >= $this->params['month_start'])
            ));
            if (isset($this->params['location_employee_id'])) {
                foreach ([
                    'own_contract.client_id = c.client_id',
                    'own_contract.employee_id = :location_employee_id',
                    'own_contract.property_node_id = c.property_node_id',
                    'COALESCE(own_contract.department_id, 0) = COALESCE(c.department_id, 0)',
                    'own_contract.archived_at IS NULL',
                    "own_contract.contract_type IN ('primary', 'temporary')",
                    'own_contract.start_date <= :own_month_end',
                    'own_contract.end_date >= :own_month_start',
                ] as $predicate) {
                    check(str_contains($this->sql, $predicate), 'Missing group scope predicate: ' . $predicate);
                }
                check(!isset($this->params['manager_id']), 'Print visibility must not depend on manager assignment.');
                check($this->params['own_month_start'] === $this->params['month_start']
                    && $this->params['own_month_end'] === $this->params['month_end'], 'Own contract month differs.');
                $groups = [];
                foreach ($contracts as $c) {
                    if ($c['employee_id'] === $this->params['location_employee_id']) {
                        $groups[$c['property_node_id'] . ':' . ($c['department_id'] ?? 0)] = true;
                    }
                }
                $contracts = array_values(array_filter($contracts, static fn(array $c): bool =>
                    isset($groups[$c['property_node_id'] . ':' . ($c['department_id'] ?? 0)])
                ));
            }
            if (isset($this->params['scope_manager_id'])) {
                $managed = array_filter(PrintDatabase::$contracts, fn(array $c): bool =>
                    $c['client_id'] === $this->params['client_id']
                    && $c['manager_user_id'] === $this->params['scope_assigned_manager_id']
                    && $c['employee_id'] !== $this->params['scope_other_user_id']
                    && $c['archived_at'] === null && $c['status'] === 'active'
                    && $c['start_date'] <= $this->params['scope_month_end']
                    && ($c['end_date'] === null || $c['end_date'] >= $this->params['scope_month_start'])
                );
                $contracts = array_values(array_filter($contracts, fn(array $c): bool =>
                    $c['manager_user_id'] === $this->params['scope_manager_id']
                    || ($managed !== [] && $c['employee_id'] === $this->params['scope_employee_id'])
                ));
            }
            $employees = array_filter($this->params, static fn(string $key): bool => str_starts_with($key, 'employee_'), ARRAY_FILTER_USE_KEY);
            if ($employees !== []) {
                $contracts = array_values(array_filter($contracts, static fn(array $c): bool =>
                    $c['contract_type'] === 'primary' && in_array($c['employee_id'], $employees, true)
                ));
            }

            return $contracts;
        }
        if (str_contains($this->sql, 'FROM employee_schedule_entries')) {
            $ids = array_filter($this->params, static fn(string $key): bool => str_starts_with($key, 'contract_'), ARRAY_FILTER_USE_KEY);
            $employees = array_filter($this->params, static fn(string $key): bool => str_starts_with($key, 'employee_'), ARRAY_FILTER_USE_KEY);
            $entries = [];
            $managed = isset($this->params['scope_manager_id']) ? array_filter(PrintDatabase::$contracts, fn(array $c): bool =>
                $c['client_id'] === $this->params['client_id']
                && $c['manager_user_id'] === $this->params['scope_assigned_manager_id']
                && $c['employee_id'] !== $this->params['scope_other_user_id']
                && $c['archived_at'] === null && $c['status'] === 'active'
                && $c['start_date'] <= $this->params['scope_month_end']
                && ($c['end_date'] === null || $c['end_date'] >= $this->params['scope_month_start'])
            ) : [];
            foreach (PrintDatabase::$contracts as $c) {
                if ($c['client_id'] !== $this->params['client_id'] || $c['archived_at'] !== null
                    || ($ids !== [] && !in_array($c['contract_id'], $ids, true))
                    || ($employees !== [] && !in_array($c['employee_id'], $employees, true))
                    || (isset($this->params['scope_manager_id']) && $c['manager_user_id'] !== $this->params['scope_manager_id']
                        && !($managed !== [] && $c['employee_id'] === $this->params['scope_employee_id']))) {
                    continue;
                }
                $date = '2026-10-15';
                $start = $this->params['range_start'] ?? $this->params['month_start'];
                if ($date < $start || $date > $this->params['month_end']
                    || $date < $c['start_date'] || ($c['end_date'] !== null && $date > $c['end_date'])) {
                    continue;
                }
                $entries[] = [
                    'contract_id' => $c['contract_id'], 'employee_id' => $c['employee_id'],
                    'schedule_date' => $date, 'template_id' => 1, 'template_type' => 'shift',
                    'code' => 'D', 'template_name' => 'Day', 'start_time' => '08:00:00',
                    'duration_minutes' => 480, 'color_hex' => '#008000', 'template_status' => 'active',
                ];
            }

            return $entries;
        }
        throw new RuntimeException('Unexpected query: ' . $this->sql);
    }
}

function db(): PDO
{
    static $database;

    return $database ??= new PrintDatabase();
}

class PropertyService
{
    public static function listTreeForClient(int $clientId): array { return []; }
}

class PublicHolidayService
{
    public static function isDayOff(string $date): bool { return false; }
}

class ClientContext
{
    public static int $userId = 5;
    public static function isLoggedIn(): bool { return true; }
    public static function clientId(): int { return 1; }
    public static function userId(): int { return self::$userId; }
}

class ModuleService
{
    public static function isActiveForClient(int $clientId, string $module): bool { return true; }
}

class SubstituteService
{
    public static function activeScheduleManagers(int $clientId, int $userId): array { return []; }
    public static function canAssignScheduleSubstitute(int $clientId, int $userId): bool { return false; }
}

class LanguageService
{
    public static function currentCode(): string { return 'en'; }
}

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function t(string $key, string $fallback): string { return $fallback; }

$rendered = [];
function render_panel_page(string $title, string $view, array $vars = [], string $menu = ''): void
{
    global $rendered;
    $rendered = $vars;
}
function render_page(string $title, string $view, array $vars = []): void { echo $view; }

foreach (['RoleService', 'ScheduleService'] as $class) {
    $source = file_get_contents(__DIR__ . '/../src/Services/' . $class . '.php');
    $start = $source === false ? false : strpos($source, 'class ' . $class);
    if ($start === false) {
        throw new RuntimeException('Cannot load service ' . $class);
    }
    eval(substr($source, $start));
}
$router = file_get_contents(__DIR__ . '/../public/index.php');
if ($router === false) {
    throw new RuntimeException('Cannot load router.');
}
foreach (['require_schedule_modules', 'handle_panel_schedule', 'handle_panel_schedule_print',
    'require_schedule_admin', 'handle_panel_schedule_holidays', 'handle_panel_schedule_holiday_import',
    'handle_panel_schedule_workload', 'handle_panel_schedule_templates'] as $function) {
    $start = strpos($router, 'function ' . $function . '(');
    $end = $start === false ? false : strpos($router, "\nfunction ", $start + 1);
    if ($start === false || $end === false) {
        throw new RuntimeException('Cannot load handler ' . $function);
    }
    eval(substr($router, $start, $end - $start));
}

$base = [
    'client_id' => 1, 'employee_id' => 3, 'property_node_id' => 1, 'department_id' => null,
    'contract_type' => 'primary', 'start_date' => '2026-01-01', 'end_date' => null,
    'archived_at' => null, 'status' => 'active', 'manager_user_id' => 9,
    'workload_id' => 1, 'workload_percent' => 100, 'full_name' => 'Same Name',
    'username' => 'employee', 'department_name' => null, 'property_name' => 'Location 1',
];
$cases = [
    10 => ['employee_id' => 5, 'workload_percent' => 50],
    11 => [],
    12 => ['department_id' => 1, 'department_name' => 'Other department'],
    20 => ['employee_id' => 5, 'contract_type' => 'temporary', 'property_node_id' => 2, 'department_id' => 2,
        'department_name' => 'Dept 2', 'start_date' => '2026-10-10', 'end_date' => '2026-10-20'],
    21 => ['property_node_id' => 2, 'department_id' => 2, 'department_name' => 'Dept 2'],
    30 => ['property_node_id' => 3, 'manager_user_id' => 5],
    40 => ['employee_id' => 5, 'property_node_id' => 4, 'end_date' => '2026-09-30'],
    41 => ['property_node_id' => 4],
    50 => ['employee_id' => 5, 'property_node_id' => 5, 'start_date' => '2026-11-01'],
    51 => ['property_node_id' => 5],
    60 => ['employee_id' => 5, 'property_node_id' => 6, 'archived_at' => '2026-10-01'],
    61 => ['property_node_id' => 6],
    70 => ['employee_id' => 5, 'client_id' => 2, 'property_node_id' => 7],
    71 => ['property_node_id' => 7],
    80 => ['property_node_id' => 8],
    90 => ['employee_id' => 4, 'status' => 'inactive'],
    91 => ['property_node_id' => 9],
];
foreach ($cases as $id => $changes) {
    PrintDatabase::$contracts[] = array_replace($base, $changes, ['contract_id' => $id]);
}

$_GET = ['month' => '2026-10', 'employee_id' => 3, 'client_id' => 2, 'location' => '8:0'];
foreach ([['level_f'], ['level_f', 'viewer'], ['viewer'], ['client_admin'], ['level_f', 'client_admin']] as $roles) {
    PrintDatabase::$roles = $roles;
    $printOnly = in_array('level_f', $roles, true) && !in_array('client_admin', $roles, true);
    handle_panel_schedule_print();
    check($rendered['printOnly'] === $printOnly, 'Unexpected print-only flag.');
    $keys = array_keys($rendered['locations']);
    $expected = $printOnly ? ['1:0', '2:2'] :
        (in_array('client_admin', $roles, true) ? ['1:0', '1:1', '2:2', '3:0', '4:0', '5:0', '6:0', '7:0', '8:0', '9:0'] : ['1:0', '2:2', '3:0']);
    sort($keys);
    sort($expected);
    check($keys === $expected, 'Unexpected location or department scope.');
    $canManageTemplates = $rendered['canManageTemplates'];
    $month = $rendered['month'];
    $scheduleSection = 'print';
    ob_start();
    require __DIR__ . '/../views/panel/schedule/nav.php';
    $nav = ob_get_clean();
    check(str_contains($nav, 'Monthly schedule') === !$printOnly, 'Monthly navigation policy changed.');
    check(str_contains($nav, '/panel/schedule/templates') === $canManageTemplates
        && str_contains($nav, '/panel/schedule/holidays') === $canManageTemplates, 'Administrator tabs changed.');
    $activeModuleKeys = ['schedule', 'property', 'employees', 'employment'];
    $activeMenu = 'schedule';
    $workspaceContent = '';
    ob_start();
    require __DIR__ . '/../views/panel/shell.php';
    $shell = ob_get_clean();
    check(str_contains($shell, 'href="' . ($printOnly ? '/panel/schedule/print' : '/panel/schedule') . '"'), 'Sidebar target incorrect.');
    foreach (['/panel/modules', '/panel/property'] as $href) {
        check(str_contains($shell, 'href="' . $href . '"') === !$printOnly, 'Forbidden module/property sidebar link visible.');
    }
    check(str_contains($shell, 'href="/panel/users"') && str_contains($shell, 'href="/panel/employment/contracts"'), 'Own employee/contract links lost.');
    if (!$printOnly) {
        continue;
    }
    check(array_column($rendered['locations']['1:0']['contracts'], 'contract_id') === [10, 11], 'Missing coworkers or same-name account leaked elsewhere.');
    $temporaryIds = array_column($rendered['locations']['2:2']['contracts'], 'contract_id');
    sort($temporaryIds);
    check($temporaryIds === [20, 21], 'Temporary workplace group missing.');
    $entryIds = array_keys($rendered['entries']);
    sort($entryIds);
    check($entryIds === [10, 11, 20, 21], 'Hidden location entries leaked.');
    check($rendered['locations']['1:0']['contracts'][0]['planned_hours'] === 16.0, 'Primary total lost temporary hours.');
    $temporaryContracts = array_column($rendered['locations']['2:2']['contracts'], null, 'contract_id');
    check($temporaryContracts[20]['planned_hours'] === 8.0, 'Temporary total incorrect.');
    extract($rendered, EXTR_OVERWRITE);
    ob_start();
    require __DIR__ . '/../views/panel/schedule/print.php';
    $html = ob_get_clean();
    check(!str_contains($html, 'Monthly schedule') && !str_contains($html, '/panel/schedule/templates')
        && !str_contains($html, '/panel/schedule/holidays'), 'Forbidden schedule tabs visible.');
    check(str_contains($html, 'Print / Save as PDF') && str_contains($html, 'window.print()'), 'PDF printing missing.');
    $dom = new DOMDocument();
    check($dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING), 'Cannot parse print view.');
    $xpath = new DOMXPath($dom);
    check($xpath->query('//section[@data-location-key]')->length === 2, 'Incorrect number of print pages.');
    check($xpath->query('//form[@method="post"]')->length === 0, 'Print view has mutation form.');
    $before = count(PrintDatabase::$queries);
    http_response_code(200);
    handle_panel_schedule('GET');
    check(http_response_code() === 302, 'Monthly GET must redirect to print.');
    foreach (['', 'xmlhttprequest'] as $ajax) {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = $ajax;
        http_response_code(200);
        ob_start();
        handle_panel_schedule('POST');
        $response = ob_get_clean();
        check(http_response_code() === 403, 'Schedule POST not denied.');
        check($ajax === '' ? $response === 'errors/403.php' : json_decode($response, true)['success'] === false, 'Incorrect POST denial format.');
    }
    ob_start();
    handle_panel_schedule_workload('POST');
    $response = ob_get_clean();
    check(http_response_code() === 403 && json_decode($response, true)['success'] === false, 'Workload mutation not denied.');
    foreach (['handle_panel_schedule_templates', 'handle_panel_schedule_holiday_import'] as $handler) {
        foreach (['GET', 'POST'] as $method) {
            http_response_code(200);
            ob_start();
            $handler($method);
            ob_end_clean();
            check(http_response_code() === 403, 'Schedule admin handler accessible.');
        }
    }
    ob_start();
    handle_panel_schedule_holidays();
    ob_end_clean();
    check(http_response_code() === 403, 'Holiday page accessible.');
    check(count(PrintDatabase::$queries) > $before, 'Role checks not executed.');
    foreach (array_slice(PrintDatabase::$queries, $before) as [$sql]) {
        check(str_contains($sql, 'SELECT r.role_key'), 'Denied handler accessed schedule data.');
    }
}

PrintDatabase::$roles = ['level_f'];
ClientContext::$userId = 999;
handle_panel_schedule_print();
check($rendered['locations'] === [] && $rendered['entries'] === [], 'Account without contract can see schedules.');
ClientContext::$userId = 5;
$_GET['month'] = '2026-09';
handle_panel_schedule_print();
check(!isset($rendered['locations']['2:2']) && isset($rendered['locations']['4:0']), 'Month-specific contract validity ignored.');
$_GET['month'] = 'invalid';
ob_start();
handle_panel_schedule_print();
ob_end_clean();
check(http_response_code() === 400, 'Invalid month accepted.');

if (in_array('--sqlite', $argv ?? [], true)) {
    $database = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $database->exec('
        CREATE TABLE employment_contracts (id INTEGER, client_id INTEGER, employee_id INTEGER,
            property_node_id INTEGER, department_id INTEGER, contract_type TEXT, start_date TEXT,
            end_date TEXT, workload_id INTEGER, workload_percent REAL, archived_at TEXT, manager_user_id INTEGER);
        CREATE TABLE system_users (id INTEGER, client_id INTEGER, username TEXT, full_name TEXT, status TEXT);
        CREATE TABLE property_nodes (id INTEGER, client_id INTEGER, name TEXT);
        CREATE TABLE employment_departments (id INTEGER, client_id INTEGER, name TEXT);
        CREATE TABLE system_substitutes (client_id INTEGER, original_user_id INTEGER, substitute_user_id INTEGER,
            substitute_type TEXT, status TEXT, starts_at TEXT, ends_at TEXT);
    ');
    $database->sqliteCreateFunction('NOW', static fn(): string => date('Y-m-d H:i:s'));
    $insert = $database->prepare('INSERT INTO employment_contracts VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $users = $properties = $departments = [];
    foreach (PrintDatabase::$contracts as $c) {
        $insert->execute([
            $c['contract_id'], $c['client_id'], $c['employee_id'], $c['property_node_id'],
            $c['department_id'], $c['contract_type'], $c['start_date'], $c['end_date'],
            $c['workload_id'], $c['workload_percent'], $c['archived_at'], $c['manager_user_id'],
        ]);
        $users[$c['client_id'] . ':' . $c['employee_id']] = [$c['employee_id'], $c['client_id'], $c['username'], $c['full_name'], $c['status']];
        $properties[$c['client_id'] . ':' . $c['property_node_id']] = [$c['property_node_id'], $c['client_id'], $c['property_name']];
        if ($c['department_id'] !== null) {
            $departments[$c['client_id'] . ':' . $c['department_id']] = [$c['department_id'], $c['client_id'], $c['department_name']];
        }
    }
    foreach ([
        ['INSERT INTO system_users VALUES (?,?,?,?,?)', $users],
        ['INSERT INTO property_nodes VALUES (?,?,?)', $properties],
        ['INSERT INTO employment_departments VALUES (?,?,?)', $departments],
    ] as [$sql, $rows]) {
        $insert = $database->prepare($sql);
        foreach ($rows as $row) {
            $insert->execute($row);
        }
    }
    $checked = [];
    $expected = ['5:2026-10-01' => [10, 11, 20, 21], '999:2026-10-01' => [], '5:2026-09-01' => [10, 11, 40, 41]];
    foreach (PrintDatabase::$queries as [$sql, $params]) {
        if (!isset($params['location_employee_id'])) {
            continue;
        }
        $key = $params['location_employee_id'] . ':' . $params['month_start'];
        $statement = $database->prepare($sql);
        $statement->execute($params);
        $ids = array_column($statement->fetchAll(PDO::FETCH_ASSOC), 'contract_id');
        sort($ids);
        check($ids === $expected[$key], 'Actual SQL returned incorrect print scope: ' . $key);
        $checked[$key] = true;
    }
    check(count($checked) === 3, 'Actual SQL fixture cases missing.');
    echo "PASS: unmodified group-scope SQL executed on isolated SQLite fixtures.\n";
}

echo "PASS: Level F print groups, primary/temporary membership, coworkers, departments, month validity, archived/inactive accounts, client isolation, URL tampering, denied mutations, navigation and PDF controls.\n";
