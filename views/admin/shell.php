<div class="container-fluid flex-grow-1">
    <div class="row">
        <nav class="col-12 col-md-3 col-lg-2 border-end py-3">
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : 'link-dark' ?>" href="/admin"><?= e(t('admin.dashboard', 'Dashboard')) ?></a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0"><?= e(t('admin.client_solutions', 'Client solutions')) ?></span>
                    <a class="nav-link ps-4 <?= $activeMenu === 'clients' ? 'active' : 'link-dark' ?>" href="/admin/clients"><?= e(t('admin.client_list', 'Client list')) ?></a>
                    <a class="nav-link ps-4 <?= $activeMenu === 'clients_create' ? 'active' : 'link-dark' ?>" href="/admin/clients/create"><?= e(t('admin.add_client', 'Add client')) ?></a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0"><?= e(t('admin.modules', 'Modules')) ?></span>
                    <a class="nav-link ps-4 <?= $activeMenu === 'modules' ? 'active' : 'link-dark' ?>" href="/admin/modules"><?= e(t('admin.all_modules', 'All modules')) ?></a>
                    <a class="nav-link ps-4 <?= $activeMenu === 'modules_create' ? 'active' : 'link-dark' ?>" href="/admin/modules/create"><?= e(t('admin.add_module', 'Add module')) ?></a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0"><?= e(t('admin.dev_languages', 'Dev module: languages')) ?></span>
                    <a class="nav-link ps-4 <?= $activeMenu === 'languages' ? 'active' : 'link-dark' ?>" href="/admin/languages"><?= e(t('admin.languages', 'Languages')) ?></a>
                    <a class="nav-link ps-4 <?= $activeMenu === 'translations' ? 'active' : 'link-dark' ?>" href="/admin/translations"><?= e(t('admin.translations', 'Translations')) ?></a>
                    <a class="nav-link ps-4 <?= $activeMenu === 'translations_missing' ? 'active' : 'link-dark' ?>" href="/admin/translations/missing"><?= e(t('admin.missing_translations', 'Missing translations')) ?></a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0"><?= e(t('admin.users_permissions', 'Users & permissions')) ?></span>
                    <a class="nav-link ps-4 <?= $activeMenu === 'roles' ? 'active' : 'link-dark' ?>" href="/admin/roles"><?= e(t('admin.roles', 'Roles')) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'logs' ? 'active' : 'link-dark' ?>" href="/admin/logs"><?= e(t('admin.system_logs', 'System logs')) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'notifications' ? 'active' : 'link-dark' ?>" href="/admin/notifications"><?= e(t('admin.notifications', 'Notifications')) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'support' ? 'active' : 'link-dark' ?>" href="/admin/support"><?= e(t('admin.support', 'Support')) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'settings' ? 'active' : 'link-dark' ?>" href="/admin/settings"><?= e(t('admin.system_settings', 'System settings')) ?></a>
                </li>
            </ul>
        </nav>
        <section class="col-12 col-md-9 col-lg-10 py-3">
            <?= $workspaceContent ?>
        </section>
    </div>
</div>
