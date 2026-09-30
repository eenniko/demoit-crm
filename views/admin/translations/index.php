<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Translations</h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="/admin/translations/missing?language=<?= (int) $selectedLanguage['id'] ?>">Missing translations</a>
        <a class="btn btn-primary btn-sm" href="/admin/translations/create">Add key</a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<form method="get" action="/admin/translations" class="row g-2 mb-3">
    <div class="col-auto">
        <select class="form-select form-select-sm" name="language" onchange="this.form.submit()">
            <?php foreach ($languages as $language): ?>
                <option value="<?= (int) $language['id'] ?>" <?= (int) $language['id'] === (int) $selectedLanguage['id'] ? 'selected' : '' ?>>
                    <?= e($language['name']) ?> (<?= e($language['language_code']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Key</th>
                <th>English (base)</th>
                <th><?= e($selectedLanguage['name']) ?> value</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><code><?= e($row['translation_key']) ?></code><br><small class="text-muted"><?= e($row['module_context']) ?></small></td>
                    <td><?= e($row['en_value']) ?></td>
                    <td>
                        <?php if ($selectedLanguage['language_code'] === 'en'): ?>
                            <?= e($row['en_value']) ?>
                        <?php else: ?>
                            <form method="post" action="/admin/translations/update" class="d-flex gap-2">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="key_id" value="<?= (int) $row['key_id'] ?>">
                                <input type="hidden" name="language_id" value="<?= (int) $selectedLanguage['id'] ?>">
                                <input class="form-control form-control-sm" type="text" name="value"
                                       value="<?= e($row['value']) ?>"
                                       placeholder="<?= empty($row['value']) ? '(falls back to English)' : '' ?>">
                                <button class="btn btn-outline-primary btn-sm text-nowrap" type="submit">Save</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
