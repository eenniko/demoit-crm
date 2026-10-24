<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Services/ClientDomainEmbedService.php';
require_once __DIR__ . '/../src/Services/SchemaInstaller.php';

$checks = 0;
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function t(string $key, string $fallback = ''): string
{
    return $fallback;
}
class Csrf
{
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="test-token">';
    }
}
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}

check(ClientDomainEmbedService::normalizeDomain(' Portal.Example.COM. ') === 'portal.example.com', 'Domain normalization failed.');
check(ClientDomainEmbedService::normalizeDomain('example.com') === 'example.com', 'Valid domain was rejected.');
foreach (['', 'localhost', '127.0.0.1', 'https://example.com', 'example.com/path', 'example.com:8443', '-invalid.example'] as $invalidDomain) {
    check(ClientDomainEmbedService::normalizeDomain($invalidDomain) === null, 'Invalid domain was accepted: ' . $invalidDomain);
}

$html = ClientDomainEmbedService::downloadHtml([
    'company_name' => '<script>alert("x")</script> & Co',
    'customer_domain' => 'portal.example.com',
    'embed_token' => str_repeat('a', 64),
    'client_code' => 'CL-123',
]);
check(str_contains($html, 'DemoIT CRM - &lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; Co'), 'Company name was not HTML-escaped.');
check(str_contains($html, 'https://crm.demoit.eu/embed?token=' . str_repeat('a', 64)), 'Embed URL does not contain the configured token.');
check(str_contains($html, 'https://crm.demoit.eu/login?client_code=CL-123'), 'Standalone login fallback does not pin the client code.');
check(str_contains($html, 'referrerpolicy="no-referrer"') && str_contains($html, '<meta name="referrer" content="no-referrer">'), 'Referrer protection is missing.');

$error = null;
$old = ['client_code' => 'CL-123', 'username' => 'employee'];
$pinnedClientCode = 'CL-123';
$companyName = '<img src=x>';
$formAction = '/embed?token=' . str_repeat('b', 64);
$embedFallbackUrl = 'https://crm.demoit.eu/login?client_code=CL-123';
ob_start();
require __DIR__ . '/../views/auth/login.php';
$loginHtml = ob_get_clean();
check(str_contains($loginHtml, 'action="/embed?token=' . str_repeat('b', 64) . '"'), 'Embed login form lost its token action.');
check(str_contains($loginHtml, 'name="client_code" value="CL-123"'), 'Pinned client code is missing from login form.');
check(str_contains($loginHtml, '&lt;img src=x&gt;'), 'Company name was not escaped in the login view.');
check(str_contains($loginHtml, 'target="_blank" rel="noopener noreferrer"'), 'Standalone login fallback is missing.');

$migration = file_get_contents(__DIR__ . '/../sql/081_client_domain_embed.sql');
$installer = file_get_contents(__DIR__ . '/../src/Services/SchemaInstaller.php');
check(is_string($migration) && str_contains($migration, 'CREATE TABLE IF NOT EXISTS system_client_domain_embeds'), 'Embed migration does not create its configuration table.');
check(is_string($migration) && str_contains($migration, "VALUES ('1.94'"), 'Embed migration does not register its release version.');
check(is_string($migration) && str_contains($migration, "'embed.domain_saved'") && str_contains($migration, "l.language_code IN ('en', 'et', 'ru')"), 'Embed UI translations are not seeded for all supported languages.');
check(is_string($installer) && str_contains($installer, "sql/081_client_domain_embed.sql") && str_contains($installer, "version = '1.94'"), 'Schema installer does not register the embed migration.');
$splitter = new ReflectionMethod(SchemaInstaller::class, 'splitStatements');
$statements = array_values(array_filter($splitter->invoke(null, (string) $migration), static fn (string $statement): bool => $statement !== ''));
check(count($statements) === 7, 'Embed migration must split into seven complete statements.');
check(str_contains($statements[6], "VALUES ('1.94'"), 'Migration version statement was truncated by the installer splitter.');

echo 'PASS: ' . $checks . " client-domain embed checks.\n";
