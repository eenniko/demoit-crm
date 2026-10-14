<?php $selfService = RoleService::isSelfServiceUser(ClientContext::userId(), ClientContext::clientId()); ?>
<?php $schedulePrintOnly = $selfService && SubstituteService::activeScheduleManagers(ClientContext::clientId(), ClientContext::userId()) === []; ?>
<div class="container-fluid flex-grow-1">
    <div class="row">
        <nav class="col-12 col-md-3 col-lg-2 border-end py-3">
            <ul class="nav nav-pills flex-column gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : 'link-dark' ?>" href="/panel"><?= e(t('panel.dashboard', 'Dashboard')) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'client' ? 'active' : 'link-dark' ?>" href="/panel/client"><?= e(t('panel.client_management', 'Client management')) ?></a>
                </li>
                <li class="nav-item">
                    <span class="nav-link disabled text-uppercase small text-muted mb-0 pb-0"><?= e(t('panel.modules', 'Modules')) ?></span>
                    <?php if (!$selfService): ?>
                        <a class="nav-link ps-4 <?= $activeMenu === 'modules' ? 'active' : 'link-dark' ?>" href="/panel/modules"><?= e(t('panel.active_modules', 'Active modules')) ?></a>
                    <?php endif; ?>
                    <?php if (in_array('employees', $activeModuleKeys ?? [], true)): ?>
                        <a class="nav-link ps-4 <?= $activeMenu === 'users' ? 'active' : 'link-dark' ?>" href="/panel/users"><?= e(t('panel.employees_permissions', 'Employees & permissions')) ?></a>
                    <?php endif; ?>
                    <?php if (in_array('patients', $activeModuleKeys ?? [], true)): ?>
                        <a class="nav-link ps-4 <?= $activeMenu === 'patients' ? 'active' : 'link-dark' ?>" href="/panel/patients"><?= e(t('panel.patients', 'Patients')) ?></a>
                    <?php endif; ?>
                    <?php if (!$selfService && in_array('property', $activeModuleKeys ?? [], true)): ?>
                        <a class="nav-link ps-4 <?= $activeMenu === 'property' ? 'active' : 'link-dark' ?>" href="/panel/property"><?= e(t('panel.property_structure', 'Property structure')) ?></a>
                    <?php endif; ?>
                    <?php if (in_array('employment', $activeModuleKeys ?? [], true)): ?>
                        <a class="nav-link ps-4 <?= $activeMenu === 'employment' ? 'active' : 'link-dark' ?>" href="/panel/employment/contracts"><?= e(t('panel.employment', 'Employment')) ?></a>
                    <?php endif; ?>
                    <?php if (in_array('schedule', $activeModuleKeys ?? [], true)): ?>
                        <a class="nav-link ps-4 <?= $activeMenu === 'schedule' ? 'active' : 'link-dark' ?>" href="<?= $schedulePrintOnly ? '/panel/schedule/print' : '/panel/schedule' ?>"><?= e(t('panel.work_schedule', 'Work schedule')) ?></a>
                    <?php endif; ?>
                    <?php if (in_array('organisation', $activeModuleKeys ?? [], true)): ?>
                        <a class="nav-link ps-4 <?= $activeMenu === 'org' ? 'active' : 'link-dark' ?>" href="/panel/org"><?= e(t('panel.organisation', 'Organisation')) ?></a>
                        <a class="nav-link ps-4 <?= $activeMenu === 'substitutes' ? 'active' : 'link-dark' ?>" href="/panel/substitutes"><?= e(t('panel.substitutes', 'Substitutes')) ?></a>
                    <?php endif; ?>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'reports' ? 'active' : 'link-dark' ?>" href="/panel/reports"><?= e(t('panel.reports_statistics', 'Reports & statistics')) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link link-dark disabled"><?= e(t('panel.settings', 'Settings')) ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex justify-content-between align-items-center <?= $activeMenu === 'support' ? 'active' : 'link-dark' ?>" href="/panel/support">
                        <span><?= e(t('panel.support', 'Support')) ?></span>
                        <?php if (($unreadSupportCount ?? 0) > 0): ?>
                            <span class="badge rounded-pill text-bg-danger" title="Unread active support tickets"><span data-no-translate><?= (int) $unreadSupportCount ?></span></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex justify-content-between align-items-center <?= $activeMenu === 'notifications' ? 'active' : 'link-dark' ?>" href="/panel/notifications">
                        <span><?= e(t('panel.notifications', 'Notifications')) ?></span>
                        <?php if (($unreadNotificationCount ?? 0) > 0): ?>
                            <span class="badge rounded-pill text-bg-danger" title="Unread notifications from the last 24 hours"><span data-no-translate><?= (int) $unreadNotificationCount ?></span></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeMenu === 'account' ? 'active' : 'link-dark' ?>" href="/panel/account"><?= e(t('panel.my_account', 'My account')) ?></a>
                </li>
            </ul>
        </nav>
        <section class="col-12 col-md-9 col-lg-10 py-3">
            <?= $workspaceContent ?>
        </section>
    </div>
</div>
