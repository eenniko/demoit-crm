<h1 class="h4 mb-3">Add module</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/admin/modules/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>

    <div class="col-md-4">
        <label class="form-label" for="module_key">Module key</label>
        <input class="form-control" type="text" id="module_key" name="module_key" required
               placeholder="e.g. appointments" value="<?= e($old['module_key'] ?? '') ?>">
    </div>
    <div class="col-md-8">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" type="text" id="name" name="name" required
               value="<?= e($old['name'] ?? '') ?>">
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3"><?= e($old['description'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_demo_available" name="is_demo_available" value="1">
            <label class="form-check-label" for="is_demo_available">Available in demo mode</label>
        </div>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Create module</button>
    </div>
</form>
