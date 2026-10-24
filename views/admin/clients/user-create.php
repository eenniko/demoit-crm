<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= e(t('admin.client_admin_add', 'Add administrator')) ?> — <?= e($client['company_name']) ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/clients/view?id=<?= (int) $client['id'] ?>"><?= e(t('admin.client_admin_back', 'Back to client')) ?></a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/admin/clients/users/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>
    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">

    <div class="col-md-6">
        <label class="form-label" for="username"><?= e(t('admin.client_admin_username', 'Username')) ?></label>
        <input class="form-control" type="text" id="username" name="username" required value="<?= e($old['username'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="full_name"><?= e(t('admin.client_admin_name', 'Full name')) ?></label>
        <input class="form-control" type="text" id="full_name" name="full_name" required value="<?= e($old['full_name'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email"><?= e(t('admin.client_admin_email', 'E-mail')) ?></label>
        <input class="form-control" type="email" id="email" name="email" required value="<?= e($old['email'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone"><?= e(t('admin.client_admin_phone', 'Phone')) ?></label>
        <input class="form-control" type="tel" id="phone" name="phone" value="<?= e($old['phone'] ?? '') ?>">
    </div>
    <p class="form-text mb-0"><?= e(t('admin.client_admin_temp_password_note', 'A temporary password will be generated and shown once after creation.')) ?></p>
    <div class="col-12">
        <button class="btn btn-primary" type="submit"><?= e(t('admin.client_admin_create', 'Create administrator')) ?></button>
    </div>
</form>
