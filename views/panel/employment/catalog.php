<?php $employmentSection = $catalog; require __DIR__ . '/nav.php'; ?>

<h1 class="h4 mb-3"><?= e($catalogLabel) ?>s</h1>
<?php if (!empty($message)): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if ($canManage): ?>
    <form method="post" class="row g-2 align-items-end mb-4">
        <?= Csrf::field() ?>
        <div class="col-md-8">
            <label class="form-label" for="new_name">New <?= e(strtolower($catalogLabel)) ?></label>
            <input class="form-control" type="text" id="new_name" name="name" maxlength="191" required>
        </div>
        <div class="col-auto"><button class="btn btn-primary" type="submit">Add</button></div>
    </form>
<?php endif; ?>

<?php if (empty($entries)): ?>
    <p class="text-muted">No entries yet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th><?= e($catalogLabel) ?></th><th>Status</th><?php if ($canManage): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td>
                            <?php if ($canManage): ?>
                                <form method="post" class="d-flex gap-2">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
                                    <input class="form-control form-control-sm" type="text" name="name" maxlength="191" required value="<?= e($entry['name']) ?>" aria-label="<?= e($catalogLabel) ?> name">
                                    <button class="btn btn-outline-primary btn-sm" type="submit">Save</button>
                                </form>
                            <?php else: ?>
                                <?= e($entry['name']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= e(ucfirst($entry['status'])) ?></td>
                        <?php if ($canManage): ?>
                            <td>
                                <form method="post">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <button class="btn btn-outline-secondary btn-sm" type="submit"><?= $entry['status'] === 'active' ? 'Hide' : 'Activate' ?></button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>