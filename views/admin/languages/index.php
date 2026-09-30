<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Languages</h1>
    <a class="btn btn-primary btn-sm" href="/admin/languages/create">Add language</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Default</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($languages as $language): ?>
                <tr>
                    <td><code><?= e($language['language_code']) ?></code></td>
                    <td><?= e($language['name']) ?></td>
                    <td><?= (int) $language['is_default'] === 1 ? 'Yes' : '' ?></td>
                    <td>
                        <span class="badge <?= (int) $language['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                            <?= (int) $language['is_active'] === 1 ? 'active' : 'inactive' ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <?php if ($language['language_code'] !== 'en'): ?>
                            <form method="post" action="/admin/languages/toggle" class="d-inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $language['id'] ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit">
                                    <?= (int) $language['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
