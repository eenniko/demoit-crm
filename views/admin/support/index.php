<h1 class="h4 mb-3">Support tickets</h1>

<?php if (empty($tickets)): ?>
    <p class="text-muted">No open tickets.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Subject</th>
                    <th>Created by</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?= e($ticket['client_code']) ?></td>
                        <td><?= e($ticket['subject']) ?></td>
                        <td><?= e($ticket['created_by_username']) ?></td>
                        <td><span class="badge text-bg-secondary"><?= e($ticket['status']) ?></span></td>
                        <td><?= e($ticket['created_at']) ?></td>
                        <td class="text-end">
                            <form method="post" action="/admin/support/status" class="d-flex gap-1">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $ticket['id'] ?>">
                                <select class="form-select form-select-sm" name="status">
                                    <?php foreach (['open', 'in_progress', 'closed'] as $status): ?>
                                        <option value="<?= $status ?>" <?= $ticket['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-primary btn-sm" type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
