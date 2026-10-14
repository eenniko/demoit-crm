<h1 class="h4 mb-3">Notifications</h1>

<nav class="nav nav-tabs mb-3">
    <a class="nav-link <?= !$archive ? 'active' : '' ?>" href="/panel/notifications">Last 30 days</a>
    <a class="nav-link <?= $archive ? 'active' : '' ?>" href="/panel/notifications?archive=1">Archive</a>
</nav>
<p class="text-muted"><?= $archive ? 'Notifications older than 30 days.' : 'Notifications from the last 30 days.' ?></p>
<?php if (empty($notifications)): ?>
    <p class="text-muted">No notifications yet.</p>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($notifications as $notification): ?>
            <div class="list-group-item d-flex justify-content-between align-items-start <?= (int) $notification['is_read'] === 0 ? 'bg-light' : '' ?>">
                <div>
                    <div class="fw-semibold" data-no-translate><?= e($notification['title']) ?></div>
                    <div data-no-translate><?= e($notification['message']) ?></div>
                    <small class="text-muted"><?= e($notification['created_at']) ?></small>
                </div>
                <?php if ((int) $notification['is_read'] === 0): ?>
                    <form method="post" action="/panel/notifications/read">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $notification['id'] ?>">
                        <input type="hidden" name="archive" value="<?= $archive ? 1 : 0 ?>">
                        <input type="hidden" name="page" value="<?= (int) $page ?>">
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Mark read</button>
                    </form>
                <?php endif; ?>
                <?php if ($page > 1 || $hasNextPage): ?>
                    <nav class="d-flex gap-2 mt-3">
                        <?php if ($page > 1): ?>
                            <a class="btn btn-outline-secondary" href="/panel/notifications?archive=<?= $archive ? 1 : 0 ?>&amp;page=<?= $page - 1 ?>">Previous</a>
                        <?php endif; ?>
                        <?php if ($hasNextPage): ?>
                            <a class="btn btn-outline-secondary" href="/panel/notifications?archive=<?= $archive ? 1 : 0 ?>&amp;page=<?= $page + 1 ?>">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
