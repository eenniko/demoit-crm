<h1 class="h4 mb-3">Active modules</h1>

<?php if (empty($modules)): ?>
    <p class="text-muted">No modules are activated for your account yet. Please contact support.</p>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($modules as $module): ?>
            <div class="list-group-item"><?= e($module['name']) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
