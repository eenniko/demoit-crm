<h1 class="h4 mb-3">Request substitute</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="get" action="/panel/substitutes/create" class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label" for="original_user_id">User being substituted</label>
        <select class="form-select" id="original_user_id" name="original_user_id" onchange="this.form.submit()">
            <option value="">Select a user</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= (int) $user['id'] ?>" <?= (int) ($old['original_user_id'] ?? 0) === (int) $user['id'] ? 'selected' : '' ?>>
                    <?= e($user['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (!empty($old['original_user_id'])): ?>
    <?php if (empty($eligibleSubstitutes)): ?>
        <div class="alert alert-warning">No eligible substitutes found (must be in the same organisation unit, a subordinate, or the direct manager).</div>
    <?php else: ?>
        <form method="post" action="/panel/substitutes/create" class="row g-3">
            <?= Csrf::field() ?>
            <input type="hidden" name="original_user_id" value="<?= (int) $old['original_user_id'] ?>">

            <div class="col-md-6">
                <label class="form-label" for="substitute_user_id">Substitute</label>
                <select class="form-select" id="substitute_user_id" name="substitute_user_id" required>
                    <?php foreach ($eligibleSubstitutes as $candidate): ?>
                        <option value="<?= (int) $candidate['id'] ?>"><?= e($candidate['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="substitute_type">Type</label>
                <input class="form-control" type="text" id="substitute_type" name="substitute_type" value="temporary">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="starts_at">Starts at</label>
                <input class="form-control" type="datetime-local" id="starts_at" name="starts_at" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ends_at">Ends at</label>
                <input class="form-control" type="datetime-local" id="ends_at" name="ends_at" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="reason">Reason</label>
                <textarea class="form-control" id="reason" name="reason" rows="2"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Submit request</button>
            </div>
        </form>
    <?php endif; ?>
<?php endif; ?>
