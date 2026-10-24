<?php

declare(strict_types=1);

class EmployeeListDatabase extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $query = str_replace(
            ['ON DUPLICATE KEY UPDATE page_size = VALUES(page_size)', "GROUP_CONCAT(r.role_key SEPARATOR ',')", "GROUP_CONCAT(r.name SEPARATOR ', ')"],
            ['ON CONFLICT(client_id) DO UPDATE SET page_size = excluded.page_size', "GROUP_CONCAT(r.role_key, ',')", "GROUP_CONCAT(r.name, ', ')"],
            $query
        );
        $query = preg_replace("/GROUP_CONCAT\\(DISTINCT\\s+(CASE.*?END)\\s+ORDER BY c.contract_type SEPARATOR ', '\\)/s", 'GROUP_CONCAT(DISTINCT $1)', $query);

        return parent::prepare($query, $options);
    }
}

function db(): PDO
{
    static $pdo;
    return $pdo ??= new EmployeeListDatabase('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
class AuditLogService
{
    public static array $events = [];
    public static function log(mixed ...$args): void { self::$events[] = $args; }
}
class ClientContext
{
    public static function clientId(): int { return 1; }
    public static function userId(): int { return 1; }
}
class RoleService
{
    public static bool $selfOnly = false;
    public static bool $canManage = true;
    public static function isSelfServiceUser(int $user, int $client): bool { return self::$selfOnly; }
    public static function hasAnyRole(int $user, int $client, array $roles): bool { return self::$canManage; }
}
class Csrf
{
    public static function field(): string { return '<input name="csrf_token" value="valid">'; }
    public static function validate(?string $token): bool { return $token === 'valid'; }
}
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function t(string $key, string $fallback = ''): string { return $fallback; }
function render_panel_page(string $title, string $view, array $vars, string $menu): void
{
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/../views/panel/' . $view;
}
function render_page(string $title, string $view): void { echo $view; }
$managerAllowed = true;
function require_panel_manager(): bool
{
    global $managerAllowed;
    if (!$managerAllowed) {
        http_response_code(403);
    }
    return $managerAllowed;
}
function require_employee_module(): bool { return true; }
$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}
$source = file_get_contents(__DIR__ . '/../src/Services/UserService.php');
eval(substr($source, strpos($source, 'class UserService')));
$router = file_get_contents(__DIR__ . '/../public/index.php');
foreach (['handle_panel_users', 'handle_panel_users_list_settings'] as $function) {
    $start = strpos($router, 'function ' . $function . '(');
    $end = strpos($router, "\nfunction ", $start + 1);
    eval(substr($router, $start, $end - $start));
}
$pdo = db();
$pdo->exec("CREATE TABLE employee_list_settings (client_id INTEGER PRIMARY KEY, page_size INTEGER);
    CREATE TABLE system_users (id INTEGER PRIMARY KEY, client_id INTEGER, username TEXT, full_name TEXT, email TEXT, phone TEXT, status TEXT, must_change_password INTEGER);
    CREATE TABLE system_roles (id INTEGER PRIMARY KEY, role_key TEXT, name TEXT);
    CREATE TABLE system_user_roles (user_id INTEGER, client_id INTEGER, role_id INTEGER, status TEXT);
    CREATE TABLE employment_contracts (client_id INTEGER, employee_id INTEGER, contract_type TEXT, start_date TEXT, end_date TEXT);");
$insert = $pdo->prepare("INSERT INTO system_users VALUES (?, ?, ?, ?, '', '', 'active', 0)");
for ($id = 1; $id <= 25; $id++) {
    $insert->execute([$id, 1, sprintf('user%02d', $id), 'Employee ' . $id]);
}
$insert->execute([26, 2, 'other', 'Employee Other']);
$insert->execute([27, 1, 'literal', '100%_! Name']);
check(UserService::employeeListPageSize(1) === 10, 'Default page size must be ten.');
$first = UserService::employeeListPage(1, null, '', 1);
$last = UserService::employeeListPage(1, null, '', 3);
check($first['total'] === 26 && $first['pages'] === 3 && count($first['users']) === 10, 'Total and page count must reflect only this client.');
check(count($last['users']) === 6, 'Last page must contain the remainder.');
check(array_intersect(array_column($first['users'], 'id'), array_column($last['users'], 'id')) === [], 'Pages must not overlap.');
check(UserService::employeeListPage(1, null, '', 99)['page'] === 3, 'Outdated page must clamp to the last page.');
check(count(UserService::listForClient(1)) === 26, 'Existing unpaginated consumers must remain unchanged.');
$search = UserService::employeeListPage(1, null, 'Employee 1', 1);
check($search['total'] === 11 && $search['pages'] === 2 && count($search['users']) === 10, 'Name search must paginate filtered results.');
check(UserService::employeeListPage(1, null, '%_!', 1)['total'] === 1, 'LIKE wildcards must be treated as literal search input.');
check(UserService::employeeListPage(1, null, 'not found', 1)['total'] === 0, 'Unknown name should return an empty page.');
check(UserService::employeeListPage(1, 1, 'Employee', 1)['total'] === 1, 'Self-service users must only count their own account.');
check(UserService::employeeListPage(1, 1, 'Employee 2', 1)['total'] === 0, 'Search must not bypass self-service scope.');
UserService::saveEmployeeListPageSize(1, 5, 1);
check(UserService::employeeListPageSize(1) === 5 && UserService::employeeListPageSize(2) === 10, 'Settings must be client-scoped and persistent.');
check(UserService::employeeListPage(1, null, '', 1)['pages'] === 6, 'Changed page size must affect the full list.');
check(UserService::employeeListPage(1, null, 'Employee 1', 1)['pages'] === 3, 'Changed page size must also affect search.');
foreach ([0, 101] as $invalid) {
    try {
        UserService::saveEmployeeListPageSize(1, $invalid, 1);
        throw new RuntimeException('Invalid page size accepted.');
    } catch (InvalidArgumentException $exception) {
        check(UserService::employeeListPageSize(1) === 5, 'Invalid page size must not be persisted.');
    }
}
$_GET = ['search' => 'Employee 1', 'page' => '2'];
ob_start();
handle_panel_users();
$html = ob_get_clean();
check(substr_count($html, 'class="page-link"') === 3, 'UI must render one link for every filtered page.');
check(str_contains($html, 'search=Employee+1&amp;page=3'), 'Pagination must retain the name filter.');
check(str_contains($html, 'aria-current="page"'), 'Current page must be identified.');
check(str_contains($html, 'name="page_size"'), 'Administrator settings form must be visible.');
RoleService::$canManage = false;
ob_start();
handle_panel_users();
$html = ob_get_clean();
check(!str_contains($html, 'name="page_size"') && str_contains($html, 'name="search"'), 'Viewer must retain search without settings.');
RoleService::$canManage = true;
$_GET = ['search' => '"><script>alert(1)</script>'];
ob_start();
handle_panel_users();
$html = ob_get_clean();
check(!str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;'), 'Search text must be HTML escaped.');
check(str_contains($html, 'No employees found.'), 'Empty search must render a clear message.');
$_GET = ['page' => '0'];
http_response_code(200);
ob_start();
handle_panel_users();
ob_end_clean();
check(http_response_code() === 400, 'Invalid page must return 400.');
foreach ([['page' => ['1']], ['search' => ['name']], ['search' => str_repeat('a', 192)]] as $invalidQuery) {
    $_GET = $invalidQuery;
    http_response_code(200);
    ob_start();
    handle_panel_users();
    ob_end_clean();
    check(http_response_code() === 400, 'Malformed search/page must be rejected.');
}
$_POST = ['page_size' => '20', 'csrf_token' => 'invalid'];
http_response_code(200);
ob_start();
handle_panel_users_list_settings('POST');
ob_end_clean();
check(http_response_code() === 403 && UserService::employeeListPageSize(1) === 5, 'Invalid CSRF must not change settings.');
$managerAllowed = false;
$_POST['csrf_token'] = 'valid';
handle_panel_users_list_settings('POST');
check(UserService::employeeListPageSize(1) === 5, 'Non-admin must not change settings.');
$managerAllowed = true;
foreach (['0', '101', '1.5', ['10']] as $invalidSize) {
    $_POST = ['page_size' => $invalidSize, 'csrf_token' => 'valid'];
    handle_panel_users_list_settings('POST');
    check(UserService::employeeListPageSize(1) === 5, 'Invalid settings POST must not change the shared size.');
}
http_response_code(200);
ob_start();
handle_panel_users_list_settings('GET');
ob_end_clean();
check(http_response_code() === 405 && UserService::employeeListPageSize(1) === 5, 'Only POST may save settings.');
foreach ([1, 100, 10] as $validSize) {
    $_POST = ['page_size' => (string) $validSize, 'csrf_token' => 'valid'];
    ob_start();
    handle_panel_users_list_settings('POST');
    ob_end_clean();
    check(UserService::employeeListPageSize(1) === $validSize, 'Valid settings POST must persist the exact page size.');
}
$installer = file_get_contents(__DIR__ . '/../src/Services/SchemaInstaller.php');
eval(substr($installer, strpos($installer, 'class SchemaInstaller')));
$splitter = new ReflectionMethod(SchemaInstaller::class, 'splitStatements');
$sql = file_get_contents(__DIR__ . '/../sql/083_employee_list_pagination.sql');
$statements = array_values(array_filter($splitter->invoke(null, $sql)));
check(count($statements) === 4 && str_ends_with($statements[3], 'NOW())'), 'Migration must survive the actual installer splitter.');
$pdo->sqliteCreateFunction('NOW', static fn(): string => '2026-10-24 12:00:00');
$pdo->exec("CREATE TABLE system_languages (id INTEGER PRIMARY KEY, language_code TEXT UNIQUE, is_active INTEGER);
    INSERT INTO system_languages VALUES (1, 'en', 1), (2, 'et', 1), (3, 'ru', 1);
    CREATE TABLE system_translation_keys (id INTEGER PRIMARY KEY, translation_key TEXT UNIQUE, module_context TEXT);
    CREATE TABLE system_translation_values (translation_key_id INTEGER, language_id INTEGER, value TEXT, status TEXT, UNIQUE(translation_key_id, language_id));
    CREATE TABLE system_version_logs (version TEXT PRIMARY KEY, description TEXT, affected_modules TEXT, database_changes TEXT, released_at TEXT);");
for ($run = 0; $run < 2; $run++) {
    foreach (array_slice($statements, 1) as $statement) {
        $pdo->exec(str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $statement));
    }
}
check((int) $pdo->query('SELECT COUNT(*) FROM system_translation_values')->fetchColumn() === 36, 'All twelve labels must have three translations, without duplicates.');
check((int) $pdo->query("SELECT COUNT(*) FROM system_translation_values WHERE value IS NULL OR value = ''")->fetchColumn() === 0, 'No translation may be empty.');
check((int) $pdo->query("SELECT COUNT(*) FROM system_version_logs WHERE version = '1.96'")->fetchColumn() === 1, 'Migration version must be idempotent.');
echo "PASS: {$checks} employee pagination, search, settings, isolation and migration checks.\n";
