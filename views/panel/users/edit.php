<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Edit employee</h1>
    <a class="btn btn-outline-secondary btn-sm" href="/panel/users">Back</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/panel/users/edit?id=<?= (int) $employee['id'] ?>" class="row g-3" novalidate>
    <?= Csrf::field() ?>
    <input type="hidden" name="user_id" value="<?= (int) $employee['id'] ?>">

    <div class="col-md-6">
        <label class="form-label" for="username">Employee code / username</label>
        <input class="form-control" type="text" id="username" value="<?= e($employee['username']) ?>" disabled>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="full_name">Employee name</label>
        <input class="form-control" type="text" id="full_name" name="full_name" required value="<?= e($employee['full_name']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email">E-mail</label>
        <input class="form-control" type="email" id="email" name="email" required value="<?= e($employee['email']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" type="tel" id="phone" name="phone" value="<?= e($employee['phone']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="role_key">Access level</label>
        <select class="form-select" id="role_key" name="role_key" required>
            <?php foreach ($roles as $role): ?>
                <option value="<?= e($role['role_key']) ?>" <?= (int) $employee['role_id'] === (int) $role['id'] ? 'selected' : '' ?>>
                    <?= e($role['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="org_unit_id">Organisation unit</label>
        <select class="form-select" id="org_unit_id" name="org_unit_id">
            <option value="">None</option>
            <?php foreach ($orgUnits as $unit): ?>
                <option value="<?= (int) $unit['id'] ?>" <?= (string) ($employee['org_unit_id'] ?? '') === (string) $unit['id'] ? 'selected' : '' ?>>
                    <?= e($unit['org_level']) ?> - <?= e($unit['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Save changes</button>
    </div>
</form>