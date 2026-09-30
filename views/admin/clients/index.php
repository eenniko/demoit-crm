<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Client list</h1>
    <a class="btn btn-primary btn-sm" href="/admin/clients/create">Add client</a>
</div>

<?php if (empty($clients)): ?>
    <p class="text-muted">No clients yet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Client code</th>
                    <th>Company name</th>
                    <th>E-mail</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= e($client['client_code']) ?></td>
                        <td><?= e($client['company_name']) ?></td>
                        <td><?= e($client['email']) ?></td>
                        <td>
                            <span class="badge <?= $client['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= e($client['status']) ?>
                            </span>
                        </td>
                        <td><?= e($client['created_at']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-outline-secondary btn-sm" href="/admin/clients/view?id=<?= (int) $client['id'] ?>">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
