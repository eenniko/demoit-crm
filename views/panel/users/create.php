<h1 class="h4 mb-3">Add employee</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/panel/users/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>

    <div class="col-md-6">
        <label class="form-label" for="username">Employee code / username</label>
        <input class="form-control" type="text" id="username" name="username" required
               value="<?= e($old['username'] ?? '') ?>">
        <div class="form-text">The inactive account can be activated after its details are checked.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="full_name">Employee name</label>
        <input class="form-control" type="text" id="full_name" name="full_name" required
               value="<?= e($old['full_name'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email">E-mail</label>
        <input class="form-control" type="email" id="email" name="email" required
               value="<?= e($old['email'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" type="tel" id="phone" name="phone"
               value="<?= e($old['phone'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="role_key">Access level</label>
        <select class="form-select" id="role_key" name="role_key" required>
            <?php foreach ($roles as $role): ?>
                <option value="<?= e($role['role_key']) ?>" <?= ($old['role_key'] ?? 'level_f') === $role['role_key'] ? 'selected' : '' ?>>
                    <?= e($role['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="org_unit_id">Organisation unit (optional)</label>
        <select class="form-select" id="org_unit_id" name="org_unit_id">
            <option value="">None</option>
            <?php foreach ($orgUnits as $unit): ?>
                <option value="<?= (int) $unit['id'] ?>" <?= (string) ($old['org_unit_id'] ?? '') === (string) $unit['id'] ? 'selected' : '' ?>>
                    <?= e($unit['org_level']) ?> — <?= e($unit['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Create employee</button>
    </div>
</form>
