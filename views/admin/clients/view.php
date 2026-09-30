<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= e($client['company_name']) ?> <small class="text-muted">(<?= e($client['client_code']) ?>)</small></h1>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/clients">Back to list</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <h2 class="h6">Client details</h2>
        <table class="table table-sm">
            <tr><th class="w-25">Status</th><td><?= e($client['status']) ?></td></tr>
            <tr><th>Registry code</th><td><?= e($client['registry_code']) ?></td></tr>
            <tr><th>Address</th><td><?= e($client['address']) ?></td></tr>
            <tr><th>Phone</th><td><?= e($client['phone']) ?></td></tr>
            <tr><th>E-mail</th><td><?= e($client['email']) ?></td></tr>
        </table>

        <h2 class="h6 mt-4">Users</h2>

        <?php if (!empty($tempPassword)): ?>
            <div class="alert alert-success">
                Password reset. New temporary password (shown only once): <code><?= e($tempPassword) ?></code>
            </div>
        <?php endif; ?>

        <?php if (empty($users)): ?>
            <p class="text-muted">No users yet.</p>
        <?php else: ?>
            <table class="table table-sm table-striped">
                <thead><tr><th>Username</th><th>Full name</th><th>Roles</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= e($user['username']) ?></td>
                            <td><?= e($user['full_name']) ?></td>
                            <td><?= e($user['roles']) ?></td>
                            <td><?= e($user['status']) ?></td>
                            <td class="text-end">
                                <form method="post" action="/admin/clients/users/reset-password" class="d-inline"
                                      onsubmit="return confirm('Reset password for this user?');">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <button class="btn btn-outline-secondary btn-sm" type="submit">Reset password</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="col-lg-6">
        <h2 class="h6">Modules</h2>
        <table class="table table-sm">
            <thead><tr><th>Module</th><th>Catalogue</th><th>Activated for this client</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($modules as $module): ?>
                    <tr>
                        <td><?= e($module['name']) ?></td>
                        <td>
                            <span class="badge <?= $module['catalogue_status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= e($module['catalogue_status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php $clientModuleActive = ($module['client_status'] ?? 'inactive') === 'active'; ?>
                            <span class="badge <?= $clientModuleActive ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= $clientModuleActive ? 'active' : 'inactive' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="post" action="/admin/clients/modules/toggle" class="d-inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
                                <input type="hidden" name="module_id" value="<?= (int) $module['id'] ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit"
                                        <?= $module['catalogue_status'] !== 'active' ? 'disabled' : '' ?>>
                                    <?= $clientModuleActive ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
