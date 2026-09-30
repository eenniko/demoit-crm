<div class="container">
    <div class="card auth-card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">Initial system setup</h1>
            <p class="text-muted">Create the first system administrator account. This can only be done once.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="/setup" novalidate>
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label" for="full_name">Full name</label>
                    <input class="form-control" type="text" id="full_name" name="full_name" required
                           value="<?= e($old['full_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input class="form-control" type="email" id="email" name="email" required
                           value="<?= e($old['email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input class="form-control" type="text" id="username" name="username" required
                           value="<?= e($old['username'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="8" required>
                    <div class="form-text">Minimum 8 characters.</div>
                </div>
                <button class="btn btn-primary w-100" type="submit">Create administrator</button>
            </form>
        </div>
    </div>
</div>
