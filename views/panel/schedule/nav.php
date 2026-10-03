<nav class="nav nav-tabs mb-3" aria-label="Work schedule">
    <a class="nav-link <?= $scheduleSection === 'month' ? 'active' : '' ?>" href="/panel/schedule?month=<?= e($month['month'] ?? date('Y-m')) ?>">Monthly schedule</a>
    <?php if ($canManageTemplates): ?>
        <a class="nav-link <?= $scheduleSection === 'templates' ? 'active' : '' ?>" href="/panel/schedule/templates">Shift settings</a>
    <?php endif; ?>
</nav>