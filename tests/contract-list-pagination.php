<?php

declare(strict_types=1);

class ContractListDatabase extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return parent::prepare(str_replace(
            'ON DUPLICATE KEY UPDATE page_size = VALUES(page_size)',
            'ON CONFLICT(client_id) DO UPDATE SET page_size = excluded.page_size',
            $query
        ), $options);
    }
}
function db(): PDO
{
    static $pdo;
    return $pdo ??= new ContractListDatabase('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
class AuditLogService
{
    public static array $events = [];
    public static function log(mixed ...$args): void { self::$events[] = $args; }
}
class PropertyService
{
    public static function listTreeForClient(int $clientId): array
    {
        return [['id' => $clientId, 'path' => ['Building', 'Floor']]];
    }
}
class ClientContext
{
    public static function isLoggedIn(): bool { return true; }
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
class ModuleService
{
    public static bool $active = true;
    public static function isActiveForClient(int $client, string $module): bool { return self::$active; }
}
class TranslationService
{
    public static function translate(string $key, string $fallback = ''): string { return $fallback; }
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
$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$source = file_get_contents(__DIR__ . '/../src/Services/EmploymentContractService.php');
eval(substr($source, strpos($source, 'class EmploymentContractService')));
$router = file_get_contents(__DIR__ . '/../public/index.php');
foreach (['handle_panel_employment_contracts', 'handle_panel_employment_contracts_list_settings',
    'require_employment_module', 'require_employment_manager'] as $function) {
    $start = strpos($router, 'function ' . $function . '(');
    $end = strpos($router, "\nfunction ", $start + 1);
    eval(substr($router, $start, $end - $start));
}
$pdo = db();
$pdo->exec("CREATE TABLE employment_contract_list_settings (client_id INTEGER PRIMARY KEY, page_size INTEGER);
    CREATE TABLE system_users (id INTEGER PRIMARY KEY, client_id INTEGER, username TEXT, full_name TEXT);
    CREATE TABLE property_nodes (id INTEGER PRIMARY KEY, client_id INTEGER, name TEXT, node_type TEXT);
    CREATE TABLE employment_job_titles (id INTEGER PRIMARY KEY, client_id INTEGER, name TEXT);
    CREATE TABLE employment_workloads (id INTEGER PRIMARY KEY, client_id INTEGER, name TEXT);
    CREATE TABLE employment_departments (id INTEGER PRIMARY KEY, client_id INTEGER, name TEXT);
    CREATE TABLE employee_schedule_entries (id INTEGER PRIMARY KEY, client_id INTEGER, contract_id INTEGER);
    CREATE TABLE employment_contracts (id INTEGER PRIMARY KEY, client_id INTEGER, employee_id INTEGER,
        property_node_id INTEGER, job_title_id INTEGER, workload_id INTEGER, department_id INTEGER,
        manager_user_id INTEGER, contract_type TEXT, start_date TEXT, end_date TEXT, workload_percent INTEGER, archived_at TEXT);
    INSERT INTO property_nodes VALUES (1, 1, 'Floor', 'floor'), (2, 2, 'Other Floor', 'floor');
    INSERT INTO employment_job_titles VALUES (1, 1, 'Nurse'), (2, 2, 'Other title');
    INSERT INTO employment_workloads VALUES (1, 1, 'Full time'), (2, 2, 'Other workload');");
$userInsert = $pdo->prepare('INSERT INTO system_users VALUES (?, ?, ?, ?)');
$contractInsert = $pdo->prepare("INSERT INTO employment_contracts VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, '2020-01-01', ?, 100, ?)");
for ($id = 1; $id <= 25; $id++) {
    $userInsert->execute([$id, 1, sprintf('user%02d', $id), 'Employee ' . $id]);
    $contractInsert->execute([$id, 1, $id, 1, 1, 1, 'primary', null, null]);
}
$userInsert->execute([26, 2, 'other', 'Employee Other']);
$contractInsert->execute([26, 2, 26, 2, 2, 2, 'primary', null, null]);
$userInsert->execute([27, 1, 'literal', '100%_! Name']);
$contractInsert->execute([27, 1, 27, 1, 1, 1, 'primary', null, null]);
$contractInsert->execute([28, 1, 1, 1, 1, 1, 'temporary', '2020-01-02', '2020-01-03 12:00:00']);
$pdo->exec("INSERT INTO employee_schedule_entries VALUES (1, 1, 1)");

check(EmploymentContractService::listPageSize(1) === 10, 'Default page size must be ten.');
$first = EmploymentContractService::listPage(1, null, '', 1);
$last = EmploymentContractService::listPage(1, null, '', 3);
check($first['total'] === 27 && $first['pages'] === 3 && count($first['contracts']) === 10, 'Count must be contracts, including multiple contracts per employee, scoped to the client.');
check(count($last['contracts']) === 7, 'Last page must contain the exact remainder.');
check(array_intersect(array_column($first['contracts'], 'id'), array_column($last['contracts'], 'id')) === [], 'Pages must not overlap.');
check(EmploymentContractService::listPage(1, null, '', 99)['page'] === 3, 'Page beyond end must clamp.');
check(count(EmploymentContractService::listForClient(1)) === 27, 'Existing unpaginated API must remain unchanged.');
$contracts = EmploymentContractService::listForClient(1, 1);
check(count($contracts) === 2 && in_array('archived', array_column($contracts, 'period_status'), true), 'Archived contracts and multiple contracts must remain visible.');
check($contracts[0]['property_path'] === 'Building - Floor', 'Full property paths must remain intact.');
check((int) $contracts[0]['has_schedule_entries'] === 0 && (int) $contracts[1]['has_schedule_entries'] === 1, 'Schedule/archive action flags must be preserved.');
$search = EmploymentContractService::listPage(1, null, 'Employee 1', 1);
check($search['total'] === 12 && $search['pages'] === 2 && count($search['contracts']) === 10, 'Name substring search must paginate all matching contracts.');
check(EmploymentContractService::listPage(1, null, '%_!', 1)['total'] === 1, 'LIKE wildcard characters must be literal.');
check(EmploymentContractService::listPage(1, 1, 'Employee', 1)['total'] === 2, 'Self-service count must only include own contracts.');
check(EmploymentContractService::listPage(1, 1, 'Employee 2', 1)['total'] === 0, 'Name search must not bypass self scope.');
$empty = EmploymentContractService::listPage(1, null, 'missing', 9);
check($empty['total'] === 0 && $empty['page'] === 1 && $empty['pages'] === 1 && $empty['contracts'] === [], 'Empty results must have the required shape.');
EmploymentContractService::saveListPageSize(1, 5, 1);
check(EmploymentContractService::listPageSize(1) === 5 && EmploymentContractService::listPageSize(2) === 10, 'Settings must persist only for the chosen client.');
check(EmploymentContractService::listPage(1, null, '', 1)['pages'] === 6, 'Full list must use saved setting.');
check(EmploymentContractService::listPage(1, null, 'Employee 1', 1)['pages'] === 3, 'Search must use the same saved setting.');
check(AuditLogService::$events[0][2] === 'employment.contracts.page_size_updated', 'Settings change must be audited.');
foreach ([0, 101] as $invalid) {
    try {
        EmploymentContractService::saveListPageSize(1, $invalid, 1);
        throw new RuntimeException('Invalid size accepted.');
    } catch (InvalidArgumentException $exception) {
        check(EmploymentContractService::listPageSize(1) === 5, 'Invalid size must not persist.');
    }
}
$pdo->exec('UPDATE employment_contract_list_settings SET page_size = 0 WHERE client_id = 1');
try {
    EmploymentContractService::listPageSize(1);
    throw new LogicException('Corrupt stored size must fail explicitly.');
} catch (RuntimeException $exception) {
    check($exception->getMessage() === 'Invalid stored contract list page size.', 'Stored size validation must prevent division by zero.');
}
$pdo->exec('UPDATE employment_contract_list_settings SET page_size = 5 WHERE client_id = 1');
$_GET = ['search' => 'Employee 1', 'page' => '2'];
ob_start();
handle_panel_employment_contracts();
$html = ob_get_clean();
check(substr_count($html, 'class="page-link"') === 3, 'UI must render all page numbers.');
check(substr_count($html, '<ul class="pagination flex-wrap">') === 1
    && strpos($html, '<ul class="pagination flex-wrap">') > strpos($html, '</table>'),
    'Pagination must render exactly once, below the whole table, never inside contract rows.');
check(str_contains($html, 'search=Employee+1&amp;page=3') && str_contains($html, 'aria-current="page"'), 'Pagination must retain the filter and mark current page.');
check(str_contains($html, 'name="page_size"'), 'Admin must see settings form.');
RoleService::$canManage = false;
ob_start();
handle_panel_employment_contracts();
$html = ob_get_clean();
check(!str_contains($html, 'name="page_size"') && str_contains($html, 'name="search"'), 'Viewer must have search without settings.');
RoleService::$selfOnly = true;
$_GET = ['employee_id' => 2, 'client_id' => 2];
ob_start();
handle_panel_employment_contracts();
$html = ob_get_clean();
check(str_contains($html, 'Results: <span data-no-translate>2</span>') && !str_contains($html, '>Employee 2<'), 'Handler must ignore tampered client/employee inputs for self-service.');
$_POST = ['page_size' => '20', 'csrf_token' => 'valid'];
http_response_code(200);
ob_start();
handle_panel_employment_contracts_list_settings('POST');
ob_end_clean();
check(http_response_code() === 403 && EmploymentContractService::listPageSize(1) === 5, 'Non-admin cannot write settings.');
RoleService::$canManage = true;
RoleService::$selfOnly = false;
$_GET = ['search' => '"><script>alert(1)</script>'];
ob_start();
handle_panel_employment_contracts();
$html = ob_get_clean();
check(!str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;') && str_contains($html, 'No contracts found.'), 'Empty results and HTML escaping must be correct.');
foreach ([['page' => '0'], ['page' => ['1']], ['search' => ['name']], ['search' => str_repeat('a', 192)]] as $invalid) {
    $_GET = $invalid;
    http_response_code(200);
    ob_start();
    handle_panel_employment_contracts();
    ob_end_clean();
    check(http_response_code() === 400, 'Malformed list query must return 400.');
}
foreach (['invalid', ['valid']] as $invalidToken) {
    $_POST = ['page_size' => '20', 'csrf_token' => $invalidToken];
    http_response_code(200);
    ob_start();
    handle_panel_employment_contracts_list_settings('POST');
    ob_end_clean();
    check(http_response_code() === 403 && EmploymentContractService::listPageSize(1) === 5, 'Invalid CSRF must not write.');
}
foreach (['0', '101', '1.5', ['10']] as $invalidSize) {
    $_POST = ['page_size' => $invalidSize, 'csrf_token' => 'valid'];
    handle_panel_employment_contracts_list_settings('POST');
    check(EmploymentContractService::listPageSize(1) === 5, 'Invalid settings input must not write.');
}
http_response_code(200);
ob_start();
handle_panel_employment_contracts_list_settings('GET');
ob_end_clean();
check(http_response_code() === 405, 'Settings route must require POST.');
foreach ([1, 100, 10] as $validSize) {
    $_POST = ['page_size' => (string) $validSize, 'csrf_token' => 'valid', 'client_id' => 2];
    handle_panel_employment_contracts_list_settings('POST');
    check(EmploymentContractService::listPageSize(1) === $validSize && EmploymentContractService::listPageSize(2) === 10, 'POST must save the exact value only for the session client.');
}
ModuleService::$active = false;
$_GET = [];
http_response_code(200);
ob_start();
handle_panel_employment_contracts();
ob_end_clean();
check(http_response_code() === 403, 'Inactive module must block the list.');
http_response_code(200);
$_POST = ['page_size' => '20', 'csrf_token' => 'valid'];
ob_start();
handle_panel_employment_contracts_list_settings('POST');
ob_end_clean();
check(http_response_code() === 403 && EmploymentContractService::listPageSize(1) === 10, 'Inactive module must block settings writes.');

$installer = file_get_contents(__DIR__ . '/../src/Services/SchemaInstaller.php');
eval(substr($installer, strpos($installer, 'class SchemaInstaller')));
$splitter = new ReflectionMethod(SchemaInstaller::class, 'splitStatements');
$sql = file_get_contents(__DIR__ . '/../sql/084_contract_list_pagination.sql');
$statements = array_values(array_filter($splitter->invoke(null, $sql)));
check(count($statements) === 4 && str_ends_with($statements[3], 'NOW())'), 'Migration must survive the actual SQL splitter.');
check(str_contains($installer, "/084_contract_list_pagination.sql'"), 'Migration must be registered.');
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
check((int) $pdo->query('SELECT COUNT(*) FROM system_translation_values')->fetchColumn() === 15, 'All five labels must have en/et/ru translations without duplicates.');
check((int) $pdo->query("SELECT COUNT(*) FROM system_translation_values WHERE value IS NULL OR value = ''")->fetchColumn() === 0, 'Translations must not be empty.');
check((int) $pdo->query("SELECT COUNT(*) FROM system_version_logs WHERE version = '1.97'")->fetchColumn() === 1, 'Version insertion must be idempotent.');
echo "PASS: {$checks} contract pagination, search, settings, access, rendering and migration checks.\n";
