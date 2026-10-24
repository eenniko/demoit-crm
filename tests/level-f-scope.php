<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

class TranslationService
{
    public static function translate(string $key, string $fallback = ''): string
    {
        return $fallback;
    }
}

class ScopeStatement extends PDOStatement
{
    private array $params = [];

    public function __construct(private string $sql) {}

    public function execute(?array $params = null): bool
    {
        $this->params = $params ?? [];

        return true;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        if (str_contains($this->sql, 'SELECT r.role_key')) {
            return array_map(static fn(string $role): array => ['role_key' => $role], ScopeDatabase::$roles);
        }
        if (str_contains($this->sql, 'FROM property_nodes')) {
            return [];
        }
        $contracts = !str_contains($this->sql, 'FROM system_users u');
        $filterKey = $contracts ? 'employee_id' : 'user_id';
        $expectedFilter = $contracts ? 'c.employee_id = :employee_id' : 'u.id = :user_id';
        check(str_contains($this->sql, $contracts ? 'WHERE c.client_id = :client_id' : 'WHERE u.client_id = :client_id'), 'Client filter missing.');
        check(!isset($this->params[$filterKey]) || str_contains($this->sql, $expectedFilter), 'Account filter missing from SQL.');
        $rows = $contracts ? ScopeDatabase::$contracts : ScopeDatabase::$users;

        return array_values(array_filter($rows, fn(array $row): bool =>
            $row['client_id'] === $this->params['client_id']
            && (!isset($this->params[$filterKey]) || $row[$contracts ? 'employee_id' : 'id'] === $this->params[$filterKey])
        ));
    }

    public function fetchColumn(int $column = 0): mixed
    {
        if (str_contains($this->sql, 'FROM employee_list_settings') || str_contains($this->sql, 'FROM employment_contract_list_settings')) {
            return false;
        }

        return count($this->fetchAll());
    }
}

class ScopeDatabase extends PDO
{
    public static array $roles = ['level_f'];
    public static array $users = [
        ['id' => 5, 'client_id' => 1, 'full_name' => 'Same Name'],
        ['id' => 3, 'client_id' => 1, 'full_name' => 'Same Name'],
        ['id' => 7, 'client_id' => 2, 'full_name' => 'Other Client'],
    ];
    public static array $contracts = [
        ['id' => 10, 'client_id' => 1, 'employee_id' => 5, 'property_node_id' => 1, 'property_name' => 'Test location'],
        ['id' => 11, 'client_id' => 1, 'employee_id' => 3, 'property_node_id' => 1, 'property_name' => 'Test location'],
        ['id' => 12, 'client_id' => 2, 'employee_id' => 5, 'property_node_id' => 2, 'property_name' => 'Other location'],
    ];

    public function __construct() {}

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return new ScopeStatement($query);
    }
}

function db(): PDO
{
    static $database;

    return $database ??= new ScopeDatabase();
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach (['RoleService', 'UserService', 'EmploymentContractService', 'PropertyService'] as $class) {
    $source = file_get_contents(__DIR__ . '/../src/Services/' . $class . '.php');
    $start = $source === false ? false : strpos($source, 'class ' . $class);
    if ($start === false) {
        throw new RuntimeException('Cannot load actual service ' . $class);
    }
    eval(substr($source, $start));
}

class ClientContext
{
    public static function isLoggedIn(): bool { return true; }
    public static function clientId(): int { return 1; }
    public static function userId(): int { return 5; }
}

class ModuleService
{
    public static int $reads = 0;
    public static function isActiveForClient(int $clientId, string $module): bool { return true; }
    public static function listActiveForClient(int $clientId): array
    {
        self::$reads++;
        return [['module_key' => 'property']];
    }
}

class EmploymentCatalogService
{
    public static int $reads = 0;

    public static function listForClient(int $clientId, string $catalog): array
    {
        self::$reads++;

        return [];
    }

}

class SubstituteService
{
    public static function activeScheduleManagers(int $clientId, int $userId): array { return []; }
}

$rendered = [];
function render_panel_page(string $title, string $view, array $vars = [], string $menu = ''): void
{
    global $rendered;
    $rendered = $vars;
}

function render_page(string $title, string $view, array $vars = []): void
{
    echo $view;
}

$router = file_get_contents(__DIR__ . '/../public/index.php');
if ($router === false) {
    throw new RuntimeException('Cannot load router.');
}
foreach (['handle_panel_modules', 'require_property_module', 'require_property_manager',
    'handle_panel_property_index', 'handle_panel_property_create', 'handle_panel_property_edit',
    'handle_panel_property_reorder', 'handle_panel_users', 'require_employee_module', 'require_panel_manager',
    'require_employment_module', 'require_employment_manager', 'handle_panel_employment_contracts',
    'handle_panel_employment_catalog'] as $function) {
    $start = strpos($router, 'function ' . $function . '(');
    $end = $start === false ? false : strpos($router, "\nfunction ", $start + 1);
    if ($start === false || $end === false) {
        throw new RuntimeException('Cannot load handler ' . $function);
    }
    eval(substr($router, $start, $end - $start));
}

foreach ([['level_f'], ['level_f', 'viewer'], ['viewer'], ['client_admin'], ['level_f', 'client_admin']] as $roles) {
    ScopeDatabase::$roles = $roles;
    $selfOnly = in_array('level_f', $roles, true) && !in_array('client_admin', $roles, true);
    check(RoleService::isSelfServiceUser(5, 1) === $selfOnly, 'Unexpected role policy.');
    $before = ModuleService::$reads;
    http_response_code(200);
    ob_start();
    handle_panel_modules();
    $response = ob_get_clean();
    check(http_response_code() === ($selfOnly ? 403 : 200), 'Unexpected active modules access.');
    check(ModuleService::$reads === $before + ($selfOnly ? 0 : 1), 'Forbidden modules were read.');
    check(!$selfOnly || $response === 'errors/403.php', 'Missing modules forbidden view.');
    http_response_code(200);
    ob_start();
    $allowed = require_property_module();
    ob_end_clean();
    check($allowed === !$selfOnly && http_response_code() === ($selfOnly ? 403 : 200), 'Unexpected property access.');
    http_response_code(200);
    ob_start();
    handle_panel_property_index();
    $response = ob_get_clean();
    check(http_response_code() === ($selfOnly ? 403 : 200), 'Unexpected property index status.');
    check(!$selfOnly || $response === 'errors/403.php', 'Missing property forbidden view.');
    if ($selfOnly) {
        foreach (['handle_panel_property_create', 'handle_panel_property_edit', 'handle_panel_property_reorder'] as $handler) {
            foreach (['GET', 'POST'] as $method) {
                http_response_code(200);
                ob_start();
                $handler($method);
                $response = ob_get_clean();
                check(http_response_code() === 403 && $response === 'errors/403.php', 'Property subroute accessible.');
            }
        }
    }
    $_GET = ['user_id' => 3, 'employee_id' => 3, 'client_id' => 2];
    handle_panel_users();
    check(array_column($rendered['users'], 'id') === ($selfOnly ? [5] : [5, 3]), 'Employee list leaks another account or trusts URL inputs.');
    handle_panel_employment_contracts();
    check(array_column($rendered['contracts'], 'id') === ($selfOnly ? [10] : [10, 11]), 'Contracts leak another account or client.');
    check($rendered['canManage'] === in_array('client_admin', $roles, true), 'Unexpected write permissions.');
    foreach (['require_panel_manager', 'require_employment_manager'] as $guard) {
        http_response_code(200);
        ob_start();
        $allowed = $guard();
        ob_end_clean();
        check($allowed === in_array('client_admin', $roles, true), 'Unexpected employee/contract mutation access.');
        check(http_response_code() === ($allowed ? 200 : 403), 'Missing mutation denial status.');
    }

    foreach (['titles', 'departments', 'workloads'] as $catalog) {
        $before = EmploymentCatalogService::$reads;
        http_response_code(200);
        ob_start();
        handle_panel_employment_catalog('GET', $catalog);
        $html = ob_get_clean();
        check(http_response_code() === ($selfOnly ? 403 : 200), 'Unexpected catalog GET status.');
        check(EmploymentCatalogService::$reads === $before + ($selfOnly ? 0 : 1), 'Forbidden catalog must not be loaded.');
        if ($selfOnly) {
            check($html === 'errors/403.php', 'Missing forbidden view.');
            http_response_code(200);
            ob_start();
            handle_panel_employment_catalog('POST', $catalog);
            ob_end_clean();
            check(http_response_code() === 403, 'Level F catalog POST must be denied.');
        }
    }
    $canManage = in_array('client_admin', $roles, true);
    $employmentSection = 'contracts';
    ob_start();
    require __DIR__ . '/../views/panel/employment/nav.php';
    $nav = ob_get_clean();
    foreach (['titles', 'departments', 'workloads'] as $catalog) {
        check(str_contains($nav, '/panel/employment/' . $catalog) === $canManage, 'Unexpected catalog navigation.');
    }
}

echo "PASS: Level F SQL/account scoping, catalog/property/modules denial, same-name accounts, client isolation, tampered URL inputs, navigation and preserved administrator/viewer behavior.\n";
