<h1 class="h4 mb-3">Add organisation unit</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/panel/org/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>

    <div class="col-md-3">
        <label class="form-label" for="org_level">Level</label>
        <select class="form-select" id="org_level" name="org_level" required>
            <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $level): ?>
                <option value="<?= $level ?>" <?= ($old['org_level'] ?? '') === $level ? 'selected' : '' ?>><?= $level ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-9">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" type="text" id="name" name="name" required
               value="<?= e($old['name'] ?? '') ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="parent_org_unit_id">Parent unit (optional)</label>
        <select class="form-select" id="parent_org_unit_id" name="parent_org_unit_id">
            <option value="">None (top level)</option>
            <?php foreach ($units as $unit): ?>
                <option value="<?= (int) $unit['id'] ?>" <?= (string) ($old['parent_org_unit_id'] ?? '') === (string) $unit['id'] ? 'selected' : '' ?>>
                    <?= e($unit['org_level']) ?> — <?= e($unit['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="manager_user_id">Manager (optional)</label>
        <select class="form-select" id="manager_user_id" name="manager_user_id">
            <option value="">None</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= (int) $user['id'] ?>" <?= (string) ($old['manager_user_id'] ?? '') === (string) $user['id'] ? 'selected' : '' ?>>
                    <?= e($user['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12">
        <button class="btn btn-primary" type="submit">Create unit</button>
    </div>
</form>
