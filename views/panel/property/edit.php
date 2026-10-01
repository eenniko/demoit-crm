<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Edit <?= e($node['node_type']) ?> name</h1>
    <a class="btn btn-outline-secondary btn-sm" href="/panel/property">Back</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/panel/property/edit" class="row g-3" novalidate>
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $node['id'] ?>">
    <div class="col-md-8">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" type="text" id="name" name="name" maxlength="191" required value="<?= e($node['name']) ?>">
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Save changes</button>
    </div>
</form>