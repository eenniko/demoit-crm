<nav class="nav nav-tabs mb-3" aria-label="Employment">
    <a class="nav-link <?= $employmentSection === 'contracts' ? 'active' : '' ?>" href="/panel/employment/contracts">Contracts</a>
    <?php if ($canManage): ?>
        <a class="nav-link <?= $employmentSection === 'titles' ? 'active' : '' ?>" href="/panel/employment/titles">Job titles</a>
        <a class="nav-link <?= $employmentSection === 'departments' ? 'active' : '' ?>" href="/panel/employment/departments">Departments</a>
        <a class="nav-link <?= $employmentSection === 'workloads' ? 'active' : '' ?>" href="/panel/employment/workloads">Workloads</a>
    <?php endif; ?>
</nav>