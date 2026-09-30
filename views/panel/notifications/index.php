<h1 class="h4 mb-3">Notifications</h1>

<?php if (empty($notifications)): ?>
    <p class="text-muted">No notifications yet.</p>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($notifications as $notification): ?>
            <div class="list-group-item d-flex justify-content-between align-items-start <?= (int) $notification['is_read'] === 0 ? 'bg-light' : '' ?>">
                <div>
                    <div class="fw-semibold"><?= e($notification['title']) ?></div>
                    <div><?= e($notification['message']) ?></div>
                    <small class="text-muted"><?= e($notification['created_at']) ?></small>
                </div>
                <?php if ($notification['user_id'] !== null && (int) $notification['is_read'] === 0): ?>
                    <form method="post" action="/panel/notifications/read">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $notification['id'] ?>">
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Mark read</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
