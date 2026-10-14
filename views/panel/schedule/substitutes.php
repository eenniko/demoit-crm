<?php $scheduleSection = 'substitutes'; require __DIR__ . '/nav.php'; ?>
<h1 class="h4 mb-3">Schedule substitutes</h1>
<p>Substitutes can edit current and future months only while the assignment is active.</p>
<p>Account roles are unchanged. No employment contract is required for the substitute.</p>
<p>If the substitute is not listed, ask the client administrator to add and activate the account under Employees &amp; permissions.</p>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if (!empty($message)): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<form method="post" action="/panel/schedule/substitutes" class="row g-3 mb-4">
    <?= Csrf::field() ?>
    <?php if ($isAdmin): ?>
        <div class="col-md-6">
            <label class="form-label" for="original_user_id">Manager being substituted</label>
            <select class="form-select" id="original_user_id" name="original_user_id" required>
                <option value="">Select a user</option>
                <?php foreach ($users as $user): ?>
                    <?php if ($user['status'] !== 'active') { continue; } ?>
                    <option value="<?= (int) $user['id'] ?>" <?= (int) ($old['original_user_id'] ?? 0) === (int) $user['id'] ? 'selected' : '' ?>><?= e($user['full_name'] ?: $user['username']) ?> (<?= e($user['username']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-md-6">
        <label class="form-label" for="substitute_user_id">Substitute</label>
        <select class="form-select" id="substitute_user_id" name="substitute_user_id" required>
            <option value="">Select a user</option>
            <?php foreach ($users as $user): ?>
                <?php if ($user['status'] !== 'active' || (!$isAdmin && (int) $user['id'] === $currentUserId)) { continue; } ?>
                <option value="<?= (int) $user['id'] ?>" <?= (int) ($old['substitute_user_id'] ?? 0) === (int) $user['id'] ? 'selected' : '' ?>><?= e($user['full_name'] ?: $user['username']) ?> (<?= e($user['username']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="starts_at"><span>Starts at</span> (<?= e(date_default_timezone_get()) ?>)</label>
        <input class="form-control" type="datetime-local" id="starts_at" name="starts_at" value="<?= e(is_string($old['starts_at'] ?? null) ? $old['starts_at'] : '') ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="ends_at"><span>Ends at</span> (<?= e(date_default_timezone_get()) ?>)</label>
        <input class="form-control" type="datetime-local" id="ends_at" name="ends_at" value="<?= e(is_string($old['ends_at'] ?? null) ? $old['ends_at'] : '') ?>" required>
    </div>
    <div class="col-12">
        <label class="form-label" for="reason">Reason</label>
        <input class="form-control" id="reason" name="reason" value="<?= e(is_string($old['reason'] ?? null) ? $old['reason'] : '') ?>">
    </div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Assign substitute</button></div>
</form>
<div class="table-responsive">
    <table class="table table-striped">
        <thead><tr><th>Manager</th><th>Substitute</th><th>Period</th><th>Status</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($assignments as $assignment): ?>
                <?php
                $now = date('Y-m-d H:i:s');
                $state = $assignment['status'] !== 'approved' ? $assignment['status']
                    : ($assignment['ends_at'] <= $now ? 'ended' : ($assignment['starts_at'] > $now ? 'scheduled' : 'active'));
                ?>
                <tr>
                    <td><?= e($assignment['original_name'] ?: $assignment['original_username']) ?></td>
                    <td><?= e($assignment['substitute_name'] ?: $assignment['substitute_username']) ?></td>
                    <td><?= e($assignment['starts_at']) ?> &rarr; <?= e($assignment['ends_at']) ?></td>
                    <td><?= e($state) ?></td>
                    <td>
                        <?php if ($assignment['status'] === 'approved' && $assignment['ends_at'] > $now && ($isAdmin || (int) $assignment['original_user_id'] === $currentUserId)): ?>
                            <form method="post" action="/panel/schedule/substitutes">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="end">
                                <input type="hidden" name="id" value="<?= (int) $assignment['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm" type="submit">End substitute rights</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
