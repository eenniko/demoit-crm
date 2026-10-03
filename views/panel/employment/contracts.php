<?php $employmentSection = 'contracts'; require __DIR__ . '/nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Employment contracts</h1>
    <?php if ($canManage): ?>
        <a class="btn btn-primary btn-sm" href="/panel/employment/contracts/create">Add contract</a>
    <?php endif; ?>
</div>

<?php if (!empty($message)): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if (empty($contracts)): ?>
    <p class="text-muted">No contracts have been added.</p>
<?php else: ?>
    <?php $typeLabels = ['primary' => 'Primary', 'temporary' => 'Temporary']; ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Contract</th>
                    <th>Location</th>
                    <th>Job title / department</th>
                    <th>Workload</th>
                    <th>Manager</th>
                    <th>Period</th>
                    <th>Status</th>
                    <?php if ($canManage): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contracts as $contract): ?>
                    <tr>
                        <td><?= e($contract['full_name'] ?: $contract['username']) ?><br><small class="text-muted"><?= e($contract['username']) ?></small></td>
                        <td><?= e($typeLabels[$contract['contract_type']]) ?></td>
                        <td><?= e($contract['property_path']) ?></td>
                        <td><?= e($contract['job_title']) ?><br><small class="text-muted"><?= e($contract['department_name'] ?: 'No department') ?></small></td>
                        <td><?= e($contract['workload_name']) ?><br><small class="text-muted"><?= e(number_format((float) $contract['workload_percent'], 2)) ?>%</small></td>
                        <td><?= e($contract['manager_name'] ?: $contract['manager_username'] ?: '—') ?></td>
                        <td><?= e($contract['start_date']) ?> – <?= e($contract['end_date'] ?: 'Open-ended') ?></td>
                        <td><?= e(ucfirst($contract['period_status'])) ?></td>
                        <?php if ($canManage): ?>
                            <td><a class="btn btn-outline-secondary btn-sm" href="/panel/employment/contracts/edit?id=<?= (int) $contract['id'] ?>">Edit</a></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>