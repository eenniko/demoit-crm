<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">All modules</h1>
    <a class="btn btn-primary btn-sm" href="/admin/modules/create">Add module</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<?php if (empty($modules)): ?>
    <p class="text-muted">No modules yet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Name</th>
                    <th>Demo</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modules as $module): ?>
                    <tr>
                        <td><code><?= e($module['module_key']) ?></code></td>
                        <td><?= e($module['name']) ?></td>
                        <td><?= (int) $module['is_demo_available'] === 1 ? 'Yes' : 'No' ?></td>
                        <td>
                            <span class="badge <?= $module['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= e($module['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="post" action="/admin/modules/toggle" class="d-inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $module['id'] ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit">
                                    <?= $module['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
