<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class PublicHolidayService
{
    private static ?array $dayOffDates = null;

    public static function importXml(string $xml): array
    {
        [$valid, $rows] = self::parseRows($xml);
        if (!$valid) {
            return [false, $rows];
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();
            $insert = $pdo->prepare(
                'INSERT IGNORE INTO system_public_holidays
                    (holiday_date, title, notes, kind, kind_id, source_key)
                 VALUES (:holiday_date, :title, :notes, :kind, :kind_id, :source_key)'
            );
            $added = 0;
            foreach ($rows as $row) {
                $insert->execute([
                    'holiday_date' => $row['date'],
                    'title' => $row['title'],
                    'notes' => $row['notes'],
                    'kind' => $row['kind'],
                    'kind_id' => $row['kind_id'],
                    'source_key' => hash('sha256', $row['date'] . "\0" . $row['title'] . "\0" . $row['kind_id']),
                ]);
                $added += $insert->rowCount();
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [false, 'Could not import the public holidays.'];
        }

        self::$dayOffDates = null;
        $existing = count($rows) - $added;

        return [true, ['added' => $added, 'existing' => $existing]];
    }

    public static function isDayOff(string $date): bool
    {
        if (self::$dayOffDates === null) {
            $stmt = db()->query(
                'SELECT DISTINCT holiday_date
                 FROM system_public_holidays
                 WHERE kind_id IN (1, 2)'
            );
            self::$dayOffDates = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
        }

        return isset(self::$dayOffDates[$date]);
    }

    public static function listAll(): array
    {
        $stmt = db()->query(
            'SELECT holiday_date, title, notes, kind, kind_id
             FROM system_public_holidays
             ORDER BY holiday_date, kind_id, title'
        );

        return $stmt->fetchAll();
    }

    private static function parseRows(string $xml): array
    {
        if ($xml === '' || strlen($xml) > 2 * 1024 * 1024 || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
            return [false, 'The XML file is empty, too large, or contains a forbidden declaration.'];
        }

        $previousErrorMode = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOBLANKS);
        $hasErrors = libxml_get_errors() !== [];
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorMode);

        if ($document === false || $hasErrors || $document->getName() !== 'xml') {
            return [false, 'The file is not a valid public-holiday XML document.'];
        }

        $xmlRows = $document->row;
        if (count($xmlRows) < 1 || count($xmlRows) > 10000) {
            return [false, 'The XML file must contain between 1 and 10,000 entries.'];
        }

        $rows = [];
        foreach ($xmlRows as $xmlRow) {
            $date = trim((string) ($xmlRow->date ?? ''));
            $title = trim((string) ($xmlRow->title ?? ''));
            $notes = trim((string) ($xmlRow->notes ?? ''));
            $kind = trim((string) ($xmlRow->kind ?? ''));
            $kindId = filter_var(trim((string) ($xmlRow->kind_id ?? '')), FILTER_VALIDATE_INT);
            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $dateErrors = DateTimeImmutable::getLastErrors();

            if (
                $parsedDate === false
                || $parsedDate->format('Y-m-d') !== $date
                || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                || preg_match('/\A.{1,255}\z/us', $title) !== 1
                || preg_match('/\A.{1,100}\z/us', $kind) !== 1
                || strlen($notes) > 65535
                || $kindId === false
                || $kindId < 1
                || $kindId > 255
            ) {
                return [false, 'The XML contains an entry with invalid date, title, kind, or kind ID.'];
            }

            $rows[] = [
                'date' => $date,
                'title' => $title,
                'notes' => $notes === '' ? null : $notes,
                'kind' => $kind,
                'kind_id' => $kindId,
            ];
        }

        return [true, $rows];
    }
}