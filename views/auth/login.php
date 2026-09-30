<div class="container">
    <div class="card auth-card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">Client login</h1>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="/login" novalidate>
                <?= Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label" for="client_code">Client code</label>
                    <input class="form-control" type="text" id="client_code" name="client_code" required
                           value="<?= e($old['client_code'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input class="form-control" type="text" id="username" name="username" required
                           value="<?= e($old['username'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Log in</button>
            </form>
        </div>
    </div>
</div>
