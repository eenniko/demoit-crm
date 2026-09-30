<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Organisation</h1>
    <?php if ($canManage): ?>
        <a class="btn btn-primary btn-sm" href="/panel/org/create">Add unit</a>
    <?php endif; ?>
</div>

<?php if (empty($units)): ?>
    <p class="text-muted">No organisation units yet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Name</th>
                    <th>Parent unit</th>
                    <th>Manager</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($units as $unit): ?>
                    <tr>
                        <td><?= e($unit['org_level']) ?></td>
                        <td><?= e($unit['name']) ?></td>
                        <td><?= e($unit['parent_name']) ?></td>
                        <td><?= e($unit['manager_username']) ?></td>
                        <td>
                            <span class="badge <?= $unit['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= e($unit['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
