<?php $scheduleSection = 'templates'; require __DIR__ . '/nav.php'; ?>

<h1 class="h4 mb-3">Shift settings</h1>
<?php if (!empty($message)): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="row g-2 align-items-end mb-4">
    <?= Csrf::field() ?>
    <div class="col-md-2">
        <label class="form-label" for="template_type">Type</label>
        <select class="form-select" id="template_type" name="template_type">
            <option value="shift">Shift</option>
            <option value="exception">Exception</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label" for="code">Code</label>
        <input class="form-control" type="text" id="code" name="code" maxlength="20" required>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" type="text" id="name" name="name" maxlength="100">
    </div>
    <div class="col-md-2">
        <label class="form-label" for="start_time">Start time</label>
        <input class="form-control" type="time" id="start_time" name="start_time" value="08:00" required>
    </div>
    <div class="col-md-2">
        <label class="form-label" for="duration_hours">Duration (hours)</label>
        <input class="form-control" type="number" id="duration_hours" name="duration_hours" min="0.25" max="24" step="0.25" value="8" required>
    </div>
    <div class="col-auto"><button class="btn btn-primary" type="submit">Add template</button></div>
</form>

<?php if (empty($templates)): ?>
    <p class="text-muted">No shift templates available.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Type</th><th>Code</th><th>Name</th><th>Starts</th><th>Duration</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($templates as $template): ?>
                    <tr>
                        <td><?= e(ucfirst($template['template_type'])) ?></td>
                        <td colspan="4">
                            <form id="schedule-template-<?= (int) $template['id'] ?>" method="post" class="row g-2">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $template['id'] ?>">
                                <input type="hidden" name="template_type" value="<?= e($template['template_type']) ?>">
                                <div class="col-3"><input class="form-control form-control-sm" name="code" maxlength="20" required value="<?= e($template['code']) ?>" aria-label="Code"></div>
                                <div class="col-4"><input class="form-control form-control-sm" name="name" maxlength="100" value="<?= e($template['name']) ?>" aria-label="Name"></div>
                                <div class="col-2"><input class="form-control form-control-sm" type="time" name="start_time" required value="<?= e(substr($template['start_time'], 0, 5)) ?>" aria-label="Start time"></div>
                                <div class="col-2"><input class="form-control form-control-sm" type="number" name="duration_hours" min="0.25" max="24" step="0.25" required value="<?= e(number_format((int) $template['duration_minutes'] / 60, 2, '.', '')) ?>" aria-label="Duration in hours"></div>
                            </form>
                        </td>
                        <td><?= e(ucfirst($template['status'])) ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-outline-primary btn-sm" type="submit" form="schedule-template-<?= (int) $template['id'] ?>">Save</button>
                            <form method="post" class="d-inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int) $template['id'] ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit"><?= $template['status'] === 'active' ? 'Hide' : 'Activate' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>