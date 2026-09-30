<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Import legacy employees</h1>
    <a class="btn btn-outline-secondary btn-sm" href="/panel/users">Back</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="alert alert-info">
    Upload a phpMyAdmin SQL dump containing the <strong>db_user_data</strong> table.
    The legacy user ID becomes the employee code and username. Imported accounts are inactive and receive Level F access.
    Existing usernames are skipped. Passwords are not imported.
</div>

<form method="post" action="/panel/users/import" enctype="multipart/form-data" class="row g-3">
    <?= Csrf::field() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880">

    <div class="col-12 col-lg-8">
        <label class="form-label" for="legacy_sql">Legacy database SQL file</label>
        <input class="form-control" type="file" id="legacy_sql" name="legacy_sql" accept=".sql,text/plain,application/sql" required>
        <div class="form-text">Maximum file size: 5 MB. Only db_user_data INSERT rows are read; uploaded SQL is never executed.</div>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit" onclick="return confirm('Import legacy employees into this client?');">Import employees</button>
    </div>
</form>