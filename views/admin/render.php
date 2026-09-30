<?php

declare(strict_types=1);

/** Same header/footer shell as the public site, plus a left menu + right workspace (doc 01 §3-4). */
function render_admin_page(string $title, string $contentView, array $vars = [], string $activeMenu = ''): void
{
    extract($vars, EXTR_SKIP);

    ob_start();
    require __DIR__ . '/' . $contentView;
    $workspaceContent = ob_get_clean();

    header('Content-Type: text/html; charset=utf-8');

    require __DIR__ . '/../layout/header.php';
    require __DIR__ . '/shell.php';
    require __DIR__ . '/../layout/footer.php';
}
