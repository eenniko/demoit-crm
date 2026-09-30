<h1 class="h4 mb-3">Add translation key</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/admin/translations/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>

    <div class="col-md-6">
        <label class="form-label" for="translation_key">Translation key</label>
        <input class="form-control" type="text" id="translation_key" name="translation_key" required
               placeholder="e.g. dashboard.welcome_title" value="<?= e($old['translation_key'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="module_context">Module / context</label>
        <input class="form-control" type="text" id="module_context" name="module_context"
               value="<?= e($old['module_context'] ?? '') ?>">
    </div>
    <div class="col-12">
        <label class="form-label" for="english_value">English value</label>
        <input class="form-control" type="text" id="english_value" name="english_value" required
               value="<?= e($old['english_value'] ?? '') ?>">
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Create key</button>
    </div>
</form>
