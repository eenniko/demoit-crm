<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= e(t('admin.client_admin_edit_title', 'Edit client administrator')) ?> — <?= e($client['company_name']) ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/clients/view?id=<?= (int) $client['id'] ?>"><?= e(t('admin.client_admin_back', 'Back to client')) ?></a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/admin/clients/users/edit" class="row g-3" novalidate>
    <?= Csrf::field() ?>
    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">

    <div class="col-md-6">
        <label class="form-label" for="username"><?= e(t('admin.client_admin_username', 'Username')) ?></label>
        <input class="form-control" type="text" id="username" value="<?= e($user['username']) ?>" disabled>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="full_name"><?= e(t('admin.client_admin_name', 'Full name')) ?></label>
        <input class="form-control" type="text" id="full_name" name="full_name" required value="<?= e($user['full_name']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email"><?= e(t('admin.client_admin_email', 'E-mail')) ?></label>
        <input class="form-control" type="email" id="email" name="email" required value="<?= e($user['email']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone"><?= e(t('admin.client_admin_phone', 'Phone')) ?></label>
        <input class="form-control" type="tel" id="phone" name="phone" value="<?= e($user['phone']) ?>">
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit"><?= e(t('admin.client_admin_save', 'Save changes')) ?></button>
    </div>
</form>
