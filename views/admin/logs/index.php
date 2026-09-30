<h1 class="h4 mb-3">System logs</h1>

<form method="get" action="/admin/logs" class="row g-2 mb-3">
    <div class="col-auto">
        <input class="form-control form-control-sm" type="text" name="action" placeholder="Action contains…" value="<?= e($filters['action'] ?? '') ?>">
    </div>
    <div class="col-auto">
        <input class="form-control form-control-sm" type="text" name="client_code" placeholder="Client code" value="<?= e($filters['client_code'] ?? '') ?>">
    </div>
    <div class="col-auto">
        <input class="form-control form-control-sm" type="text" name="username" placeholder="Username contains…" value="<?= e($filters['username'] ?? '') ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-primary btn-sm" type="submit">Filter</button>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/logs">Reset</a>
    </div>
</form>

<?php if (empty($logs)): ?>
    <p class="text-muted">No log entries found.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-sm align-middle">
            <thead>
                <tr>
                    <th>When</th>
                    <th>User</th>
                    <th>Client</th>
                    <th>Action</th>
                    <th>Object</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-nowrap"><?= e($log['created_at']) ?></td>
                        <td><?= e($log['username'] ?? '—') ?></td>
                        <td><?= e($log['client_code'] ?? '—') ?></td>
                        <td><code><?= e($log['action']) ?></code></td>
                        <td><?= e($log['object_type']) ?> <?= e($log['object_id']) ?></td>
                        <td><?= e($log['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted small">Showing the latest <?= count($logs) ?> entries.</p>
<?php endif; ?>
