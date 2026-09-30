<h1 class="h4 mb-3">Client management</h1>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/panel/client" class="row g-3" novalidate>
    <?= Csrf::field() ?>

    <div class="col-md-6">
        <label class="form-label" for="company_name">Company name</label>
        <input class="form-control" type="text" id="company_name" name="company_name" required
               value="<?= e($client['company_name']) ?>" <?= $canManage ? '' : 'readonly' ?>>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="address">Address</label>
        <input class="form-control" type="text" id="address" name="address"
               value="<?= e($client['address']) ?>" <?= $canManage ? '' : 'readonly' ?>>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" type="text" id="phone" name="phone"
               value="<?= e($client['phone']) ?>" <?= $canManage ? '' : 'readonly' ?>>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email">E-mail</label>
        <input class="form-control" type="email" id="email" name="email"
               value="<?= e($client['email']) ?>" <?= $canManage ? '' : 'readonly' ?>>
    </div>

    <hr class="my-2">
    <h2 class="h6">Representative</h2>

    <div class="col-md-4">
        <label class="form-label" for="contact_person_name">Full name</label>
        <input class="form-control" type="text" id="contact_person_name" name="contact_person_name"
               value="<?= e($client['contact_person_name']) ?>" <?= $canManage ? '' : 'readonly' ?>>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="contact_person_phone">Phone</label>
        <input class="form-control" type="text" id="contact_person_phone" name="contact_person_phone"
               value="<?= e($client['contact_person_phone']) ?>" <?= $canManage ? '' : 'readonly' ?>>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="contact_person_email">E-mail</label>
        <input class="form-control" type="email" id="contact_person_email" name="contact_person_email"
               value="<?= e($client['contact_person_email']) ?>" <?= $canManage ? '' : 'readonly' ?>>
    </div>

    <?php if ($canManage): ?>
        <div class="col-12">
            <button class="btn btn-primary" type="submit">Save changes</button>
        </div>
    <?php endif; ?>
</form>
