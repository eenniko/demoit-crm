<div class="container">
    <div class="alert alert-danger">
        <h1 class="h4">Something went wrong</h1>
        <p class="mb-0">The application could not start. Please check the database connection settings in <code>.env</code>, or try again shortly.</p>
        <?php if (!empty($debug)): ?>
            <hr>
            <pre class="mb-0 small"><?= e($debug) ?></pre>
        <?php endif; ?>
    </div>
</div>
