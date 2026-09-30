<h1 class="h4 mb-3">Add patient</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/panel/patients/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>

    <div class="col-md-6">
        <label class="form-label" for="full_name">Full name</label>
        <input class="form-control" type="text" id="full_name" name="full_name" required
               value="<?= e($old['full_name'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="personal_code">Personal code</label>
        <input class="form-control" type="text" id="personal_code" name="personal_code"
               value="<?= e($old['personal_code'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="birth_date">Date of birth</label>
        <input class="form-control" type="date" id="birth_date" name="birth_date"
               value="<?= e($old['birth_date'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" type="text" id="phone" name="phone"
               value="<?= e($old['phone'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="email">E-mail</label>
        <input class="form-control" type="email" id="email" name="email"
               value="<?= e($old['email'] ?? '') ?>">
    </div>
    <div class="col-12">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="3"><?= e($old['notes'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
        <button class="btn btn-primary" type="submit">Create patient</button>
    </div>
</form>
