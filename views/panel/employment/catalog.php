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
        <?php if ($catalog === 'workloads'): ?>
            <div class="col-md-3">
                <label class="form-label" for="new_workload_percent">Workload (%)</label>
                <input class="form-control" type="number" id="new_workload_percent" name="workload_percent" min="0.01" max="100" step="0.01" required>
            </div>
        <?php endif; ?>
        <div class="col-auto"><button class="btn btn-primary" type="submit">Add</button></div>
    </form>
<?php endif; ?>

<?php if (empty($entries)): ?>
    <p class="text-muted">No entries yet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th><?= e($catalogLabel) ?></th><?php if ($catalog === 'workloads'): ?><th>Percentage</th><?php endif; ?><th>Status</th><?php if ($canManage): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td>
                            <?php if ($canManage): ?>
                                <form id="employment-catalog-<?= (int) $entry['id'] ?>" method="post" class="d-flex gap-2">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
                                    <input class="form-control form-control-sm" type="text" name="name" maxlength="191" required value="<?= e($entry['name']) ?>" aria-label="<?= e($catalogLabel) ?> name">
                                </form>
                                <?php if ($catalog !== 'workloads'): ?>
                                    <button class="btn btn-outline-primary btn-sm mt-2" type="submit" form="employment-catalog-<?= (int) $entry['id'] ?>">Save</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <?= e($entry['name']) ?>
                            <?php endif; ?>
                        </td>
                        <?php if ($catalog === 'workloads'): ?>
                            <td>
                                <?php if ($canManage): ?>
                                    <input class="form-control form-control-sm" type="number" name="workload_percent" min="0.01" max="100" step="0.01" required value="<?= e($entry['workload_percent']) ?>" aria-label="Workload percentage" form="employment-catalog-<?= (int) $entry['id'] ?>">
                                    <button class="btn btn-outline-primary btn-sm mt-2" type="submit" form="employment-catalog-<?= (int) $entry['id'] ?>">Save</button>
                                <?php else: ?>
                                    <?= e($entry['workload_percent']) ?>%
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
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