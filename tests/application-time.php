<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Support/ApplicationTime.php';

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}

foreach ([
    '2026-01-15T12:00:00Z' => '+02:00',
    '2026-07-15T12:00:00Z' => '+03:00',
    '2026-10-14T09:10:51Z' => '+03:00',
    '2026-03-29T00:59:59Z' => '+02:00',
    '2026-03-29T01:00:00Z' => '+03:00',
    '2026-10-25T00:59:59Z' => '+03:00',
    '2026-10-25T01:00:00Z' => '+02:00',
    '2027-01-01T00:00:00Z' => '+02:00',
] as $time => $offset) {
    check(ApplicationTime::mysqlOffset(new DateTimeImmutable($time)) === $offset, 'Wrong Tallinn offset: ' . $time);
}
date_default_timezone_set('America/New_York');
check(ApplicationTime::mysqlOffset(new DateTimeImmutable('2026-10-14 09:10:51', new DateTimeZone('UTC'))) === '+03:00',
    'Offset must not depend on hosting timezone.');

class TimeStatement extends PDOStatement
{
    public array $parameters = [];
    public bool $fail = false;
    public function execute(?array $params = null): bool
    {
        if ($this->fail) {
            throw new PDOException('Session timezone rejected.');
        }
        $this->parameters = $params ?? [];
        return true;
    }
}
class TimeDatabase extends PDO
{
    public string $query = '';
    public TimeStatement $statement;
    public function __construct() { $this->statement = new TimeStatement(); }
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->query = $query;
        return $this->statement;
    }
}
$pdo = new TimeDatabase();
ApplicationTime::configureDatabase($pdo, '+03:00');
check($pdo->query === 'SET SESSION time_zone = :time_zone'
    && $pdo->statement->parameters === ['time_zone' => '+03:00'], 'SQL session must use bound Tallinn offset.');
$pdo->statement->fail = true;
try {
    ApplicationTime::configureDatabase($pdo, '+02:00');
    check(false, 'Timezone error must propagate.');
} catch (PDOException $exception) {
    check($exception->getMessage() === 'Session timezone rejected.', 'Timezone failure must not be hidden.');
}
$source = file_get_contents(__DIR__ . '/../config/database.php');
check(strpos($source, 'ApplicationTime::configureDatabase($connection, $currentOffset)')
    < strpos($source, '$pdo = $connection;'), 'Failed connection configuration must not publish an unsynchronized PDO.');
check(str_contains($source, '$timeZoneOffset !== $currentOffset'), 'Long-lived connection must recheck DST offset.');
require_once __DIR__ . '/../config/database.php';
check(date_default_timezone_get() === 'Europe/Tallinn', 'Bootstrap must configure PHP explicitly.');
check((new DateTimeImmutable('2026-10-14T09:10:51Z'))->setTimezone(new DateTimeZone(date_default_timezone_get()))
    ->format('Y-m-d H:i:s') === '2026-10-14 12:10:51', 'PHP display must match Estonian summer time.');
require_once __DIR__ . '/../src/Services/SchemaInstaller.php';
$splitter = new ReflectionMethod(SchemaInstaller::class, 'splitStatements');
$sql = file_get_contents(__DIR__ . '/../sql/072_estonian_time_zone.sql');
$statements = array_values(array_filter($splitter->invoke(null, $sql)));
check(count($statements) === 1 && $statements[0] === rtrim(trim($sql), ';'), 'Time migration must survive actual splitter.');
echo "PASS: {$checks} application timezone, DST, connection configuration and migration checks.\n";
