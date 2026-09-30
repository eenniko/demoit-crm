<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Employees &amp; permissions</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="/panel/account">Change my password</a>
        <?php if ($canManage): ?>
            <a class="btn btn-outline-primary btn-sm" href="/panel/users/import">Import legacy database</a>
            <a class="btn btn-primary btn-sm" href="/panel/users/create">Add employee</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Employee code</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Access level</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['username']) ?></td>
                    <td><?= e($user['full_name']) ?></td>
                    <td>
                        <div><?= e($user['email']) ?></div>
                        <small class="text-body-secondary"><?= e($user['phone']) ?></small>
                    </td>
                    <td><?= e($user['roles']) ?></td>
                    <td>
                        <span class="badge <?= $user['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                            <?= e($user['status']) ?>
                        </span>
                    </td>
                    <td class="text-end d-flex gap-1 justify-content-end">
                        <?php if ($canManage): ?>
                            <a class="btn btn-outline-primary btn-sm" href="/panel/users/edit?id=<?= (int) $user['id'] ?>">Edit</a>
                        <?php endif; ?>
                        <?php if ($canManage && (int) $user['id'] !== (int) $currentUserId): ?>
                            <form method="post" action="/panel/users/reset-password" class="d-inline"
                                  onsubmit="return confirm('Reset password for this user?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit">Reset password</button>
                            </form>
                            <form method="post" action="/panel/users/toggle" class="d-inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit">
                                    <?= $user['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

