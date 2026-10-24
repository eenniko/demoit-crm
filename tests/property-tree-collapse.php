<?php

declare(strict_types=1);

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
class Csrf
{
    public static function field(): string { return '<input name="csrf_token" value="test">'; }
}
$source = file_get_contents(__DIR__ . '/../src/Services/PropertyService.php');
eval(substr($source, strpos($source, 'class PropertyService')));
$nodes = PropertyService::buildTree([
    ['id' => 1, 'parent_id' => null, 'node_type' => 'building', 'name' => 'Building <A>', 'sort_order' => 0],
    ['id' => 2, 'parent_id' => 1, 'node_type' => 'wing', 'name' => 'Wing A', 'sort_order' => 0],
    ['id' => 3, 'parent_id' => 2, 'node_type' => 'floor', 'name' => 'Floor 1', 'sort_order' => 0],
    ['id' => 4, 'parent_id' => 3, 'node_type' => 'room', 'name' => 'Room 1', 'sort_order' => 0],
    ['id' => 5, 'parent_id' => 1, 'node_type' => 'floor', 'name' => 'Direct floor', 'sort_order' => 0],
    ['id' => 6, 'parent_id' => null, 'node_type' => 'building', 'name' => 'Empty building', 'sort_order' => 1],
]);
$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}
foreach ([true, false] as $canManage) {
    ob_start();
    require __DIR__ . '/../views/panel/property/index.php';
    $html = ob_get_clean();
    $document = new DOMDocument();
    $document->loadHTML($html);
    $xpath = new DOMXPath($document);
    check($xpath->query('//tbody/tr')->length === 6, 'All nodes must remain in the DOM.');
    check($xpath->query('//tbody/tr[not(@hidden)]')->length === 2, 'Only root buildings should start visible.');
    check($xpath->query('//tbody/tr[@hidden]')->length === 4, 'All descendants must start collapsed.');
    check($xpath->query('//button[@data-property-toggle]')->length === 3, 'Only nodes with children should have toggles.');
    check($xpath->query('//button[@data-property-toggle and @aria-expanded="false"]')->length === 3, 'All toggles must start collapsed.');
    check($xpath->query('//tr[@id="property-node-1"]//button[@aria-controls="property-node-2 property-node-5"]')->length === 1, 'Building must control its direct wing and floor.');
    check($xpath->query('//tr[@id="property-node-4"]//button[@data-property-toggle]')->length === 0, 'Room leaf should not have a toggle.');
    check(str_contains($html, 'Building &lt;A&gt;') && !str_contains($html, 'Building <A>'), 'Node names and attributes must be escaped.');
    check((str_contains($html, 'action="/panel/property/reorder"')) === $canManage, 'Reordering remains admin-only.');
    check((str_contains($html, '/panel/property/edit?id=4')) === $canManage, 'Editing remains admin-only, including hidden rows.');
}
$nodes = [];
$canManage = false;
ob_start();
require __DIR__ . '/../views/panel/property/index.php';
$html = ob_get_clean();
check(str_contains($html, 'No buildings yet.') && !str_contains($html, 'id="property-tree"'), 'Empty state must remain intact.');
$installer = file_get_contents(__DIR__ . '/../src/Services/SchemaInstaller.php');
eval(substr($installer, strpos($installer, 'class SchemaInstaller')));
$splitter = new ReflectionMethod(SchemaInstaller::class, 'splitStatements');
$statements = array_values(array_filter($splitter->invoke(null, file_get_contents(__DIR__ . '/../sql/085_property_tree_collapse.sql'))));
check(count($statements) === 1 && str_contains($statements[0], "'1.98'") && str_ends_with($statements[0], 'NOW())'), 'Version migration must survive the installer SQL splitter.');
echo "PASS: {$checks} property tree default collapse, rendering, permissions and migration checks.\n";
