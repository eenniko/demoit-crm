<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Services/SchemaInstaller.php';

class ClientContext
{
    public static function userId(): int { return 7; }
}

class ClientService
{
    public static function find(int $clientId): ?array
    {
        return $clientId === 12 ? [
            'id' => 12,
            'company_name' => 'Clinic <script>',
            'client_code' => 'C12',
            'status' => 'active',
            'registry_code' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
        ] : null;
    }
}

class UserService
{
    public static array $listedClientIds = [];
    public static array $updated = [];

    public static function listClientAdministrators(int $clientId): array
    {
        self::$listedClientIds[] = $clientId;

        return [[
            'id' => 9,
            'username' => 'clinic-admin',
            'full_name' => 'Admin <script>',
            'email' => 'admin@example.invalid',
            'phone' => null,
            'status' => 'active',
        ]];
    }

    public static function findClientAdministrator(int $clientId, int $userId): ?array
    {
        if ($clientId !== 12 || $userId !== 9) {
            return null;
        }

        return [
            'id' => 9,
            'username' => 'clinic-admin',
            'full_name' => 'Admin <script>',
            'email' => 'admin@example.invalid',
            'phone' => '555-0100',
            'status' => 'active',
        ];
    }

    public static function updateClientAdministrator(int $clientId, int $userId, array $data, int $actorId): array
    {
        self::$updated[] = [$clientId, $userId];
        return [true, 'Updated'];
    }

    public static function createClientAdministrator(int $clientId, array $data, int $actorId): array
    {
        self::$updated[] = [$clientId, 0];
        return [true, 'Created', 'temporary-password'];
    }
}

class ModuleService
{
    public static function listForClient(int $clientId): array
    {
        return [];
    }
}

class SupportService
{
    public static function unreadCount(int $clientId, int $userId, bool $systemWorkspace = false): int
    {
        return 0;
    }
}

function require_system_role(): bool { return true; }
function render_page(string $title, string $view): void { echo $view; }
function render_admin_page(string $title, string $view, array $vars = [], string $activeMenu = ''): void
{
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/../views/admin/' . $view;
}
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function t(string $key, string $fallback = ''): string { return $fallback; }
class Csrf
{
    public static function field(): string { return '<input type="hidden" name="csrf_token" value="test">'; }
    public static function validate(?string $token): bool { return $token === 'valid'; }
}
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$router = file_get_contents(__DIR__ . '/../public/index.php');
$start = strpos($router, 'function handle_admin_clients_view(): void');
$viewEnd = strpos($router, "\nfunction handle_admin_client_admin_create(", $start);
$handlersEnd = strpos($router, "\nfunction handle_admin_clients_reset_password(", $viewEnd);
if ($start === false || $viewEnd === false || $handlersEnd === false) {
    throw new RuntimeException('Cannot find client details handler.');
}
eval(substr($router, $start, $handlersEnd - $start));

$_GET = ['id' => '12'];
$_SESSION = [];
ob_start();
handle_admin_clients_view();
$html = ob_get_clean();

check(UserService::$listedClientIds === [12], 'Client details must load administrators scoped to the selected client.');
check(str_contains($html, 'Client administrators'), 'Administrator section heading is missing.');
check(str_contains($html, 'clinic-admin') && str_contains($html, 'admin@example.invalid'), 'Administrator row is missing.');
check(str_contains($html, 'Add administrator') && str_contains($html, '/admin/clients/users/create?client_id=12'), 'Add administrator action is missing or not client-scoped.');
check(str_contains($html, '/admin/clients/users/edit?client_id=12&amp;user_id=9'), 'Edit action is missing or not client-scoped.');
check(!str_contains($html, '<script>'), 'Client or user names must be escaped.');

$serviceSource = file_get_contents(__DIR__ . '/../src/Services/UserService.php');
$listStart = strpos($serviceSource, 'public static function listClientAdministrators');
$listEnd = strpos($serviceSource, "\n    public static function findClientAdministrator", $listStart);
$listQuery = $listStart !== false && $listEnd !== false ? substr($serviceSource, $listStart, $listEnd - $listStart) : '';
check(str_contains($listQuery, "r.role_key = 'client_admin'") && str_contains($listQuery, "ur.status = 'active'"), 'Administrator query must filter active client_admin role assignments.');
check(str_contains($listQuery, 'WHERE u.client_id = :client_id'), 'Administrator query must be tenant-scoped.');

$_GET = ['client_id' => '12'];
http_response_code(200);
ob_start();
handle_admin_client_admin_create('GET');
$html = ob_get_clean();
check(str_contains($html, 'name="client_id" value="12"') && str_contains($html, 'Create administrator'), 'Create form is not scoped to selected client.');

$_GET = ['client_id' => '12', 'user_id' => '9'];
http_response_code(200);
ob_start();
handle_admin_client_admin_edit('GET');
$html = ob_get_clean();
check(str_contains($html, 'name="user_id" value="9"') && str_contains($html, 'value="clinic-admin" disabled'), 'Edit form must keep username fixed and bind selected user.');
check(!str_contains($html, '<script>'), 'Edit form must escape user data.');

$_GET = ['client_id' => '99', 'user_id' => '9'];
http_response_code(200);
ob_start();
handle_admin_client_admin_edit('GET');
$html = ob_get_clean();
check(http_response_code() === 404, 'Cross-client administrator edit must be rejected.');

$_GET = [];
$_POST = ['client_id' => '12', 'user_id' => '9', 'csrf_token' => 'invalid'];
http_response_code(200);
ob_start();
handle_admin_client_admin_edit('POST');
$html = ob_get_clean();
check(UserService::$updated === [], 'Invalid CSRF must not update an administrator.');
check(str_contains($html, 'Invalid session token'), 'Invalid CSRF error should be visible.');

$migration = file_get_contents(__DIR__ . '/../sql/082_client_administrator_management.sql');
check($migration !== false, 'Cannot read administrator management migration.');
$splitter = new ReflectionMethod(SchemaInstaller::class, 'splitStatements');
$statements = array_values(array_filter(
    $splitter->invoke(null, $migration),
    static fn (string $statement): bool => $statement !== ''
));
check(count($statements) === 3, 'Administrator migration must contain exactly three complete statements.');
$versionInsert = substr($migration, strrpos($migration, 'INSERT IGNORE INTO system_version_logs'));
check($statements[2] === rtrim(trim($versionInsert), ';'), 'Installer must preserve the complete version INSERT without splitting SQL strings.');

echo "PASS: client details use client-scoped administrator list and provide add/edit actions with escaped values.\n";
