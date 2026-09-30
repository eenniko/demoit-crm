<div class="container-fluid flex-grow-1">
    <div class="row">
        <nav class="col-12 col-md-3 col-lg-2 border-end py-3">
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : 'link-dark' ?>" href="/admin">Dashboard</a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0">Client solutions</span>
                    <a class="nav-link ps-4 <?= $activeMenu === 'clients' ? 'active' : 'link-dark' ?>" href="/admin/clients">Client list</a>
                    <a class="nav-link ps-4 <?= $activeMenu === 'clients_create' ? 'active' : 'link-dark' ?>" href="/admin/clients/create">Add client</a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0">Modules</span>
                    <a class="nav-link ps-4 <?= $activeMenu === 'modules' ? 'active' : 'link-dark' ?>" href="/admin/modules">All modules</a>
                    <a class="nav-link ps-4 <?= $activeMenu === 'modules_create' ? 'active' : 'link-dark' ?>" href="/admin/modules/create">Add module</a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0">Dev module: languages</span>
                    <a class="nav-link ps-4 <?= $activeMenu === 'languages' ? 'active' : 'link-dark' ?>" href="/admin/languages">Languages</a>
                    <a class="nav-link ps-4 <?= $activeMenu === 'translations' ? 'active' : 'link-dark' ?>" href="/admin/translations">Translations</a>
                    <a class="nav-link ps-4 <?= $activeMenu === 'translations_missing' ? 'active' : 'link-dark' ?>" href="/admin/translations/missing">Missing translations</a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0">Users &amp; permissions</span>
                    <a class="nav-link ps-4 <?= $activeMenu === 'roles' ? 'active' : 'link-dark' ?>" href="/admin/roles">Roles</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'logs' ? 'active' : 'link-dark' ?>" href="/admin/logs">System logs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'notifications' ? 'active' : 'link-dark' ?>" href="/admin/notifications">Notifications</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'support' ? 'active' : 'link-dark' ?>" href="/admin/support">Support</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'settings' ? 'active' : 'link-dark' ?>" href="/admin/settings">System settings</a>
                </li>
            </ul>
        </nav>
        <section class="col-12 col-md-9 col-lg-10 py-3">
            <?= $workspaceContent ?>
        </section>
    </div>
</div>
