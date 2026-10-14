<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Support</h1>
    <?php if (!$systemWorkspace): ?>
        <a class="btn btn-primary btn-sm" href="/panel/support/create">New ticket</a>
    <?php endif; ?>
</div>
<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<?php foreach (['open' => 'Open tickets', 'closed' => 'Recently closed tickets'] as $segment => $heading): ?>
    <section class="mb-4">
        <h2 class="h5"><?= e($heading) ?></h2>
        <?php if ($segment === 'closed'): ?>
            <p class="text-muted small">The 10 most recently closed tickets are shown.</p>
        <?php endif; ?>
        <?php if (empty($lists[$segment])): ?>
            <p class="text-muted"><?= $segment === 'closed' ? 'No closed tickets.' : 'No open tickets.' ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr>
                        <?php if ($systemWorkspace): ?><th>Client</th><?php endif; ?>
                        <th>Subject</th><th>Created by</th><th>Status</th><th>Created</th>
                        <?php if ($segment === 'closed'): ?><th>Closed</th><?php endif; ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($lists[$segment] as $ticket): ?>
                        <tr class="<?= (int) $ticket['is_unread'] === 1 ? 'fw-bold' : '' ?>">
                            <?php if ($systemWorkspace): ?><td><?= e($ticket['client_code']) ?></td><?php endif; ?>
                            <td>
                                <a data-no-translate href="<?= e($basePath) ?>/view?id=<?= (int) $ticket['id'] ?>"><?= e($ticket['subject']) ?></a>
                                <?php if ((int) $ticket['is_unread'] === 1): ?>
                                    <span class="badge text-bg-primary ms-2"><?= (int) $ticket['has_unread_reply'] === 1 ? 'New reply' : 'Unread' ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-no-translate><?= e($ticket['creator_name']) ?></td>
                            <td><span class="badge text-bg-secondary"><?= e($ticket['status']) ?></span></td>
                            <td><?= e($ticket['created_at']) ?></td>
                            <?php if ($segment === 'closed'): ?><td><?= e($ticket['closed_at']) ?></td><?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
