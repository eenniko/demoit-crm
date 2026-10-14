<h1 class="h4 mb-3">New support ticket</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="/panel/support/create" class="row g-3" novalidate>
    <?= Csrf::field() ?>
    <div class="col-12">
        <label class="form-label" for="recipient-type">Recipient</label>
        <select class="form-select" id="recipient-type" name="recipient_type" required>
            <?php foreach (['system' => 'System support', 'client_admin' => 'Client administrator', 'manager' => 'Employee manager'] as $type => $label): ?>
                <option value="<?= $type ?>" <?= ($old['recipient_type'] ?? 'system') === $type ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12" id="recipient-person-row" hidden>
        <label class="form-label" for="recipient-person">Choose recipient</label>
        <select class="form-select" id="recipient-person" name="recipient_user_id" disabled>
            <option value="">Choose recipient</option>
            <?php foreach ($recipientOptions as $type => $people): ?>
                <?php foreach ($people as $person): ?>
                    <option data-type="<?= e($type) ?>" data-no-translate value="<?= (int) $person['id'] ?>"
                        <?= ($old['recipient_type'] ?? '') === $type && (int) ($old['recipient_user_id'] ?? 0) === (int) $person['id'] ? 'selected' : '' ?>><?= e($person['name']) ?></option>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </select>
        <p class="text-muted small mt-2" id="no-recipients" hidden>No available recipients.</p>
    </div>
    <div class="col-12">
        <label class="form-label" for="subject">Subject</label>
        <input class="form-control" type="text" id="subject" name="subject" required maxlength="191"
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
<script>
(() => {
    const type = document.getElementById('recipient-type');
    const person = document.getElementById('recipient-person');
    function updateRecipients() {
        const system = type.value === 'system';
        document.getElementById('recipient-person-row').hidden = system;
        person.disabled = system;
        person.required = !system;
        let available = 0;
        Array.from(person.options).forEach(option => {
            if (!option.dataset.type) return;
            const matches = option.dataset.type === type.value;
            option.hidden = !matches;
            option.disabled = !matches;
            if (matches) available++;
            if (!matches && option.selected) person.value = '';
        });
        document.getElementById('no-recipients').hidden = system || available > 0;
    }
    type.addEventListener('change', updateRecipients);
    updateRecipients();
})();
</script>
