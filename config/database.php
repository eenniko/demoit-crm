<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../src/Support/ApplicationTime.php';

date_default_timezone_set(ApplicationTime::ZONE);

/**
 * Returns a shared PDO connection using credentials from the environment (.env).
 */
function db(): PDO
{
    static $pdo = null;
    static $timeZoneOffset = null;

    $currentOffset = ApplicationTime::mysqlOffset();
    if ($pdo instanceof PDO) {
        if ($timeZoneOffset !== $currentOffset) {
            ApplicationTime::configureDatabase($pdo, $currentOffset);
            $timeZoneOffset = $currentOffset;
        }
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $name = getenv('DB_NAME') ?: '';
    $user = getenv('DB_USER') ?: '';
    $pass = getenv('DB_PASS') ?: '';
    $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

    $connection = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    ApplicationTime::configureDatabase($connection, $currentOffset);
    $timeZoneOffset = $currentOffset;
    $pdo = $connection;

    return $pdo;
}
