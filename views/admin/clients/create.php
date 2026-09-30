<h1 class="h4 mb-3">Add client</h1>

<?php if (!empty($tempPassword)): ?>
    <div class="alert alert-success">
        Client created. Temporary password for the first user (shown only once):
        <code><?= e($tempPassword) ?></code>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/admin/clients/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>

    <div class="col-md-4">
        <label class="form-label" for="client_code">Client code</label>
        <input class="form-control" type="text" id="client_code" name="client_code" required
               value="<?= e($old['client_code'] ?? '') ?>">
    </div>
    <div class="col-md-8">
        <label class="form-label" for="company_name">Company name</label>
        <input class="form-control" type="text" id="company_name" name="company_name" required
               value="<?= e($old['company_name'] ?? '') ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="registry_code">Registry code</label>
        <input class="form-control" type="text" id="registry_code" name="registry_code"
               value="<?= e($old['registry_code'] ?? '') ?>">
    </div>
    <div class="col-md-8">
        <label class="form-label" for="address">Address</label>
        <input class="form-control" type="text" id="address" name="address"
               value="<?= e($old['address'] ?? '') ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="phone">Contact phone</label>
        <input class="form-control" type="text" id="phone" name="phone"
               value="<?= e($old['phone'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email">Contact e-mail</label>
        <input class="form-control" type="email" id="email" name="email"
               value="<?= e($old['email'] ?? '') ?>">
    </div>

    <hr class="my-2">
    <h2 class="h6">Representative</h2>

    <div class="col-md-4">
        <label class="form-label" for="contact_person_name">Full name</label>
        <input class="form-control" type="text" id="contact_person_name" name="contact_person_name"
               value="<?= e($old['contact_person_name'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="contact_person_id_code">Personal ID code</label>
        <input class="form-control" type="text" id="contact_person_id_code" name="contact_person_id_code"
               value="<?= e($old['contact_person_id_code'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="contact_person_phone">Phone</label>
        <input class="form-control" type="text" id="contact_person_phone" name="contact_person_phone"
               value="<?= e($old['contact_person_phone'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="contact_person_email">E-mail</label>
        <input class="form-control" type="email" id="contact_person_email" name="contact_person_email"
               value="<?= e($old['contact_person_email'] ?? '') ?>">
    </div>

    <hr class="my-2">
    <h2 class="h6">First user (client administrator)</h2>

    <div class="col-md-6">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" type="text" id="username" name="username" required
               value="<?= e($old['username'] ?? '') ?>">
        <div class="form-text">A temporary password will be generated automatically.</div>
    </div>

    <div class="col-12">
        <button class="btn btn-primary" type="submit">Create client</button>
    </div>
</form>
