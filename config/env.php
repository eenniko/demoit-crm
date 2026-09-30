<?php

declare(strict_types=1);

/**
 * Loads key=value pairs from a .env file into the environment, if not already set.
 */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

load_env(dirname(__DIR__) . '/.env');
