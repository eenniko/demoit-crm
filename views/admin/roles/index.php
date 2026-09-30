<h1 class="h4 mb-3">Roles</h1>
<p class="text-muted">Rename how roles are displayed across the system. Technical role keys stay unchanged.</p>

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
                <th>Key</th>
                <th>Scope</th>
                <th>Level</th>
                <th>Display name</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td><code><?= e($role['role_key']) ?></code></td>
                    <td><?= e($role['scope']) ?></td>
                    <td><?= e($role['org_level']) ?></td>
                    <td>
                        <form method="post" action="/admin/roles/update" class="d-flex gap-2">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                            <input class="form-control form-control-sm" type="text" name="name" value="<?= e($role['name']) ?>" required>
                            <button class="btn btn-outline-primary btn-sm text-nowrap" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
