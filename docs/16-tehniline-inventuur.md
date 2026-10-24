# Tehniline inventuur

**Sihtversioon: 1.98. Genereeritud kontrollitud tööpuust 24.10.2026.**

See register täiendab [taastamisspetsifikatsiooni](12-tehniline-taastamisspetsifikatsioon.md) ja [baasfunktsioonide juhendit](11-baasfunktsioonid-ja-teostuse-piirid.md). Faililingid on originaalallikate tuvastamiseks; iseseisva taastamise käitumine on dokumentides 11–14 ja skeem paketis 15. Saladustega konfiguratsiooni, isiklikke IDE seadeid ega andmebaasi sisu ei inventeerita.

Inventuur katab 86 switch-marsruuti + enne switch'i käsitletava `/language` marsruudi, 40 loodavat tabelit ja 85 registreeritud migratsiooni. Meetodid tähistavad kavandatud vormikasutust; praegune router ei keela kõiki muid HTTP meetodeid ühtse 405-ga.

## 1. Kõik marsruudid ja sisendid

Sisendid loetakse handler'ist ja selle kohalike helper'ite kaudu; kogu POST-i teenusele edasiandvad vormid on täpsustatud vormide registris. `message/error` on UI tagasiside, mitte usaldatud kliendikontekst. Väravaveerg nimetab otsese helper'i; rollid/moodulisõltuvused on dokumendis 11.

| Route | Handler / erijuht | Kasutus | Otsene värav | Handler'i sisendid |
|---|---|---|---|---|
| `/setup` | `handle_setup` | GET/POST (vt 11/12) |  | POST:csrf_token, POST:full_name, POST:email, POST:username, POST:password |
| `/login` | `handle_login` | GET/POST (vt 11/12) | ClientContext::isLoggedIn | GET:client_code, POST:csrf_token, POST:client_code, POST:username, POST:password |
| `/embed` | `handle_client_domain_embed` | GET/POST (vt 11/12) | ClientContext::isLoggedIn | GET:token, POST:csrf_token, POST:username, POST:password |
| `/logout` | `AuthService::logout + redirect` | GET |  |  |
| `/admin` | `handle_admin_dashboard` | GET | require_system_role |  |
| `/admin/clients` | `handle_admin_clients_index` | GET | require_system_role |  |
| `/admin/clients/create` | `handle_admin_clients_create` | GET/POST (vt 11/12) | require_system_role | POST:csrf_token |
| `/admin/clients/view` | `handle_admin_clients_view` | GET | require_system_role | GET:id, GET:message, GET:error |
| `/admin/clients/users/create` | `handle_admin_client_admin_create` | GET/POST (vt 11/12) | require_system_role | POST:client_id, GET:client_id, POST:csrf_token |
| `/admin/clients/users/edit` | `handle_admin_client_admin_edit` | GET/POST (vt 11/12) | require_system_role | POST:client_id, GET:client_id, POST:user_id, GET:user_id, POST:csrf_token, POST:full_name, POST:email, POST:phone |
| `/panel/users/list-settings` | `handle_panel_users_list_settings` | GET/POST (vt 11/12) | require_panel_manager | POST:csrf_token, POST:page_size |
| `/admin/clients/modules/toggle` | `handle_admin_clients_modules_toggle` | POST | require_system_role | POST:client_id, POST:csrf_token, POST:module_id |
| `/admin/clients/users/reset-password` | `handle_admin_clients_reset_password` | POST | require_system_role | POST:client_id, POST:user_id, POST:csrf_token |
| `/admin/modules` | `handle_admin_modules_index` | GET | require_system_role |  |
| `/admin/modules/create` | `handle_admin_modules_create` | GET/POST (vt 11/12) | require_system_role | POST:csrf_token |
| `/admin/modules/toggle` | `handle_admin_modules_toggle` | POST | require_system_role | POST:csrf_token, POST:id |
| `/admin/languages` | `handle_admin_languages_index` | GET | require_system_role |  |
| `/admin/languages/create` | `handle_admin_languages_create` | GET/POST (vt 11/12) | require_system_role | POST:csrf_token |
| `/admin/languages/toggle` | `handle_admin_languages_toggle` | POST | require_system_role | POST:csrf_token, POST:id |
| `/admin/translations` | `handle_admin_translations_index` | GET | require_system_role | GET:language |
| `/admin/translations/create` | `handle_admin_translations_create` | GET/POST (vt 11/12) | require_system_role | POST:csrf_token, POST:translation_key, POST:module_context, POST:english_value |
| `/admin/translations/update` | `handle_admin_translations_update` | POST | require_system_role | POST:csrf_token, POST:key_id, POST:language_id, POST:value |
| `/admin/translations/missing` | `handle_admin_translations_missing` | GET | require_system_role | GET:language |
| `/admin/logs` | `handle_admin_logs` | GET | require_system_role | GET:action, GET:client_code, GET:username |
| `/admin/settings` | `handle_admin_settings` | GET/POST (vt 11/12) | require_system_role | POST:csrf_token, POST:setting_key, POST:setting_value |
| `/admin/roles` | `handle_admin_roles` | GET | require_system_role | GET:message, GET:error |
| `/admin/roles/update` | `handle_admin_roles_update` | POST | require_system_role | POST:csrf_token, POST:role_id, POST:name |
| `/admin/notifications` | `handle_admin_notifications` | GET/POST (vt 11/12) | require_system_role | GET:client_id, POST:csrf_token |
| `/admin/support` | `handle_admin_support` | GET | require_system_role |  |
| `/admin/support/status` | `handle_admin_support_status` | POST |  | POST:status, GET:id, POST:id, POST:csrf_token, GET:message |
| `/admin/support/view` | `handle_support_ticket` | GET/POST (vt 11/12) | require_system_role | GET:id, POST:id, POST:csrf_token, GET:message |
| `/admin/support/reply` | `handle_support_ticket` | GET/POST (vt 11/12) | require_system_role | GET:id, POST:id, POST:csrf_token, GET:message |
| `/admin/support/invite` | `handle_support_ticket` | GET/POST (vt 11/12) | require_system_role | GET:id, POST:id, POST:csrf_token, GET:message |
| `/admin/support/close` | `handle_support_ticket` | GET/POST (vt 11/12) | require_system_role | GET:id, POST:id, POST:csrf_token, GET:message |
| `/panel` | `handle_panel_dashboard` | GET | ClientContext::isLoggedIn |  |
| `/panel/modules` | `handle_panel_modules` | GET | ClientContext::isLoggedIn |  |
| `/panel/users` | `handle_panel_users` | GET | require_employee_module | GET:search, GET:page, GET:message, GET:error |
| `/panel/users/create` | `handle_panel_users_create` | GET/POST (vt 11/12) | require_panel_manager | POST:csrf_token |
| `/panel/users/edit` | `handle_panel_users_edit` | GET/POST (vt 11/12) | require_panel_manager | GET:id, POST:user_id, POST:csrf_token |
| `/panel/users/import` | `handle_panel_users_import` | GET/POST (vt 11/12) | require_panel_manager | POST:csrf_token, FILES:legacy_sql |
| `/panel/users/toggle` | `handle_panel_users_toggle` | POST | require_panel_manager | POST:csrf_token, POST:user_id |
| `/panel/users/reset-password` | `handle_panel_users_reset_password` | POST | require_panel_manager | POST:csrf_token, POST:user_id |
| `/panel/account` | `handle_panel_account` | GET | ClientContext::isLoggedIn | GET:message, GET:error |
| `/panel/account/password` | `handle_panel_account_password` | POST | ClientContext::isLoggedIn | POST:csrf_token, POST:new_password, POST:new_password_confirm, POST:current_password |
| `/panel/org` | `handle_panel_org_index` | GET | require_organisation_module |  |
| `/panel/org/create` | `handle_panel_org_create` | GET/POST (vt 11/12) | require_organisation_manager | POST:csrf_token |
| `/panel/substitutes` | `handle_panel_substitutes_index` | GET | require_organisation_module | GET:message, GET:error |
| `/panel/substitutes/create` | `handle_panel_substitutes_create` | GET/POST (vt 11/12) | require_organisation_module | POST:csrf_token |
| `/panel/substitutes/approve` | `handle_panel_substitutes_decision` | POST | require_organisation_manager | POST:csrf_token, POST:id |
| `/panel/substitutes/reject` | `handle_panel_substitutes_decision` | POST | require_organisation_manager | POST:csrf_token, POST:id |
| `/panel/client` | `handle_panel_client` | GET/POST (vt 11/12) | ClientContext::isLoggedIn | POST:csrf_token |
| `/panel/domain-embed` | `handle_panel_domain_embed` | GET/POST (vt 11/12) | require_client_domain_embed_manager | POST:csrf_token, POST:customer_domain, GET:saved |
| `/panel/domain-embed/download` | `handle_panel_domain_embed_download` | GET | require_client_domain_embed_manager |  |
| `/panel/notifications` | `handle_panel_notifications` | GET | ClientContext::isLoggedIn | GET:archive, GET:page |
| `/panel/notifications/read` | `handle_panel_notifications_read` | POST | ClientContext::isLoggedIn | POST:csrf_token, POST:archive, POST:page, POST:id |
| `/panel/reports` | `handle_panel_reports` | GET | ClientContext::isLoggedIn |  |
| `/panel/support` | `handle_panel_support` | GET | ClientContext::isLoggedIn | GET:message |
| `/panel/support/create` | `handle_panel_support_create` | GET/POST (vt 11/12) | ClientContext::isLoggedIn | POST:csrf_token |
| `/panel/support/view` | `handle_support_ticket` | GET/POST (vt 11/12) | require_system_role | GET:id, POST:id, POST:csrf_token, GET:message |
| `/panel/support/reply` | `handle_support_ticket` | GET/POST (vt 11/12) | require_system_role | GET:id, POST:id, POST:csrf_token, GET:message |
| `/panel/support/invite` | `handle_support_ticket` | GET/POST (vt 11/12) | require_system_role | GET:id, POST:id, POST:csrf_token, GET:message |
| `/panel/support/close` | `handle_support_ticket` | GET/POST (vt 11/12) | require_system_role | GET:id, POST:id, POST:csrf_token, GET:message |
| `/panel/patients` | `handle_panel_patients_index` | GET | require_patients_module | GET:message |
| `/panel/patients/create` | `handle_panel_patients_create` | GET/POST (vt 11/12) | require_patients_module | POST:csrf_token |
| `/panel/patients/toggle` | `handle_panel_patients_toggle` | POST | require_patients_module | POST:csrf_token, POST:id |
| `/panel/property` | `handle_panel_property_index` | GET | require_property_module | GET:message |
| `/panel/property/create` | `handle_panel_property_create` | GET/POST (vt 11/12) | require_property_manager | POST:parent_id, GET:parent, POST:csrf_token |
| `/panel/property/edit` | `handle_panel_property_edit` | GET/POST (vt 11/12) | require_property_manager | POST:id, GET:id, POST:csrf_token, POST:name |
| `/panel/property/reorder` | `handle_panel_property_reorder` | POST | require_property_manager | POST:csrf_token, POST:id, POST:direction |
| `/panel/schedule/holidays/import` | `handle_panel_schedule_holiday_import` | POST | require_schedule_admin | POST:csrf_token, FILES:public_holidays_xml |
| `/panel/schedule` | `handle_panel_schedule` | GET/POST (vt 11/12) | require_schedule_modules | GET:month, POST:month, GET:error, GET:message, POST:csrf_token, POST:assignments, POST:repeat_days |
| `/panel/schedule/print` | `handle_panel_schedule_print` | GET | require_schedule_modules | GET:month |
| `/panel/schedule/workload` | `handle_panel_schedule_workload` | POST + AJAX | require_schedule_modules | POST:csrf_token, POST:month, POST:contract_id, POST:workload_id |
| `/panel/schedule/substitutes` | `handle_panel_schedule_substitutes` | GET/POST (vt 11/12) | require_schedule_modules | POST:csrf_token, POST:action, POST:id |
| `/panel/schedule/templates` | `handle_panel_schedule_templates` | GET/POST (vt 11/12) | require_schedule_modules | GET:message, POST:csrf_token, POST:action, POST:id |
| `/panel/schedule/holidays` | `handle_panel_schedule_holidays` | GET | require_schedule_admin | GET:message, GET:error |
| `/panel/employment` | `redirect` | GET |  |  |
| `/panel/employment/contracts` | `handle_panel_employment_contracts` | GET | require_employment_module | GET:search, GET:page, GET:message, GET:error |
| `/panel/employment/contracts/list-settings` | `handle_panel_employment_contracts_list_settings` | GET/POST (vt 11/12) | require_employment_manager | POST:csrf_token, POST:page_size |
| `/panel/employment/contracts/archive` | `handle_panel_employment_contract_archive` | POST | require_employment_manager | POST:csrf_token, POST:id |
| `/panel/employment/contracts/create` | `handle_panel_employment_contract_form` | GET/POST (vt 11/12) | require_employment_manager | POST:csrf_token |
| `/panel/employment/contracts/edit` | `handle_panel_employment_contract_form` | GET/POST (vt 11/12) | require_employment_manager | GET:id, POST:id, POST:csrf_token |
| `/panel/employment/titles` | `handle_panel_employment_catalog` | GET/POST (vt 11/12) | require_employment_module | GET:message, POST:csrf_token, POST:action, POST:id, POST:name, POST:workload_percent |
| `/panel/employment/departments` | `handle_panel_employment_catalog` | GET/POST (vt 11/12) | require_employment_module | GET:message, POST:csrf_token, POST:action, POST:id, POST:name, POST:workload_percent |
| `/panel/employment/workloads` | `handle_panel_employment_catalog` | GET/POST (vt 11/12) | require_employment_module | GET:message, POST:csrf_token, POST:action, POST:id, POST:name, POST:workload_percent |
| `/` | `render_page('home.php')` | GET |  |  |
| `/language` | `handle_language_change` | POST |  | POST:csrf_token, POST:language_code, POST:return_to |

## 2. Väravate definitsioonid

Allpool on kontrollitud helper'ite täpne lähtekuju. See on autoriseerimise taastamisviide, mitte uute õiguste lubadus.

### `require_system_role`

```php
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
```

### `require_employee_module`

```php
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
```

### `require_panel_manager`

```php
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
```

### `require_organisation_module`

```php
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
```

### `require_organisation_manager`

```php
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
```

### `require_client_domain_embed_manager`

```php
function require_client_domain_embed_manager(): bool
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');
        return false;
    }

    $clientId = ClientContext::clientId();
    if (!ModuleService::isActiveForClient($clientId, 'client_domain_embed')
        || !RoleService::hasAnyRole(ClientContext::userId(), $clientId, ['client_admin'])) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');
        return false;
    }

    return true;
}
```

### `require_property_module`

```php
function require_property_module(): bool
{
    if (!ClientContext::isLoggedIn()) {
        header('Location: /login');
        return false;
    }

    if (RoleService::isSelfServiceUser(ClientContext::userId(), ClientContext::clientId())
        || !ModuleService::isActiveForClient(ClientContext::clientId(), 'property')) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');
        return false;
    }

    return true;
}
```

### `require_property_manager`

```php
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
```

### `require_employment_module`

```php
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
```

### `require_employment_manager`

```php
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
```

### `require_schedule_modules`

```php
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
```

### `require_schedule_admin`

```php
function require_schedule_admin(): bool
{
    if (!require_schedule_modules()) {
        return false;
    }

    $clientId = ClientContext::clientId();
    $userId = ClientContext::userId();
    if (!RoleService::hasAnyRole($userId, $clientId, ['client_admin'])) {
        http_response_code(403);
        render_page('Forbidden', 'errors/403.php');
        return false;
    }

    return true;
}
```

### `require_patients_module`

```php
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
```

## 3. Teenuste ja tugiklasside avalikud liidesed

Teenused tagastavad PDO array-andmeid või success/message tuple'e; täpsed ärireeglid on moodulijuhendites ja dokumendis 12. Private helper'eid ei loeta avalikuks API-ks.

### [AuditLogService.php](../src/Services/AuditLogService.php)

```php
public static function log(
        ?int $userId,
        ?int $clientId,
        string $action,
        ?string $objectType = null,
        ?string $objectId = null,
        ?string $oldValue = null,
        ?string $newValue = null
    ): void;
public static function listRecent(array $filters = [], int $limit = 100): array;
```

### [AuthService.php](../src/Services/AuthService.php)

```php
public static function attemptLogin(string $clientCode, string $username, string $password): array;
public static function logout(): void;
```

### [ClientDomainEmbedService.php](../src/Services/ClientDomainEmbedService.php)

```php
public static function normalizeDomain(string $domain): ?string;
public static function findForClient(int $clientId): ?array;
public static function findActiveByToken(string $token): ?array;
public static function save(int $clientId, string $domain, int $actorUserId): array;
public static function downloadHtml(array $config): string;
public static function applyFramePolicy(?string $domain): void;
```

### [ClientService.php](../src/Services/ClientService.php)

```php
public static function listActive(): array;
public static function find(int $clientId): ?array;
public static function updateOwnDetails(int $clientId, array $data, int $actorUserId): array;
public static function createClient(array $data, int $createdByUserId): array;
```

### [EmailService.php](../src/Services/EmailService.php)

```php
public static function sendTemporaryPassword(string $email, string $fullName, string $username, string $password): bool;
```

### [EmploymentCatalogService.php](../src/Services/EmploymentCatalogService.php)

```php
public static function listForClient(int $clientId, string $catalog): array;
public static function save(int $clientId, string $catalog, ?int $id, string $name, int $actorUserId, ?string $workloadPercent = null): array;
public static function toggleStatus(int $clientId, string $catalog, int $id, int $actorUserId): array;
```

### [EmploymentContractService.php](../src/Services/EmploymentContractService.php)

```php
public static function listPageSize(int $clientId): int;
public static function saveListPageSize(int $clientId, int $pageSize, int $actorUserId): void;
public static function listPage(int $clientId, ?int $employeeId, string $search, int $page): array;
public static function listForClient(int $clientId, ?int $employeeId = null, string $search = '', ?int $limit = null, int $offset = 0): array;
public static function addPropertyPaths(array $contracts, array $propertyNodes): array;
public static function toggleTemporaryArchive(int $clientId, int $contractId, int $actorUserId): array;
public static function findForClient(int $clientId, int $contractId): ?array;
public static function formOptions(int $clientId, ?array $contract = null): array;
public static function dateRangesOverlap(string $firstStart, ?string $firstEnd, string $secondStart, ?string $secondEnd): bool;
public static function dateRangesShareMonth(string $firstStart, ?string $firstEnd, string $secondStart, ?string $secondEnd): bool;
public static function dateRangeContains(string $outerStart, ?string $outerEnd, string $innerStart, ?string $innerEnd): bool;
public static function monthlyRequiredHours(float $workloadPercent, int $workingDays, float $hoursPerDay = 8.0): float;
public static function save(int $clientId, ?int $contractId, array $data, int $actorUserId): array;
```

### [InitialSetupService.php](../src/Services/InitialSetupService.php)

```php
public static function isCompleted(): bool;
public static function createFirstAdmin(string $fullName, string $email, string $username, string $password): array;
```

### [LanguageService.php](../src/Services/LanguageService.php)

```php
public static function listAll(): array;
public static function listActive(): array;
public static function currentCode(): string;
public static function setCurrentCode(string $code): bool;
public static function create(array $data, int $actorUserId): array;
public static function toggleActive(int $languageId, int $actorUserId): array;
```

### [LegacyEmployeeImportService.php](../src/Services/LegacyEmployeeImportService.php)

```php
public static function importForClient(int $clientId, string $sql, int $actorUserId): array;
public static function parse(string $sql): array;
```

### [ModuleProvisioningService.php](../src/Services/ModuleProvisioningService.php)

```php
public static function provision(int $clientId, int $moduleId, string $moduleKey): void;
public static function markInactive(int $clientId, int $moduleId): void;
```

### [ModuleService.php](../src/Services/ModuleService.php)

```php
public static function listAll(): array;
public static function create(array $data, int $actorUserId): array;
public static function toggleStatus(int $moduleId, int $actorUserId): void;
public static function listForClient(int $clientId): array;
public static function listActiveForClient(int $clientId): array;
public static function isActiveForClient(int $clientId, string $moduleKey): bool;
public static function toggleClientActivation(int $clientId, int $moduleId, int $actorUserId): array;
```

### [NotificationService.php](../src/Services/NotificationService.php)

```php
public static function listForUser(int $clientId, int $userId, bool $archive = false, int $page = 1): array;
public static function unreadCount(int $clientId, int $userId): int;
public static function markRead(int $notificationId, int $userId, int $clientId): bool;
public static function send(int $clientId, array $data, int $actorUserId): array;
public static function listSentForClient(int $clientId): array;
```

### [OrgUnitService.php](../src/Services/OrgUnitService.php)

```php
public static function listForClient(int $clientId): array;
public static function create(int $clientId, array $data, int $actorUserId): array;
```

### [PatientService.php](../src/Services/PatientService.php)

```php
public static function isActiveForClient(int $clientId): bool;
public static function listForClient(int $clientId): array;
public static function find(int $clientId, int $patientId): ?array;
public static function create(int $clientId, array $data, int $actorUserId): array;
public static function toggleStatus(int $clientId, int $patientId, int $actorUserId): void;
```

### [PropertyService.php](../src/Services/PropertyService.php)

```php
public static function allowedChildTypes(?string $parentType): array;
public static function findForClient(int $clientId, int $nodeId): ?array;
public static function listTreeForClient(int $clientId): array;
public static function buildTree(array $nodes): array;
public static function create(int $clientId, array $data, int $actorUserId): array;
public static function moveSibling(int $clientId, int $nodeId, string $direction, int $actorUserId): array;
public static function rename(int $clientId, int $nodeId, string $name, int $actorUserId): array;
```

### [PublicHolidayService.php](../src/Services/PublicHolidayService.php)

```php
public static function importXml(string $xml): array;
public static function isDayOff(string $date): bool;
public static function listAll(): array;
```

### [ReportService.php](../src/Services/ReportService.php)

```php
public static function systemStats(): array;
public static function clientStats(int $clientId): array;
```

### [RoleService.php](../src/Services/RoleService.php)

```php
public static function rolesFor(int $userId, int $clientId): array;
public static function hasAnyRole(int $userId, int $clientId, array $roleKeys): bool;
public static function isSelfServiceUser(int $userId, int $clientId): bool;
public static function listAssignableClientRoles(): array;
public static function listAll(): array;
public static function updateName(int $roleId, string $newName, int $actorUserId): array;
```

### [ScheduleService.php](../src/Services/ScheduleService.php)

```php
public static function monthInfo(string $month): ?array;
public static function countWeekdays(DateTimeImmutable $start, DateTimeImmutable $end): int;
public static function isNonWorkingDay(string $date): bool;
public static function requiredHoursForMonth(float $workloadPercent, int $workingDays): float;
public static function listTemplates(int $clientId): array;
public static function saveTemplate(int $clientId, ?int $templateId, array $data, int $actorUserId): array;
public static function toggleTemplate(int $clientId, int $templateId, int $actorUserId): array;
public static function monthData(int $clientId, int $managerId, bool $isClientAdmin, array $month, ?int $locationEmployeeId = null): array;
public static function saveMonthlyWorkload(int $clientId, int $contractId, string $month, ?int $workloadId, int $actorUserId): array;
public static function templateCountsAsPlanned(string $templateType, string $scheduleDate): bool;
public static function shiftMinutesInMonth(string $scheduleDate, string $startTime, int $durationMinutes, array $month): int;
public static function formatHours(float $hours): string;
public static function formatBalance(float $hours): string;
public static function templateLabel(array $template): string;
public static function listScheduleContracts(int $clientId, int $managerId, bool $isClientAdmin, array $month, bool $forUpdate = false, ?int $locationEmployeeId = null, bool $includeDelegation = true): array;
public static function getMonthEntries(int $clientId, array $contracts, array $month): array;
public static function saveMonth(int $clientId, int $actorUserId, bool $isClientAdmin, string $managerMonth, mixed $assignments, int $repeatDays = 1): array;
public static function findFirstShiftOverlap(array $entries): ?string;
public static function formatTemplate(array $template): string;
public static function safeTemplateColor(mixed $color): string;
```

### [SchemaInstaller.php](../src/Services/SchemaInstaller.php)

```php
public static function ensureInstalled(): void;
```

### [SettingsService.php](../src/Services/SettingsService.php)

```php
public static function all(): array;
public static function set(string $key, string $value, int $actorUserId): void;
```

### [SubstituteService.php](../src/Services/SubstituteService.php)

```php
public static function activeScheduleManagers(int $clientId, int $userId): array;
public static function canAssignScheduleSubstitute(int $clientId, int $userId): bool;
public static function scheduleAssignments(int $clientId, int $userId, bool $isAdmin): array;
public static function assignScheduleSubstitute(int $clientId, int $actorId, array $data): array;
public static function endScheduleSubstitute(int $clientId, int $actorId, int $id): array;
public static function listForClient(int $clientId): array;
public static function eligibleSubstitutes(int $clientId, int $originalUserId): array;
public static function create(int $clientId, array $data, int $requestedByUserId): array;
public static function approve(int $clientId, int $substituteId, int $approverUserId): array;
public static function reject(int $clientId, int $substituteId, int $approverUserId): array;
```

### [SupportService.php](../src/Services/SupportService.php)

```php
public static function listVisible(int $clientId, int $userId, bool $systemWorkspace = false): array;
public static function unreadCount(int $clientId, int $userId, bool $systemWorkspace = false): int;
public static function markOpened(int $ticketId, int $clientId, int $userId, bool $systemWorkspace = false, int $lastReplyId = 0): bool;
public static function ticket(int $ticketId, int $clientId, int $userId, bool $systemWorkspace = false, bool $lock = false): ?array;
public static function recipientOptions(int $clientId, int $userId): array;
public static function create(int $clientId, int $userId, array $data): array;
public static function replies(int $ticketId, int $clientId, int $userId, bool $systemWorkspace = false): array;
public static function participants(int $ticketId, int $clientId, int $userId, bool $systemWorkspace = false): array;
public static function inviteOptions(int $ticketId, int $clientId, int $userId, bool $systemWorkspace = false): array;
public static function act(int $ticketId, int $clientId, int $userId, string $action, array $data, bool $systemWorkspace = false): array;
```

### [TranslationService.php](../src/Services/TranslationService.php)

```php
public static function translate(string $key, string $fallback = ''): string;
public static function textMap(): array;
public static function listForLanguage(int $languageId): array;
public static function missingForLanguage(int $languageId): array;
public static function createKey(string $translationKey, ?string $moduleContext, string $englishValue, int $actorUserId): array;
public static function upsertValue(int $keyId, int $languageId, string $value, int $actorUserId): void;
```

### [UserService.php](../src/Services/UserService.php)

```php
public static function listClientAdministrators(int $clientId): array;
public static function findClientAdministrator(int $clientId, int $userId): ?array;
public static function createClientAdministrator(int $clientId, array $data, int $actorUserId): array;
public static function updateClientAdministrator(int $clientId, int $userId, array $data, int $actorUserId): array;
public static function employeeListPageSize(int $clientId): int;
public static function saveEmployeeListPageSize(int $clientId, int $pageSize, int $actorUserId): void;
public static function employeeListPage(int $clientId, ?int $userId, string $search, int $page): array;
public static function listForClient(int $clientId, ?int $userId = null, string $search = '', ?int $limit = null, int $offset = 0): array;
public static function createForClient(int $clientId, array $data, int $actorUserId): array;
public static function findForClient(int $clientId, int $userId): ?array;
public static function updateForClient(int $clientId, int $userId, array $data, int $actorUserId): array;
public static function toggleStatus(int $clientId, int $userId, int $actorUserId): array;
public static function resetPassword(int $clientId, int $userId, int $actorUserId): array;
public static function changeOwnPassword(int $userId, string $currentPassword, string $newPassword): array;
```

### [ApplicationTime.php](../src/Support/ApplicationTime.php)

```php
public static function mysqlOffset(?DateTimeImmutable $instant = null): string;
public static function configureDatabase(PDO $pdo, string $offset): void;
```

### [ClientContext.php](../src/Support/ClientContext.php)

```php
public static function start(): void;
public static function isLoggedIn(): bool;
public static function userId(): ?int;
public static function clientId(): ?int;
public static function clientCode(): ?string;
public static function username(): ?string;
public static function displayName(): ?string;
public static function login(int $userId, int $clientId, string $clientCode, string $username, string $fullName = ''): void;
public static function logout(): void;
```

### [Csrf.php](../src/Support/Csrf.php)

```php
public static function token(): string;
public static function field(): string;
public static function validate(?string $token): bool;
```

### Auditvõtmete lähteviide

Kutse on teenuse/handler'i lähtekujul; interpolatsiooniga võtmed tuleb asendada vastava tegevusega. Parooli ega sessioonisaladusi auditisse ei lisata. Täieliku vana/uue objekti salvestamist iga kutse ei taga.

| Allikas | AuditLogService::log kutse |
|---|---|
| `public/index.php` | `AuditLogService::log(                         $userId,                         $clientId,                         'schedule.public_holidays_imported',                         'system_public_holidays',                         null,                         null,                         $result                     );` |
| `src/Services/AuthService.php` | `AuditLogService::log(null, null, 'auth.login_failed', 'system_users', $username);` |
| `src/Services/AuthService.php` | `AuditLogService::log((int) $user['id'], (int) $user['client_id'], 'auth.login_blocked', 'system_users', (string) $user['id']);` |
| `src/Services/AuthService.php` | `AuditLogService::log((int) $user['id'], (int) $user['client_id'], 'auth.login_success', 'system_users', (string) $user['id']);` |
| `src/Services/AuthService.php` | `AuditLogService::log(ClientContext::userId(), ClientContext::clientId(), 'auth.logout');` |
| `src/Services/ClientDomainEmbedService.php` | `AuditLogService::log($actorUserId, $clientId, 'client_domain_embed.configured', 'system_client_domain_embeds', (string) $clientId, null, $domain);` |
| `src/Services/ClientService.php` | `AuditLogService::log($actorUserId, $clientId, 'client.details_updated', 'system_clients', (string) $clientId);` |
| `src/Services/ClientService.php` | `AuditLogService::log($createdByUserId, $clientId, 'client.created', 'system_clients', (string) $clientId);` |
| `src/Services/ClientService.php` | `AuditLogService::log($createdByUserId, $clientId, 'client.first_user_created', 'system_users', (string) $userId);` |
| `src/Services/EmploymentCatalogService.php` | `AuditLogService::log($actorUserId, $clientId, "employment.{$catalog}.{$action}", $table, (string) $id);` |
| `src/Services/EmploymentCatalogService.php` | `AuditLogService::log($actorUserId, $clientId, "employment.{$catalog}.status_changed", $table, (string) $id, (string) $status, $newStatus);` |
| `src/Services/EmploymentContractService.php` | `AuditLogService::log($actorUserId, $clientId, 'employment.contracts.page_size_updated', 'employment_contract_list_settings', (string) $clientId, null, (string) $pageSize);` |
| `src/Services/EmploymentContractService.php` | `AuditLogService::log($actorUserId, $clientId, "employment.contract.{$action}", 'employment_contracts', (string) $contractId);` |
| `src/Services/EmploymentContractService.php` | `AuditLogService::log($actorUserId, $clientId, "employment.contract.{$action}", 'employment_contracts', (string) $contractId);` |
| `src/Services/InitialSetupService.php` | `AuditLogService::log($userId, $clientId, 'initial_setup.completed', 'system_users', (string) $userId);` |
| `src/Services/LanguageService.php` | `AuditLogService::log($actorUserId, null, 'language.created', 'system_languages', (string) db()->lastInsertId());` |
| `src/Services/LanguageService.php` | `AuditLogService::log($actorUserId, null, 'language.status_changed', 'system_languages', (string) $languageId);` |
| `src/Services/LegacyEmployeeImportService.php` | `AuditLogService::log(             $actorUserId,             $clientId,             'user.legacy_imported',             'system_users',             null,             null,             "Imported: {$imported}; skipped: {$skipped}"         );` |
| `src/Services/ModuleService.php` | `AuditLogService::log($actorUserId, null, 'module.created', 'system_modules', (string) db()->lastInsertId());` |
| `src/Services/ModuleService.php` | `AuditLogService::log($actorUserId, null, 'module.status_changed', 'system_modules', (string) $moduleId, $module['status'], $newStatus);` |
| `src/Services/ModuleService.php` | `AuditLogService::log($actorUserId, $clientId, 'client_module.status_changed', 'system_client_modules', $clientId . ':' . $moduleId, null, $newStatus);` |
| `src/Services/NotificationService.php` | `AuditLogService::log($actorUserId, $clientId, 'notification.sent', 'system_notifications', (string) db()->lastInsertId());` |
| `src/Services/OrgUnitService.php` | `AuditLogService::log($actorUserId, $clientId, 'org_unit.created', 'system_org_units', (string) db()->lastInsertId());` |
| `src/Services/PatientService.php` | `AuditLogService::log($actorUserId, $clientId, 'patient.created', 'patients', (string) db()->lastInsertId());` |
| `src/Services/PatientService.php` | `AuditLogService::log($actorUserId, $clientId, 'patient.status_changed', 'patients', (string) $patientId, $patient['status'], $newStatus);` |
| `src/Services/PropertyService.php` | `AuditLogService::log($actorUserId, $clientId, 'property_node.created', 'property_nodes', (string) db()->lastInsertId());` |
| `src/Services/PropertyService.php` | `AuditLogService::log($actorUserId, $clientId, 'property_node.reordered', 'property_nodes', (string) $nodeId, null, $direction);` |
| `src/Services/PropertyService.php` | `AuditLogService::log($actorUserId, $clientId, 'property_node.renamed', 'property_nodes', (string) $nodeId);` |
| `src/Services/RoleService.php` | `AuditLogService::log($actorUserId, null, 'role.renamed', 'system_roles', (string) $roleId, $role['name'], $newName);` |
| `src/Services/ScheduleService.php` | `AuditLogService::log($actorUserId, $clientId, "schedule.template.{$action}", 'employee_schedule_templates', (string) $templateId);` |
| `src/Services/ScheduleService.php` | `AuditLogService::log($actorUserId, $clientId, 'schedule.template.status_changed', 'employee_schedule_templates', (string) $templateId, (string) $status, $newStatus);` |
| `src/Services/ScheduleService.php` | `AuditLogService::log(             $actorUserId,             $clientId,             'schedule.monthly_workload_saved',             'employment_contract_monthly_workloads',             $contractId . ':' . $month         );` |
| `src/Services/ScheduleService.php` | `AuditLogService::log($actorUserId, $clientId, 'schedule.month_saved', 'employee_schedule_entries', $managerMonth);` |
| `src/Services/SettingsService.php` | `AuditLogService::log($actorUserId, null, 'setting.updated', 'system_settings', $key, null, $value);` |
| `src/Services/SubstituteService.php` | `AuditLogService::log($actorId, $clientId, 'schedule.substitute_assigned', 'system_substitutes', (string) db()->lastInsertId());` |
| `src/Services/SubstituteService.php` | `AuditLogService::log($actorId, $clientId, 'schedule.substitute_ended', 'system_substitutes', (string) $id);` |
| `src/Services/SubstituteService.php` | `AuditLogService::log($requestedByUserId, $clientId, 'substitute.requested', 'system_substitutes', (string) db()->lastInsertId());` |
| `src/Services/SubstituteService.php` | `AuditLogService::log($approverUserId, $clientId, 'substitute.approved', 'system_substitutes', (string) $substituteId);` |
| `src/Services/SubstituteService.php` | `AuditLogService::log($approverUserId, $clientId, 'substitute.rejected', 'system_substitutes', (string) $substituteId);` |
| `src/Services/SupportService.php` | `AuditLogService::log($userId, $clientId, 'support_ticket.created', 'system_support_tickets', (string) $id);` |
| `src/Services/SupportService.php` | `AuditLogService::log($userId, (int) $ticket['client_id'], 'support_ticket.' . $action,                 'system_support_tickets', (string) $ticketId, null, $action === 'invite' ? (string) $invitee : null);` |
| `src/Services/TranslationService.php` | `AuditLogService::log($actorUserId, null, 'translation.key_created', 'system_translation_keys', (string) $keyId);` |
| `src/Services/TranslationService.php` | `AuditLogService::log($actorUserId, null, 'translation.value_updated', 'system_translation_values', $keyId . ':' . $languageId, null, $value);` |
| `src/Services/UserService.php` | `AuditLogService::log($actorUserId, $clientId, 'client_admin.created', 'system_users', (string) $userId);` |
| `src/Services/UserService.php` | `AuditLogService::log($actorUserId, $clientId, 'client_admin.updated', 'system_users', (string) $userId);` |
| `src/Services/UserService.php` | `AuditLogService::log($actorUserId, $clientId, 'employees.page_size_updated', 'employee_list_settings', (string) $clientId, null, (string) $pageSize);` |
| `src/Services/UserService.php` | `AuditLogService::log($actorUserId, $clientId, 'user.created', 'system_users', (string) $userId);` |
| `src/Services/UserService.php` | `AuditLogService::log($actorUserId, $clientId, 'user.updated', 'system_users', (string) $userId);` |
| `src/Services/UserService.php` | `AuditLogService::log($actorUserId, $clientId, 'user.status_changed', 'system_users', (string) $userId, $user['status'], $newStatus);` |
| `src/Services/UserService.php` | `AuditLogService::log($actorUserId, $clientId, 'user.password_reset', 'system_users', (string) $userId);` |
| `src/Services/UserService.php` | `AuditLogService::log($userId, null, 'user.password_changed_self', 'system_users', (string) $userId);` |

## 4. Vaated ja vormiväljad

Kõik vaated on loetletud, ka need, millel pole vormi. PHP-ga dünaamilised atribuudid on lähtekujul. CSRF helper'i väli on lisatud eraldi, kui vaade seda kasutab. Meetod/action loetakse HTML-vormist; mitme vormi puhul on valikud loendina.

| Vaade | Vorm (meetod/action) | name-väljad |
|---|---|---|
| [views/admin/clients/create.php](../views/admin/clients/create.php) | post /admin/clients/create | client_code, company_name, registry_code, address, phone, email, contact_person_name, contact_person_id_code, contact_person_phone, contact_person_email, username, csrf_token |
| [views/admin/clients/index.php](../views/admin/clients/index.php) |  |  |
| [views/admin/clients/user-create.php](../views/admin/clients/user-create.php) | post /admin/clients/users/create | client_id, username, full_name, email, phone, csrf_token |
| [views/admin/clients/user-edit.php](../views/admin/clients/user-edit.php) | post /admin/clients/users/edit | client_id, user_id, full_name, email, phone, csrf_token |
| [views/admin/clients/view.php](../views/admin/clients/view.php) | post /admin/clients/users/reset-password; post /admin/clients/modules/toggle | client_id, user_id, module_id, csrf_token |
| [views/admin/dashboard.php](../views/admin/dashboard.php) |  |  |
| [views/admin/languages/create.php](../views/admin/languages/create.php) | post /admin/languages/create | language_code, name, csrf_token |
| [views/admin/languages/index.php](../views/admin/languages/index.php) | post /admin/languages/toggle | id, csrf_token |
| [views/admin/logs/index.php](../views/admin/logs/index.php) | get /admin/logs | action, client_code, username |
| [views/admin/modules/create.php](../views/admin/modules/create.php) | post /admin/modules/create | module_key, name, description, is_demo_available, csrf_token |
| [views/admin/modules/index.php](../views/admin/modules/index.php) | post /admin/modules/toggle | id, csrf_token |
| [views/admin/notifications/index.php](../views/admin/notifications/index.php) | get /admin/notifications; post (sama URL) | client_id, user_id, title, message, csrf_token |
| [views/admin/render.php](../views/admin/render.php) |  |  |
| [views/admin/roles/index.php](../views/admin/roles/index.php) | post /admin/roles/update | role_id, name, csrf_token |
| [views/admin/settings/index.php](../views/admin/settings/index.php) | post /admin/settings | setting_key, setting_value, csrf_token |
| [views/admin/shell.php](../views/admin/shell.php) |  |  |
| [views/admin/support/index.php](../views/admin/support/index.php) |  |  |
| [views/admin/support/view.php](../views/admin/support/view.php) |  |  |
| [views/admin/translations/create.php](../views/admin/translations/create.php) | post /admin/translations/create | translation_key, module_context, english_value, csrf_token |
| [views/admin/translations/index.php](../views/admin/translations/index.php) | get /admin/translations; post /admin/translations/update | language, key_id, language_id, value, csrf_token |
| [views/admin/translations/missing.php](../views/admin/translations/missing.php) | post /admin/translations/update | key_id, language_id, value, csrf_token |
| [views/auth/login.php](../views/auth/login.php) | post (sama URL) | client_code, username, password, csrf_token |
| [views/errors/400.php](../views/errors/400.php) |  |  |
| [views/errors/403.php](../views/errors/403.php) |  |  |
| [views/errors/404.php](../views/errors/404.php) |  |  |
| [views/errors/500.php](../views/errors/500.php) |  |  |
| [views/home.php](../views/home.php) |  |  |
| [views/layout/footer.php](../views/layout/footer.php) |  |  |
| [views/layout/header.php](../views/layout/header.php) | post /language | viewport, return_to, language_code, csrf_token |
| [views/panel/account/index.php](../views/panel/account/index.php) | post /panel/account/password | current_password, new_password, new_password_confirm, csrf_token |
| [views/panel/client/index.php](../views/panel/client/index.php) | post /panel/client | company_name, address, phone, email, contact_person_name, contact_person_phone, contact_person_email, csrf_token |
| [views/panel/dashboard.php](../views/panel/dashboard.php) |  |  |
| [views/panel/domain-embed/index.php](../views/panel/domain-embed/index.php) | post /panel/domain-embed | customer_domain, csrf_token |
| [views/panel/employment/catalog.php](../views/panel/employment/catalog.php) | post (sama URL); GET (sama URL) | name, workload_percent, id, action, csrf_token |
| [views/panel/employment/contract-form.php](../views/panel/employment/contract-form.php) | post (sama URL) | id, employee_id, contract_type, property_node_id, job_title_id, workload_id, department_id, manager_user_id, start_date, end_date, csrf_token |
| [views/panel/employment/contracts.php](../views/panel/employment/contracts.php) | get /panel/employment/contracts; post /panel/employment/contracts/list-settings; post /panel/employment/contracts/archive | search, page_size, id, csrf_token |
| [views/panel/employment/nav.php](../views/panel/employment/nav.php) |  |  |
| [views/panel/modules/index.php](../views/panel/modules/index.php) |  |  |
| [views/panel/notifications/index.php](../views/panel/notifications/index.php) | post /panel/notifications/read | id, archive, page, csrf_token |
| [views/panel/org/create.php](../views/panel/org/create.php) | post /panel/org/create | org_level, name, parent_org_unit_id, manager_user_id, csrf_token |
| [views/panel/org/index.php](../views/panel/org/index.php) |  |  |
| [views/panel/pagination.php](../views/panel/pagination.php) |  |  |
| [views/panel/patients/create.php](../views/panel/patients/create.php) | post /panel/patients/create | full_name, personal_code, birth_date, phone, email, notes, csrf_token |
| [views/panel/patients/index.php](../views/panel/patients/index.php) | post /panel/patients/toggle | id, csrf_token |
| [views/panel/property/create.php](../views/panel/property/create.php) | post /panel/property/create | parent_id, node_type, name, csrf_token |
| [views/panel/property/edit.php](../views/panel/property/edit.php) | post /panel/property/edit | id, name, csrf_token |
| [views/panel/property/index.php](../views/panel/property/index.php) | post /panel/property/reorder | id, direction, csrf_token |
| [views/panel/render.php](../views/panel/render.php) |  |  |
| [views/panel/reports/index.php](../views/panel/reports/index.php) |  |  |
| [views/panel/schedule/holidays.php](../views/panel/schedule/holidays.php) | post /panel/schedule/holidays/import | public_holidays_xml, csrf_token |
| [views/panel/schedule/index.php](../views/panel/schedule/index.php) | get /panel/schedule; post /panel/schedule | month, assignments[<?= $contractId ?>][<?= e($date) ?>], csrf_token |
| [views/panel/schedule/nav.php](../views/panel/schedule/nav.php) |  |  |
| [views/panel/schedule/print.php](../views/panel/schedule/print.php) | get /panel/schedule/print | month |
| [views/panel/schedule/substitutes.php](../views/panel/schedule/substitutes.php) | post /panel/schedule/substitutes | original_user_id, substitute_user_id, starts_at, ends_at, reason, action, id, csrf_token |
| [views/panel/schedule/templates.php](../views/panel/schedule/templates.php) | post (sama URL); GET (sama URL) | template_type, code, name, start_time, duration_hours, color_hex, id, action, csrf_token |
| [views/panel/shell.php](../views/panel/shell.php) |  |  |
| [views/panel/substitutes/create.php](../views/panel/substitutes/create.php) | get /panel/substitutes/create; post /panel/substitutes/create | original_user_id, substitute_user_id, substitute_type, starts_at, ends_at, reason, csrf_token |
| [views/panel/substitutes/index.php](../views/panel/substitutes/index.php) | post /panel/substitutes/approve; post /panel/substitutes/reject | id, csrf_token |
| [views/panel/support/create.php](../views/panel/support/create.php) | post /panel/support/create | recipient_type, recipient_user_id, subject, message, csrf_token |
| [views/panel/support/index.php](../views/panel/support/index.php) |  |  |
| [views/panel/support/view.php](../views/panel/support/view.php) |  |  |
| [views/panel/users/create.php](../views/panel/users/create.php) | post /panel/users/create | username, full_name, email, phone, role_key, csrf_token |
| [views/panel/users/edit.php](../views/panel/users/edit.php) | post (sama URL) | user_id, full_name, email, phone, role_key, csrf_token |
| [views/panel/users/import.php](../views/panel/users/import.php) | post /panel/users/import | MAX_FILE_SIZE, legacy_sql, csrf_token |
| [views/panel/users/index.php](../views/panel/users/index.php) | get /panel/users; post /panel/users/list-settings; post /panel/users/reset-password; post /panel/users/toggle | search, page_size, user_id, csrf_token |
| [views/render.php](../views/render.php) |  |  |
| [views/setup/create_admin.php](../views/setup/create_admin.php) | post /setup | full_name, email, username, password, csrf_token |
| [views/support/index.php](../views/support/index.php) |  |  |
| [views/support/view.php](../views/support/view.php) | post (sama URL) | id, message, user_id, csrf_token |

## 5. Tabelid ja migratsioonide register

CREATE tabeliallikas ei ole lõplik skeem: järgnevad ALTER-id ja PHP parandus-handler'id kuuluvad samuti taastamise juurde. [SQL-taastepakett](15-andmebaasi-taastepakett.sql) sisaldab kõiki algseid lauseid ning handler'ite tingimuslikke ekvivalente.

| Tabel | Esmane CREATE migratsioon |
|---|---|
| `system_settings` | `001_core_schema.sql` |
| `system_clients` | `001_core_schema.sql` |
| `system_modules` | `001_core_schema.sql` |
| `system_module_versions` | `001_core_schema.sql` |
| `system_languages` | `001_core_schema.sql` |
| `system_translation_keys` | `001_core_schema.sql` |
| `system_translation_values` | `001_core_schema.sql` |
| `system_users` | `001_core_schema.sql` |
| `system_roles` | `001_core_schema.sql` |
| `system_permissions` | `001_core_schema.sql` |
| `system_role_permissions` | `001_core_schema.sql` |
| `system_org_units` | `001_core_schema.sql` |
| `system_user_roles` | `001_core_schema.sql` |
| `system_substitutes` | `001_core_schema.sql` |
| `system_audit_logs` | `001_core_schema.sql` |
| `system_version_logs` | `001_core_schema.sql` |
| `system_initial_setup` | `001_core_schema.sql` |
| `system_client_modules` | `002_client_modules.sql` |
| `system_notifications` | `003_notifications_support.sql` |
| `system_support_tickets` | `003_notifications_support.sql` |
| `patients` | `004_module_patients.sql` |
| `system_client_module_provisions` | `008_module_tenant_provisioning.sql` |
| `employee_module_settings` | `008_module_tenant_provisioning.sql` |
| `property_nodes` | `015_property_structure.sql` |
| `employment_job_titles` | `017_employment_module.sql` |
| `employment_departments` | `017_employment_module.sql` |
| `employment_contracts` | `017_employment_module.sql` |
| `employment_workloads` | `018_employment_workloads.sql` |
| `employee_schedule_templates` | `023_employee_scheduling.sql` |
| `employee_schedule_entries` | `023_employee_scheduling.sql` |
| `employment_contract_monthly_workloads` | `050_monthly_workload_overrides.sql` |
| `system_public_holidays` | `054_public_holidays.sql` |
| `system_support_replies` | `071_support_conversations.sql` |
| `system_support_participants` | `071_support_conversations.sql` |
| `system_notification_reads` | `073_notification_archive.sql` |
| `system_support_reads` | `074_support_unopened_count.sql` |
| `system_support_reply_reads` | `075_support_reply_unread.sql` |
| `system_client_domain_embeds` | `081_client_domain_embed.sql` |
| `employee_list_settings` | `083_employee_list_pagination.sql` |
| `employment_contract_list_settings` | `084_contract_list_pagination.sql` |

### Täpne installeri register

```php
['file' => __DIR__ . '/../../sql/001_core_schema.sql', 'marker' => 'system_initial_setup'],
['file' => __DIR__ . '/../../sql/002_client_modules.sql', 'marker' => 'system_client_modules'],
['file' => __DIR__ . '/../../sql/003_notifications_support.sql', 'marker' => 'system_notifications'],
['file' => __DIR__ . '/../../sql/004_module_patients.sql', 'marker' => 'patients'],
['file' => __DIR__ . '/../../sql/005_module_organisation.sql', 'rowCheck' => "SELECT 1 FROM system_modules WHERE module_key = 'organisation' LIMIT 1"],
['file' => __DIR__ . '/../../sql/006_employee_users.sql', 'rowCheck' => "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_users' AND COLUMN_NAME = 'phone' LIMIT 1"],
['file' => __DIR__ . '/../../sql/007_legacy_employee_import.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.20' LIMIT 1"],
['file' => __DIR__ . '/../../sql/008_module_tenant_provisioning.sql', 'rowCheck' => "SELECT 1 FROM system_modules WHERE module_key = 'employees' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_client_module_provisions') AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_module_settings') LIMIT 1"],
['file' => __DIR__ . '/../../sql/009_employee_module_documentation.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.22' LIMIT 1"],
['file' => __DIR__ . '/../../sql/010_readme_version_history.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.23' LIMIT 1"],
['file' => __DIR__ . '/../../sql/011_signed_in_display_name.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.24' LIMIT 1"],
['file' => __DIR__ . '/../../sql/012_employee_edit_org_unit.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.25' LIMIT 1"],
['file' => __DIR__ . '/../../sql/013_employee_create_org_unit.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.26' LIMIT 1"],
['file' => __DIR__ . '/../../sql/014_bilingual_changelog.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.27' LIMIT 1"],
['file' => __DIR__ . '/../../sql/015_property_structure.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.28' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_nodes') AND EXISTS (SELECT 1 FROM system_modules WHERE module_key = 'property') LIMIT 1"],
['file' => __DIR__ . '/../../sql/016_group_panel_modules.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.29' LIMIT 1"],
['file' => __DIR__ . '/../../sql/017_employment_module.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.30' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts') AND EXISTS (SELECT 1 FROM system_modules WHERE module_key = 'employment') LIMIT 1"],
['file' => __DIR__ . '/../../sql/018_employment_workloads.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.31' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_workloads') AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts' AND COLUMN_NAME = 'workload_id') LIMIT 1"],
['file' => __DIR__ . '/../../sql/019_contract_workload_schedule_scope.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.32' LIMIT 1"],
['file' => __DIR__ . '/../../sql/020_contract_property_path.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.33' LIMIT 1"],
['file' => __DIR__ . '/../../sql/021_property_sibling_order.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.34' AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'property_nodes' AND COLUMN_NAME = 'sort_order') LIMIT 1"],
['file' => __DIR__ . '/../../sql/022_employee_active_contract_column.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.35' LIMIT 1"],
['file' => __DIR__ . '/../../sql/023_employee_scheduling.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.36' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries') AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'created_by') AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'updated_by') AND EXISTS (SELECT 1 FROM system_modules WHERE module_key = 'schedule') LIMIT 1"],
['file' => __DIR__ . '/../../sql/024_schedule_save_optimization.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.37' LIMIT 1"],
['file' => __DIR__ . '/../../sql/025_schedule_save_error_logging.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.38' LIMIT 1"],
['file' => __DIR__ . '/../../sql/026_schedule_error_code_feedback.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.39' LIMIT 1"],
['file' => __DIR__ . '/../../sql/027_schedule_audit_columns.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.40' AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'created_by') AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND COLUMN_NAME = 'updated_by') LIMIT 1", 'handler' => 'scheduleAuditColumns'],
['file' => __DIR__ . '/../../sql/028_schedule_repair_compatibility.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.41' LIMIT 1"],
['file' => __DIR__ . '/../../sql/029_schedule_modal_picker.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.42' LIMIT 1"],
['file' => __DIR__ . '/../../sql/030_schedule_autosave.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.43' LIMIT 1"],
['file' => __DIR__ . '/../../sql/031_schedule_template_colors.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.44' AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_templates' AND COLUMN_NAME = 'color_hex') LIMIT 1", 'handler' => 'scheduleTemplateColor'],
['file' => __DIR__ . '/../../sql/032_schedule_color_allowlist.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.45' LIMIT 1"],
['file' => __DIR__ . '/../../sql/033_schedule_modal_active_templates.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.46' LIMIT 1"],
['file' => __DIR__ . '/../../sql/034_schedule_collapsible_groups.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.47' LIMIT 1"],
['file' => __DIR__ . '/../../sql/035_schedule_viewport_width.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.48' LIMIT 1"],
['file' => __DIR__ . '/../../sql/036_schedule_workload_under_employee.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.49' LIMIT 1"],
['file' => __DIR__ . '/../../sql/037_schedule_hour_balances.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.50' LIMIT 1"],
['file' => __DIR__ . '/../../sql/038_schedule_balance_autosave.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.51' LIMIT 1"],
['file' => __DIR__ . '/../../sql/039_schedule_four_month_balance.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.52' LIMIT 1"],
['file' => __DIR__ . '/../../sql/040_schedule_cross_month_hours.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.53' LIMIT 1"],
['file' => __DIR__ . '/../../sql/041_schedule_month_boundary_balances.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.54' LIMIT 1"],
['file' => __DIR__ . '/../../sql/042_schedule_contract_day_index.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.55' AND EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND INDEX_NAME = 'uq_employee_schedule_contract_day' AND NON_UNIQUE = 0) AND EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_entries' AND INDEX_NAME = 'idx_employee_schedule_employee_day') LIMIT 1", 'handler' => 'scheduleContractDayIndex'],
['file' => __DIR__ . '/../../sql/043_schedule_temp_label.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.56' LIMIT 1"],
['file' => __DIR__ . '/../../sql/044_schedule_weekday_exception_hours.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.57' LIMIT 1"],
['file' => __DIR__ . '/../../sql/045_schedule_single_cell_autosave.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.58' LIMIT 1"],
['file' => __DIR__ . '/../../sql/046_employment_contract_archive.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.59' AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contracts' AND COLUMN_NAME = 'archived_at') LIMIT 1", 'handler' => 'employmentContractArchive'],
['file' => __DIR__ . '/../../sql/047_schedule_block_templates.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.60' AND EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_schedule_templates' AND COLUMN_NAME = 'template_type' AND COLUMN_TYPE LIKE '%block%') LIMIT 1"],
['file' => __DIR__ . '/../../sql/048_ui_languages.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.61' AND EXISTS (SELECT 1 FROM system_languages WHERE language_code = 'et' AND is_active = 1) AND EXISTS (SELECT 1 FROM system_languages WHERE language_code = 'ru' AND is_active = 1) AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = 'layout.language') LIMIT 1"],
['file' => __DIR__ . '/../../sql/049_ui_static_translations.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.62' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = CONCAT('ui.', SHA2('Access level', 256)) AND module_context = 'ui') AND EXISTS (SELECT 1 FROM system_translation_values v INNER JOIN system_translation_keys k ON k.id = v.translation_key_id INNER JOIN system_languages l ON l.id = v.language_id WHERE k.translation_key = CONCAT('ui.', SHA2('Access level', 256)) AND l.language_code = 'et' AND v.status = 'active') LIMIT 1"],
['file' => __DIR__ . '/../../sql/050_monthly_workload_overrides.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.63' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contract_monthly_workloads') AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = 'panel.contract_default_workload') AND EXISTS (SELECT 1 FROM system_translation_values v INNER JOIN system_translation_keys k ON k.id = v.translation_key_id INNER JOIN system_languages l ON l.id = v.language_id WHERE k.translation_key = 'panel.contract_default_workload' AND l.language_code = 'et' AND v.status = 'active') AND EXISTS (SELECT 1 FROM system_translation_values v INNER JOIN system_translation_keys k ON k.id = v.translation_key_id INNER JOIN system_languages l ON l.id = v.language_id WHERE k.translation_key = 'panel.contract_default_workload' AND l.language_code = 'ru' AND v.status = 'active') LIMIT 1"],
['file' => __DIR__ . '/../../sql/051_schedule_monthly_workload_modal.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.64' LIMIT 1"],
['file' => __DIR__ . '/../../sql/052_schedule_weekend_color.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.65' LIMIT 1"],
['file' => __DIR__ . '/../../sql/053_schedule_weekend_transparency.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.66' LIMIT 1"],
['file' => __DIR__ . '/../../sql/054_public_holidays.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.67' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_public_holidays') LIMIT 1"],
['file' => __DIR__ . '/../../sql/055_public_holiday_translations.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.68' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = CONCAT('ui.', SHA2('Public holidays XML', 256))) LIMIT 1"],
['file' => __DIR__ . '/../../sql/056_public_holiday_tab_translations.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.69' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = CONCAT('ui.', SHA2('Public holidays', 256))) LIMIT 1"],
['file' => __DIR__ . '/../../sql/057_schedule_sum_label_translation.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.70' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = CONCAT('ui.', SHA2('Sum h', 256))) LIMIT 1"],
['file' => __DIR__ . '/../../sql/058_schedule_printing.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.71' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = CONCAT('ui.', SHA2('Print schedule', 256))) LIMIT 1"],
['file' => __DIR__ . '/../../sql/059_schedule_print_row_height.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.72' LIMIT 1"],
['file' => __DIR__ . '/../../sql/060_schedule_tab_visibility.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.73' LIMIT 1"],
['file' => __DIR__ . '/../../sql/061_schedule_print_admin_tabs.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.74' LIMIT 1"],
['file' => __DIR__ . '/../../sql/062_documentation_alignment.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.75' LIMIT 1"],
['file' => __DIR__ . '/../../sql/063_reconstruction_documentation.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.76' LIMIT 1"],
['file' => __DIR__ . '/../../sql/064_documentation_migration_delimiter_fix.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.77' LIMIT 1"],
['file' => __DIR__ . '/../../sql/065_client_management_read_only.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.78' LIMIT 1"],
['file' => __DIR__ . '/../../sql/066_level_f_self_service_scope.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.79' LIMIT 1"],
['file' => __DIR__ . '/../../sql/067_level_f_schedule_print_scope.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.80' LIMIT 1"],
['file' => __DIR__ . '/../../sql/068_level_f_hidden_modules_property.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.81' LIMIT 1"],
['file' => __DIR__ . '/../../sql/069_manager_own_schedule_scope.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.82' LIMIT 1"],
['file' => __DIR__ . '/../../sql/070_schedule_manager_substitutes.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.83' LIMIT 1"],
['file' => __DIR__ . '/../../sql/071_support_conversations.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.84' LIMIT 1", 'handler' => 'supportTicketColumns'],
['file' => __DIR__ . '/../../sql/072_estonian_time_zone.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.85' LIMIT 1"],
['file' => __DIR__ . '/../../sql/073_notification_archive.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.86' LIMIT 1"],
['file' => __DIR__ . '/../../sql/074_support_unopened_count.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.87' LIMIT 1"],
['file' => __DIR__ . '/../../sql/075_support_reply_unread.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.88' LIMIT 1"],
['file' => __DIR__ . '/../../sql/076_support_unread_rows.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.89' LIMIT 1"],
['file' => __DIR__ . '/../../sql/077_property_type_translations.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.90' LIMIT 1"],
['file' => __DIR__ . '/../../sql/078_new_job_title_translation.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.91' LIMIT 1"],
['file' => __DIR__ . '/../../sql/079_employment_ui_translations.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.92' LIMIT 1"],
['file' => __DIR__ . '/../../sql/080_schedule_consecutive_exception_translations.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.93' LIMIT 1"],
['file' => __DIR__ . '/../../sql/081_client_domain_embed.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.94' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_client_domain_embeds') AND EXISTS (SELECT 1 FROM system_modules WHERE module_key = 'client_domain_embed') LIMIT 1"],
['file' => __DIR__ . '/../../sql/082_client_administrator_management.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.95' AND EXISTS (SELECT 1 FROM system_translation_keys WHERE translation_key = 'admin.client_admins') LIMIT 1"],
['file' => __DIR__ . '/../../sql/083_employee_list_pagination.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.96' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_list_settings') LIMIT 1"],
['file' => __DIR__ . '/../../sql/084_contract_list_pagination.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.97' AND EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employment_contract_list_settings') LIMIT 1"],
['file' => __DIR__ . '/../../sql/085_property_tree_collapse.sql', 'rowCheck' => "SELECT 1 FROM system_version_logs WHERE version = '1.98' LIMIT 1"],
```

## 6. Käivitusfailide kontrollsummad

SHA-256 on selle tööpuu tuvastamiseks, mitte dokumentatsiooni muutumatuse tõend ega taastatud koodi kohustuslik kontrollsumma. Prindivaates oli auditi alguses kasutaja pooleliolev muudatus (tõlgitud kuunimi ja päevanumbri märgistus); selle failiräsi kajastab tööpuud, mitte varasemat kinnitatud väljalaset. README/CHANGELOG/docs pole tabelis: need sisaldavad inventuuri ennast või väljalaskemetaandmeid.

| Fail | SHA-256 |
|---|---|
| [.env.example](../.env.example) | `48ef6325d08338dc40aa050d5905541f3d0b23fea48d60ac87eff87d8f986a0d` |
| [.gitignore](../.gitignore) | `a510932e756d1a231c404fc66d25ace6b26def98ae416062466267a1c15fc1b8` |
| [.htaccess](../.htaccess) | `a7bf99dc6050ad65c2ed70b4a75b5d047be6f0d38f2eb1730616cf2718de5a19` |
| [config/database.php](../config/database.php) | `f68506f3c78f2d5a847f7ebcd3fce37d56cd396b37842d0f37eb31f761644864` |
| [config/env.php](../config/env.php) | `83e93c36295f31e41d406c3e4a361b298417242529aa0ee075c3dbeade0b00fe` |
| [index.php](../index.php) | `207fcdd589d2388abc7f275127c7f141906f25af0968f9ae83c2c108865ed972` |
| [public/assets/css/app.css](../public/assets/css/app.css) | `284f388de6f317d91155283884096a2ae4880a5a03127d5fd1f5e7721e2825ea` |
| [public/index.php](../public/index.php) | `330f6ccecacd20c0fb1eddc77637b11c149b0edb03c28973a4ae9c154b6abbaf` |
| [sql/001_core_schema.sql](../sql/001_core_schema.sql) | `8649d0c9edc42cdec495ffa9d05e19cc2705ad2fe97e6262bb001630147fb17a` |
| [sql/002_client_modules.sql](../sql/002_client_modules.sql) | `47a7a2a0f14e2cf7841542abfa5ecc99ee5d20fd845761348a181e2396794da1` |
| [sql/003_notifications_support.sql](../sql/003_notifications_support.sql) | `141cd5ae46803b3601310f91c46318fac5d1b85f3d12d5f92c7e3e53f03bc96e` |
| [sql/004_module_patients.sql](../sql/004_module_patients.sql) | `5f57582ed43158346da3a793c145e501de7c6c953a50e0283062132fbbef7256` |
| [sql/005_module_organisation.sql](../sql/005_module_organisation.sql) | `83d8490cb176046c8de2eb1a73a81f9f0599d75b75a9903e8167c655f7d23e0c` |
| [sql/006_employee_users.sql](../sql/006_employee_users.sql) | `c23eedc6c635b90254551819b79add85e231cb02d492442be52c13a3d378c683` |
| [sql/007_legacy_employee_import.sql](../sql/007_legacy_employee_import.sql) | `eec1053e97c19afd15b8a9a3c2a31f02c9ca22c51ef39fbad087499a736c38cf` |
| [sql/008_module_tenant_provisioning.sql](../sql/008_module_tenant_provisioning.sql) | `665d145dfe254b49263f47bf22535de37e812e9225460aa9bdd77392b2c2dcb9` |
| [sql/009_employee_module_documentation.sql](../sql/009_employee_module_documentation.sql) | `37cbfbb76312885a6814baa15f9198e86300f399c69b1439f0523d393fa9a078` |
| [sql/010_readme_version_history.sql](../sql/010_readme_version_history.sql) | `6420b79dbf759103d565f1bef8303b182ed50c20b28ae0bc02b6cf10a16abc5d` |
| [sql/011_signed_in_display_name.sql](../sql/011_signed_in_display_name.sql) | `920415b6adcd5e4ec8cee3bfbe670b44ff315d0a9cf41b32844f5e02946e991d` |
| [sql/012_employee_edit_org_unit.sql](../sql/012_employee_edit_org_unit.sql) | `e0b8967bbff4d7bdfb0e6c876772ed4d1e86c6431af1abe523e22d841e2c9de7` |
| [sql/013_employee_create_org_unit.sql](../sql/013_employee_create_org_unit.sql) | `6d408b8bad17ab976332c54c344e9117f8688000de50853ecc3437ec99fbb2e2` |
| [sql/014_bilingual_changelog.sql](../sql/014_bilingual_changelog.sql) | `aebf2f6a0e728f186428895808688799cbd3bd159a57c6c61c8c9251d7a19dd4` |
| [sql/015_property_structure.sql](../sql/015_property_structure.sql) | `9e05e547986b24cadb14fc5497a318031b1968faebef7065760401d22b8256bd` |
| [sql/016_group_panel_modules.sql](../sql/016_group_panel_modules.sql) | `932d645aec932c0f5d603ba1bdf8ceb19958ed371b21652c88398bbcfda1f26a` |
| [sql/017_employment_module.sql](../sql/017_employment_module.sql) | `01a4a64e85e7b3e3efa1232c2ec07f533a55fb18c3ff7b03296c01c30832b9cd` |
| [sql/018_employment_workloads.sql](../sql/018_employment_workloads.sql) | `f3a3e2c37dd2a2cbe937751bd8419d5e9f48709a2e65df1ba3fad1051f0a8a18` |
| [sql/019_contract_workload_schedule_scope.sql](../sql/019_contract_workload_schedule_scope.sql) | `973ab3d8beb38a1bda7de5abce37a00b1714ac7a743677cbb8198eaeed61f9da` |
| [sql/020_contract_property_path.sql](../sql/020_contract_property_path.sql) | `b7428f82daad29a2cedffb14ad45748adf173134cf03b2db98f0fb7057483286` |
| [sql/021_property_sibling_order.sql](../sql/021_property_sibling_order.sql) | `dbc139b7eb720e6eb662270e113e0f54c225bdc369a0912083455b65893c1786` |
| [sql/022_employee_active_contract_column.sql](../sql/022_employee_active_contract_column.sql) | `c8cd8a420ddafd5bf0c410ad120d4c7976f5f9607de3fcfd036d0d08dc263026` |
| [sql/023_employee_scheduling.sql](../sql/023_employee_scheduling.sql) | `98ea2e459bb1f77aae8d69340e3b5611a2e7ab500a76e52b7b8ff2faa35df9c2` |
| [sql/024_schedule_save_optimization.sql](../sql/024_schedule_save_optimization.sql) | `1ba7c1b616a4548cd3a87041f883b366b0d23ed3ff4cdb70ada51ac43c35b3b1` |
| [sql/025_schedule_save_error_logging.sql](../sql/025_schedule_save_error_logging.sql) | `680d5aa6f8d5ba203e092f7352030fc61e83dd1a8a16fe8f1a982a65ae2cc801` |
| [sql/026_schedule_error_code_feedback.sql](../sql/026_schedule_error_code_feedback.sql) | `6b634c839850ee11c1d06c65f0179a5a505cdf50b1190c31cdaaa0801aca6af1` |
| [sql/027_schedule_audit_columns.sql](../sql/027_schedule_audit_columns.sql) | `619d876708e2417a2daea41dfab10a7d6710ebd1da33781221d104cf46ddb93a` |
| [sql/028_schedule_repair_compatibility.sql](../sql/028_schedule_repair_compatibility.sql) | `a9590e0c1dc11967ae1f6554ab2e0979366e8f080dc11f675369bb8c66f4df30` |
| [sql/029_schedule_modal_picker.sql](../sql/029_schedule_modal_picker.sql) | `87deb4c6a70b4a5bf1c7bbfb516f26a8d3a82bbe1dab2e6679f3939993c569e4` |
| [sql/030_schedule_autosave.sql](../sql/030_schedule_autosave.sql) | `b08b2bc1c31ee35fd7b37157b6a974494b7b98a830d9463ca0fdea0f71edeeda` |
| [sql/031_schedule_template_colors.sql](../sql/031_schedule_template_colors.sql) | `6db0a6b2fbada8a6ae57025a047ed6db40307135a89c3987b5793ae53e45e68c` |
| [sql/032_schedule_color_allowlist.sql](../sql/032_schedule_color_allowlist.sql) | `9a3a8c957f4ed2afcc9a956198d622c2e9b79b6aa4a725ee7853ade369c11e64` |
| [sql/033_schedule_modal_active_templates.sql](../sql/033_schedule_modal_active_templates.sql) | `3e83ecb2b905bc29cf0006c5257884fb161f74edf1f285dcf2b9bd439a8ae93d` |
| [sql/034_schedule_collapsible_groups.sql](../sql/034_schedule_collapsible_groups.sql) | `abc32eb3e624563bebc1cf527ba08572e52fc8b6e299c431bdd7f447d56240ae` |
| [sql/035_schedule_viewport_width.sql](../sql/035_schedule_viewport_width.sql) | `23743f8f2bada6202b3e4b42df794aa7a7028c2a096ef414d53bac04db8742fe` |
| [sql/036_schedule_workload_under_employee.sql](../sql/036_schedule_workload_under_employee.sql) | `f27258a727d6fae7101aa921ef8fd8dc2d8437cd89b7d80faa03ab2449cd4a4c` |
| [sql/037_schedule_hour_balances.sql](../sql/037_schedule_hour_balances.sql) | `db2ce41415847910b20d4a6749492fdbe146dc045948205684df0e3a4a243a60` |
| [sql/038_schedule_balance_autosave.sql](../sql/038_schedule_balance_autosave.sql) | `4e54c155253b3683e317c142a3d47db8fcdc41c7f712736721bcb55c81476d15` |
| [sql/039_schedule_four_month_balance.sql](../sql/039_schedule_four_month_balance.sql) | `f5a92160beb89c259e19b17e1c00599dc4f7f77a6da47492568364a7efe42ee3` |
| [sql/040_schedule_cross_month_hours.sql](../sql/040_schedule_cross_month_hours.sql) | `844de4665085c510fbacfc7877c1435c9d6cd59e31489caf11f8a458ef1f6975` |
| [sql/041_schedule_month_boundary_balances.sql](../sql/041_schedule_month_boundary_balances.sql) | `25e6d69fe6b864ce6e3bc2543361183461861f84423644f6f1cd4097e66fb1f1` |
| [sql/042_schedule_contract_day_index.sql](../sql/042_schedule_contract_day_index.sql) | `61f075183d2181063b289aae95e2b49f07250313280618b3e427723059fefc44` |
| [sql/043_schedule_temp_label.sql](../sql/043_schedule_temp_label.sql) | `c4a6e90ca2da7ad1e4776c46558029aded367f1269690a4c9d3b299dcde62705` |
| [sql/044_schedule_weekday_exception_hours.sql](../sql/044_schedule_weekday_exception_hours.sql) | `0a4e3f12ad442200981c0dad5b221efc08f58ad91f3629fb37cfbc8275eb540a` |
| [sql/045_schedule_single_cell_autosave.sql](../sql/045_schedule_single_cell_autosave.sql) | `08be5b3890d2b64dedb8e6001fb6bcde8b709ab5dffdf035340fd0f737f2593d` |
| [sql/046_employment_contract_archive.sql](../sql/046_employment_contract_archive.sql) | `5a8d5a92cd07efa61dd2b5b5f3bcf0b57900d6622ccf96fc08e55aed173b473c` |
| [sql/047_schedule_block_templates.sql](../sql/047_schedule_block_templates.sql) | `ca4e56e302df9f70f14ae08a50d90fdd718177427b03325b8a9577f46337aef6` |
| [sql/048_ui_languages.sql](../sql/048_ui_languages.sql) | `ed048a4a1cc0a5bbf058c22a2fd15c63e08b74d7527097c111061397976de90d` |
| [sql/049_ui_static_translations.sql](../sql/049_ui_static_translations.sql) | `5c41edc480bee429142c3c810a255af4a8452468203fd95dd000e6589e1c1aa3` |
| [sql/050_monthly_workload_overrides.sql](../sql/050_monthly_workload_overrides.sql) | `77e870993a2956d8c3191acd1bb23e277d966d0a017a9eaf681f6296251c6479` |
| [sql/051_schedule_monthly_workload_modal.sql](../sql/051_schedule_monthly_workload_modal.sql) | `8bebff4b5605130d187b6fbfa4338172f223f1c2396294a585f646ac2707a474` |
| [sql/052_schedule_weekend_color.sql](../sql/052_schedule_weekend_color.sql) | `93d955a0ca490db60b78ab33853ecea19709c248ad238920f2b2636aff0ae8bc` |
| [sql/053_schedule_weekend_transparency.sql](../sql/053_schedule_weekend_transparency.sql) | `31c5cfe6a3c672e646c489cf51fc1c2cf246e02b3e035a89b2e5256cf6285d0f` |
| [sql/054_public_holidays.sql](../sql/054_public_holidays.sql) | `fcdc0ad5230ea42d8bfc65541d63915658cb053ec5edf6a8af9d0cb417bb9bbc` |
| [sql/055_public_holiday_translations.sql](../sql/055_public_holiday_translations.sql) | `ac67a52671d0400eae93cc38a1ce694e3ef72583d1c5c9ae35c9752aeaab96e7` |
| [sql/056_public_holiday_tab_translations.sql](../sql/056_public_holiday_tab_translations.sql) | `68dde9444e5c17b6c4da0c6404db889eec3c19020c3bd8a93b594e0aa3fa4fb0` |
| [sql/057_schedule_sum_label_translation.sql](../sql/057_schedule_sum_label_translation.sql) | `6c242ed8d459265e4ceb3467f8d887f597f7cbf2a5110876ec9ea3a33117f9ac` |
| [sql/058_schedule_printing.sql](../sql/058_schedule_printing.sql) | `e6c35020d1bce094a5656fbcb38e7e5297e47b1d35745a920a9c77ef1b6dbcbe` |
| [sql/059_schedule_print_row_height.sql](../sql/059_schedule_print_row_height.sql) | `f3949d8fff76246c960be5301da134b73e686b6c1994ca56302d114ae0715405` |
| [sql/060_schedule_tab_visibility.sql](../sql/060_schedule_tab_visibility.sql) | `e0fd6e811723f8bb733843fdb3c0ab23e8bf8590eec00a6a821b19b591b5c1d8` |
| [sql/061_schedule_print_admin_tabs.sql](../sql/061_schedule_print_admin_tabs.sql) | `0f3be0682cdedb8d201ea2b430aa578be698e86ee22a8e007d0ef1f9d7b8cb09` |
| [sql/062_documentation_alignment.sql](../sql/062_documentation_alignment.sql) | `434274b17bb6dc89e384837e675b8c2434d5a6f219500795d0bc60b0e149968f` |
| [sql/063_reconstruction_documentation.sql](../sql/063_reconstruction_documentation.sql) | `12367b4fe3e3fe0ea8d8e78ff8a328ef4d5ff435be2a1142abfc3bdd1cf6b5b8` |
| [sql/064_documentation_migration_delimiter_fix.sql](../sql/064_documentation_migration_delimiter_fix.sql) | `963923434e70b7279e2099a8163e20d8e70fc35e8e1924826ab37f1de68e1d8b` |
| [sql/065_client_management_read_only.sql](../sql/065_client_management_read_only.sql) | `b6c20582c0f668e0ca41f2e39a813dd1deef54ee0d99a9c476b74bacf1db7092` |
| [sql/066_level_f_self_service_scope.sql](../sql/066_level_f_self_service_scope.sql) | `a545ebbf0bb1559495e03691846b24d9e4b165f62f796fa789f30836dcaa2e11` |
| [sql/067_level_f_schedule_print_scope.sql](../sql/067_level_f_schedule_print_scope.sql) | `8a0c60b728054d9f26490b636334c55db72fb431e9b76b26f4f28649a51fee32` |
| [sql/068_level_f_hidden_modules_property.sql](../sql/068_level_f_hidden_modules_property.sql) | `f80c4dc7c0090dc95569cd084b388df92b7fa1f0fd882034710a847ae7a6441e` |
| [sql/069_manager_own_schedule_scope.sql](../sql/069_manager_own_schedule_scope.sql) | `73decf9d4285fee6e3c526fb9ce56b68bfa40d0ff0479168729509a5bacfe8e5` |
| [sql/070_schedule_manager_substitutes.sql](../sql/070_schedule_manager_substitutes.sql) | `c7b74214a8d95fcdcef43ce267f79483e7dcd5b85a0895b386d7b5ea340fad43` |
| [sql/071_support_conversations.sql](../sql/071_support_conversations.sql) | `5a25f86a67c349aa9c1f987eeb6fc1fc1ffa84a06eacf5abfd8b899a84b79e3d` |
| [sql/072_estonian_time_zone.sql](../sql/072_estonian_time_zone.sql) | `67d448ee574ecd103943a0bddfde224473c48526ab2d885faa5444a2bef05a9f` |
| [sql/073_notification_archive.sql](../sql/073_notification_archive.sql) | `75b649fbbe1ae3099524f9ce327eb6aa1f9a19170d1c8831191f78e5f031731b` |
| [sql/074_support_unopened_count.sql](../sql/074_support_unopened_count.sql) | `d89ef8516c4ec5abb1fb9ed68f9460ec168cb81072bc4501586cd21b0c3c5da2` |
| [sql/075_support_reply_unread.sql](../sql/075_support_reply_unread.sql) | `68abe9a7bfbd142c63a87385c1e9b360873344af4b410e80472f1d82ed54a53e` |
| [sql/076_support_unread_rows.sql](../sql/076_support_unread_rows.sql) | `9750d379098599e9adfba163c73c523b131c306956e665593a313527413ec864` |
| [sql/077_property_type_translations.sql](../sql/077_property_type_translations.sql) | `afb243a61ea522b46f03112e964f176ce8ca835288be91466ef5de5f5fa1449e` |
| [sql/078_new_job_title_translation.sql](../sql/078_new_job_title_translation.sql) | `1b067e89c9a82a2eb59a639210ec99fd611b23e9f67657a74773d2c5d0427ed9` |
| [sql/079_employment_ui_translations.sql](../sql/079_employment_ui_translations.sql) | `4adbf7831e5a09ea24187fb79a7d12df618af46901f5083a2ed8c6f7c25c4c17` |
| [sql/080_schedule_consecutive_exception_translations.sql](../sql/080_schedule_consecutive_exception_translations.sql) | `a65f484f5347445b94419ceaf64d9fa47f9507ae4a974c3e8232019d4fb6339f` |
| [sql/081_client_domain_embed.sql](../sql/081_client_domain_embed.sql) | `f5eff144566409d90ff3e8041b4d0bc171c56b738462fbf18b37ba125b241072` |
| [sql/082_client_administrator_management.sql](../sql/082_client_administrator_management.sql) | `d0a92130bec64f057d29ad76313d55700e48aea8f95f639d50f54425775f8ba0` |
| [sql/083_employee_list_pagination.sql](../sql/083_employee_list_pagination.sql) | `4d67fb6935efb7b7c8440fb11de2df0f8d20a8ff84ef176dd5724e48345822b1` |
| [sql/084_contract_list_pagination.sql](../sql/084_contract_list_pagination.sql) | `7f5ef56d396b8358fc2b1bf914cb4e14e62403d9f4fc79f58f120cf5ac1d64b2` |
| [sql/085_property_tree_collapse.sql](../sql/085_property_tree_collapse.sql) | `5b4d62af2336c3b6d3eee992aec8b787d74d1e2af68ddea5b5ca5e2e186f294a` |
| [src/Services/AuditLogService.php](../src/Services/AuditLogService.php) | `fe57d5c474bce7adf8c120887ad1065c6e33666b9f558cdfc64b8ac3bbcb737a` |
| [src/Services/AuthService.php](../src/Services/AuthService.php) | `44e30c47e47d5b5d4e740c296ba0a22bcafaf256879a20f754b986499b424455` |
| [src/Services/ClientDomainEmbedService.php](../src/Services/ClientDomainEmbedService.php) | `5fdddb11d729d86e4a9e6bf4bbe1acf40a54235c0e86eeb482cf2f2511a589ff` |
| [src/Services/ClientService.php](../src/Services/ClientService.php) | `c725843bb6e2d31eb133c0ff3c928f26f6306074ee110e9fd2fc98f30dde06fc` |
| [src/Services/EmailService.php](../src/Services/EmailService.php) | `24299a694bb7cf9abacc1bcfb17ffb8a9787e2b3a743185dc2b981b540543295` |
| [src/Services/EmploymentCatalogService.php](../src/Services/EmploymentCatalogService.php) | `b4394bc29af3f4d857ad047ff3b93833b81c9b32bdc529f1936ab6617f497ec6` |
| [src/Services/EmploymentContractService.php](../src/Services/EmploymentContractService.php) | `5c1facc7dd97768090a16e8dcc453d811e6c7c79ed13ad5569df02a02b061c62` |
| [src/Services/InitialSetupService.php](../src/Services/InitialSetupService.php) | `0359068c82f7d5226cc22af595f2f61e1373cfee0697e9fba110a09a17c046b8` |
| [src/Services/LanguageService.php](../src/Services/LanguageService.php) | `a3860e2ef724b177db48a025305ff0bc786d2a4441026a1295644c4d8494d3f5` |
| [src/Services/LegacyEmployeeImportService.php](../src/Services/LegacyEmployeeImportService.php) | `940a7c5c71cce54b91c14ad553da5229a0a94430efc7506b31f96bf84336f0f8` |
| [src/Services/ModuleProvisioningService.php](../src/Services/ModuleProvisioningService.php) | `aba7009a30829c1ec1ccc86c0ba79d4bec0c41c8627ce055000e7975ce7724e3` |
| [src/Services/ModuleService.php](../src/Services/ModuleService.php) | `888669a3336d987ae556f510fb53786b68a622f6fb78e861423990cab6acbe76` |
| [src/Services/NotificationService.php](../src/Services/NotificationService.php) | `4035564e69fd2c2acc67e463c44d3a96c332f89dac3fe3e6e9fa400e1713f36b` |
| [src/Services/OrgUnitService.php](../src/Services/OrgUnitService.php) | `37d70cd698d6e2d10ca2e057b8da02a53a0b14dff92fc7dfdb12212e4da242ef` |
| [src/Services/PatientService.php](../src/Services/PatientService.php) | `35ea7ed8193aca7d90c127f52418ee9014ed006b3ad0a12e9dddb8b9a2d61fb5` |
| [src/Services/PropertyService.php](../src/Services/PropertyService.php) | `d91e8788a9e499440b87690fd083bf5332581bfa737d45334341229b361adfc2` |
| [src/Services/PublicHolidayService.php](../src/Services/PublicHolidayService.php) | `41fc1c3c075bfd2f455b6745d8c0d194ece89d908709a42db7176b1707a34946` |
| [src/Services/ReportService.php](../src/Services/ReportService.php) | `a9c40089db595b491e3d100898d3e636be1eed414548d26ff0ca43edb5f35e04` |
| [src/Services/RoleService.php](../src/Services/RoleService.php) | `8df34a5880502201727a1c85c31982e434dbf462b754474d32c9766e5bf022c5` |
| [src/Services/ScheduleService.php](../src/Services/ScheduleService.php) | `074bf7112f88d00dfa67067f1787a1d2083f33f910aaaf612dc4c8ea3079ed29` |
| [src/Services/SchemaInstaller.php](../src/Services/SchemaInstaller.php) | `8503e9d5df48510f71a61c94ac73cffc0f8cfd58eb121e4429d5474a523f2c10` |
| [src/Services/SettingsService.php](../src/Services/SettingsService.php) | `152b9ce88ee95d6d9769bc7a317dbacf5d38da9585b6505f2eb34ed5c5874f88` |
| [src/Services/SubstituteService.php](../src/Services/SubstituteService.php) | `6b8b8d8ade0496ef8f53810e025c183596a4fda1de0849c139c40a49bdee2060` |
| [src/Services/SupportService.php](../src/Services/SupportService.php) | `85b0cd8bb8e62e8b69b32dce9d01eb452b6886e0f23b555b6d35cbcb9b572f74` |
| [src/Services/TranslationService.php](../src/Services/TranslationService.php) | `46b427baa32b28554c38dc00b430ba215d89c9a276115442c70d478dbc8c948f` |
| [src/Services/UserService.php](../src/Services/UserService.php) | `7782becabbe176e65e7c95416c38e884c9c5e3a56b31407921b7d911e355863f` |
| [src/Support/ApplicationTime.php](../src/Support/ApplicationTime.php) | `99bf8db33c7e367dd1ad0aa2e659567edd1736cbd0709d4c9fd30b0a9628663f` |
| [src/Support/ClientContext.php](../src/Support/ClientContext.php) | `19019bf515942ba7ade02384b067afe93728900cb026a3c1df25219e87edecf6` |
| [src/Support/Csrf.php](../src/Support/Csrf.php) | `96bc139dd57801fd9a1da1e842fd7aa177b20f93d193f36a27fb37110d27e0f2` |
| [tests/application-time.php](../tests/application-time.php) | `089726d6628c70e6ddb96df3a604fe92455157eac56893e2f54afe6ed18e6ecc` |
| [tests/client-admin-management.php](../tests/client-admin-management.php) | `a77ea2b0b2779b45755e15550c331da16c98e93af45a626f0d509475d3c12dfb` |
| [tests/client-domain-embed.php](../tests/client-domain-embed.php) | `032cb7573e240b4632497778d8dd16d78ac49ab9072bf1a6b7a5d846a55da3b5` |
| [tests/client-management.php](../tests/client-management.php) | `2171fb43728562a9201239c4c04b7a136629bc7b674b329b7072d7e6eebc844b` |
| [tests/contract-list-pagination.php](../tests/contract-list-pagination.php) | `1c9fe6d47148ce2edf038f4a44f88159cd506aa63cc1bff20077bebb8ce1f0d4` |
| [tests/employee-list-pagination.php](../tests/employee-list-pagination.php) | `3463bb24a14bf7fb176a28d23c7717947206dafeb5d03d2d2409562a867e629e` |
| [tests/level-f-scope.php](../tests/level-f-scope.php) | `2435d325c400a81572d1818d2c30ac9c6387a4714f8b344f33d18a8d1230d555` |
| [tests/manager-schedule.php](../tests/manager-schedule.php) | `8f26b5f4df2220797274e5946b2a8eac856f2da15b4ea71f3e1e9cde9a98210f` |
| [tests/notifications.php](../tests/notifications.php) | `7990282293c0ef1a3910b96e3e9e9fb93b7bda4a5edee08ebd13eb5281f1fc11` |
| [tests/property-translations.php](../tests/property-translations.php) | `dad031046a20d50effe545eb0d5b988cf659f0c6ff4e8af39577b9260ded5863` |
| [tests/property-tree-collapse.js](../tests/property-tree-collapse.js) | `ef6c79747c458ae7339891105505b3744facbfca0cefe93138a54802be5112f5` |
| [tests/property-tree-collapse.php](../tests/property-tree-collapse.php) | `8fa1b64a383460d8fcdc5f522c376b0366f74eba47abe34f57d74656d2e54c01` |
| [tests/schedule-print-scope.php](../tests/schedule-print-scope.php) | `6e58778727cda377d25e498c8212bb360236a91dd35842882fb26f198075e4bf` |
| [tests/schedule-substitutes.php](../tests/schedule-substitutes.php) | `746683bff2bb2640e45a0282b0f4fedae3ff07e746fe3df699224cd936630bda` |
| [tests/support-tickets.php](../tests/support-tickets.php) | `97457a9287a7b47e26b939c74b7331bca03836f250d624f481d4fecd92047ef0` |
| [views/admin/clients/create.php](../views/admin/clients/create.php) | `90991d4a53ea174fe583e2425d5bb69f21c0c89ff5704f6e95516176318a27e4` |
| [views/admin/clients/index.php](../views/admin/clients/index.php) | `8fa836ae3718c66c0c93049650aa21cc2f714156339280932d9f5effd59b8f24` |
| [views/admin/clients/user-create.php](../views/admin/clients/user-create.php) | `71c3264e8c240af110ad92fbf24c493ea58e6acdfec253307106c1b089fcad89` |
| [views/admin/clients/user-edit.php](../views/admin/clients/user-edit.php) | `57bbec65ca078149e39c01337bbef0ae130ad4d83be8e953ed57f4b28f41205c` |
| [views/admin/clients/view.php](../views/admin/clients/view.php) | `23f90f2f7cac351803e6ebc5cb0bcb1a1d02ba223325499cab1add00826cb547` |
| [views/admin/dashboard.php](../views/admin/dashboard.php) | `7e23599ea530aaaaffab59aaf5f5242fa4746361666a33c7bba66079fc6ab383` |
| [views/admin/languages/create.php](../views/admin/languages/create.php) | `608b17beb765ceb78931835923f1e3dbedb72f6bef8ab2b94600422164d51edc` |
| [views/admin/languages/index.php](../views/admin/languages/index.php) | `66da4a542aa0511043ed49c52cd411bde967390caf8f594cd763cdd254b6f468` |
| [views/admin/logs/index.php](../views/admin/logs/index.php) | `98fbf439becf3de0a800561bab1f2a7313bda39f10a0eef497dc41a644259004` |
| [views/admin/modules/create.php](../views/admin/modules/create.php) | `2704636e94fe87e070664a63c03587fd12d5ab61735ce2a52fe68d514f7eb01b` |
| [views/admin/modules/index.php](../views/admin/modules/index.php) | `f0f7e9ac9cb88b747512d4be5a868c276c071f4f45a24237ff2bd27c6ee00c0d` |
| [views/admin/notifications/index.php](../views/admin/notifications/index.php) | `8f7f2e85b4e6353f380e0a5a0ebf8bc1603b1f831be2122f47cf7af57fabfa06` |
| [views/admin/render.php](../views/admin/render.php) | `87067c47f72f538af52e23b4124733e3e8e5e01e1e8fadaa4abac9ae3c3c26a4` |
| [views/admin/roles/index.php](../views/admin/roles/index.php) | `4be611873e381a144117d210d082e12ce53326e6c1c47e52cf7e081be5c7b728` |
| [views/admin/settings/index.php](../views/admin/settings/index.php) | `e1aa7828de9cb814c091c5fb57c460f0840781020cef05c208190727b8e20318` |
| [views/admin/shell.php](../views/admin/shell.php) | `006c3efac03cc623240470fc124bac14c2255e2def6e33a2e58eef00b4d47ad3` |
| [views/admin/support/index.php](../views/admin/support/index.php) | `bd7a625cc10f4207c4e0c6fe7a871033be708da8070539f715a74d83c33576b2` |
| [views/admin/support/view.php](../views/admin/support/view.php) | `4b60205cb80a6ca6ff9c4e01d7c924d29fb1f68ec4fe702508c168144181fd43` |
| [views/admin/translations/create.php](../views/admin/translations/create.php) | `580520b564a14813737c6fb2a3393a9aa683c76d49a1f4778b6f80827cf3db74` |
| [views/admin/translations/index.php](../views/admin/translations/index.php) | `a5ab43547b947af835674a806ef94ca6f1a1b8a522e140f55bc74c62899dc126` |
| [views/admin/translations/missing.php](../views/admin/translations/missing.php) | `2ea154eaf7538ae94152f98bf7dcb3ad3397df42e10d297052256813323b026e` |
| [views/auth/login.php](../views/auth/login.php) | `11e36b13e81577c370757f275a9fffb4b09bf1968f4d71ab6f1c06aa727e3c36` |
| [views/errors/400.php](../views/errors/400.php) | `0e24b98a832cdd97d5d79154bc9fb3ecb16813c780be2d7925f21415c53f1f57` |
| [views/errors/403.php](../views/errors/403.php) | `d5c2a20cb2af74f69889f0b63a3b4309adb3b8ede190c62f417ed6b4ecc96440` |
| [views/errors/404.php](../views/errors/404.php) | `6c93121432c4241b34b42e16531ec11ff5d18292c29cf4c515e330aba7f7302e` |
| [views/errors/500.php](../views/errors/500.php) | `ae088fbda552e5a0da05515ce14869905ff9798590e20bb25b12b8031f309c41` |
| [views/home.php](../views/home.php) | `3772d5f8585cb6060ef213b513080b58b779998d3c7740627978ca940167b51f` |
| [views/layout/footer.php](../views/layout/footer.php) | `01d277504bfac1e4153aa175a297cc73662bcc37e6cf6359dc4a0f7385a1658e` |
| [views/layout/header.php](../views/layout/header.php) | `21d274053a4c9ff53a2831cffcf0264360737088e4e89ac1815bfaaf197149e0` |
| [views/panel/account/index.php](../views/panel/account/index.php) | `2eb32486e4240af4635d5b3fa3511d15859888b752eaa16871fcf9dfaaa95c3c` |
| [views/panel/client/index.php](../views/panel/client/index.php) | `2f8dadef3c476e88add842c7a9110a7bf3efc2278a91098b68f87cc78dbb8af1` |
| [views/panel/dashboard.php](../views/panel/dashboard.php) | `ab10afb76a90944704a9c312c7ecb030d51abea3b60df9c18742e7f984f407bc` |
| [views/panel/domain-embed/index.php](../views/panel/domain-embed/index.php) | `7a9a5c24aef937ef4782abe135bba0d1c7dc30b35c2ee1eb4e8e8b6e90f96707` |
| [views/panel/employment/catalog.php](../views/panel/employment/catalog.php) | `8b54c4235837ccc97f9c7d09d8c6e5190d43e45e3c7edd75157088362d13857d` |
| [views/panel/employment/contract-form.php](../views/panel/employment/contract-form.php) | `fc90f87c611d20c1423c3419a84aa0a1a6b41e96ff5ffa5f19831104c13b6eba` |
| [views/panel/employment/contracts.php](../views/panel/employment/contracts.php) | `20573c99c8bcbf344cd1366f17977f53c4b99546c711d478c6ac6feb40e1ddcf` |
| [views/panel/employment/nav.php](../views/panel/employment/nav.php) | `36f7c29c74b22fef002f530bccbc70ca96e7ba0c29ff149d97d953f73d24229f` |
| [views/panel/modules/index.php](../views/panel/modules/index.php) | `5b9b35b88e52658b87ef8c7736a9d5e6168e9c624ba1749f0c8c8507e312efce` |
| [views/panel/notifications/index.php](../views/panel/notifications/index.php) | `a6f450a1df3c57bcd779dd3a8a23954196b05fd6ebb31dbd21e7c660a7f18989` |
| [views/panel/org/create.php](../views/panel/org/create.php) | `a4fd0d3339a299098cf6cbb95be2b3749753b08d5bb5f1463f7b8b28c1d1a776` |
| [views/panel/org/index.php](../views/panel/org/index.php) | `ceaab5e18aeb3ddd460b51444c81db3efcfcadf5a2c6dbebf0c69ec2f6d15158` |
| [views/panel/pagination.php](../views/panel/pagination.php) | `2cbedc37d5abfe822a854fb21f8627cde96ccd1c09c86baa4d0a6fb4308902a3` |
| [views/panel/patients/create.php](../views/panel/patients/create.php) | `d87aeecd15bb40e8a39e01fd4e0bdae6a8b09a18dcd5cbce194f7b359663e025` |
| [views/panel/patients/index.php](../views/panel/patients/index.php) | `9069d23b040f07eec08223801cc22475268d58f40c43f25ac5407ef407e93dc1` |
| [views/panel/property/create.php](../views/panel/property/create.php) | `666ab0fe5b5a163ea8a346578270cbaea943c8b32ae313b9a181821d73d3986d` |
| [views/panel/property/edit.php](../views/panel/property/edit.php) | `928b047bc6a27332dd104250231892bc750e54e1acdf2f32ced4e79fa10d6712` |
| [views/panel/property/index.php](../views/panel/property/index.php) | `1ead2e51b1c335868d74737c302b1a25a33d372de9661321cdefb68c5c85f8ee` |
| [views/panel/render.php](../views/panel/render.php) | `27f823d5498f9b3eb1a34e04d172a31947242dc89af7bef2d19f9a5caaab1a2d` |
| [views/panel/reports/index.php](../views/panel/reports/index.php) | `59035f3f5650ff7baecb64749903a6655a0c1b441a58056d2d5207101c13d13e` |
| [views/panel/schedule/holidays.php](../views/panel/schedule/holidays.php) | `1ffb4c509d7b017be5018c9fcc00fa8cede17133f7696d4d2024950d7091bf11` |
| [views/panel/schedule/index.php](../views/panel/schedule/index.php) | `05cf84d0348e15ded0d4341fbfc6b382503c2db10b99e263b101f810846383e7` |
| [views/panel/schedule/nav.php](../views/panel/schedule/nav.php) | `ea268eb7fd448529e7a715a71940a226a4ea2ad8aa70bb479f5d58fd2ae17a08` |
| [views/panel/schedule/print.php](../views/panel/schedule/print.php) | `89bdbf665ed2a4797df4190ab3aed82b622233ebc79225a2b6b8a991aa76358e` |
| [views/panel/schedule/substitutes.php](../views/panel/schedule/substitutes.php) | `9917112346131cb5fc992e03c15b7f337dfaae4096d786c1c896b79d8eee8065` |
| [views/panel/schedule/templates.php](../views/panel/schedule/templates.php) | `0ba8cff0dde57b509a9d7cb63ff461d5f6ad088f0f67a417f2aacab05d1843b6` |
| [views/panel/shell.php](../views/panel/shell.php) | `39de3193b75874ca61b5dcd54ef48873426e0ecf36d13d6de759d16101529e21` |
| [views/panel/substitutes/create.php](../views/panel/substitutes/create.php) | `8e0e689f49503be9b7d2bf269f81e1943f3dd4b10917b2d735239bb00ad192e0` |
| [views/panel/substitutes/index.php](../views/panel/substitutes/index.php) | `e798b9893b42d02d6f82b1f655400b8f14afc97e56367ce3c8b25e17831dc559` |
| [views/panel/support/create.php](../views/panel/support/create.php) | `212d9bb5a0b20394a7f32c753aafadcef9297035f410f859873b30a422d0841b` |
| [views/panel/support/index.php](../views/panel/support/index.php) | `bd7a625cc10f4207c4e0c6fe7a871033be708da8070539f715a74d83c33576b2` |
| [views/panel/support/view.php](../views/panel/support/view.php) | `4b60205cb80a6ca6ff9c4e01d7c924d29fb1f68ec4fe702508c168144181fd43` |
| [views/panel/users/create.php](../views/panel/users/create.php) | `3cf9f707ac7192161c7508d1d643ffeec11d10a7ed002bd600158145f8f63911` |
| [views/panel/users/edit.php](../views/panel/users/edit.php) | `ee9cbf98d8f52f11c5f839939a7eea26a979ff431e029b68e904b74d0d0db0e5` |
| [views/panel/users/import.php](../views/panel/users/import.php) | `0b59ad849639dfbff34fc31a7b0932b11612ad07ecdc0b30a3a21b94a8832e09` |
| [views/panel/users/index.php](../views/panel/users/index.php) | `b41d9e86b6df3efd770581c71dc0f1b209a08addbdbe5eccec4c414b6bca4b68` |
| [views/render.php](../views/render.php) | `13f143f7f37c753f7cd034ca70b8b43975f0ab2e34d6e7871b4f9fa93c4f5d89` |
| [views/setup/create_admin.php](../views/setup/create_admin.php) | `4f19ccdc31dc43d1aa83bc53a9a837f0c19b2676f09e804503ae86146339290d` |
| [views/support/index.php](../views/support/index.php) | `8506c4a467af718052fba202fa409bba9dd0eccd23f87dc7dc6901b9add5c76d` |
| [views/support/view.php](../views/support/view.php) | `08d01a248b07e6e449ae1690270d6c91602957986eda6426e7bfb24e14bc5707` |
