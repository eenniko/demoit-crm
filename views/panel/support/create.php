<h1 class="h4 mb-3">New support ticket</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/panel/support/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>
    <div class="col-12">
        <label class="form-label" for="subject">Subject</label>
        <input class="form-control" type="text" id="subject" name="subject" required
               value="<?= e($old['subject'] ?? '') ?>">
    </div>
    <div class="col-12">
        <label class="form-label" for="message">Message</label>
        <textarea class="form-control" id="message" name="message" rows="4" required><?= e($old['message'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Submit ticket</button>
    </div>
</form>
