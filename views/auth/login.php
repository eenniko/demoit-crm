<div class="container">
    <div class="card auth-card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-1">DemoIT CRM</h1>
            <?php if (!empty($companyName)): ?>
                <p class="text-muted mb-3"><?= e(t('embed.company_workspace', 'Client workspace')) ?> - <?= e($companyName) ?></p>
            <?php else: ?>
                <p class="text-muted mb-3">Client login</p>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e($formAction ?? '/login') ?>" novalidate>
                <?= Csrf::field() ?>
                <?php if (!empty($pinnedClientCode)): ?>
                    <input type="hidden" name="client_code" value="<?= e($pinnedClientCode) ?>">
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label" for="client_code">Client code</label>
                        <input class="form-control" type="text" id="client_code" name="client_code" required
                               value="<?= e($old['client_code'] ?? '') ?>">
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input class="form-control" type="text" id="username" name="username" required
                           value="<?= e($old['username'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Log in</button>
            </form>
            <?php if (!empty($embedFallbackUrl)): ?>
                <p class="small text-muted mt-3 mb-0">
                    <?= e(t('embed.signin_fallback', 'If sign-in is blocked in this embedded window, open the CRM in a separate tab.')) ?>
                    <a href="<?= e($embedFallbackUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('embed.open_new_tab', 'Open in a new tab')) ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
