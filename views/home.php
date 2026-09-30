<div class="container">
    <?php if (ClientContext::isLoggedIn()): ?>
        <h1 class="h3">Welcome, <?= e(ClientContext::displayName()) ?></h1>
        <p class="text-muted">Client: <?= e(ClientContext::clientCode()) ?></p>
        <?php if (RoleService::hasAnyRole(ClientContext::userId(), ClientContext::clientId(), ['system_admin', 'developer', 'client_manager', 'client_support'])): ?>
            <a class="btn btn-primary" href="/admin">Open admin panel</a>
        <?php else: ?>
            <a class="btn btn-primary" href="/panel">Open C-panel</a>
        <?php endif; ?>
    <?php else: ?>
        <div class="p-5 mb-4 bg-light rounded-3">
            <h1 class="display-6">DemoIT CRM</h1>
            <p class="col-md-8 fs-5">
                A modular business management system. Clients activate only the modules they need,
                and active modules share data and workflows.
            </p>
            <a class="btn btn-primary" href="/login">Client login</a>
        </div>
    <?php endif; ?>
</div>
