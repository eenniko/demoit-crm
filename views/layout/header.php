<!DOCTYPE html>
<html lang="<?= e(LanguageService::currentCode()) ?>">
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
        <form method="post" action="/language" class="m-0">
            <?= Csrf::field() ?>
            <input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
            <select class="form-select form-select-sm" name="language_code" aria-label="<?= e(t('layout.language', 'Language')) ?>" onchange="this.form.submit()">
                <?php foreach (LanguageService::listActive() as $language): ?>
                    <option value="<?= e($language['language_code']) ?>" <?= $language['language_code'] === LanguageService::currentCode() ? 'selected' : '' ?>><?= e($language['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if (ClientContext::isLoggedIn()): ?>
            <span class="text-muted"><?= e(t('layout.signed_in_as', 'Signed in as')) ?> <strong><?= e(ClientContext::displayName()) ?></strong></span>
            <a class="btn btn-outline-secondary btn-sm" href="/logout"><?= e(t('layout.log_out', 'Log out')) ?></a>
        <?php else: ?>
            <a class="btn btn-outline-primary btn-sm" href="/login"><?= e(t('layout.client_login', 'Client login')) ?></a>
        <?php endif; ?>
    </nav>
</header>
<main class="app-main">
