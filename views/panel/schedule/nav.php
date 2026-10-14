<nav class="nav nav-tabs mb-3" aria-label="Work schedule">
    <?php if (empty($printOnly)): ?>
        <a class="nav-link <?= $scheduleSection === 'month' ? 'active' : '' ?>" href="/panel/schedule?month=<?= e($month['month'] ?? date('Y-m')) ?>">Monthly schedule</a>
    <?php endif; ?>
    <a class="nav-link <?= $scheduleSection === 'print' ? 'active' : '' ?>" href="/panel/schedule/print?month=<?= e($month['month'] ?? date('Y-m')) ?>"><?= e(t('ui.' . hash('sha256', 'Print schedule'), 'Print schedule')) ?></a>
    <?php if ($canManageTemplates): ?>
        <a class="nav-link <?= $scheduleSection === 'templates' ? 'active' : '' ?>" href="/panel/schedule/templates">Shift settings</a>
        <a class="nav-link <?= $scheduleSection === 'holidays' ? 'active' : '' ?>" href="/panel/schedule/holidays">Public holidays</a>
    <?php endif; ?>
    <?php if (!empty($canAssignSubstitute)): ?>
        <a class="nav-link <?= $scheduleSection === 'substitutes' ? 'active' : '' ?>" href="/panel/schedule/substitutes">Schedule substitutes</a>
    <?php endif; ?>
</nav>