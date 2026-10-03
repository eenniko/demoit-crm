<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Support/ClientContext.php';
require_once __DIR__ . '/../src/Support/Csrf.php';
require_once __DIR__ . '/../src/Services/SchemaInstaller.php';
require_once __DIR__ . '/../src/Services/AuditLogService.php';
require_once __DIR__ . '/../src/Services/InitialSetupService.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Services/RoleService.php';
require_once __DIR__ . '/../src/Services/ClientService.php';
require_once __DIR__ . '/../src/Services/ModuleService.php';
require_once __DIR__ . '/../src/Services/LanguageService.php';
require_once __DIR__ . '/../src/Services/TranslationService.php';
require_once __DIR__ . '/../src/Services/UserService.php';
require_once __DIR__ . '/../src/Services/LegacyEmployeeImportService.php';
require_once __DIR__ . '/../src/Services/OrgUnitService.php';
require_once __DIR__ . '/../src/Services/SubstituteService.php';
require_once __DIR__ . '/../src/Services/SettingsService.php';
require_once __DIR__ . '/../src/Services/NotificationService.php';
require_once __DIR__ . '/../src/Services/ReportService.php';
require_once __DIR__ . '/../src/Services/SupportService.php';
require_once __DIR__ . '/../src/Services/PatientService.php';
require_once __DIR__ . '/../src/Services/PropertyService.php';
require_once __DIR__ . '/../src/Services/EmploymentCatalogService.php';
require_once __DIR__ . '/../src/Services/EmploymentContractService.php';
require_once __DIR__ . '/../src/Services/ScheduleService.php';
require_once __DIR__ . '/../views/render.php';
require_once __DIR__ . '/../views/admin/render.php';
require_once __DIR__ . '/../views/panel/render.php';

const ADMIN_SYSTEM_ROLES = ['system_admin', 'developer', 'client_manager', 'client_support'];

try {
    ClientContext::start();

    // Auto-installs sql/*.sql on the very first request, so no manual phpMyAdmin step is required.
    SchemaInstaller::ensureInstalled();

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = rtrim($path, '/');
    if ($path === '') {
        $path = '/';
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Force the one-time initial setup flow until it has been completed.
    if (!InitialSetupService::isCompleted() && $path !== '/setup') {
        header('Location: /setup');
        exit;
    }

    if (InitialSetupService::isCompleted() && $path === '/setup') {
        header('Location: /');
        exit;
    }

switch (true) {
    case $path === '/setup':
        handle_setup($method);
        break;

    case $path === '/login':
        handle_login($method);
        break;

    case $path === '/logout':
        AuthService::logout();
        header('Location: /');
        break;

    case $path === '/admin':
        handle_admin_dashboard();
        break;

    case $path === '/admin/clients':
        handle_admin_clients_index();
        break;

    case $path === '/admin/clients/create':
        handle_admin_clients_create($method);
        break;

    case $path === '/admin/clients/view':
        handle_admin_clients_view();
        break;

    case $path === '/admin/clients/modules/toggle':
        handle_admin_clients_modules_toggle($method);
        break;

    case $path === '/admin/clients/users/reset-password':
        handle_admin_clients_reset_password($method);
        break;

    case $path === '/admin/modules':
        handle_admin_modules_index();
        break;

    case $path === '/admin/modules/create':
        handle_admin_modules_create($method);
        break;

    case $path === '/admin/modules/toggle':
        handle_admin_modules_toggle($method);
        break;

    case $path === '/admin/languages':
        handle_admin_languages_index();
        break;

    case $path === '/admin/languages/create':
        handle_admin_languages_create($method);
        break;

    case $path === '/admin/languages/toggle':
        handle_admin_languages_toggle($method);
        break;

    case $path === '/admin/translations':
        handle_admin_translations_index();
        break;

    case $path === '/admin/translations/create':
        handle_admin_translations_create($method);
        break;

    case $path === '/admin/translations/update':
        handle_admin_translations_update($method);
        break;

    case $path === '/admin/translations/missing':
        handle_admin_translations_missing();
        break;

    case $path === '/admin/logs':
        handle_admin_logs();
        break;

    case $path === '/admin/settings':
        handle_admin_settings($method);
        break;

    case $path === '/admin/roles':
        handle_admin_roles();
        break;

    case $path === '/admin/roles/update':
        handle_admin_roles_update($method);
        break;

    case $path === '/admin/notifications':
        handle_admin_notifications($method);
        break;

    case $path === '/admin/support':
        handle_admin_support();
        break;

    case $path === '/admin/support/status':
        handle_admin_support_status($method);
        break;

    case $path === '/panel':
        handle_panel_dashboard();
        break;

    case $path === '/panel/modules':
        handle_panel_modules();
        break;

    case $path === '/panel/users':
        handle_panel_users();
        break;

    case $path === '/panel/users/create':
        handle_panel_users_create($method);
        break;

    case $path === '/panel/users/edit':
        handle_panel_users_edit($method);
        break;

    case $path === '/panel/users/import':
        handle_panel_users_import($method);
        break;

    case $path === '/panel/users/toggle':
        handle_panel_users_toggle($method);
        break;

    case $path === '/panel/users/reset-password':
        handle_panel_users_reset_password($method);
        break;

    case $path === '/panel/account':
        handle_panel_account();
        break;

    case $path === '/panel/account/password':
        handle_panel_account_password($method);
        break;

    case $path === '/panel/org':
        handle_panel_org_index();
        break;

    case $path === '/panel/org/create':
        handle_panel_org_create($method);
        break;

    case $path === '/panel/substitutes':
        handle_panel_substitutes_index();
        break;

    case $path === '/panel/substitutes/create':
        handle_panel_substitutes_create($method);
        break;

    case $path === '/panel/substitutes/approve':
        handle_panel_substitutes_decision($method, true);
        break;

    case $path === '/panel/substitutes/reject':
        handle_panel_substitutes_decision($method, false);
        break;

    case $path === '/panel/client':
        handle_panel_client($method);
        break;

    case $path === '/panel/notifications':
        handle_panel_notifications();
        break;

    case $path === '/panel/notifications/read':
        handle_panel_notifications_read($method);
        break;

    case $path === '/panel/reports':
        handle_panel_reports();
        break;

    case $path === '/panel/support':
        handle_panel_support();
        break;

    case $path === '/panel/support/create':
        handle_panel_support_create($method);
        break;

    case $path === '/panel/patients':
        handle_panel_patients_index();
        break;

    case $path === '/panel/patients/create':
        handle_panel_patients_create($method);
        break;

    case $path === '/panel/patients/toggle':
        handle_panel_patients_toggle($method);
        break;

    case $path === '/panel/property':
        handle_panel_property_index();
        break;

    case $path === '/panel/property/create':
        handle_panel_property_create($method);
        break;

    case $path === '/panel/property/edit':
        handle_panel_property_edit($method);
        break;

    case $path === '/panel/property/reorder':
        handle_panel_property_reorder($method);
        break;

    case $path === '/panel/schedule':
        handle_panel_schedule($method);
        break;

    case $path === '/panel/schedule/templates':
        handle_panel_schedule_templates($method);
        break;

    case $path === '/panel/employment':
        header('Location: /panel/employment/contracts');
        break;

    case $path === '/panel/employment/contracts':
        handle_panel_employment_contracts();
        break;

    case $path === '/panel/employment/contracts/create':
        handle_panel_employment_contract_form($method, null);
        break;

    case $path === '/panel/employment/contracts/edit':
        $contractId = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($contractId === false || $contractId <= 0) {
            http_response_code(404);
            render_page('Not found', 'errors/404.php');
            break;
        }
        handle_panel_employment_contract_form($method, $contractId);
        break;

    case $path === '/panel/employment/titles':
        handle_panel_employment_catalog($method, 'titles');
        break;

    case $path === '/panel/employment/departments':
        handle_panel_employment_catalog($method, 'departments');
        break;

    case $path === '/panel/employment/workloads':
        handle_panel_employment_catalog($method, 'workloads');
        break;

    case $path === '/':
        render_page('DemoIT CRM', 'home.php');
        break;

    default:
        http_response_code(404);
        render_page('Not found', 'errors/404.php');
        break;
}
} catch (Throwable $e) {
    http_response_code(500);
    $debug = (getenv('APP_DEBUG') === '1') ? ($e->getMessage() . "\n" . $e->getTraceAsString()) : null;
    render_page('Error', 'errors/500.php', ['debug' => $debug]);
    exit;
}

function require_system_role(): bool
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return false;
    }

    if (!RoleService::hasAnyRole(ClientContext::userId(), ClientContext::clientId(), ADMIN_SYSTEM_ROLES)) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');

        return false;
    }

    return true;
}

function handle_admin_dashboard(): void
{
    if (!require_system_role()) {
        return;
    }

    render_admin_page('Dashboard', 'dashboard.php', [], 'dashboard');
}

function handle_admin_clients_index(): void
{
    if (!require_system_role()) {
        return;
    }

    render_admin_page('Client list', 'clients/index.php', ['clients' => ClientService::listActive()], 'clients');
}

function handle_admin_clients_create(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    $error = null;
    $old = [];
    $tempPassword = null;

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = $_POST;
            [$success, $message, $tempPassword] = ClientService::createClient($_POST, ClientContext::userId());

            if ($success) {
                $old = [];
            } else {
                $error = $message;
            }
        }
    }

    render_admin_page('Add client', 'clients/create.php', [
        'error' => $error,
        'old' => $old,
        'tempPassword' => $tempPassword,
    ], 'clients_create');
}

function handle_admin_clients_view(): void
{
    if (!require_system_role()) {
        return;
    }

    $clientId = (int) ($_GET['id'] ?? 0);
    $client = ClientService::find($clientId);

    if ($client === null) {
        http_response_code(404);
        render_page('Not found', 'errors/404.php');

        return;
    }

    render_admin_page('Client details', 'clients/view.php', [
        'client' => $client,
        'users' => UserService::listForClient($clientId),
        'modules' => ModuleService::listForClient($clientId),
        'tempPassword' => $_GET['temp_password'] ?? null,
        'message' => $_GET['message'] ?? null,
        'error' => $_GET['error'] ?? null,
    ], 'clients');
}

function handle_admin_clients_reset_password(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    $clientId = (int) ($_POST['client_id'] ?? 0);
    $tempPassword = null;

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        [, , $tempPassword] = UserService::resetPassword($clientId, (int) ($_POST['user_id'] ?? 0), ClientContext::userId());
    }

    $query = $tempPassword !== null ? '&temp_password=' . rawurlencode((string) $tempPassword) : '';
    header('Location: /admin/clients/view?id=' . $clientId . $query);
}

function handle_admin_clients_modules_toggle(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    $clientId = (int) ($_POST['client_id'] ?? 0);
    $message = null;
    $error = null;

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        [$success, $resultMessage] = ModuleService::toggleClientActivation(
            $clientId,
            (int) ($_POST['module_id'] ?? 0),
            ClientContext::userId()
        );
        $message = $success ? $resultMessage : null;
        $error = $success ? null : $resultMessage;
    } elseif ($method === 'POST') {
        $error = 'Invalid session token, please try again.';
    }

    $query = $message !== null ? '&message=' . rawurlencode($message) : ($error !== null ? '&error=' . rawurlencode($error) : '');
    header('Location: /admin/clients/view?id=' . $clientId . $query);
}

function handle_panel_dashboard(): void
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return;
    }

    render_panel_page('Dashboard', 'dashboard.php', [], 'dashboard');
}

function handle_panel_modules(): void
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return;
    }

    render_panel_page('Active modules', 'modules/index.php', [
        'modules' => ModuleService::listActiveForClient(ClientContext::clientId()),
    ], 'modules');
}

function handle_panel_account(): void
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return;
    }

    render_panel_page('My account', 'account/index.php', [
        'message' => $_GET['message'] ?? null,
        'error' => $_GET['error'] ?? null,
    ], 'account');
}

function handle_panel_account_password(string $method): void
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return;
    }

    $message = null;
    $error = null;

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } elseif ((string) ($_POST['new_password'] ?? '') !== (string) ($_POST['new_password_confirm'] ?? '')) {
            $error = 'New password and confirmation do not match.';
        } else {
            [$success, $resultMessage] = UserService::changeOwnPassword(
                ClientContext::userId(),
                (string) ($_POST['current_password'] ?? ''),
                (string) ($_POST['new_password'] ?? '')
            );

            if ($success) {
                $message = $resultMessage;
            } else {
                $error = $resultMessage;
            }
        }
    }

    $query = $message !== null ? '?message=' . rawurlencode($message) : ($error !== null ? '?error=' . rawurlencode($error) : '');
    header('Location: /panel/account' . $query);
}

function handle_panel_users(): void
{
    if (!require_employee_module()) {
        return;
    }

    $clientId = ClientContext::clientId();

    render_panel_page('Users & permissions', 'users/index.php', [
        'users' => UserService::listForClient($clientId),
        'canManage' => RoleService::hasAnyRole(ClientContext::userId(), $clientId, ['client_admin']),
        'currentUserId' => ClientContext::userId(),
        'message' => $_GET['message'] ?? null,
        'error' => $_GET['error'] ?? null,
    ], 'users');
}

function require_employee_module(): bool
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');
        return false;
    }

    if (!ModuleService::isActiveForClient(ClientContext::clientId(), 'employees')) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');
        return false;
    }

    return true;
}

function require_panel_manager(): bool
{
    if (!require_employee_module()) {
        return false;
    }

    if (!RoleService::hasAnyRole(ClientContext::userId(), ClientContext::clientId(), ['client_admin'])) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');

        return false;
    }

    return true;
}

function handle_panel_users_create(string $method): void
{
    if (!require_panel_manager()) {
        return;
    }

    $error = null;
    $old = [];

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = $_POST;
            [$success, $message] = UserService::createForClient(ClientContext::clientId(), $_POST, ClientContext::userId());

            if ($success) {
                header('Location: /panel/users?message=' . rawurlencode($message));
                exit;
            }

            $error = $message;
        }
    }

    render_panel_page('Add user', 'users/create.php', [
        'error' => $error,
        'old' => $old,
        'roles' => RoleService::listAssignableClientRoles(),
    ], 'users');
}

function handle_panel_users_edit(string $method): void
{
    if (!require_panel_manager()) {
        return;
    }

    $userId = (int) ($_GET['id'] ?? $_POST['user_id'] ?? 0);
    $employee = UserService::findForClient(ClientContext::clientId(), $userId);
    if ($employee === null) {
        http_response_code(404);
        render_page('Not found', 'errors/404.php');
        return;
    }

    $error = null;
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            [$success, $message] = UserService::updateForClient(ClientContext::clientId(), $userId, $_POST, ClientContext::userId());
            if ($success) {
                header('Location: /panel/users?message=' . rawurlencode($message));
                exit;
            }
            $error = $message;
            $employee = array_merge($employee, $_POST);
        }
    }

    render_panel_page('Edit employee', 'users/edit.php', [
        'error' => $error,
        'employee' => $employee,
        'roles' => RoleService::listAssignableClientRoles(),
    ], 'users');
}

function handle_panel_users_import(string $method): void
{
    if (!require_panel_manager()) {
        return;
    }

    $error = null;
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $file = $_FILES['legacy_sql'] ?? null;
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $error = legacy_upload_error((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE));
            } elseif ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
                $error = 'The SQL file must not exceed 5 MB.';
            } elseif (strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'sql') {
                $error = 'Select a .sql file.';
            } elseif (!is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
                $error = 'The uploaded file could not be verified.';
            } else {
                $sql = file_get_contents((string) $file['tmp_name']);
                if ($sql === false) {
                    $error = 'The uploaded file could not be read.';
                } else {
                    [$success, $message] = LegacyEmployeeImportService::importForClient(
                        ClientContext::clientId(),
                        $sql,
                        ClientContext::userId()
                    );
                    if ($success) {
                        header('Location: /panel/users?message=' . rawurlencode($message));
                        exit;
                    }
                    $error = $message;
                }
            }
        }
    }

    render_panel_page('Import legacy employees', 'users/import.php', [
        'error' => $error,
    ], 'users');
}

function legacy_upload_error(int $errorCode): string
{
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded SQL file is too large.',
        UPLOAD_ERR_PARTIAL => 'The SQL file was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Select a legacy SQL file.',
        default => 'The SQL file upload failed.',
    };
}

function handle_panel_users_toggle(string $method): void
{
    if (!require_panel_manager()) {
        return;
    }

    $message = null;
    $error = null;

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        [$success, $resultMessage] = UserService::toggleStatus(
            ClientContext::clientId(),
            (int) ($_POST['user_id'] ?? 0),
            ClientContext::userId()
        );

        if ($success) {
            $message = $resultMessage;
        } else {
            $error = $resultMessage;
        }
    }

    $query = $message !== null ? '?message=' . rawurlencode($message) : ($error !== null ? '?error=' . rawurlencode($error) : '');
    header('Location: /panel/users' . $query);
}

function handle_panel_users_reset_password(string $method): void
{
    if (!require_panel_manager()) {
        return;
    }

    $message = null;
    $error = null;

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        [$success, $resultMessage, $tempPassword] = UserService::resetPassword(
            ClientContext::clientId(),
            (int) ($_POST['user_id'] ?? 0),
            ClientContext::userId()
        );

        $message = $success ? $resultMessage . ($resultMessage === 'Password reset, but e-mail delivery failed.' ? " Temporary password: {$tempPassword}" : '') : null;
        $error = $success ? null : $resultMessage;
    }

    $query = $message !== null ? '?message=' . rawurlencode($message) : ($error !== null ? '?error=' . rawurlencode($error) : '');
    header('Location: /panel/users' . $query);
}

function require_organisation_module(): bool
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return false;
    }

    if (!ModuleService::isActiveForClient(ClientContext::clientId(), 'organisation')) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');

        return false;
    }

    return true;
}

function require_organisation_manager(): bool
{
    if (!require_organisation_module()) {
        return false;
    }

    if (!RoleService::hasAnyRole(ClientContext::userId(), ClientContext::clientId(), ['client_admin'])) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');

        return false;
    }

    return true;
}

function handle_panel_org_index(): void
{
    if (!require_organisation_module()) {
        return;
    }

    $clientId = ClientContext::clientId();

    render_panel_page('Organisation', 'org/index.php', [
        'units' => OrgUnitService::listForClient($clientId),
        'canManage' => RoleService::hasAnyRole(ClientContext::userId(), $clientId, ['client_admin']),
    ], 'org');
}

function handle_panel_org_create(string $method): void
{
    if (!require_organisation_manager()) {
        return;
    }

    $clientId = ClientContext::clientId();
    $error = null;
    $old = [];

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = $_POST;
            [$success, $message] = OrgUnitService::create($clientId, $_POST, ClientContext::userId());

            if ($success) {
                header('Location: /panel/org');
                exit;
            }

            $error = $message;
        }
    }

    render_panel_page('Add organisation unit', 'org/create.php', [
        'error' => $error,
        'old' => $old,
        'units' => OrgUnitService::listForClient($clientId),
        'users' => UserService::listForClient($clientId),
    ], 'org');
}

function handle_panel_substitutes_index(): void
{
    if (!require_organisation_module()) {
        return;
    }

    $clientId = ClientContext::clientId();

    render_panel_page('Substitutes', 'substitutes/index.php', [
        'substitutes' => SubstituteService::listForClient($clientId),
        'canApprove' => RoleService::hasAnyRole(ClientContext::userId(), $clientId, ['client_admin']),
        'message' => $_GET['message'] ?? null,
        'error' => $_GET['error'] ?? null,
    ], 'substitutes');
}

function handle_panel_substitutes_create(string $method): void
{
    if (!require_organisation_module()) {
        return;
    }

    $clientId = ClientContext::clientId();
    $error = null;
    $old = $_GET;
    $eligibleSubstitutes = [];

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = $_POST;
            [$success, $message] = SubstituteService::create($clientId, $_POST, ClientContext::userId());

            if ($success) {
                header('Location: /panel/substitutes?message=' . rawurlencode($message));
                exit;
            }

            $error = $message;
        }
    }

    if (!empty($old['original_user_id'])) {
        $eligibleSubstitutes = SubstituteService::eligibleSubstitutes($clientId, (int) $old['original_user_id']);
    }

    render_panel_page('Request substitute', 'substitutes/create.php', [
        'error' => $error,
        'old' => $old,
        'users' => UserService::listForClient($clientId),
        'eligibleSubstitutes' => $eligibleSubstitutes,
    ], 'substitutes');
}

function handle_panel_substitutes_decision(string $method, bool $approve): void
{
    if (!require_organisation_manager()) {
        return;
    }

    $clientId = ClientContext::clientId();
    $message = null;
    $error = null;

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        $id = (int) ($_POST['id'] ?? 0);
        [$success, $resultMessage] = $approve
            ? SubstituteService::approve($clientId, $id, ClientContext::userId())
            : SubstituteService::reject($clientId, $id, ClientContext::userId());

        if ($success) {
            $message = $resultMessage;
        } else {
            $error = $resultMessage;
        }
    }

    $query = $message !== null ? '?message=' . rawurlencode($message) : ($error !== null ? '?error=' . rawurlencode($error) : '');
    header('Location: /panel/substitutes' . $query);
}

function handle_admin_modules_index(): void
{
    if (!require_system_role()) {
        return;
    }

    render_admin_page('Modules', 'modules/index.php', ['modules' => ModuleService::listAll()], 'modules');
}

function handle_admin_modules_create(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    $error = null;
    $old = [];

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = $_POST;
            [$success, $message] = ModuleService::create($_POST, ClientContext::userId());

            if ($success) {
                header('Location: /admin/modules');
                exit;
            }

            $error = $message;
        }
    }

    render_admin_page('Add module', 'modules/create.php', ['error' => $error, 'old' => $old], 'modules_create');
}

function handle_admin_modules_toggle(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        ModuleService::toggleStatus((int) ($_POST['id'] ?? 0), ClientContext::userId());
    }

    header('Location: /admin/modules');
}

function handle_admin_languages_index(): void
{
    if (!require_system_role()) {
        return;
    }

    render_admin_page('Languages', 'languages/index.php', ['languages' => LanguageService::listAll()], 'languages');
}

function handle_admin_languages_create(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    $error = null;
    $old = [];

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = $_POST;
            [$success, $message] = LanguageService::create($_POST, ClientContext::userId());

            if ($success) {
                header('Location: /admin/languages');
                exit;
            }

            $error = $message;
        }
    }

    render_admin_page('Add language', 'languages/create.php', ['error' => $error, 'old' => $old], 'languages');
}

function handle_admin_languages_toggle(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    $error = null;

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        [, $error] = LanguageService::toggleActive((int) ($_POST['id'] ?? 0), ClientContext::userId());
    }

    header('Location: /admin/languages');
}

function selected_language_or_default(): array
{
    $languages = LanguageService::listAll();
    $languageId = (int) ($_GET['language'] ?? 0);

    foreach ($languages as $language) {
        if ((int) $language['id'] === $languageId) {
            return [$languages, $language];
        }
    }

    foreach ($languages as $language) {
        if ($language['language_code'] === 'en') {
            return [$languages, $language];
        }
    }

    return [$languages, $languages[0] ?? ['id' => 0, 'name' => 'English', 'language_code' => 'en']];
}

function handle_admin_translations_index(): void
{
    if (!require_system_role()) {
        return;
    }

    [$languages, $selectedLanguage] = selected_language_or_default();
    $rows = TranslationService::listForLanguage((int) $selectedLanguage['id']);

    render_admin_page('Translations', 'translations/index.php', [
        'languages' => $languages,
        'selectedLanguage' => $selectedLanguage,
        'rows' => $rows,
    ], 'translations');
}

function handle_admin_translations_create(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    $error = null;
    $old = [];

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = $_POST;
            [$success, $message] = TranslationService::createKey(
                (string) ($_POST['translation_key'] ?? ''),
                (string) ($_POST['module_context'] ?? ''),
                (string) ($_POST['english_value'] ?? ''),
                ClientContext::userId()
            );

            if ($success) {
                header('Location: /admin/translations');
                exit;
            }

            $error = $message;
        }
    }

    render_admin_page('Add translation key', 'translations/create.php', ['error' => $error, 'old' => $old], 'translations');
}

function handle_admin_translations_update(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        TranslationService::upsertValue(
            (int) ($_POST['key_id'] ?? 0),
            (int) ($_POST['language_id'] ?? 0),
            (string) ($_POST['value'] ?? ''),
            ClientContext::userId()
        );
    }

    header('Location: /admin/translations?language=' . (int) ($_POST['language_id'] ?? 0));
}

function handle_admin_translations_missing(): void
{
    if (!require_system_role()) {
        return;
    }

    [$languages, $selectedLanguage] = selected_language_or_default();
    $rows = TranslationService::missingForLanguage((int) $selectedLanguage['id']);

    render_admin_page('Missing translations', 'translations/missing.php', [
        'languages' => $languages,
        'selectedLanguage' => $selectedLanguage,
        'rows' => $rows,
    ], 'translations_missing');
}

function handle_admin_logs(): void
{
    if (!require_system_role()) {
        return;
    }

    $filters = [
        'action' => trim((string) ($_GET['action'] ?? '')),
        'client_code' => trim((string) ($_GET['client_code'] ?? '')),
        'username' => trim((string) ($_GET['username'] ?? '')),
    ];

    render_admin_page('System logs', 'logs/index.php', [
        'filters' => $filters,
        'logs' => AuditLogService::listRecent(array_filter($filters), 100),
    ], 'logs');
}

function handle_admin_settings(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    $error = null;
    $message = null;

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            SettingsService::set((string) ($_POST['setting_key'] ?? ''), (string) ($_POST['setting_value'] ?? ''), ClientContext::userId());
            $message = 'Setting saved.';
        }
    }

    render_admin_page('System settings', 'settings/index.php', [
        'settings' => SettingsService::all(),
        'error' => $error,
        'message' => $message,
    ], 'settings');
}

function handle_admin_roles(): void
{
    if (!require_system_role()) {
        return;
    }

    render_admin_page('Roles', 'roles/index.php', [
        'roles' => RoleService::listAll(),
        'message' => $_GET['message'] ?? null,
        'error' => $_GET['error'] ?? null,
    ], 'roles');
}

function handle_admin_roles_update(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    $message = null;
    $error = null;

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            [$success, $resultMessage] = RoleService::updateName(
                (int) ($_POST['role_id'] ?? 0),
                (string) ($_POST['name'] ?? ''),
                ClientContext::userId()
            );

            if ($success) {
                $message = $resultMessage;
            } else {
                $error = $resultMessage;
            }
        }
    }

    $query = $message !== null ? '?message=' . rawurlencode($message) : ($error !== null ? '?error=' . rawurlencode($error) : '');
    header('Location: /admin/roles' . $query);
}

function handle_panel_client(string $method): void
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return;
    }

    $clientId = ClientContext::clientId();
    $canManage = RoleService::hasAnyRole(ClientContext::userId(), $clientId, ['client_admin']);
    $error = null;
    $message = null;

    if ($method === 'POST') {
        if (!$canManage) {
            http_response_code(403);
            render_page('Forbidden', 'errors/403.php');

            return;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            [$success, $resultMessage] = ClientService::updateOwnDetails($clientId, $_POST, ClientContext::userId());

            if ($success) {
                $message = $resultMessage;
            } else {
                $error = $resultMessage;
            }
        }
    }

    render_panel_page('Client management', 'client/index.php', [
        'client' => ClientService::find($clientId),
        'canManage' => $canManage,
        'error' => $error,
        'message' => $message,
    ], 'client');
}

function handle_admin_notifications(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    $clients = ClientService::listActive();
    $clientId = (int) ($_GET['client_id'] ?? ($clients[0]['id'] ?? 0));
    $client = ClientService::find($clientId);
    $error = null;
    $message = null;

    if ($client === null) {
        http_response_code(404);
        render_page('Not found', 'errors/404.php');

        return;
    }

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            [$success, $resultMessage] = NotificationService::send($clientId, $_POST, ClientContext::userId());

            if ($success) {
                $message = $resultMessage;
            } else {
                $error = $resultMessage;
            }
        }
    }

    render_admin_page('Notifications', 'notifications/index.php', [
        'clients' => $clients,
        'client' => $client,
        'users' => UserService::listForClient($clientId),
        'sent' => NotificationService::listSentForClient($clientId),
        'error' => $error,
        'message' => $message,
    ], 'notifications');
}

function handle_admin_support(): void
{
    if (!require_system_role()) {
        return;
    }

    render_admin_page('Support tickets', 'support/index.php', [
        'tickets' => SupportService::listAllOpen(),
    ], 'support');
}

function handle_admin_support_status(string $method): void
{
    if (!require_system_role()) {
        return;
    }

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        SupportService::updateStatus((int) ($_POST['id'] ?? 0), (string) ($_POST['status'] ?? ''), ClientContext::userId());
    }

    header('Location: /admin/support');
}

function handle_panel_notifications(): void
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return;
    }

    render_panel_page('Notifications', 'notifications/index.php', [
        'notifications' => NotificationService::listForUser(ClientContext::clientId(), ClientContext::userId()),
    ], 'notifications');
}

function handle_panel_notifications_read(string $method): void
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return;
    }

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        NotificationService::markRead((int) ($_POST['id'] ?? 0), ClientContext::userId());
    }

    header('Location: /panel/notifications');
}

function handle_panel_reports(): void
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return;
    }

    render_panel_page('Reports & statistics', 'reports/index.php', [
        'stats' => ReportService::clientStats(ClientContext::clientId()),
    ], 'reports');
}

function handle_panel_support(): void
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return;
    }

    render_panel_page('Support', 'support/index.php', [
        'tickets' => SupportService::listForClient(ClientContext::clientId()),
        'message' => $_GET['message'] ?? null,
    ], 'support');
}

function handle_panel_support_create(string $method): void
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return;
    }

    $error = null;
    $old = [];

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = $_POST;
            [$success, $message] = SupportService::create(ClientContext::clientId(), ClientContext::userId(), $_POST);

            if ($success) {
                header('Location: /panel/support?message=' . rawurlencode($message));
                exit;
            }

            $error = $message;
        }
    }

    render_panel_page('New support ticket', 'support/create.php', ['error' => $error, 'old' => $old], 'support');
}

function require_property_module(): bool
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');
        return false;
    }

    if (!ModuleService::isActiveForClient(ClientContext::clientId(), 'property')) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');
        return false;
    }

    return true;
}

function require_property_manager(): bool
{
    if (!require_property_module()) {
        return false;
    }
    if (!RoleService::hasAnyRole(ClientContext::userId(), ClientContext::clientId(), ['client_admin'])) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');
        return false;
    }

    return true;
}

function handle_panel_property_index(): void
{
    if (!require_property_module()) {
        return;
    }

    $clientId = ClientContext::clientId();
    render_panel_page('Property structure', 'property/index.php', [
        'nodes' => PropertyService::listTreeForClient($clientId),
        'canManage' => RoleService::hasAnyRole(ClientContext::userId(), $clientId, ['client_admin']),
        'message' => $_GET['message'] ?? null,
    ], 'property');
}

function handle_panel_property_create(string $method): void
{
    if (!require_property_manager()) {
        return;
    }

    $clientId = ClientContext::clientId();
    $parentId = (int) ($method === 'POST' ? ($_POST['parent_id'] ?? 0) : ($_GET['parent'] ?? 0));
    $parent = $parentId > 0 ? PropertyService::findForClient($clientId, $parentId) : null;
    $allowedTypes = PropertyService::allowedChildTypes($parent['node_type'] ?? null);
    if (($parentId > 0 && $parent === null) || $allowedTypes === []) {
        http_response_code(404);
        render_page('Not found', 'errors/404.php');
        return;
    }

    $error = null;
    $old = [];
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = $_POST;
            [$success, $message] = PropertyService::create($clientId, $_POST, ClientContext::userId());
            if ($success) {
                header('Location: /panel/property?message=' . rawurlencode($message));
                exit;
            }
            $error = $message;
        }
    }

    render_panel_page('Add location', 'property/create.php', [
        'parent' => $parent,
        'allowedTypes' => $allowedTypes,
        'error' => $error,
        'old' => $old,
    ], 'property');
}

function handle_panel_property_edit(string $method): void
{
    if (!require_property_manager()) {
        return;
    }

    $clientId = ClientContext::clientId();
    $nodeId = (int) ($method === 'POST' ? ($_POST['id'] ?? 0) : ($_GET['id'] ?? 0));
    $node = PropertyService::findForClient($clientId, $nodeId);
    if ($node === null) {
        http_response_code(404);
        render_page('Not found', 'errors/404.php');
        return;
    }

    $error = null;
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $name = is_string($_POST['name'] ?? null) ? $_POST['name'] : '';
            [$success, $message] = PropertyService::rename($clientId, $nodeId, $name, ClientContext::userId());
            if ($success) {
                header('Location: /panel/property?message=' . rawurlencode($message));
                exit;
            }
            $error = $message;
            $node['name'] = $name;
        }
    }

    render_panel_page('Edit location', 'property/edit.php', ['node' => $node, 'error' => $error], 'property');
}

function handle_panel_property_reorder(string $method): void
{
    if (!require_property_manager()) {
        return;
    }
    if ($method !== 'POST' || !Csrf::validate($_POST['csrf_token'] ?? null)) {
        header('Location: /panel/property');
        return;
    }

    $nodeId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $direction = is_string($_POST['direction'] ?? null) ? $_POST['direction'] : '';
    if ($nodeId === false || $nodeId <= 0) {
        header('Location: /panel/property?error=' . rawurlencode('Invalid location.'));
        return;
    }

    [$success, $message] = PropertyService::moveSibling(ClientContext::clientId(), $nodeId, $direction, ClientContext::userId());
    $queryKey = $success ? 'message' : 'error';
    header('Location: /panel/property?' . $queryKey . '=' . rawurlencode($message));
}

function require_employment_module(): bool
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');
        return false;
    }
    if (!ModuleService::isActiveForClient(ClientContext::clientId(), 'employment')) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');
        return false;
    }

    return true;
}

function require_employment_manager(): bool
{
    if (!require_employment_module()) {
        return false;
    }
    if (!RoleService::hasAnyRole(ClientContext::userId(), ClientContext::clientId(), ['client_admin'])) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');
        return false;
    }

    return true;
}

function handle_panel_employment_contracts(): void
{
    if (!require_employment_module()) {
        return;
    }

    $clientId = ClientContext::clientId();
    render_panel_page('Employment contracts', 'employment/contracts.php', [
        'contracts' => EmploymentContractService::listForClient($clientId),
        'canManage' => RoleService::hasAnyRole(ClientContext::userId(), $clientId, ['client_admin']),
        'message' => $_GET['message'] ?? null,
        'error' => $_GET['error'] ?? null,
    ], 'employment');
}

function handle_panel_employment_contract_form(string $method, ?int $contractId): void
{
    if (!require_employment_manager()) {
        return;
    }

    $clientId = ClientContext::clientId();
    if (!ModuleService::isActiveForClient($clientId, 'property')) {
        http_response_code(403);
        render_page('Property module required', 'errors/403.php');
        return;
    }
    $contract = $contractId !== null ? EmploymentContractService::findForClient($clientId, $contractId) : null;
    if ($contractId !== null && $contract === null) {
        http_response_code(404);
        render_page('Not found', 'errors/404.php');
        return;
    }

    $error = null;
    $old = [];
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            foreach (['employee_id', 'property_node_id', 'job_title_id', 'workload_id', 'department_id', 'manager_user_id', 'contract_type', 'start_date', 'end_date'] as $field) {
                $old[$field] = is_string($_POST[$field] ?? null) ? $_POST[$field] : '';
            }
            [$success, $message] = EmploymentContractService::save($clientId, $contractId, $_POST, ClientContext::userId());
            if ($success) {
                header('Location: /panel/employment/contracts?message=' . rawurlencode($message));
                exit;
            }
            $error = $message;
        }
    }

    render_panel_page($contractId === null ? 'Add contract' : 'Edit contract', 'employment/contract-form.php', [
        'contract' => $contract,
        'old' => $old,
        'error' => $error,
        'options' => EmploymentContractService::formOptions($clientId, $contract),
    ], 'employment');
}

function handle_panel_employment_catalog(string $method, string $catalog): void
{
    if (!require_employment_module()) {
        return;
    }
    $clientId = ClientContext::clientId();
    $canManage = RoleService::hasAnyRole(ClientContext::userId(), $clientId, ['client_admin']);
    $error = null;
    $message = $_GET['message'] ?? null;

    if ($method === 'POST') {
        if (!$canManage) {
            http_response_code(403);
            render_page('Forbidden', 'errors/403.php');
            return;
        }
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } elseif (($_POST['action'] ?? '') === 'toggle') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            [$success, $result] = EmploymentCatalogService::toggleStatus($clientId, $catalog, $id !== false && $id > 0 ? $id : 0, ClientContext::userId());
            $success ? $message = $result : $error = $result;
        } else {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $id = $id !== false && $id > 0 ? $id : 0;
            $name = is_string($_POST['name'] ?? null) ? $_POST['name'] : '';
            $workloadPercent = is_string($_POST['workload_percent'] ?? null) ? $_POST['workload_percent'] : null;
            [$success, $result] = EmploymentCatalogService::save($clientId, $catalog, $id > 0 ? $id : null, $name, ClientContext::userId(), $workloadPercent);
            $success ? $message = $result : $error = $result;
        }
    }

    render_panel_page($catalog === 'titles' ? 'Job titles' : 'Departments', 'employment/catalog.php', [
        'catalog' => $catalog,
        'catalogLabel' => match ($catalog) {
            'titles' => 'Job title',
            'departments' => 'Department',
            'workloads' => 'Workload',
        },
        'entries' => EmploymentCatalogService::listForClient($clientId, $catalog),
        'canManage' => $canManage,
        'message' => $message,
        'error' => $error,
    ], 'employment');
}

function require_schedule_modules(): bool
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');
        return false;
    }
    foreach (['schedule', 'employment', 'property'] as $moduleKey) {
        if (!ModuleService::isActiveForClient(ClientContext::clientId(), $moduleKey)) {
            http_response_code(403);
            render_page('Forbidden', 'errors/403.php');
            return false;
        }
    }

    return true;
}

function handle_panel_schedule(string $method): void
{
    if (!require_schedule_modules()) {
        return;
    }

    $isAjaxRequest = $method === 'POST'
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    $clientId = ClientContext::clientId();
    $userId = ClientContext::userId();
    $isClientAdmin = RoleService::hasAnyRole($userId, $clientId, ['client_admin']);
    $monthValue = $method === 'POST' ? ($_POST['month'] ?? '') : ($_GET['month'] ?? date('Y-m'));
    $month = ScheduleService::monthInfo(is_string($monthValue) ? $monthValue : '');
    if ($month === null) {
        if ($isAjaxRequest) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Choose a valid month.']);
            return;
        }
        http_response_code(400);
        render_page('Invalid month', 'errors/404.php');
        return;
    }

    $error = null;
    $message = $_GET['message'] ?? null;
    $oldAssignments = [];
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            [$success, $result] = ScheduleService::saveMonth(
                $clientId,
                $userId,
                $isClientAdmin,
                $month['month'],
                $_POST['assignments'] ?? []
            );
            if ($success) {
                $message = $result;
                if (!$isAjaxRequest) {
                    header('Location: /panel/schedule?month=' . rawurlencode($month['month']) . '&message=' . rawurlencode($result));
                    exit;
                }
            } else {
                $error = $result;
            }
        }
    }

    $data = ScheduleService::monthData($clientId, $userId, $isClientAdmin, $month);
    if ($isAjaxRequest) {
        if ($error !== null) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $error], JSON_INVALID_UTF8_SUBSTITUTE);
            return;
        }

        $plannedHours = [];
        foreach ($data['contracts'] as $contract) {
            $plannedHours[(string) $contract['contract_id']] = (float) $contract['planned_hours'];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message ?? 'Schedule saved.',
            'plannedHours' => $plannedHours,
        ], JSON_INVALID_UTF8_SUBSTITUTE);
        return;
    }

    render_panel_page('Work schedule', 'schedule/index.php', [
        'month' => $month,
        'contracts' => $data['contracts'],
        'templates' => $data['templates'],
        'entries' => $data['entries'],
        'oldAssignments' => $oldAssignments,
        'canManage' => $isClientAdmin || $data['contracts'] !== [],
        'canManageTemplates' => $isClientAdmin,
        'message' => $message,
        'error' => $error,
    ], 'schedule');
}

function handle_panel_schedule_templates(string $method): void
{
    if (!require_schedule_modules()) {
        return;
    }
    if (!RoleService::hasAnyRole(ClientContext::userId(), ClientContext::clientId(), ['client_admin'])) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');
        return;
    }

    $clientId = ClientContext::clientId();
    $message = $_GET['message'] ?? null;
    $error = null;
    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } elseif (($_POST['action'] ?? '') === 'toggle') {
            $templateId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            [$success, $result] = ScheduleService::toggleTemplate($clientId, $templateId !== false && $templateId > 0 ? $templateId : 0, ClientContext::userId());
            $success ? $message = $result : $error = $result;
        } else {
            $templateId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $templateId = $templateId !== false && $templateId > 0 ? $templateId : 0;
            [$success, $result] = ScheduleService::saveTemplate($clientId, $templateId > 0 ? $templateId : null, $_POST, ClientContext::userId());
            $success ? $message = $result : $error = $result;
        }
    }

    render_panel_page('Schedule settings', 'schedule/templates.php', [
        'templates' => ScheduleService::listTemplates($clientId),
        'message' => $message,
        'error' => $error,
    ], 'schedule');
}

function require_patients_module(): bool
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');

        return false;
    }

    if (!PatientService::isActiveForClient(ClientContext::clientId())) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');

        return false;
    }

    return true;
}

function handle_panel_patients_index(): void
{
    if (!require_patients_module()) {
        return;
    }

    render_panel_page('Patients', 'patients/index.php', [
        'patients' => PatientService::listForClient(ClientContext::clientId()),
        'message' => $_GET['message'] ?? null,
    ], 'patients');
}

function handle_panel_patients_create(string $method): void
{
    if (!require_patients_module()) {
        return;
    }

    $error = null;
    $old = [];

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = $_POST;
            [$success, $message] = PatientService::create(ClientContext::clientId(), $_POST, ClientContext::userId());

            if ($success) {
                header('Location: /panel/patients?message=' . rawurlencode($message));
                exit;
            }

            $error = $message;
        }
    }

    render_panel_page('Add patient', 'patients/create.php', ['error' => $error, 'old' => $old], 'patients');
}

function handle_panel_patients_toggle(string $method): void
{
    if (!require_patients_module()) {
        return;
    }

    if ($method === 'POST' && Csrf::validate($_POST['csrf_token'] ?? null)) {
        PatientService::toggleStatus(ClientContext::clientId(), (int) ($_POST['id'] ?? 0), ClientContext::userId());
    }

    header('Location: /panel/patients');
}

function handle_setup(string $method): void
{
    $error = null;
    $old = [];

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = [
                'full_name' => trim((string) ($_POST['full_name'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
                'username' => trim((string) ($_POST['username'] ?? '')),
            ];

            [$success, $message] = InitialSetupService::createFirstAdmin(
                $old['full_name'],
                $old['email'],
                $old['username'],
                (string) ($_POST['password'] ?? '')
            );

            if ($success) {
                header('Location: /login');
                exit;
            }

            $error = $message;
        }
    }

    render_page('Initial setup', 'setup/create_admin.php', ['error' => $error, 'old' => $old]);
}

function handle_login(string $method): void
{
    if (ClientContext::isLoggedIn()) {
        header('Location: /');
        exit;
    }

    $error = null;
    $old = [];

    if ($method === 'POST') {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $error = 'Invalid session token, please try again.';
        } else {
            $old = [
                'client_code' => trim((string) ($_POST['client_code'] ?? '')),
                'username' => trim((string) ($_POST['username'] ?? '')),
            ];

            [$success, $message] = AuthService::attemptLogin($old['client_code'], $old['username'], (string) ($_POST['password'] ?? ''));

            if ($success) {
                header('Location: /');
                exit;
            }

            $error = $message;
        }
    }

    render_page('Client login', 'auth/login.php', ['error' => $error, 'old' => $old]);
}
