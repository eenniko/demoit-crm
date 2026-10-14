<?php

declare(strict_types=1);

final class ApplicationTime
{
    public const ZONE = 'Europe/Tallinn';

    public static function mysqlOffset(?DateTimeImmutable $instant = null): string
    {
        return ($instant ?? new DateTimeImmutable('now'))
            ->setTimezone(new DateTimeZone(self::ZONE))
            ->format('P');
    }

    public static function configureDatabase(PDO $pdo, string $offset): void
    {
        $stmt = $pdo->prepare('SET SESSION time_zone = :time_zone');
        $stmt->execute(['time_zone' => $offset]);
    }
}
