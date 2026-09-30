<div class="container-fluid flex-grow-1">
    <div class="row">
        <nav class="col-12 col-md-3 col-lg-2 border-end py-3">
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : 'link-dark' ?>" href="/panel">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'client' ? 'active' : 'link-dark' ?>" href="/panel/client">Client management</a>
                </li>
                <?php if (in_array('employees', $activeModuleKeys ?? [], true)): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeMenu === 'users' ? 'active' : 'link-dark' ?>" href="/panel/users">Employees &amp; permissions</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'modules' ? 'active' : 'link-dark' ?>" href="/panel/modules">Active modules</a>
                </li>
                <?php if (in_array('patients', $activeModuleKeys ?? [], true)): ?>
                    <li class="nav-item">
                        <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0">Modules</span>
                        <a class="nav-link ps-4 <?= $activeMenu === 'patients' ? 'active' : 'link-dark' ?>" href="/panel/patients">Patients</a>
                    </li>
                <?php endif; ?>
                <?php if (in_array('organisation', $activeModuleKeys ?? [], true)): ?>
                    <li class="nav-item">
                        <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0">Modules</span>
                        <a class="nav-link ps-4 <?= $activeMenu === 'org' ? 'active' : 'link-dark' ?>" href="/panel/org">Organisation</a>
                        <a class="nav-link ps-4 <?= $activeMenu === 'substitutes' ? 'active' : 'link-dark' ?>" href="/panel/substitutes">Substitutes</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'reports' ? 'active' : 'link-dark' ?>" href="/panel/reports">Reports &amp; statistics</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link link-dark disabled">Settings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'support' ? 'active' : 'link-dark' ?>" href="/panel/support">Support</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'notifications' ? 'active' : 'link-dark' ?>" href="/panel/notifications">Notifications</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'account' ? 'active' : 'link-dark' ?>" href="/panel/account">My account</a>
                </li>
            </ul>
        </nav>
        <section class="col-12 col-md-9 col-lg-10 py-3">
            <?= $workspaceContent ?>
        </section>
    </div>
</div>
