<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Services/ModuleService.php';

/** Same header/footer shell as the public site, plus the client (C-panel) left menu + workspace. */
function render_panel_page(string $title, string $contentView, array $vars = [], string $activeMenu = ''): void
{
    extract($vars, EXTR_SKIP);

    $activeModuleKeys = ClientContext::isLoggedIn()
        ? array_column(ModuleService::listActiveForClient(ClientContext::clientId()), 'module_key')
        : [];

    ob_start();
    require __DIR__ . '/' . $contentView;
    $workspaceContent = ob_get_clean();

    header('Content-Type: text/html; charset=utf-8');

    require __DIR__ . '/../layout/header.php';
    require __DIR__ . '/shell.php';
    require __DIR__ . '/../layout/footer.php';
}
