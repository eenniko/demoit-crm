<?php

declare(strict_types=1);

ob_start();
require __DIR__ . '/manager-schedule.php';
ob_end_clean();

RoleService::$levelF = false;
RoleService::$fUsers = [8];
ClientContext::$userId = 5;
$pdo->exec('DELETE FROM employee_schedule_entries; DELETE FROM employment_contracts WHERE employee_id=8;
    UPDATE employment_contracts SET manager_user_id=5 WHERE client_id=1 AND id IN (20,22)');
$now = new DateTimeImmutable();
$month = $now->format('Y-m');
$previousMonth = $now->modify('first day of previous month')->format('Y-m');
$futureMonth = $now->modify('first day of next month')->format('Y-m');
$date = $now->format('Y-m-20');
$futureDate = $futureMonth . '-20';
$period = [
    'original_user_id' => 99, 'substitute_user_id' => 8,
    'starts_at' => $now->modify('-1 hour')->format('Y-m-d\TH:i'),
    'ends_at' => $now->modify('+1 day')->format('Y-m-d\TH:i'),
    'reason' => 'Vacation',
];
check(SubstituteService::canAssignScheduleSubstitute(1,5), 'Manager cannot assign self substitute.');
[$success, $message] = SubstituteService::assignScheduleSubstitute(1,5,$period);
check($success, 'Manager assignment failed: ' . $message);
$assignmentId = (int) $pdo->lastInsertId();
$assignment = $pdo->query('SELECT * FROM system_substitutes')->fetch();
check((int) $assignment['original_user_id'] === 5 && $assignment['status'] === 'approved', 'Manager can impersonate another original or approval required.');
check(SubstituteService::activeScheduleManagers(1,8) === [5], 'Active substitute without contract not authorized.');
check(!RoleService::hasAnyRole(8,1,['client_admin']) && RoleService::isSelfServiceUser(8,1), 'Substitution promoted account roles.');
check(!SubstituteService::canAssignScheduleSubstitute(1,8), 'Level F substitute can chain delegate.');
check(SubstituteService::scheduleAssignments(1,8,false)[0]['id'] === $assignmentId, 'Own substitute assignment invisible.');
check(SubstituteService::scheduleAssignments(1,7,false) === [], 'Unrelated user sees assignment.');
[$success] = SubstituteService::assignScheduleSubstitute(1,5,$period);
check(!$success, 'Duplicate overlapping assignment accepted.');
foreach ([
    ['substitute_user_id' => 5], ['substitute_user_id' => 999], ['substitute_user_id' => 9],
    ['starts_at' => '2026-02-30T12:00'], ['starts_at' => "2026-10-14T12:00\0"], ['ends_at' => '2000-01-01T00:00'],
] as $bad) {
    [$success] = SubstituteService::assignScheduleSubstitute(1,5,array_replace($period,$bad));
    check(!$success, 'Invalid substitute assignment accepted.');
}
[$success] = SubstituteService::assignScheduleSubstitute(1,8,$period);
check(!$success, 'Substitute assigned without independent manager rights.');
[$success] = SubstituteService::create(1,['substitute_type' => 'SCHEDULE_MANAGER'],8);
check(!$success, 'Legacy organisation request can create reserved schedule delegation.');
ClientContext::$userId = 8;
$visible = ScheduleService::listScheduleContracts(1,8,false,ScheduleService::monthInfo($month));
$ids = array_column($visible,'contract_id');
sort($ids);
check($ids === [10,11,20,22], 'Substitute scope differs from original manager.');
check(ScheduleService::listScheduleContracts(1,8,false,ScheduleService::monthInfo($previousMonth)) === [], 'Past month granted.');
check(ScheduleService::listScheduleContracts(1,8,false,ScheduleService::monthInfo($month),false,null,false) === [], 'Workload scope includes substitute rights.');
[$success, $message] = ScheduleService::saveMonth(1,8,false,$month,[20 => [$date => 1]]);
check($success, 'Substitute current-month write failed: ' . $message);
[$success, $message] = ScheduleService::saveMonth(1,8,false,$futureMonth,[22 => [$futureDate => 1]]);
check($success, 'Substitute future write failed: ' . $message);
check((int) $pdo->query('SELECT COUNT(*) FROM employee_schedule_entries WHERE updated_by=8')->fetchColumn() === 2, 'Substitute writes not persisted.');
[$success] = ScheduleService::saveMonth(1,8,false,$previousMonth,[10 => [$previousMonth . '-20' => 1]]);
check(!$success, 'Substitute past write accepted.');
foreach ([21,30,40,41,50] as $id) {
    [$success] = ScheduleService::saveMonth(1,8,false,$month,[$id => [$date => 1]]);
    check(!$success, 'Out-of-scope substitute write accepted.');
}
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
$_POST = ['csrf_token' => 'valid', 'month' => $month, 'assignments' => [10 => [$date => 1]]];
$_GET = [];
http_response_code(200);
ob_start();
handle_panel_schedule('POST');
$response = json_decode(ob_get_clean(),true,512,JSON_THROW_ON_ERROR);
check(http_response_code() === 200 && $response['success'], 'F active substitute AJAX denied.');
$_POST['contract_id'] = '20';
$_POST['workload_id'] = '';
ob_start();
handle_panel_schedule_workload('POST');
$response = json_decode(ob_get_clean(),true,512,JSON_THROW_ON_ERROR);
check(http_response_code() === 403 && !$response['success'], 'F substitute received workload rights.');
RoleService::$fUsers = [];
http_response_code(200);
ob_start();
handle_panel_schedule_workload('POST');
$response = json_decode(ob_get_clean(),true,512,JSON_THROW_ON_ERROR);
check(http_response_code() === 403 && !$response['success'], 'Non-F substitute received delegated workload rights.');
RoleService::$fUsers = [8];

$rendered = [];
function render_panel_page(string $title, string $view, array $vars = [], string $menu = ''): void
{
    global $rendered;
    $rendered = $vars;
}
function e(?string $value): string { return htmlspecialchars($value ?? '',ENT_QUOTES,'UTF-8'); }
function t(string $key, string $fallback): string { return $fallback; }
class EmploymentCatalogService
{
    public static function listForClient(int $clientId, string $catalog): array { return []; }
}
class LanguageService
{
    public static function currentCode(): string { return 'en'; }
}
class UserService
{
    public static function listForClient(int $clientId): array
    {
        $stmt = db()->prepare('SELECT * FROM system_users WHERE client_id = :client_id');
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }
}
foreach (['handle_panel_schedule_print','require_schedule_admin','handle_panel_schedule_substitutes',
    'handle_panel_schedule_templates','handle_panel_schedule_holidays','handle_panel_schedule_holiday_import'] as $function) {
    $start = strpos($router,'function ' . $function . '(');
    $end = $start === false ? false : strpos($router,"\nfunction ",$start+1);
    check($start !== false && $end !== false,'Missing actual handler.');
    eval(substr($router,$start,$end-$start));
}
$_GET = ['month' => $month];
http_response_code(200);
handle_panel_schedule('GET');
check($rendered['canManage'] && !$rendered['canManageTemplates'] && !$rendered['canAssignSubstitute'], 'Substitute UI flags incorrect.');
foreach ($rendered['contracts'] as $row) {
    check(!$row['can_manage_workload'],'Substitute row workload control enabled.');
}
ob_start();
(static function (array $vars): void {
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/../views/panel/schedule/index.php';
})($rendered);
$html = ob_get_clean();
$dom = new DOMDocument();
$dom->loadHTML($html,LIBXML_NOERROR|LIBXML_NOWARNING);
$xpath = new DOMXPath($dom);
check($xpath->query('//button[contains(concat(" ",normalize-space(@class)," ")," schedule-cell-button ")]')->length > 0,'Missing shift controls.');
check($xpath->query('//button[contains(concat(" ",normalize-space(@class)," ")," schedule-workload-button ")]')->length === 0,'Substitute workload buttons visible.');
$activeModuleKeys = ['property','schedule','employees','employment'];
$activeMenu = 'schedule';
$workspaceContent = '';
ob_start();
require __DIR__ . '/../views/panel/shell.php';
$html = ob_get_clean();
check(str_contains($html,'href="/panel/schedule"') && !str_contains($html,'href="/panel/property"')
    && !str_contains($html,'href="/panel/modules"'),'F substitute base sidebar permissions changed.');
foreach (['handle_panel_schedule_templates','handle_panel_schedule_holiday_import','handle_panel_schedule_substitutes'] as $handler) {
    foreach (['GET','POST'] as $method) {
        http_response_code(200);
        ob_start();
        $handler($method);
        ob_end_clean();
        check(http_response_code() === 403,'Substitute admin/chain handler accessible.');
    }
}
ob_start();
handle_panel_schedule_holidays();
ob_end_clean();
check(http_response_code() === 403,'Substitute holidays accessible.');
ClientContext::$userId = 5;
$_POST = ['csrf_token' => 'invalid'] + $period;
$beforeCount = (int)$pdo->query('SELECT COUNT(*) FROM system_substitutes')->fetchColumn();
handle_panel_schedule_substitutes('POST');
check(http_response_code() === 403 && $rendered['error'] !== null, 'Assignment CSRF error missing.');
check((int)$pdo->query('SELECT COUNT(*) FROM system_substitutes')->fetchColumn() === $beforeCount, 'Invalid CSRF created assignment.');
ClientContext::$userId = 8;
handle_panel_schedule_print();
check(!$rendered['printOnly'] && count($rendered['locations']) > 0,'Active substitute print missing.');

$before = $pdo->query('SELECT * FROM employee_schedule_entries ORDER BY contract_id,schedule_date')->fetchAll();
$pdo->exec("UPDATE system_substitutes SET ends_at = '" . $now->format('Y-m-d H:i:s') . "' WHERE id=$assignmentId");
check(SubstituteService::activeScheduleManagers(1,8) === [],'Exact end time retains rights.');
[$success] = ScheduleService::saveMonth(1,8,false,$month,[20 => [$date => 2]]);
check(!$success,'Expired substitute direct service save accepted.');
check($pdo->query('SELECT * FROM employee_schedule_entries ORDER BY contract_id,schedule_date')->fetchAll() === $before,'Expired save changed rows.');
ob_start();
handle_panel_schedule('POST');
$response = json_decode(ob_get_clean(),true,512,JSON_THROW_ON_ERROR);
check(http_response_code() === 403 && !$response['success'],'Expired F AJAX accepted.');
$pdo->exec("UPDATE system_substitutes SET ends_at = '" . $now->modify('+1 day')->format('Y-m-d H:i:s') . "' WHERE id=$assignmentId");
foreach (['pending','rejected','ended'] as $state) {
    $pdo->exec("UPDATE system_substitutes SET status='$state' WHERE id=$assignmentId");
    check(ScheduleService::listScheduleContracts(1,8,false,ScheduleService::monthInfo($month)) === [],'Invalid assignment state grants rights.');
}
$pdo->exec("UPDATE system_substitutes SET status='approved', starts_at = '" . $now->modify('+1 hour')->format('Y-m-d H:i:s') . "' WHERE id=$assignmentId");
check(SubstituteService::activeScheduleManagers(1,8) === [],'Future assignment grants early rights.');
$pdo->exec("UPDATE system_substitutes SET starts_at = '" . $now->modify('-1 hour')->format('Y-m-d H:i:s') . "' WHERE id=$assignmentId");
$pdo->exec("UPDATE system_substitutes SET starts_at = '" . date('Y-m-d H:i:s') . "' WHERE id=$assignmentId");
check(SubstituteService::activeScheduleManagers(1,8) === [5], 'Inclusive exact start boundary denies rights.');
RoleService::$fUsers = [8,5];
check(SubstituteService::activeScheduleManagers(1,8) === [],'Original F role change leaves delegated manager rights.');
RoleService::$fUsers = [8];
$pdo->exec("UPDATE system_users SET status='inactive' WHERE client_id=1 AND id=5");
check(SubstituteService::activeScheduleManagers(1,8) === [],'Inactive original retains delegation.');
$pdo->exec("UPDATE system_users SET status='active' WHERE client_id=1 AND id=5");
$pdo->exec("UPDATE system_users SET status='inactive' WHERE client_id=1 AND id=8");
check(SubstituteService::activeScheduleManagers(1,8) === [], 'Inactive substitute retains rights.');
$pdo->exec("UPDATE system_users SET status='active' WHERE client_id=1 AND id=8");
check(SubstituteService::activeScheduleManagers(2,8) === [], 'Other client exposes delegation.');
[$success] = SubstituteService::endScheduleSubstitute(1,7,$assignmentId);
check(!$success,'Unrelated user revoked assignment.');
[$success] = SubstituteService::endScheduleSubstitute(1,5,$assignmentId);
check($success && SubstituteService::activeScheduleManagers(1,8) === [],'Manager early revoke failed.');
check((int)$pdo->query('SELECT COUNT(*) FROM employment_contracts WHERE employee_id=8')->fetchColumn() === 0,'Substitute unexpectedly requires employment contract.');

RoleService::$admins = [99];
[$success] = SubstituteService::assignScheduleSubstitute(1,99,array_replace($period,['original_user_id' => 5]));
check($success,'Administrator could not assign real manager substitute.');
$newId = (int)$pdo->lastInsertId();
[$success] = SubstituteService::endScheduleSubstitute(1,99,$newId);
check($success,'Administrator early revoke denied.');

echo "PASS: manager self-assignment, contractless F substitute, current/future persisted shifts, past/foreign/workload/admin denials, UI, time boundaries, early revoke, no role promotion, no delegation chaining and invalid input on isolated SQLite.\n";
