<?php $employmentSection = 'contracts'; require __DIR__ . '/nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $contract === null ? 'Add contract' : 'Edit contract' ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="/panel/employment/contracts">Back</a>
</div>

<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php
$selected = static function (string $key, mixed $optionValue) use ($old, $contract): string {
    $defaultValue = $contract[$key] ?? ($key === 'contract_type' ? 'primary' : '');
    return (string) ($old[$key] ?? $defaultValue) === (string) $optionValue ? 'selected' : '';
};
$contractValue = static fn (string $key): mixed => $old[$key] ?? ($contract[$key] ?? '');
?>

<form method="post" class="row g-3" novalidate>
    <?= Csrf::field() ?>
    <?php if ($contract !== null): ?>
        <input type="hidden" name="id" value="<?= (int) $contract['id'] ?>">
        <div class="col-md-6">
            <label class="form-label">Employee</label>
            <div class="form-control-plaintext"><?= e($contract['full_name'] ?: $contract['username']) ?> (<?= e($contract['username']) ?>)</div>
        </div>
    <?php else: ?>
        <div class="col-md-6">
            <label class="form-label" for="employee_id">Employee</label>
            <select class="form-select" id="employee_id" name="employee_id" required>
                <option value="">Select employee</option>
                <?php foreach ($options['employees'] as $employee): ?>
                    <option value="<?= (int) $employee['id'] ?>" <?= $selected('employee_id', $employee['id']) ?>><?= e($employee['full_name'] ?: $employee['username']) ?> (<?= e($employee['username']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-md-6">
        <label class="form-label" for="contract_type">Contract type</label>
        <?php if ($contract === null): ?>
            <select class="form-select" id="contract_type" name="contract_type" required>
                <option value="primary" <?= $selected('contract_type', 'primary') ?>>Primary workplace</option>
                <option value="temporary" <?= $selected('contract_type', 'temporary') ?>>Temporary workplace</option>
            </select>
        <?php else: ?>
            <div class="form-control-plaintext"><?= $contract['contract_type'] === 'primary' ? 'Primary workplace' : 'Temporary workplace' ?></div>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="property_node_id">Property location</label>
        <select class="form-select" id="property_node_id" name="property_node_id" required>
            <option value="">Select location</option>
            <?php foreach ($options['properties'] as $property): ?>
                <option value="<?= (int) $property['id'] ?>" <?= $selected('property_node_id', $property['id']) ?>><?= e(str_repeat('— ', (int) $property['depth']) . $property['name']) ?> (<?= e(ucfirst($property['node_type'])) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="job_title_id">Job title</label>
        <select class="form-select" id="job_title_id" name="job_title_id" required>
            <option value="">Select job title</option>
            <?php foreach ($options['jobTitles'] as $jobTitle): ?>
                <option value="<?= (int) $jobTitle['id'] ?>" <?= $selected('job_title_id', $jobTitle['id']) ?>><?= e($jobTitle['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="department_id">Department</label>
        <select class="form-select" id="department_id" name="department_id">
            <option value="">No department</option>
            <?php foreach ($options['departments'] as $department): ?>
                <option value="<?= (int) $department['id'] ?>" <?= $selected('department_id', $department['id']) ?>><?= e($department['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="manager_user_id">Manager</label>
        <select class="form-select" id="manager_user_id" name="manager_user_id">
            <option value="">No manager</option>
            <?php foreach ($options['managers'] as $manager): ?>
                <option value="<?= (int) $manager['id'] ?>" <?= $selected('manager_user_id', $manager['id']) ?>><?= e($manager['full_name'] ?: $manager['username']) ?> (<?= e($manager['username']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="start_date">Start date</label>
        <input class="form-control" type="date" id="start_date" name="start_date" required value="<?= e($contractValue('start_date')) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="end_date">End date</label>
        <input class="form-control" type="date" id="end_date" name="end_date" value="<?= e($contractValue('end_date')) ?>">
        <div class="form-text">Leave blank for an open-ended contract.</div>
    </div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Save contract</button></div>
</form>