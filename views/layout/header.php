<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'DemoIT CRM') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<header class="app-header d-flex align-items-center justify-content-between px-3">
    <a class="d-flex align-items-center text-decoration-none text-dark" href="/">
        <strong class="fs-5">DemoIT CRM</strong>
    </a>
    <nav class="d-flex align-items-center gap-3">
        <?php if (ClientContext::isLoggedIn()): ?>
            <span class="text-muted">Signed in as <strong><?= e(ClientContext::username()) ?></strong></span>
            <a class="btn btn-outline-secondary btn-sm" href="/logout">Log out</a>
        <?php else: ?>
            <a class="btn btn-outline-primary btn-sm" href="/login">Client login</a>
        <?php endif; ?>
    </nav>
</header>
<main class="app-main">
