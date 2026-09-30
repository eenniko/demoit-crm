<?php

declare(strict_types=1);

/** Renders a view file wrapped in the shared header/footer layout (doc 01 §3). */
function render_page(string $title, string $contentView, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    header('Content-Type: text/html; charset=utf-8');

    require __DIR__ . '/layout/header.php';
    require __DIR__ . '/' . $contentView;
    require __DIR__ . '/layout/footer.php';
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
