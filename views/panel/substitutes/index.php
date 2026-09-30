<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Temporary substitutes</h1>
    <a class="btn btn-primary btn-sm" href="/panel/substitutes/create">Request substitute</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<?php if (empty($substitutes)): ?>
    <p class="text-muted">No substitute requests yet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Original user</th>
                    <th>Substitute</th>
                    <th>Period</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <?php if ($canApprove): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($substitutes as $row): ?>
                    <tr>
                        <td><?= e($row['original_username']) ?></td>
                        <td><?= e($row['substitute_username']) ?></td>
                        <td><?= e($row['starts_at']) ?> &rarr; <?= e($row['ends_at']) ?></td>
                        <td><?= e($row['reason']) ?></td>
                        <td><span class="badge text-bg-secondary"><?= e($row['status']) ?></span></td>
                        <?php if ($canApprove): ?>
                            <td class="text-end">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="post" action="/panel/substitutes/approve" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button class="btn btn-success btn-sm" type="submit">Approve</button>
                                    </form>
                                    <form method="post" action="/panel/substitutes/reject" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Reject</button>
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
