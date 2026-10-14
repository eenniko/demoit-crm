<?php

declare(strict_types=1);

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}
class NotificationDatabase extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return parent::prepare(str_replace('ON DUPLICATE KEY UPDATE read_at = read_at',
            'ON CONFLICT(notification_id, user_id) DO NOTHING', $query), $options);
    }
}
function db(): PDO
{
    static $pdo;
    return $pdo ??= new NotificationDatabase('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
class AuditLogService { public static function log(mixed ...$args): void {} }
class ClientContext
{
    public static int $user = 1;
    public static function isLoggedIn(): bool { return true; }
    public static function clientId(): int { return 1; }
    public static function userId(): int { return self::$user; }
}
class RoleService { public static function isSelfServiceUser(int $user, int $client): bool { return false; } }
class SubstituteService { public static function activeScheduleManagers(int $client, int $user): array { return []; } }
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function t(string $key, string $fallback): string { return $fallback; }
function render_page(string $title, string $view, array $vars = []): void { echo $view; }
function render_panel_page(string $title, string $view, array $vars = [], string $menu = ''): void
{
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/../views/panel/' . $view;
}
$source = file_get_contents(__DIR__ . '/../src/Services/NotificationService.php');
eval(substr($source, strpos($source, 'class NotificationService')));
require_once __DIR__ . '/../src/Support/Csrf.php';
require_once __DIR__ . '/../src/Support/ApplicationTime.php';
date_default_timezone_set(ApplicationTime::ZONE);
$router = file_get_contents(__DIR__ . '/../public/index.php');
foreach (['handle_panel_notifications','handle_panel_notifications_read'] as $function) {
    $start = strpos($router, 'function ' . $function . '(');
    $end = strpos($router, "\nfunction ", $start + 1);
    eval(substr($router,$start,$end-$start));
}
$pdo = db();
$pdo->exec('CREATE TABLE system_notifications (id INTEGER PRIMARY KEY, client_id INTEGER, user_id INTEGER,
    title TEXT, message TEXT, is_read INTEGER, created_by INTEGER, created_at TEXT);
    CREATE TABLE system_notification_reads (notification_id INTEGER, user_id INTEGER, read_at TEXT,
    PRIMARY KEY(notification_id,user_id));');
$now = new DateTimeImmutable('now');
$insert = $pdo->prepare('INSERT INTO system_notifications VALUES (?,?,?,?,?,0,1,?)');
foreach ([
    [1,1,null,$now->modify('-1 hour')], [2,1,1,$now->modify('-2 hours')],
    [3,1,2,$now->modify('-3 hours')], [4,2,null,$now->modify('-1 hour')],
    [5,1,null,$now->sub(new DateInterval('PT24H'))], [6,1,null,$now->sub(new DateInterval('PT24H'))->modify('-2 seconds')],
    [7,1,null,$now->modify('-30 days')], [8,1,null,$now->modify('-30 days')->modify('-2 seconds')],
    [9,1,null,$now->modify('+1 day')], [10,1,1,$now->modify('-4 hours')],
] as [$id,$client,$user,$created]) {
    $insert->execute([$id,$client,$user,'Title <script>','Message <script>',$created->format('Y-m-d H:i:s')]);
}
$pdo->exec('UPDATE system_notifications SET is_read = 1 WHERE id = 10');
check(NotificationService::unreadCount(1,1) === 3, 'Badge must count recent unread targeted/broadcast only, inclusive 24h.');
check(NotificationService::unreadCount(1,2) === 3, 'Second recipient count must be isolated.');
$recent = array_map('intval', array_column(NotificationService::listForUser(1,1),'id'));
check(in_array(7,$recent,true) && !in_array(8,$recent,true), '30-day split must be inclusive for inbox.');
check(!in_array(3,$recent,true) && !in_array(4,$recent,true) && !in_array(9,$recent,true), 'No foreign/user-targeted/future notification leaks.');
check(array_map('intval',array_column(NotificationService::listForUser(1,1,true),'id')) === [8], 'Archive must contain only older visible notifications.');
check(NotificationService::markRead(1,1,1), 'Broadcast mark-read failed.');
check(NotificationService::unreadCount(1,1) === 2 && NotificationService::unreadCount(1,2) === 3, 'Broadcast reading must not affect other recipients.');
check(NotificationService::markRead(1,1,1) && $pdo->query('SELECT COUNT(*) FROM system_notification_reads')->fetchColumn() == 1, 'Repeated reading must be idempotent.');
check(!NotificationService::markRead(3,1,1) && !NotificationService::markRead(4,1,1) && !NotificationService::markRead(999,1,1), 'Unauthorized notification ID accepted.');
check(NotificationService::markRead(2,1,1) && NotificationService::unreadCount(1,1) === 1, 'Targeted mark-read must clear badge.');
check(NotificationService::markRead(8,1,1), 'Archived notification must remain readable.');
$states = array_column(NotificationService::listForUser(1,1),'is_read','id');
check((int)$states[1] === 1 && (int)$states[2] === 1 && (int)$states[10] === 1, 'Read receipt and legacy targeted states must display correctly.');
$states = array_column(NotificationService::listForUser(1,2),'is_read','id');
check((int)$states[1] === 0, 'Another user broadcast remains unread.');
for ($id=20;$id<125;$id++) {
    $insert->execute([$id,1,null,'Paged','Older',$now->modify('-45 days')->format('Y-m-d H:i:s')]);
}
$first = NotificationService::listForUser(1,1,true,1);
$second = NotificationService::listForUser(1,1,true,2);
$third = NotificationService::listForUser(1,1,true,3);
check(count($first) === 51 && count($second) === 51 && count($third) === 6, 'Archive must paginate without a last-50 loss.');
check(count(array_intersect(array_column(array_slice($first,0,50),'id'),array_column(array_slice($second,0,50),'id'))) === 0, 'Pages must not duplicate shown rows.');
check((int)$first[1]['id'] === 124 && (int)$third[5]['id'] === 20, 'Stable timestamp/ID ordering required.');
try {
    NotificationService::listForUser(1,1,false,0);
    check(false,'Invalid service pagination must throw.');
} catch (InvalidArgumentException $exception) {
    check($exception->getMessage() === 'Invalid notification page.','Pagination failure must be explicit.');
}
$_SESSION = [];
$token = Csrf::token();
$_GET = ['archive'=>'1','page'=>'3']; $_POST = [];
ob_start(); handle_panel_notifications(); $html = ob_get_clean();
check(str_contains($html,'Archive') && str_contains($html,'Previous') && !str_contains($html,'>Next<'), 'Archive UI must wire paging.');
$_GET = [];
ob_start(); handle_panel_notifications(); $html = ob_get_clean();
check(str_contains($html,'Title &lt;script&gt;') && !str_contains($html,'Title <script>'), 'Notification content must be escaped.');
check(str_contains($html,'name="id" value="5"') && !str_contains($html,'name="id" value="1"'), 'Unread broadcast must have mark-read form, read broadcast must not.');
$_GET = ['page'=>'-1'];
ob_start(); handle_panel_notifications(); ob_end_clean();
check(http_response_code() === 400, 'Invalid pagination must be rejected.');
$_POST = ['id'=>5,'csrf_token'=>'invalid'];
ob_start(); handle_panel_notifications_read('POST'); ob_end_clean();
check(http_response_code() === 403 && NotificationService::unreadCount(1,1) === 1, 'CSRF must prevent reading.');
ob_start(); handle_panel_notifications_read('GET'); ob_end_clean();
check(http_response_code() === 405, 'GET cannot mark read.');
$_POST = ['id'=>4,'csrf_token'=>$token];
ob_start(); handle_panel_notifications_read('POST'); ob_end_clean();
check(http_response_code() === 404, 'Cross-client mark-read must be 404.');
$_POST = ['id'=>5,'csrf_token'=>$token];
$_POST['page'] = '-1';
ob_start(); handle_panel_notifications_read('POST'); ob_end_clean();
check(http_response_code() === 400 && NotificationService::unreadCount(1,1) === 1,'Invalid return page must not mark read.');
unset($_POST['page']);
ob_start(); handle_panel_notifications_read('POST'); ob_end_clean();
check(NotificationService::unreadCount(1,1) === 0, 'Valid read handler must persist receipt.');
$activeMenu = 'notifications'; $activeModuleKeys = []; $workspaceContent = '';
$unreadNotificationCount = 3;
ob_start(); require __DIR__ . '/../views/panel/shell.php'; $html = ob_get_clean();
check(str_contains($html,'text-bg-danger') && str_contains($html,'data-no-translate>3</span>'), 'Menu badge must be numeric and on the right.');
$unreadNotificationCount = 0;
ob_start(); require __DIR__ . '/../views/panel/shell.php'; $html = ob_get_clean();
check(!str_contains($html,'text-bg-danger'), 'Zero unread must hide badge.');
$installer = file_get_contents(__DIR__ . '/../src/Services/SchemaInstaller.php');
eval(substr($installer, strpos($installer, 'class SchemaInstaller')));
$splitter = new ReflectionMethod(SchemaInstaller::class,'splitStatements');
$sql = file_get_contents(__DIR__ . '/../sql/073_notification_archive.sql');
$statements = array_values(array_filter($splitter->invoke(null,$sql)));
check(count($statements) === 7 && str_contains($statements[6],"'1.86'"), 'Notification migration must survive actual splitter.');
echo "PASS: {$checks} notification age, read-state, broadcast isolation, pagination, menu and handler checks.\n";
