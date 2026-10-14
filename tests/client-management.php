<?php

declare(strict_types=1);

class ClientContext
{
    public static function isLoggedIn(): bool { return true; }
    public static function clientId(): int { return 1; }
    public static function userId(): int { return 2; }
}

class RoleService
{
    public static string $role = 'level_f';

    public static function hasAnyRole(int $userId, int $clientId, array $roles): bool
    {
        return in_array(self::$role, $roles, true);
    }
}

class ClientService
{
    public static int $updates = 0;

    public static function find(int $clientId): array
    {
        return [
            'company_name' => '<script>unsafe</script>',
            'address' => 'Test address',
            'phone' => null,
            'email' => 'test@example.invalid',
            'contact_person_name' => 'Test Representative',
            'contact_person_phone' => null,
            'contact_person_email' => null,
        ];
    }

    public static function updateOwnDetails(int $clientId, array $data, int $actorUserId): array
    {
        self::$updates++;

        return [true, 'Client details updated.'];
    }
}

class Csrf
{
    public static function field(): string { return '<input type="hidden" name="csrf_token" value="test-token">'; }
    public static function validate(?string $token): bool { return $token === 'test-token'; }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function render_page(string $title, string $contentView, array $vars = []): void
{
    echo $contentView;
}

function render_panel_page(string $title, string $contentView, array $vars = [], string $activeMenu = ''): void
{
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/../views/panel/' . $contentView;
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$router = file_get_contents(__DIR__ . '/../public/index.php');
if ($router === false) {
    throw new RuntimeException('Cannot read the route handler.');
}
$start = strpos($router, 'function handle_panel_client(string $method): void');
$end = strpos($router, 'function handle_admin_notifications(', $start === false ? 0 : $start);
if ($start === false || $end === false) {
    throw new RuntimeException('Cannot locate the client-management handler.');
}
eval(substr($router, $start, $end - $start));

foreach (['level_f', 'viewer', 'client_admin'] as $role) {
    RoleService::$role = $role;
    http_response_code(200);
    ob_start();
    handle_panel_client('GET');
    $html = ob_get_clean();
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($document);
    $isAdmin = $role === 'client_admin';
    check(http_response_code() === 200, $role . ': view must remain accessible.');
    check($xpath->query('//form')->length === ($isAdmin ? 1 : 0), $role . ': unexpected form.');
    check($xpath->query('//input')->length === ($isAdmin ? 8 : 0), $role . ': unexpected editable fields.');
    check($xpath->query('//button[@type="submit"]')->length === ($isAdmin ? 1 : 0), $role . ': unexpected save button.');
    check($xpath->query('//script')->length === 0, $role . ': client values must be escaped.');
    check(str_contains($html, 'Test Representative'), $role . ': representative must remain visible.');
    $_POST = ['csrf_token' => 'test-token', 'company_name' => 'Changed'];
    ob_start();
    handle_panel_client('POST');
    $result = ob_get_clean();
    check(http_response_code() === ($isAdmin ? 200 : 403), $role . ': unexpected POST status.');
    check(ClientService::$updates === ($isAdmin ? 1 : 0), $role . ': unauthorized update.');
    if (!$isAdmin) {
        check($result === 'errors/403.php', $role . ': forbidden response missing.');
    }
}

$_POST = ['csrf_token' => 'invalid', 'company_name' => 'Changed'];
http_response_code(200);
ob_start();
handle_panel_client('POST');
$html = ob_get_clean();
check(ClientService::$updates === 1, 'Invalid CSRF must not update the client.');
check(str_contains($html, 'Invalid session token'), 'Invalid CSRF error must be visible.');

echo "PASS: client-management GET/POST for Level F, viewer and client administrator, HTML escaping and CSRF.\n";
