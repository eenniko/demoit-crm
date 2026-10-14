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

class SupportDatabase extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        // SQLite tests execute the service SQL, except MySQL row-lock syntax.
        $query = str_replace('ON DUPLICATE KEY UPDATE opened_at = opened_at',
            'ON CONFLICT(ticket_id, user_id) DO NOTHING', $query);
        $query = str_replace('ON DUPLICATE KEY UPDATE last_reply_id = GREATEST(last_reply_id, VALUES(last_reply_id))',
            'ON CONFLICT(ticket_id, user_id) DO UPDATE SET last_reply_id = MAX(last_reply_id, excluded.last_reply_id)', $query);
        return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options);
    }
}

function db(): PDO
{
    static $pdo;
    if ($pdo === null) {
        $pdo = new SupportDatabase('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

class AuditLogService
{
    public static bool $fail = false;
    public static array $events = [];
    public static function log(mixed ...$args): void
    {
        if (self::$fail) {
            throw new RuntimeException('Test audit failure.');
        }
        self::$events[] = $args;
    }
}
class ClientContext
{
    public static int $user = 1;
    public static int $client = 1;
    public static bool $loggedIn = true;
    public static function userId(): int { return self::$user; }
    public static function clientId(): int { return self::$client; }
    public static function isLoggedIn(): bool { return self::$loggedIn; }
}
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function render_page(string $title, string $view, array $vars = []): void { echo $view; }
function render_panel_page(string $title, string $view, array $vars = [], string $menu = ''): void
{
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/../views/panel/' . $view;
}
function render_admin_page(string $title, string $view, array $vars = [], string $menu = ''): void
{
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/../views/admin/' . $view;
}
foreach (['RoleService', 'SupportService'] as $class) {
    $source = file_get_contents(__DIR__ . '/../src/Services/' . $class . '.php');
    $start = $source === false ? false : strpos($source, 'class ' . $class);
    if ($start === false) {
        throw new RuntimeException('Cannot load service.');
    }
    eval(substr($source, $start));
}
require_once __DIR__ . '/../src/Support/Csrf.php';
const ADMIN_SYSTEM_ROLES = ['system_admin', 'developer', 'client_manager', 'client_support'];
$router = file_get_contents(__DIR__ . '/../public/index.php');
foreach (['require_system_role', 'handle_support_ticket', 'handle_panel_support', 'handle_admin_support', 'handle_admin_support_status'] as $function) {
    $start = strpos($router, 'function ' . $function . '(');
    $end = $start === false ? false : strpos($router, "\nfunction ", $start + 1);
    if ($start === false || $end === false) {
        throw new RuntimeException('Cannot load handler.');
    }
    eval(substr($router, $start, $end - $start));
}
$pdo = db();
$pdo->exec("
    CREATE TABLE system_clients (id INTEGER PRIMARY KEY, client_code TEXT);
    CREATE TABLE system_users (id INTEGER PRIMARY KEY, client_id INTEGER, username TEXT, full_name TEXT, status TEXT);
    CREATE TABLE system_roles (id INTEGER PRIMARY KEY, role_key TEXT);
    CREATE TABLE system_user_roles (user_id INTEGER, client_id INTEGER, role_id INTEGER, status TEXT);
    CREATE TABLE employment_contracts (id INTEGER PRIMARY KEY, client_id INTEGER, employee_id INTEGER,
        manager_user_id INTEGER, contract_type TEXT, start_date TEXT, end_date TEXT, archived_at TEXT);
    CREATE TABLE system_support_tickets (id INTEGER PRIMARY KEY AUTOINCREMENT, client_id INTEGER,
        created_by_user_id INTEGER, assigned_to_user_id INTEGER, recipient_type TEXT DEFAULT 'system',
        subject TEXT, message TEXT, status TEXT DEFAULT 'open', created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP, closed_at TEXT, closed_by_user_id INTEGER);
    CREATE TABLE system_support_reads (ticket_id INTEGER REFERENCES system_support_tickets(id),
        user_id INTEGER REFERENCES system_users(id), opened_at TEXT, PRIMARY KEY(ticket_id,user_id));
    CREATE TABLE system_support_reply_reads (ticket_id INTEGER REFERENCES system_support_tickets(id),
        user_id INTEGER REFERENCES system_users(id), last_reply_id INTEGER, PRIMARY KEY(ticket_id,user_id));
    CREATE TABLE system_support_replies (id INTEGER PRIMARY KEY AUTOINCREMENT, ticket_id INTEGER REFERENCES system_support_tickets(id),
        user_id INTEGER REFERENCES system_users(id), message TEXT, created_at TEXT);
    CREATE TABLE system_support_participants (ticket_id INTEGER REFERENCES system_support_tickets(id),
        user_id INTEGER REFERENCES system_users(id), added_by_user_id INTEGER, created_at TEXT, PRIMARY KEY(ticket_id,user_id));
    INSERT INTO system_clients VALUES (1,'client-one'),(2,'client-two'),(3,'13666');
    INSERT INTO system_users VALUES
        (1,1,'f-user','Employee Name','active'),(2,1,'admin','Client Admin','active'),
        (3,1,'manager','Manager One','active'),(4,1,'invitee','Participant Name','active'),
        (5,1,'unrelated','Employee Name','active'),(6,1,'inactive','Inactive','inactive'),
        (7,1,'admin2','','active'),(8,1,'manager2','Manager Two','active'),
        (9,1,'old-manager','Old Manager','active'),(10,1,'future-manager','Future Manager','active'),
        (11,2,'other','Foreign User','active'),(12,3,'system','System Admin','active'),
        (13,3,'support','System Support','active'),(14,1,'system-client','System role in client','active'),
        (15,1,'archived-manager','Archived Manager','active');
    INSERT INTO system_roles VALUES (1,'level_f'),(2,'client_admin'),(3,'system_admin'),(4,'client_support');
    INSERT INTO system_user_roles VALUES (1,1,1,'active'),(2,1,2,'active'),(7,1,2,'active'),
        (11,2,2,'active'),(12,3,3,'active'),(13,3,4,'active'),(14,1,4,'active');
");
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$yesterday = date('Y-m-d', strtotime('-1 day'));
$contract = $pdo->prepare('INSERT INTO employment_contracts VALUES (?,?,?,?,?,?,?,?)');
foreach ([
    [1,1,1,3,'primary',$today,null,null], [2,1,1,8,'temporary',$today,$today,null],
    [3,1,1,9,'primary','2020-01-01',$yesterday,null], [4,1,1,10,'temporary',$tomorrow,null,null],
    [5,1,1,15,'temporary',$today,null,$today], [6,2,1,11,'primary',$today,null,null],
] as $row) {
    $contract->execute($row);
}
$options = SupportService::recipientOptions(1, 1);
check(array_map('intval', array_column($options['client_admin'], 'id')) === [2,7], 'Only active same-client administrators.');
check(array_map('intval', array_column($options['manager'], 'id')) === [3,8], 'Only valid primary/temporary managers, inclusive today.');
check(SupportService::recipientOptions(1, 4)['manager'] === [], 'Account without contract must have no managers.');
function createTicket(string $type, int $recipient = 0, int $creator = 1, int $client = 1): int
{
    $result = SupportService::create($client, $creator, ['recipient_type' => $type, 'recipient_user_id' => $recipient,
        'subject' => 'Question <script>', 'message' => "Initial <script>alert(1)</script>\nSecond line"]);
    check($result[0], 'Ticket creation failed: ' . $result[1]);
    return $result[2];
}
$manager = createTicket('manager', 3);
$admin = createTicket('client_admin', 2);
$system = createTicket('system', 11);
$foreign = createTicket('client_admin', 11, 11, 2);
check(SupportService::ticket($system, 1, 1)['assigned_to_user_id'] === null, 'System recipient must not trust submitted person.');
foreach ([['manager',9],['manager',10],['manager',15],['manager',11],['manager',2],['client_admin',3],['client_admin',11],['unknown',2]] as [$type,$id]) {
    check(!SupportService::create(1,1,['subject'=>'x','message'=>'x','recipient_type'=>$type,'recipient_user_id'=>$id])[0], 'Invalid recipient accepted.');
}
check(!SupportService::create(1,6,['subject'=>'x','message'=>'x','recipient_type'=>'system'])[0], 'Inactive creator accepted.');
check(!SupportService::create(2,1,['subject'=>'x','message'=>'x','recipient_type'=>'system'])[0], 'Foreign creator accepted.');
check(!SupportService::create(1,1,['subject'=>' ','message'=>'x','recipient_type'=>'system'])[0], 'Empty subject accepted.');
check(!SupportService::create(1,1,['subject'=>str_repeat('x',192),'message'=>'x','recipient_type'=>'system'])[0], 'Oversized subject accepted.');
check(!SupportService::create(1,1,['subject'=>'x','message'=>str_repeat('x',65536),'recipient_type'=>'system'])[0], 'Oversized message accepted.');
$boundary = SupportService::create(1,1,['subject'=>str_repeat("\xC3\xA9",191),'message'=>str_repeat('x',65535),'recipient_type'=>'system']);
check($boundary[0] && mb_strlen(SupportService::ticket($boundary[2],1,1)['subject']) === 191, 'Exact subject/message limits must persist.');
check(SupportService::ticket($manager,1,1)['creator_name'] === 'Employee Name', 'Creator must be a name.');
check(SupportService::ticket($manager,1,3)['can_manage'], 'Assigned manager must manage.');
foreach ([2,4,5,7,8,14] as $viewer) {
    check(SupportService::ticket($manager,1,$viewer) === null, 'Unrelated same-client user saw private ticket.');
}
check(SupportService::ticket($manager,3,12,true) === null, 'System admin must not automatically see manager tickets.');
check(SupportService::ticket($admin,1,7) === null, 'Other client administrator must not see directed ticket.');
check(SupportService::ticket($foreign,1,1) === null, 'Foreign-client ticket leaked.');
check(SupportService::ticket($system,3,12,true) !== null && SupportService::ticket($system,3,13,true) !== null, 'System queue must support all active responders.');
check(SupportService::ticket($system,1,14) !== null, 'Existing system roles retain queue access.');
check(SupportService::ticket($manager,1,1,true) === null, 'Client user must not enter system workspace.');
try {
    SupportService::replies($manager,1,5);
    check(false, 'Thread leaked via service.');
} catch (RuntimeException $exception) {
    check($exception->getMessage() === 'Support ticket access denied.', 'Explicit thread access error required.');
}
check(!SupportService::act($manager,1,5,'reply',['message'=>'unauthorized'])[0], 'Unauthorized reply accepted.');
check(!SupportService::act($manager,1,1,'reply',['message'=>' '])[0], 'Empty reply accepted.');
check(!SupportService::act($manager,1,1,'reply',['message'=>str_repeat('x',65536)])[0], 'Oversized reply accepted.');
check(!SupportService::act($manager,1,1,'invalid',[])[0], 'Invalid action accepted.');
check(SupportService::act($manager,1,3,'reply',['message'=>'Manager answer'])[0], 'Recipient reply failed.');
check(SupportService::act($manager,1,1,'reply',['message'=>'Follow-up question'])[0], 'Creator follow-up failed.');
check(SupportService::ticket($manager,1,1)['status'] === 'in_progress', 'Reply must advance open ticket.');
check(array_column(SupportService::replies($manager,1,1),'message') === ['Manager answer','Follow-up question'], 'Same-second chronological thread order.');
check(!SupportService::act($manager,1,1,'invite',['user_id'=>11])[0], 'Foreign invite accepted.');
check(!SupportService::act($manager,1,1,'invite',['user_id'=>6])[0], 'Inactive invite accepted.');
check(!SupportService::act($manager,1,1,'invite',['user_id'=>3])[0], 'Recipient duplicate invite accepted.');
check(SupportService::act($manager,1,1,'invite',['user_id'=>4])[0], 'Creator invitation failed.');
check(SupportService::ticket($manager,1,4) !== null, 'Invitee cannot read ticket.');
check(SupportService::act($manager,1,4,'reply',['message'=>'Participant answer'])[0], 'Invitee cannot reply.');
check(!SupportService::act($manager,1,4,'invite',['user_id'=>5])[0], 'Invitee must not invite.');
check(!SupportService::act($manager,1,4,'close',[])[0], 'Invitee must not close.');
check(!SupportService::act($manager,1,3,'invite',['user_id'=>4])[0], 'Duplicate participant accepted.');
check(SupportService::act($manager,1,3,'invite',['user_id'=>5])[0], 'Recipient invitation failed.');
check(count(SupportService::participants($manager,1,4)) === 2, 'Participant list mismatch.');
$pdo->exec("UPDATE employment_contracts SET archived_at = '{$today}' WHERE id = 1");
check(SupportService::ticket($manager,1,3) !== null, 'Original manager keeps conversation after contract changes.');
$pdo->exec("UPDATE system_users SET status = 'inactive' WHERE id = 4");
check(SupportService::ticket($manager,1,4) === null, 'Inactive invited account must lose access.');
$pdo->exec("UPDATE system_users SET status = 'active' WHERE id = 4");
AuditLogService::$fail = true;
$before = count(SupportService::replies($manager,1,1));
try {
    SupportService::act($manager,1,1,'reply',['message'=>'Rollback']);
    check(false, 'Audit error must propagate.');
} catch (RuntimeException $exception) {
    check($exception->getMessage() === 'Test audit failure.', 'Unexpected transaction error.');
}
AuditLogService::$fail = false;
check(count(SupportService::replies($manager,1,1)) === $before && !$pdo->inTransaction(), 'Audit failure must roll back reply.');
check(SupportService::act($manager,1,3,'close',[])[0], 'Recipient closure failed.');
$closed = SupportService::ticket($manager,1,1);
check($closed['closed_at'] !== null && (int)$closed['closed_by_user_id'] === 3, 'Closure must persist timestamp and actor.');
check(SupportService::ticket($manager,1,4) !== null, 'Closed thread must remain accessible to invitees.');
foreach (['reply','invite','close'] as $action) {
    check(!SupportService::act($manager,1,1,$action,['message'=>'late','user_id'=>7])[0], 'Closed ticket mutation accepted.');
}
check(count(SupportService::replies($manager,1,4)) === 3, 'Closed thread must retain full content.');
check(SupportService::ticket($manager,1,1)['closed_at'] === $closed['closed_at'], 'Rejected late changes must preserve closure timestamp.');
check(SupportService::act($admin,1,1,'close',[])[0], 'Creator closure failed.');
check(SupportService::act($system,3,13,'invite',['user_id'=>4],true)[0], 'System responder must invite ticket-client employee.');
check(SupportService::act($system,1,4,'reply',['message'=>'System invitation reply'])[0], 'System ticket invitee cannot reply in panel.');
check(SupportService::act($system,3,12,'close',[],true)[0], 'System responder closure failed.');
$pdo->exec("UPDATE system_user_roles SET status = 'inactive' WHERE user_id = 13");
check(SupportService::ticket($system,3,13,true) === null, 'Revoked system role must lose queue access.');
$pdo->exec("UPDATE system_user_roles SET status = 'active' WHERE user_id = 13");

$expected = [];
for ($i = 1; $i <= 12; $i++) {
    $id = createTicket('client_admin',2);
    check(SupportService::act($id,1,1,'close',[])[0], 'Archive fixture closure failed.');
    $timestamp = sprintf('2099-01-%02d 00:00:00', $i);
    $stmt = $pdo->prepare('UPDATE system_support_tickets SET closed_at = :closed, created_at = :created WHERE id = :id');
    $stmt->execute(['closed'=>$timestamp,'created'=>sprintf('2000-01-%02d 00:00:00',13-$i),'id'=>$id]);
    $expected[] = $id;
}
$archive = SupportService::listVisible(1,1)['closed'];
check(count($archive) === 10, 'Archive must show exactly ten.');
check(array_map('intval',array_column($archive,'id')) === array_slice(array_reverse($expected),0,10), 'Archive must order by closure, not creation.');
$tie = $pdo->prepare('UPDATE system_support_tickets SET closed_at = :closed WHERE id = :id');
$tie->execute(['closed'=>'2099-01-12 00:00:00','id'=>$expected[10]]);
check(array_map('intval',array_column(SupportService::listVisible(1,1)['closed'],'id')) === array_slice(array_reverse($expected),0,10), 'Equal closure times must order by descending ID.');
check(SupportService::listVisible(1,8) === ['open'=>[],'closed'=>[]], 'Unrelated manager list leaked.');
check(count(SupportService::listVisible(3,12,true)['closed']) === 1, 'System archive must not expose directed client tickets.');
check(SupportService::ticket($manager,1,1) !== null, 'Old closed ticket must still open by authorized direct link.');

// Execute the real handler and templates without routing/bootstrap or a production DB.
ob_start();
$_SESSION = [];
$token = Csrf::token();
$_GET = ['id'=>$manager]; $_POST = [];
ClientContext::$user = 4;
handle_support_ticket('GET',false);
$html = ob_get_clean();
check(str_contains($html,'Participant answer') && str_contains($html,'Initial &lt;script&gt;'), 'Full escaped closed thread must render.');
check(!str_contains($html,'<form') && str_contains($html,'read-only'), 'Closed thread must not render mutation forms.');
$open = createTicket('client_admin',2);
ob_start();
ClientContext::$user = 1; $_GET = ['id'=>$open];
handle_support_ticket('GET',false);
$html = ob_get_clean();
check(str_contains($html,'/panel/support/reply') && str_contains($html,'/panel/support/invite') && str_contains($html,'/panel/support/close'), 'Creator UI must wire all actions.');
$csrfCount = count(SupportService::replies($open,1,1));
ob_start(); handle_support_ticket('GET',false,'reply'); ob_end_clean();
check(http_response_code() === 405 && count(SupportService::replies($open,1,1)) === $csrfCount, 'GET must not mutate tickets.');
$count = count(SupportService::replies($open,1,1));
$_POST = ['id'=>$open,'message'=>'CSRF attack','csrf_token'=>'invalid'];
ob_start();
handle_support_ticket('POST',false,'reply');
$html = ob_get_clean();
check(http_response_code() === 403 && str_contains($html,'Invalid session token'), 'Invalid CSRF must be explicit 403.');
check(count(SupportService::replies($open,1,1)) === $count, 'Invalid CSRF wrote a reply.');
$_POST['csrf_token'] = $token;
ob_start();
handle_support_ticket('POST',false,'reply');
ob_end_clean();
check(count(SupportService::replies($open,1,1)) === $count+1, 'Valid handler POST must persist reply.');
ClientContext::$user = 8;
$_GET = ['id'=>$open];
ob_start(); handle_support_ticket('GET',false); $html = ob_get_clean();
check(http_response_code() === 404 && $html === 'errors/404.php', 'Unauthorized detail must not disclose content.');
$_POST = ['id'=>$open,'message'=>'forged','csrf_token'=>$token];
ob_start(); handle_support_ticket('POST',false,'reply'); ob_end_clean();
check(http_response_code() === 404 && count(SupportService::replies($open,1,1)) === $count+1, 'Unauthorized handler reply wrote data.');
ClientContext::$user = 1;
ob_start(); handle_panel_support(); $html = ob_get_clean();
check(strpos($html,'Open tickets') < strpos($html,'Recently closed tickets'), 'Closed section must be below open.');
check(str_contains($html,'Employee Name') && !str_contains($html,'f-user'), 'List must display creator name, not username or ID.');
check(str_contains($html,'/view?id=' . $open) && substr_count($html,'<tbody>') === 2, 'Both segments must link to detail.');
ClientContext::$user = 12; ClientContext::$client = 3;
$_GET = ['id'=>$system]; $_POST = [];
ob_start(); handle_support_ticket('GET',true); $html = ob_get_clean();
check(str_contains($html,'System invitation reply') && str_contains($html,'/admin/support'), 'System workspace must render same full thread.');
$_POST = ['id'=>$system,'status'=>'open','csrf_token'=>$token];
ob_start(); handle_admin_support_status('POST'); $html = ob_get_clean();
check(http_response_code() === 400 && SupportService::ticket($system,3,12,true)['status'] === 'closed', 'Legacy endpoint must not reopen a ticket.');
ob_start(); handle_admin_support(); $html = ob_get_clean();
check(str_contains($html,'client-one') && !str_contains($html,'client-two'), 'System list must include only allowed tickets.');
check(count(AuditLogService::$events) > 20, 'Writes must be audited.');
$pdo->exec("UPDATE employment_contracts SET archived_at = NULL WHERE id = 1");
$pdo->exec("UPDATE system_support_tickets SET status = 'closed' WHERE status != 'closed'");
$unopened = createTicket('client_admin',2);
check(SupportService::unreadCount(1,1) === 0, 'Creator must count as read immediately, without a receipt.');
check(SupportService::unreadCount(1,2) === 1, 'Recipient must count unopened active ticket.');
check(SupportService::unreadCount(1,7) === 0 && SupportService::unreadCount(1,8) === 0,
    'Unrelated admins/managers must not count private tickets.');
check(SupportService::act($unopened,1,1,'invite',['user_id'=>4])[0]
    && SupportService::unreadCount(1,4) === 1, 'Invitee must count unopened active ticket.');
check(SupportService::act($unopened,1,1,'invite',['user_id'=>5])[0], 'Second unread invite fixture.');
check(!SupportService::markOpened($unopened,1,8) && !SupportService::markOpened($unopened,2,11),
    'Unauthorized users cannot write read receipts.');
ClientContext::$client = 1; ClientContext::$user = 2;
$_GET = [];
ob_start(); handle_panel_support(); ob_end_clean();
check(SupportService::unreadCount(1,2) === 1, 'List view must not mark tickets read.');
$_POST = ['id'=>$unopened,'message'=>'invalid','csrf_token'=>'invalid'];
ob_start(); handle_support_ticket('POST',false,'reply'); ob_end_clean();
check(SupportService::unreadCount(1,2) === 1, 'Invalid CSRF request must not mark ticket read.');
$_GET = ['id'=>$unopened];
ob_start(); handle_support_ticket('GET',false); ob_end_clean();
check(SupportService::unreadCount(1,2) === 0, 'Authorized detail GET must immediately remove recipient count.');
$receipt = $pdo->query("SELECT opened_at FROM system_support_reads WHERE ticket_id = {$unopened} AND user_id = 2")->fetchColumn();
check(SupportService::markOpened($unopened,1,2)
    && $pdo->query("SELECT opened_at FROM system_support_reads WHERE ticket_id = {$unopened} AND user_id = 2")->fetchColumn() === $receipt,
    'Repeated opens must preserve first-open receipt.');
check(SupportService::unreadCount(1,4) === 1 && SupportService::unreadCount(1,5) === 1,
    'Opening must not affect other participants.');
ClientContext::$user = 4;
ob_start(); handle_support_ticket('GET',false); ob_end_clean();
check(SupportService::unreadCount(1,4) === 0, 'Invitee detail must persist read state.');
check(SupportService::act($unopened,1,1,'reply',['message'=>'Later answer'])[0]
    && SupportService::unreadCount(1,2) === 1 && SupportService::unreadCount(1,4) === 1
    && SupportService::unreadCount(1,1) === 0,
    'New reply must notify other participants, not its author.');
check(SupportService::unreadCount(1,5) === 1, 'In-progress unopened ticket must still count.');
check(SupportService::act($unopened,1,1,'close',[])[0]
    && SupportService::unreadCount(1,5) === 0, 'Closing must remove unopened ticket from count.');
$queue = createTicket('system');
check(SupportService::unreadCount(3,12,true) === 1 && SupportService::unreadCount(3,13,true) === 1,
    'Every system responder must have independent queue unread state.');
ClientContext::$user = 12; ClientContext::$client = 3; $_GET = ['id'=>$queue]; $_POST = [];
ob_start(); handle_support_ticket('GET',true); ob_end_clean();
check(SupportService::unreadCount(3,12,true) === 0 && SupportService::unreadCount(3,13,true) === 1,
    'Admin detail GET must mark only the viewing system responder.');
$pdo->exec("UPDATE system_user_roles SET status = 'inactive' WHERE user_id = 13");
try {
    SupportService::unreadCount(3,13,true);
    check(false,'Revoked system role must not access queue unread count.');
} catch (RuntimeException $exception) {
    check($exception->getMessage() === 'Support ticket access denied.','Unread count must explicitly enforce system role.');
}
$activeMenu = 'support'; $workspaceContent = ''; $activeModuleKeys = [];
$unreadSupportCount = 2; $unreadNotificationCount = 0;
function t(string $key, string $fallback): string { return $fallback; }
class SubstituteService { public static function activeScheduleManagers(int $client, int $user): array { return []; } }
ob_start(); require __DIR__ . '/../views/panel/shell.php'; $html = ob_get_clean();
check(str_contains($html,'Unread active support tickets') && str_contains($html,'data-no-translate>2</span>'),
    'Panel support badge must render positive number.');
$unreadSupportCount = 0;
ob_start(); require __DIR__ . '/../views/panel/shell.php'; $html = ob_get_clean();
check(!str_contains($html,'Unread active support tickets'), 'Zero support count must hide badge.');
$unreadSupportCount = 3;
ob_start(); require __DIR__ . '/../views/admin/shell.php'; $html = ob_get_clean();
check(str_contains($html,'Unread active support tickets') && str_contains($html,'data-no-translate>3</span>'),
    'Admin support badge must render independent number.');
$installer = file_get_contents(__DIR__ . '/../src/Services/SchemaInstaller.php');
eval(substr($installer,strpos($installer,'class SchemaInstaller')));
$splitter = new ReflectionMethod(SchemaInstaller::class,'splitStatements');
$sql = file_get_contents(__DIR__ . '/../sql/074_support_unopened_count.sql');
$statements = array_values(array_filter($splitter->invoke(null,$sql)));
check(count($statements) === 4 && str_contains($statements[3],"'1.87'"),
    'Support read migration must survive actual splitter.');
$pdo->exec("UPDATE system_user_roles SET status = 'active' WHERE user_id = 13");
$pdo->exec("UPDATE system_support_tickets SET status = 'closed' WHERE status != 'closed'");
$replyTicket = createTicket('client_admin',2);
check(SupportService::act($replyTicket,1,1,'invite',['user_id'=>4])[0], 'Reply count invite fixture.');
check(SupportService::markOpened($replyTicket,1,2) && SupportService::markOpened($replyTicket,1,4), 'Initially opened ticket fixture.');
check(SupportService::act($replyTicket,1,2,'reply',['message'=>'Answer'])[0], 'Recipient response fixture.');
check(SupportService::unreadCount(1,1) === 1 && SupportService::unreadCount(1,4) === 1
    && SupportService::unreadCount(1,2) === 0, 'Reply must notify creator and invitee, not sender.');
$seen = (int) max(array_column(SupportService::replies($replyTicket,1,1),'id'));
check(SupportService::act($replyTicket,1,2,'reply',['message'=>'Second answer'])[0]
    && SupportService::unreadCount(1,1) === 1, 'Multiple unread replies must count only one ticket.');
check(SupportService::markOpened($replyTicket,1,1,false,$seen)
    && SupportService::unreadCount(1,1) === 1, 'Reply arriving after snapshot must remain unread.');
ClientContext::$user = 1; ClientContext::$client = 1; $_GET = ['id'=>$replyTicket]; $_POST = [];
ob_start(); handle_support_ticket('GET',false); ob_end_clean();
check(SupportService::unreadCount(1,1) === 0 && SupportService::unreadCount(1,4) === 1,
    'Opening all replies must clear only viewing account.');
check(SupportService::markOpened($replyTicket,1,1,false,$seen)
    && SupportService::unreadCount(1,1) === 0, 'Stale tab must not move read watermark backwards.');
check(SupportService::act($replyTicket,1,4,'reply',['message'=>'Invitee response'])[0]
    && SupportService::unreadCount(1,4) === 0 && SupportService::unreadCount(1,1) === 1
    && SupportService::unreadCount(1,2) === 1, 'Invitee author stays read while creator and recipient become unread.');
$beforeRead = $pdo->query("SELECT last_reply_id FROM system_support_reply_reads WHERE ticket_id = {$replyTicket} AND user_id = 2")->fetchColumn();
AuditLogService::$fail = true;
try {
    SupportService::act($replyTicket,1,2,'reply',['message'=>'Failed answer']);
    check(false,'Failed reply must throw.');
} catch (RuntimeException $exception) {
    check($exception->getMessage() === 'Test audit failure.', 'Audit failure must propagate.');
}
AuditLogService::$fail = false;
check($pdo->query("SELECT last_reply_id FROM system_support_reply_reads WHERE ticket_id = {$replyTicket} AND user_id = 2")->fetchColumn() === $beforeRead,
    'Failed reply must roll back read watermark along with message.');
check(SupportService::act($replyTicket,1,2,'close',[])[0]
    && SupportService::unreadCount(1,1) === 0 && SupportService::unreadCount(1,2) === 0,
    'Closed ticket must not count even with unread replies.');
$sql = file_get_contents(__DIR__ . '/../sql/075_support_reply_unread.sql');
$statements = array_values(array_filter($splitter->invoke(null,$sql)));
check(count($statements) === 4 && str_contains($statements[3],"'1.88'"), 'Reply read migration splitter.');
$rowTicket = createTicket('client_admin',2);
$creatorRow = array_column(SupportService::listVisible(1,1)['open'],null,'id')[$rowTicket];
$recipientRow = array_column(SupportService::listVisible(1,2)['open'],null,'id')[$rowTicket];
check((int)$creatorRow['is_unread'] === 0 && (int)$creatorRow['has_unread_reply'] === 0,
    'Creator new ticket row must be read.');
check((int)$recipientRow['is_unread'] === 1 && (int)$recipientRow['has_unread_reply'] === 0,
    'Recipient unopened row must be unread without new reply.');
ClientContext::$user = 2; ClientContext::$client = 1; $_GET = [];
ob_start(); handle_panel_support(); $html = ob_get_clean();
check(str_contains($html,'<tr class="fw-bold">') && str_contains($html,'>Unread</span>'),
    'Unopened recipient row must render bold with Unread label.');
check(SupportService::act($rowTicket,1,2,'reply',['message'=>'Row answer'])[0], 'Row response fixture.');
$creatorRow = array_column(SupportService::listVisible(1,1)['open'],null,'id')[$rowTicket];
check((int)$creatorRow['is_unread'] === 1 && (int)$creatorRow['has_unread_reply'] === 1,
    'Creator response row must show unread and new reply.');
$senderRow = array_column(SupportService::listVisible(1,2)['open'],null,'id')[$rowTicket];
check((int)$senderRow['is_unread'] === 0 && (int)$senderRow['has_unread_reply'] === 0,
    'Reply author row must not be highlighted.');
check(array_sum(array_column(SupportService::listVisible(1,1)['open'],'is_unread')) === SupportService::unreadCount(1,1),
    'Row flags and menu count must agree.');
ClientContext::$user = 1;
ob_start(); handle_panel_support(); $html = ob_get_clean();
check(str_contains($html,'<tr class="fw-bold">') && str_contains($html,'>New reply</span>'),
    'Creator unread reply row must render bold with New reply label.');
$_GET = ['id'=>$rowTicket];
ob_start(); handle_support_ticket('GET',false); ob_end_clean();
ob_start(); handle_panel_support(); $html = ob_get_clean();
check(!str_contains($html,'<tr class="fw-bold">') && !str_contains($html,'>New reply</span>'),
    'Read rows must lose bold and unread labels.');
check(SupportService::act($rowTicket,1,2,'reply',['message'=>'Another row answer'])[0]
    && SupportService::act($rowTicket,1,2,'close',[])[0], 'Closed unread row fixture.');
$pdo->exec("UPDATE system_support_tickets SET closed_at = '2100-01-01 00:00:00' WHERE id = {$rowTicket}");
$closedRow = array_column(SupportService::listVisible(1,1)['closed'],null,'id')[$rowTicket];
check((int)$closedRow['is_unread'] === 0 && (int)$closedRow['has_unread_reply'] === 0,
    'Closed rows must never highlight unread replies.');
$adminRowTicket = createTicket('system');
$adminRows = SupportService::listVisible(3,12,true);
check((int)array_column($adminRows['open'],null,'id')[$adminRowTicket]['is_unread'] === 1,
    'System workspace must expose unread row state.');
ClientContext::$user = 12; ClientContext::$client = 3; $_GET = [];
ob_start(); handle_admin_support(); $html = ob_get_clean();
check(str_contains($html,'<tr class="fw-bold">') && str_contains($html,'>Unread</span>'),
    'Shared admin list must highlight unread rows.');
$sql = file_get_contents(__DIR__ . '/../sql/076_support_unread_rows.sql');
$statements = array_values(array_filter($splitter->invoke(null,$sql)));
check(count($statements) === 3 && str_contains($statements[2],"'1.89'"), 'Unread row translation migration splitter.');
echo "PASS: {$checks} support conversation, privacy, recipient, archive, transaction and handler checks.\n";
