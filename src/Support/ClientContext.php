<?php

declare(strict_types=1);

/**
 * Wraps the current session as the trusted source of the active client + user context.
 * The client code must never be trusted from the URL or user input directly.
 */
class ClientContext
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            ]);
            session_start();
        }
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function clientId(): ?int
    {
        return isset($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;
    }

    public static function clientCode(): ?string
    {
        return $_SESSION['client_code'] ?? null;
    }

    public static function username(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    public static function login(int $userId, int $clientId, string $clientCode, string $username): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['client_id'] = $clientId;
        $_SESSION['client_code'] = $clientCode;
        $_SESSION['username'] = $username;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
