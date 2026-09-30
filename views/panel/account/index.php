<h1 class="h4 mb-3">My account</h1>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<h2 class="h6">Change password</h2>
<form method="post" action="/panel/account/password" class="row g-3" novalidate>
    <?= Csrf::field() ?>
    <div class="col-md-4">
        <label class="form-label" for="current_password">Current password</label>
        <input class="form-control" type="password" id="current_password" name="current_password" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="new_password">New password</label>
        <input class="form-control" type="password" id="new_password" name="new_password" minlength="8" required>
        <div class="form-text">Minimum 8 characters.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="new_password_confirm">Confirm new password</label>
        <input class="form-control" type="password" id="new_password_confirm" name="new_password_confirm" minlength="8" required>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Update password</button>
    </div>
</form>
