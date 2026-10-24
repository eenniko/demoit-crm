<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

final class ClientDomainEmbedService
{
    public static function normalizeDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));
        if (str_ends_with($domain, '.')) {
            $domain = substr($domain, 0, -1);
        }

        if ($domain === '' || strlen($domain) > 253
            || filter_var($domain, FILTER_VALIDATE_IP) !== false
            || filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
            || !str_contains($domain, '.')) {
            return null;
        }

        return $domain;
    }

    public static function findForClient(int $clientId): ?array
    {
        $statement = db()->prepare(
            'SELECT e.client_id, e.customer_domain, e.embed_token, c.client_code, c.company_name
             FROM system_client_domain_embeds e
             INNER JOIN system_clients c ON c.id = e.client_id
             WHERE e.client_id = :client_id
             LIMIT 1'
        );
        $statement->execute(['client_id' => $clientId]);
        $config = $statement->fetch();

        return $config === false ? null : $config;
    }

    public static function findActiveByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $statement = db()->prepare(
            "SELECT e.client_id, e.customer_domain, e.embed_token, c.client_code, c.company_name
             FROM system_client_domain_embeds e
             INNER JOIN system_clients c ON c.id = e.client_id
             INNER JOIN system_modules m ON m.module_key = 'client_domain_embed' AND m.status = 'active'
             INNER JOIN system_client_modules cm ON cm.module_id = m.id AND cm.client_id = e.client_id AND cm.status = 'active'
             WHERE e.embed_token = :embed_token AND c.status = 'active'
             LIMIT 1"
        );
        $statement->execute(['embed_token' => $token]);
        $config = $statement->fetch();

        return $config === false ? null : $config;
    }

    /** @return array{0: bool, 1: string} */
    public static function save(int $clientId, string $domain, int $actorUserId): array
    {
        $domain = self::normalizeDomain($domain) ?? '';
        if ($domain === '') {
            return [false, 'Enter a valid domain name without a scheme, path or port.'];
        }

        $moduleStatement = db()->prepare(
            "SELECT 1
             FROM system_modules m
             INNER JOIN system_client_modules cm ON cm.module_id = m.id
             WHERE m.module_key = 'client_domain_embed'
               AND m.status = 'active' AND cm.status = 'active'
               AND cm.client_id = :client_id
             LIMIT 1"
        );
        $moduleStatement->execute(['client_id' => $clientId]);
        if ($moduleStatement->fetchColumn() === false) {
            return [false, 'The customer-domain module is not active for this client.'];
        }

        $token = bin2hex(random_bytes(32));
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare(
                'INSERT INTO system_client_domain_embeds (client_id, customer_domain, embed_token, created_by)
                 VALUES (:client_id, :customer_domain, :embed_token, :created_by)
                 ON DUPLICATE KEY UPDATE
                    customer_domain = VALUES(customer_domain),
                    embed_token = VALUES(embed_token),
                    created_by = VALUES(created_by),
                    updated_at = CURRENT_TIMESTAMP'
            );
            $statement->execute([
                'client_id' => $clientId,
                'customer_domain' => $domain,
                'embed_token' => $token,
                'created_by' => $actorUserId,
            ]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ((string) $exception->getCode() === '23000') {
                return [false, 'This domain is already configured for another client.'];
            }
            throw $exception;
        }

        AuditLogService::log($actorUserId, $clientId, 'client_domain_embed.configured', 'system_client_domain_embeds', (string) $clientId, null, $domain);

        return [true, 'Customer domain saved. Download a new page to apply the change.'];
    }

    public static function downloadHtml(array $config): string
    {
        $companyName = htmlspecialchars((string) $config['company_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $embedUrl = 'https://crm.demoit.eu/embed?token=' . rawurlencode((string) $config['embed_token']);
        $loginUrl = 'https://crm.demoit.eu/login?client_code=' . rawurlencode((string) $config['client_code']);
        $safeEmbedUrl = htmlspecialchars($embedUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="referrer" content="no-referrer">
  <title>DemoIT CRM - ' . $companyName . '</title>
  <style>
    html, body { height: 100%; margin: 0; font-family: system-ui, sans-serif; }
    body { display: flex; flex-direction: column; color: #212529; }
    header { padding: .7rem 1rem; border-bottom: 1px solid #dee2e6; font-weight: 600; }
    header span { color: #6c757d; font-weight: 400; }
    iframe { width: 100%; flex: 1; min-height: 0; border: 0; }
    footer { padding: .45rem 1rem; border-top: 1px solid #dee2e6; font-size: .875rem; }
  </style>
</head>
<body>
  <header>DemoIT CRM <span>- ' . $companyName . '</span></header>
  <iframe title="DemoIT CRM - ' . $companyName . '" src="' . $safeEmbedUrl . '" referrerpolicy="no-referrer"></iframe>
  <footer>If sign-in does not work in the embedded window, <a href="' . $safeLoginUrl . '" target="_blank" rel="noopener noreferrer">open DemoIT CRM in a new tab</a>.</footer>
</body>
</html>
';
    }

    public static function applyFramePolicy(?string $domain): void
    {
        if ($domain === null) {
            return;
        }
        $normalized = self::normalizeDomain($domain);
        if ($normalized === null) {
            unset($_SESSION['client_embed_domain'], $_SESSION['client_embed_token']);
            return;
        }

        header("Content-Security-Policy: frame-ancestors 'self' https://{$normalized}");
        header('Referrer-Policy: no-referrer');
    }
}
