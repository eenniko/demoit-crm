<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-0">Add location</h1>
        <?php if ($parent !== null): ?>
            <span class="text-muted">Inside <?= e($parent['name']) ?></span>
        <?php endif; ?>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/panel/property">Back</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/panel/property/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>
    <input type="hidden" name="parent_id" value="<?= (int) ($parent['id'] ?? 0) ?>">
    <div class="col-md-5">
        <label class="form-label" for="node_type">Type</label>
        <select class="form-select" id="node_type" name="node_type" required>
            <?php foreach ($allowedTypes as $type): ?>
                <option value="<?= e($type) ?>" <?= ($old['node_type'] ?? '') === $type ? 'selected' : '' ?>><?= e(ucfirst($type)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-7">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" type="text" id="name" name="name" maxlength="191" required value="<?= e($old['name'] ?? '') ?>">
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Save location</button>
    </div>
</form>