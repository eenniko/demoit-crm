<h1 class="h4 mb-3">Add language</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/admin/languages/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>

    <div class="col-md-4">
        <label class="form-label" for="language_code">Language code</label>
        <input class="form-control" type="text" id="language_code" name="language_code" required
               placeholder="e.g. et" value="<?= e($old['language_code'] ?? '') ?>">
    </div>
    <div class="col-md-8">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" type="text" id="name" name="name" required
               placeholder="e.g. Estonian" value="<?= e($old['name'] ?? '') ?>">
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Create language</button>
    </div>
</form>
