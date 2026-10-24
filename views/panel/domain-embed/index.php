<h1 class="h4 mb-3"><?= e(t('embed.setup_title', 'Display CRM on a customer domain')) ?></h1>
<p class="text-muted"><?= e(t('embed.setup_help', 'This module generates a static HTML file that displays this client workspace in an iframe. The application and data remain on the DemoIT CRM server.')) ?></p>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="post" action="/panel/domain-embed" novalidate>
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label" for="customer_domain"><?= e(t('embed.domain_label', 'Customer domain')) ?></label>
                <input class="form-control" type="text" id="customer_domain" name="customer_domain"
                       value="<?= e($domain ?? '') ?>" placeholder="portal.example.com" required>
                <div class="form-text"><?= e(t('embed.domain_hint', 'Enter the domain without https://, a path or port. The customer website must use HTTPS.')) ?></div>
            </div>
            <button class="btn btn-primary" type="submit"><?= e(t('embed.save_domain', 'Save domain')) ?></button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h6"><?= e(t('embed.download_page', 'Download HTML page')) ?></h2>
        <p class="text-muted"><?= e(t('embed.download_hint', 'Download the file and place it as index.html in the customer website root or a suitable directory. Download a new file after changing the domain or token.')) ?></p>
        <?php if (!empty($config)): ?>
            <p><strong><?= e(t('embed.domain_label', 'Customer domain')) ?>:</strong> <?= e($config['customer_domain']) ?></p>
            <a class="btn btn-outline-primary" href="/panel/domain-embed/download"><?= e(t('embed.download_page', 'Download HTML page')) ?></a>
        <?php else: ?>
            <p class="mb-0"><?= e(t('embed.not_configured', 'No domain has been configured yet.')) ?></p>
        <?php endif; ?>
    </div>
</div>
