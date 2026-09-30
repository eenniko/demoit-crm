<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';
require_once __DIR__ . '/../Support/ClientContext.php';

/** Authenticates a user with client code + username + password (doc 01 §2, doc 04 §4). */
class AuthService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 300;

    /**
     * @return array{0: bool, 1: string}
     */
    public static function attemptLogin(string $clientCode, string $username, string $password): array
    {
        if (self::isRateLimited($clientCode, $username)) {
            return [false, 'Too many failed attempts. Please try again later.'];
        }

        $stmt = db()->prepare(
            'SELECT u.id, u.client_id, u.full_name, u.password_hash, u.status, u.must_change_password, c.client_code, c.status AS client_status
             FROM system_users u
             INNER JOIN system_clients c ON c.id = u.client_id
             WHERE c.client_code = :client_code AND u.username = :username
             LIMIT 1'
        );
        $stmt->execute(['client_code' => $clientCode, 'username' => $username]);
        $user = $stmt->fetch();

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            self::registerFailedAttempt($clientCode, $username);
            AuditLogService::log(null, null, 'auth.login_failed', 'system_users', $username);

            return [false, 'Invalid client code, username or password.'];
        }

        if ($user['status'] !== 'active' || $user['client_status'] !== 'active') {
            AuditLogService::log((int) $user['id'], (int) $user['client_id'], 'auth.login_blocked', 'system_users', (string) $user['id']);

            return [false, 'This account or client is not active.'];
        }

        self::clearAttempts($clientCode, $username);
        ClientContext::login((int) $user['id'], (int) $user['client_id'], $user['client_code'], $username, (string) ($user['full_name'] ?? ''));
        AuditLogService::log((int) $user['id'], (int) $user['client_id'], 'auth.login_success', 'system_users', (string) $user['id']);

        if ((int) $user['must_change_password'] === 1) {
            $_SESSION['must_change_password'] = true;
        }

        return [true, 'Login successful.'];
    }

    public static function logout(): void
    {
        AuditLogService::log(ClientContext::userId(), ClientContext::clientId(), 'auth.logout');
        ClientContext::logout();
    }

    private static function attemptKey(string $clientCode, string $username): string
    {
        return 'login_attempts_' . $clientCode . '_' . $username;
    }

    private static function isRateLimited(string $clientCode, string $username): bool
    {
        $key = self::attemptKey($clientCode, $username);
        $data = $_SESSION[$key] ?? null;

        if ($data === null) {
            return false;
        }

        if ($data['count'] >= self::MAX_ATTEMPTS && (time() - $data['last']) < self::LOCKOUT_SECONDS) {
            return true;
        }

        return false;
    }

    private static function registerFailedAttempt(string $clientCode, string $username): void
    {
        $key = self::attemptKey($clientCode, $username);
        $data = $_SESSION[$key] ?? ['count' => 0, 'last' => 0];
        $data['count'] += 1;
        $data['last'] = time();
        $_SESSION[$key] = $data;
    }

    private static function clearAttempts(string $clientCode, string $username): void
    {
        unset($_SESSION[self::attemptKey($clientCode, $username)]);
    }
}
