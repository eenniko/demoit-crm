<h1 class="h4 mb-3">Notifications</h1>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="get" action="/admin/notifications" class="row g-2 mb-3">
    <div class="col-auto">
        <select class="form-select form-select-sm" name="client_id" onchange="this.form.submit()">
            <?php foreach ($clients as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $client['id'] ? 'selected' : '' ?>>
                    <?= e($c['company_name']) ?> (<?= e($c['client_code']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<h2 class="h6">Send notification to <?= e($client['company_name']) ?></h2>
<form method="post" action="/admin/notifications?client_id=<?= (int) $client['id'] ?>" class="row g-3 mb-4" novalidate>
    <?= Csrf::field() ?>

    <div class="col-md-6">
        <label class="form-label" for="user_id">Recipient</label>
        <select class="form-select" id="user_id" name="user_id">
            <option value="">All users of this client (broadcast)</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= (int) $user['id'] ?>"><?= e($user['username']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="title">Title</label>
        <input class="form-control" type="text" id="title" name="title" required>
    </div>
    <div class="col-12">
        <label class="form-label" for="message">Message</label>
        <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Send</button>
    </div>
</form>

<h2 class="h6">Recently sent</h2>
<?php if (empty($sent)): ?>
    <p class="text-muted">No notifications sent yet.</p>
<?php else: ?>
    <table class="table table-sm table-striped">
        <thead><tr><th>When</th><th>Title</th><th>Recipient</th></tr></thead>
        <tbody>
            <?php foreach ($sent as $notification): ?>
                <tr>
                    <td><?= e($notification['created_at']) ?></td>
                    <td><?= e($notification['title']) ?></td>
                    <td><?= $notification['user_id'] ? 'One user' : 'All users' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
