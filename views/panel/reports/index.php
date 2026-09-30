<h1 class="h4 mb-3">Reports &amp; statistics</h1>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="display-6"><?= (int) $stats['active_users'] ?></div>
            <div class="text-muted">Active users</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="display-6"><?= (int) $stats['active_modules'] ?></div>
            <div class="text-muted">Active modules</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="display-6"><?= (int) $stats['org_units'] ?></div>
            <div class="text-muted">Organisation units</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="display-6"><?= (int) $stats['open_support_tickets'] ?></div>
            <div class="text-muted">Open support tickets</div>
        </div></div>
    </div>
</div>
