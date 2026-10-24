<?php $employmentSection = 'contracts'; require __DIR__ . '/nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Employment contracts</h1>
    <?php if ($canManage): ?>
        <a class="btn btn-primary btn-sm" href="/panel/employment/contracts/create">Add contract</a>
    <?php endif; ?>
</div>

<?php if (!empty($message)): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="get" action="/panel/employment/contracts" class="row g-2 align-items-end mb-3" role="search">
    <div class="col-sm-6 col-lg-4">
        <label class="form-label" for="contract_search"><?= e(t('employees.search_name', 'Search by name')) ?></label>
        <input class="form-control" type="search" id="contract_search" name="search" maxlength="191" value="<?= e($search) ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" type="submit"><?= e(t('employees.search', 'Search')) ?></button>
        <?php if ($search !== ''): ?>
            <a class="btn btn-outline-secondary" href="/panel/employment/contracts"><?= e(t('employees.clear_search', 'Clear search')) ?></a>
        <?php endif; ?>
    </div>
</form>

<?php if ($canManage): ?>
    <details class="mb-3">
        <summary><?= e(t('employment.list_settings', 'Contract list settings')) ?></summary>
        <form method="post" action="/panel/employment/contracts/list-settings" class="d-flex flex-wrap align-items-end gap-2 mt-2">
            <?= Csrf::field() ?>
            <div>
                <label class="form-label" for="contract_page_size"><?= e(t('employment.per_page', 'Contracts per page')) ?></label>
                <input class="form-control" type="number" id="contract_page_size" name="page_size"
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
<?php if (empty($contracts)): ?>
    <p class="text-muted"><?= e(t('employment.no_results', 'No contracts found.')) ?></p>
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
                            <td class="text-nowrap">
                                <?php if ($contract['archived_at'] === null): ?>
                                    <a class="btn btn-outline-secondary btn-sm" href="/panel/employment/contracts/edit?id=<?= (int) $contract['id'] ?>">Edit</a>
                                <?php endif; ?>

                                <?php if ($contract['contract_type'] === 'temporary' && empty($contract['has_schedule_entries'])): ?>
                                    <form method="post" action="/panel/employment/contracts/archive" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $contract['id'] ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit"><?= $contract['archived_at'] === null ? 'Archive' : 'Restore' ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
$paginationUrl = '/panel/employment/contracts';
$paginationLabel = t('employment.pagination', 'Contract list pages');
require __DIR__ . '/../pagination.php';
?>