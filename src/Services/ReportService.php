<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

/** Simple aggregate statistics (doc 01 §4-5 "Raportid ja statistika"). */
class ReportService
{
    public static function systemStats(): array
    {
        $pdo = db();

        return [
            'active_clients' => (int) $pdo->query(
                "SELECT COUNT(*) FROM system_clients WHERE status = 'active' AND client_code != '13666'"
            )->fetchColumn(),
            'active_modules' => (int) $pdo->query("SELECT COUNT(*) FROM system_modules WHERE status = 'active'")->fetchColumn(),
            'active_users' => (int) $pdo->query("SELECT COUNT(*) FROM system_users WHERE status = 'active'")->fetchColumn(),
            'logins_today' => (int) $pdo->query(
                "SELECT COUNT(*) FROM system_audit_logs WHERE action = 'auth.login_success' AND DATE(created_at) = CURDATE()"
            )->fetchColumn(),
            'open_support_tickets' => (int) $pdo->query(
                "SELECT COUNT(*) FROM system_support_tickets WHERE status != 'closed'"
            )->fetchColumn(),
        ];
    }

    public static function clientStats(int $clientId): array
    {
        $pdo = db();

        $usersStmt = $pdo->prepare("SELECT COUNT(*) FROM system_users WHERE client_id = :client_id AND status = 'active'");
        $usersStmt->execute(['client_id' => $clientId]);

        $modulesStmt = $pdo->prepare("SELECT COUNT(*) FROM system_client_modules WHERE client_id = :client_id AND status = 'active'");
        $modulesStmt->execute(['client_id' => $clientId]);

        $orgUnitsStmt = $pdo->prepare("SELECT COUNT(*) FROM system_org_units WHERE client_id = :client_id AND status = 'active'");
        $orgUnitsStmt->execute(['client_id' => $clientId]);

        $ticketsStmt = $pdo->prepare("SELECT COUNT(*) FROM system_support_tickets WHERE client_id = :client_id AND status != 'closed'");
        $ticketsStmt->execute(['client_id' => $clientId]);

        return [
            'active_users' => (int) $usersStmt->fetchColumn(),
            'active_modules' => (int) $modulesStmt->fetchColumn(),
            'org_units' => (int) $orgUnitsStmt->fetchColumn(),
            'open_support_tickets' => (int) $ticketsStmt->fetchColumn(),
        ];
    }
}
