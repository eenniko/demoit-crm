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

<form method="get" action="/panel/users" class="row g-2 align-items-end mb-3" role="search">
    <div class="col-sm-6 col-lg-4">
        <label class="form-label" for="employee_search"><?= e(t('employees.search_name', 'Search by name')) ?></label>
        <input class="form-control" type="search" id="employee_search" name="search" maxlength="191" value="<?= e($search) ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" type="submit"><?= e(t('employees.search', 'Search')) ?></button>
        <?php if ($search !== ''): ?>
            <a class="btn btn-outline-secondary" href="/panel/users"><?= e(t('employees.clear_search', 'Clear search')) ?></a>
        <?php endif; ?>
    </div>
</form>

<?php if ($canManage): ?>
    <details class="mb-3">
        <summary><?= e(t('employees.list_settings', 'Employee list settings')) ?></summary>
        <form method="post" action="/panel/users/list-settings" class="d-flex flex-wrap align-items-end gap-2 mt-2">
            <?= Csrf::field() ?>
            <div>
                <label class="form-label" for="employee_page_size"><?= e(t('employees.per_page', 'Employees per page')) ?></label>
                <input class="form-control" type="number" id="employee_page_size" name="page_size"
                       min="1" max="100" required value="<?= (int) $pagination['pageSize'] ?>">
            </div>
            <button class="btn btn-outline-primary" type="submit"><?= e(t('employees.save_settings', 'Save settings')) ?></button>
            <span class="text-muted small"><?= e(t('employees.shared_settings', 'Applies to all users of this client, including search results.')) ?></span>
        </form>
    </details>
<?php endif; ?>

<p class="text-muted">
    <?= e(t('employees.results', 'Results')) ?>: <span data-no-translate><?= (int) $pagination['total'] ?></span>
</p>
<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Employee code</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Access level</th>
                <th>Active contract</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="7" class="text-muted"><?= e(t('employees.no_results', 'No employees found.')) ?></td></tr>
            <?php endif; ?>
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
                        <?php if (!empty($user['active_contract_types'])): ?>
                            <span class="badge text-bg-success"><?= e($user['active_contract_types']) ?></span>
                        <?php else: ?>
                            <span class="text-body-secondary">No active contract</span>
                        <?php endif; ?>
                    </td>
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

<?php
$paginationUrl = '/panel/users';
$paginationLabel = t('employees.pagination', 'Employee list pages');
require __DIR__ . '/../pagination.php';
?>
