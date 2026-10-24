<?php

declare(strict_types=1);

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->sqliteCreateFunction('SHA2', static fn(string $value, int $bits): string => hash('sha' . $bits, $value));
$pdo->sqliteCreateFunction('CONCAT', static fn(string ...$values): string => implode('', $values));
$pdo->sqliteCreateFunction('NOW', static fn(): string => '2026-10-14 14:00:00');
$pdo->exec("CREATE TABLE system_languages (id INTEGER PRIMARY KEY, language_code TEXT UNIQUE, is_active INTEGER);
    INSERT INTO system_languages VALUES (1, 'en', 1), (2, 'et', 1), (3, 'ru', 1);
    CREATE TABLE system_translation_keys (id INTEGER PRIMARY KEY, translation_key TEXT UNIQUE, module_context TEXT);
    CREATE TABLE system_translation_values (
        id INTEGER PRIMARY KEY, translation_key_id INTEGER, language_id INTEGER, value TEXT, status TEXT,
        UNIQUE (translation_key_id, language_id)
    );
    CREATE TABLE system_version_logs (
        version TEXT PRIMARY KEY, description TEXT, affected_modules TEXT, database_changes TEXT, released_at TEXT
    )");

function db(): PDO
{
    global $pdo;
    return $pdo;
}

class LanguageService
{
    public static string $code = 'en';

    public static function currentCode(): string
    {
        return self::$code;
    }
}

class Csrf
{
    public static function field(): string
    {
        return '';
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$root = dirname(__DIR__);
$source = file_get_contents($root . '\\src\\Services\\TranslationService.php');
eval(substr($source, strpos($source, 'class TranslationService')));
$installerSource = file_get_contents($root . '\\src\\Services\\SchemaInstaller.php');
eval(substr($installerSource, strpos($installerSource, 'class SchemaInstaller')));
$splitter = new ReflectionMethod(SchemaInstaller::class, 'splitStatements');
$sql = file_get_contents($root . '\\sql\\077_property_type_translations.sql');
$statements = array_values(array_filter($splitter->invoke(null, $sql)));

$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}

check(count($statements) === 3, 'Migration must split into three complete statements.');
for ($run = 0; $run < 2; $run++) {
    foreach ($statements as $statement) {
        $pdo->exec(str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $statement));
    }
    check((int) $pdo->query('SELECT COUNT(*) FROM system_translation_keys')->fetchColumn() === 4, 'Four type keys, no duplicates.');
    check((int) $pdo->query('SELECT COUNT(*) FROM system_translation_values')->fetchColumn() === 12, 'All four types in all three languages.');
    check((int) $pdo->query("SELECT COUNT(*) FROM system_version_logs WHERE version = '1.90'")->fetchColumn() === 1, 'Release registered once.');
}

$expected = [
    'en' => ['Building' => 'Building', 'Wing' => 'Wing', 'Floor' => 'Floor', 'Room' => 'Room'],
    'et' => ['Building' => 'Hoone', 'Wing' => 'Korpus', 'Floor' => 'Korrus', 'Room' => 'Ruum'],
    'ru' => ['Building' => 'Здание', 'Wing' => 'Корпус', 'Floor' => 'Этаж', 'Room' => 'Помещение'],
];
$jobTitleSql = file_get_contents($root . '\\sql\\078_new_job_title_translation.sql');
$jobTitleStatements = array_values(array_filter($splitter->invoke(null, $jobTitleSql)));
check(count($jobTitleStatements) === 3, 'Job title migration must split into three complete statements.');
for ($run = 0; $run < 2; $run++) {
    foreach ($jobTitleStatements as $statement) {
        $pdo->exec(str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $statement));
    }
    check((int) $pdo->query('SELECT COUNT(*) FROM system_translation_keys')->fetchColumn() === 5, 'Job title key registered once.');
    check((int) $pdo->query('SELECT COUNT(*) FROM system_translation_values')->fetchColumn() === 15, 'Job title has all three translations without duplicates.');
    check((int) $pdo->query("SELECT COUNT(*) FROM system_version_logs WHERE version = '1.91'")->fetchColumn() === 1, 'Job title release registered once.');
}
$expected['en']['New job title'] = 'New job title';
$expected['et']['New job title'] = 'Uus ametinimetus';
$expected['ru']['New job title'] = 'Новая должность';
$employmentSql = file_get_contents($root . '\\sql\\079_employment_ui_translations.sql');
$employmentStatements = array_values(array_filter($splitter->invoke(null, $employmentSql)));
check(count($employmentStatements) === 7, 'Employment translation migration has seven complete statements.');
for ($run = 0; $run < 2; $run++) {
    foreach ($employmentStatements as $statement) {
        $statement = str_replace(['INSERT IGNORE', 'DROP TEMPORARY TABLE'], ['INSERT OR IGNORE', 'DROP TABLE'], $statement);
        $statement = preg_replace('/ CHARACTER SET utf8mb4 COLLATE utf8mb4_bin| ENGINE=InnoDB DEFAULT CHARSET=utf8mb4/', '', $statement);
        $pdo->exec($statement);
    }
    check((int) $pdo->query('SELECT COUNT(*) FROM system_translation_keys')->fetchColumn() === 15, 'Employment keys registered without duplicates.');
    check((int) $pdo->query('SELECT COUNT(*) FROM system_translation_values')->fetchColumn() === 45, 'Employment labels have all three translations.');
    check((int) $pdo->query("SELECT COUNT(*) FROM system_version_logs WHERE version = '1.92'")->fetchColumn() === 1, 'Employment release registered once.');
}
$employmentLabels = [
    'New department' => ['Uus osakond', 'Новый отдел'],
    'New workload' => ['Uus koormus', 'Новая нагрузка'],
    'Job title name' => ['Ametinimetuse nimi', 'Название должности'],
    'Department name' => ['Osakonna nimi', 'Название отдела'],
    'Workload name' => ['Koormuse nimi', 'Название нагрузки'],
    'Hide' => ['Peida', 'Скрыть'],
    'Activate' => ['Aktiveeri', 'Активировать'],
    'Edit contract' => ['Muuda lepingut', 'Изменить договор'],
    'Open-ended' => ['Tähtajatu', 'Бессрочный'],
    'Restore' => ['Taasta', 'Восстановить'],
];
foreach ($employmentLabels as $english => [$estonian, $russian]) {
    $expected['en'][$english] = $english;
    $expected['et'][$english] = $estonian;
    $expected['ru'][$english] = $russian;
}
$exceptionSql = file_get_contents($root . '\\sql\\080_schedule_consecutive_exception_translations.sql');
$exceptionStatements = array_values(array_filter($splitter->invoke(null, $exceptionSql)));
check(count($exceptionStatements) === 3, 'Schedule exception translation migration has three complete statements.');
for ($run = 0; $run < 2; $run++) {
    foreach ($exceptionStatements as $statement) {
        $pdo->exec(str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $statement));
    }
    check((int) $pdo->query('SELECT COUNT(*) FROM system_translation_keys')->fetchColumn() === 17, 'Schedule exception labels registered without duplicates.');
    check((int) $pdo->query('SELECT COUNT(*) FROM system_translation_values')->fetchColumn() === 51, 'Schedule exception labels have all three translations.');
    check((int) $pdo->query("SELECT COUNT(*) FROM system_version_logs WHERE version = '1.93'")->fetchColumn() === 1, 'Schedule exception release registered once.');
}
$exceptionLabels = [
    'Consecutive days (including selected day)' => [
        'Consecutive days (including selected day)',
        'Päevade arv (koos valitud päevaga)',
        'Количество дней (включая выбранный день)',
    ],
    'The exception cannot extend beyond this contract.' => [
        'The exception cannot extend beyond this contract.',
        'Erand ei saa ulatuda töölepingu kehtivusajast kaugemale.',
        'Исключение не может выходить за пределы срока договора.',
    ],
];
foreach ($exceptionLabels as $english => [$englishValue, $estonian, $russian]) {
    $expected['en'][$english] = $englishValue;
    $expected['et'][$english] = $estonian;
    $expected['ru'][$english] = $russian;
}
foreach ($expected as $language => $labels) {
    LanguageService::$code = $language;
    $textMap = TranslationService::textMap();
    foreach ($labels as $english => $translated) {
        check(TranslationService::translate('ui.' . hash('sha256', $english), $english) === $translated, 'Database translation: ' . $language . '/' . $english);
        check(($textMap[$english] ?? $english) === $translated, 'UI text map: ' . $language . '/' . $english);
    }
    foreach (['titles' => 'Job title', 'departments' => 'Department', 'workloads' => 'Workload'] as $catalog => $catalogLabel) {
        $canManage = true;
        $entries = [['id' => 1, 'name' => 'User entered name', 'status' => 'active', 'workload_percent' => '50.00']];
        ob_start();
        require $root . '\\views\\panel\\employment\\catalog.php';
        $html = ob_get_clean();
        check(str_contains($html, 'aria-label="' . e($labels[$catalogLabel . ' name']) . '"'), 'Rendered accessible label: ' . $language . '/' . $catalog);
        check(str_contains($html, 'value="User entered name"'), 'User catalogue names unchanged.');
    }
}

echo "PASS: {$checks} property, employment and schedule exception translation, catalogue, idempotency and migration splitter checks.\n";
