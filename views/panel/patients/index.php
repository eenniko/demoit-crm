<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Patients</h1>
    <a class="btn btn-primary btn-sm" href="/panel/patients/create">Add patient</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<?php if (empty($patients)): ?>
    <p class="text-muted">No patients yet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Full name</th>
                    <th>Personal code</th>
                    <th>Phone</th>
                    <th>E-mail</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?= e($patient['full_name']) ?></td>
                        <td><?= e($patient['personal_code']) ?></td>
                        <td><?= e($patient['phone']) ?></td>
                        <td><?= e($patient['email']) ?></td>
                        <td>
                            <span class="badge <?= $patient['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= e($patient['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="post" action="/panel/patients/toggle" class="d-inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $patient['id'] ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit">
                                    <?= $patient['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
