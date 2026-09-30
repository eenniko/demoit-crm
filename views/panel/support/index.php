<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Support</h1>
    <a class="btn btn-primary btn-sm" href="/panel/support/create">New ticket</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<?php if (empty($tickets)): ?>
    <p class="text-muted">No support tickets yet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Created by</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?= e($ticket['subject']) ?></td>
                        <td><?= e($ticket['created_by_username']) ?></td>
                        <td><span class="badge text-bg-secondary"><?= e($ticket['status']) ?></span></td>
                        <td><?= e($ticket['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
