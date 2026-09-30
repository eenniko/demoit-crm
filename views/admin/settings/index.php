<h1 class="h4 mb-3">System settings</h1>

<div class="mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="/admin/roles">Manage role names</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<?php if (!empty($settings)): ?>
    <table class="table table-sm mb-4">
        <thead><tr><th>Key</th><th>Value</th></tr></thead>
        <tbody>
            <?php foreach ($settings as $setting): ?>
                <tr>
                    <td><code><?= e($setting['setting_key']) ?></code></td>
                    <td><?= e($setting['setting_value']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2 class="h6">Add / update a setting</h2>
<form method="post" action="/admin/settings" class="row g-3" novalidate>
    <?= Csrf::field() ?>
    <div class="col-md-4">
        <label class="form-label" for="setting_key">Key</label>
        <input class="form-control" type="text" id="setting_key" name="setting_key" required>
    </div>
    <div class="col-md-8">
        <label class="form-label" for="setting_value">Value</label>
        <input class="form-control" type="text" id="setting_value" name="setting_value">
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Save setting</button>
    </div>
</form>
