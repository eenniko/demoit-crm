<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Missing translations — <?= e($selectedLanguage['name']) ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/translations?language=<?= (int) $selectedLanguage['id'] ?>">Back to translations</a>
</div>

<?php if (empty($rows)): ?>
    <div class="alert alert-success">No missing translations for this language.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>English (base)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><code><?= e($row['translation_key']) ?></code><br><small class="text-muted"><?= e($row['module_context']) ?></small></td>
                        <td><?= e($row['en_value']) ?></td>
                        <td>
                            <form method="post" action="/admin/translations/update" class="d-flex gap-2">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="key_id" value="<?= (int) $row['key_id'] ?>">
                                <input type="hidden" name="language_id" value="<?= (int) $selectedLanguage['id'] ?>">
                                <input class="form-control form-control-sm" type="text" name="value" placeholder="Add translation">
                                <button class="btn btn-outline-primary btn-sm text-nowrap" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
