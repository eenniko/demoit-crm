<?php $scheduleSection = 'holidays'; require __DIR__ . '/nav.php'; ?>

<h1 class="h4 mb-3"><?= e(t('ui.' . hash('sha256', 'Public holidays'), 'Public holidays')) ?></h1>
<?php if (!empty($message)): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<form method="post" action="/panel/schedule/holidays/import" enctype="multipart/form-data" class="d-flex flex-wrap align-items-end gap-2 mb-2">
    <?= Csrf::field() ?>
    <div>
        <label class="form-label mb-1" for="public_holidays_xml"><?= e(t('ui.' . hash('sha256', 'Public holidays XML'), 'Public holidays XML')) ?></label>
        <input class="form-control" type="file" id="public_holidays_xml" name="public_holidays_xml" accept=".xml,application/xml,text/xml" required>
    </div>
    <button class="btn btn-outline-primary" type="submit"><?= e(t('ui.' . hash('sha256', 'Import new entries'), 'Import new entries')) ?></button>
</form>
<p class="small text-muted mb-3"><?= e(t('ui.' . hash('sha256', 'Only missing entries are added. Existing records are never changed.'), 'Only missing entries are added. Existing records are never changed.')) ?></p>

<?php if (empty($holidays)): ?>
    <p class="text-muted"><?= e(t('ui.' . hash('sha256', 'No public holidays have been imported yet.'), 'No public holidays have been imported yet.')) ?></p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead><tr>
                <th><?= e(t('ui.' . hash('sha256', 'Date'), 'Date')) ?></th>
                <th><?= e(t('ui.' . hash('sha256', 'Title'), 'Title')) ?></th>
                <th><?= e(t('ui.' . hash('sha256', 'Category'), 'Category')) ?></th>
                <th><?= e(t('ui.' . hash('sha256', 'Notes'), 'Notes')) ?></th>
            </tr></thead>
            <tbody>
                <?php foreach ($holidays as $holiday): ?>
                    <tr>
                        <td class="text-nowrap"><?= e(date('d.m.Y', strtotime($holiday['holiday_date']))) ?></td>
                        <td><?= e($holiday['title']) ?></td>
                        <td><?= e($holiday['kind']) ?> <small class="text-muted">(<?= (int) $holiday['kind_id'] ?>)</small></td>
                        <td><?= e($holiday['notes'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>