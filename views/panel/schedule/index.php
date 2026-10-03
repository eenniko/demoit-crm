<?php $scheduleSection = 'month'; require __DIR__ . '/nav.php'; ?>

<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h1 class="h4 mb-1">Work schedule</h1>
        <p class="text-muted mb-0"><?= (int) $month['active_workdays'] ?> weekdays this month (Monday to Friday).</p>
    </div>
    <form method="get" action="/panel/schedule" class="d-flex align-items-end gap-2">
        <div>
            <label class="form-label mb-1" for="month">Month</label>
            <input class="form-control" type="month" id="month" name="month" value="<?= e($month['month']) ?>" required>
        </div>
        <button class="btn btn-outline-primary" type="submit">Show</button>
    </form>
</div>

<?php if (!empty($message)): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<?php if (empty($contracts)): ?>
    <p class="text-muted">No primary contracts managed by you overlap this month.</p>
<?php else: ?>
    <?php if ($canManage): ?><div class="small mb-2 d-none" id="scheduleSaveStatus" role="status" aria-live="polite"></div><?php endif; ?>
    <form method="post" action="/panel/schedule" class="table-responsive schedule-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="month" value="<?= e($month['month']) ?>">
        <table class="table table-sm table-bordered align-middle schedule-grid">
            <thead>
                <tr>
                    <th class="schedule-employee">Employee</th>
                    <th class="schedule-location">Floor / department</th>
                    <?php for ($day = 1; $day <= $month['days']; $day++): ?>
                        <?php $date = sprintf('%s-%02d', $month['month'], $day); ?>
                        <th class="text-center schedule-day <?= (int) date('N', strtotime($date)) >= 6 ? 'table-light' : '' ?>">
                            <?= $day ?><br><small><?= e(date('D', strtotime($date))) ?></small>
                        </th>
                    <?php endfor; ?>
                    <th class="schedule-total-column">Planned h</th>
                    <th class="schedule-total-column">Required h</th>
                    <th class="schedule-total-column">OT</th>
                    <th class="schedule-total-column">Tri-OT</th>
                </tr>
            </thead>
            <tbody>
                <?php $previousGroup = null; ?>
                <?php $groupIndex = 0; ?>
                <?php $groupId = ''; ?>
                <?php foreach ($contracts as $contract): ?>
                    <?php
                    $contractId = (int) $contract['contract_id'];
                    $groupLabel = $contract['property_path'] . ' · ' . ($contract['department_name'] ?: 'No department');
                    if ($groupLabel !== $previousGroup):
                        $previousGroup = $groupLabel;
                        $groupIndex++;
                        $groupId = 'schedule-group-' . $groupIndex;
                    ?>
                        <tr class="table-light">
                            <th colspan="<?= 6 + (int) $month['days'] ?>">
                                <button class="schedule-group-toggle" type="button" data-schedule-group="<?= e($groupId) ?>" aria-expanded="true">
                                    <span class="schedule-group-indicator" aria-hidden="true">▾</span>
                                    <span><?= e($groupLabel) ?></span>
                                    <span class="visually-hidden">Collapse group</span>
                                </button>
                            </th>
                        </tr>
                    <?php endif; ?>
                    <tr data-contract-id="<?= $contractId ?>" data-schedule-group="<?= e($groupId) ?>">
                        <th scope="row" class="schedule-employee">
                            <?= e($contract['full_name'] ?: $contract['username']) ?>
                            <small class="d-block text-muted"><?= e(number_format((float) $contract['workload_percent'], 2)) ?>%</small>
                        </th>
                        <td class="schedule-location">
                            <?= e($contract['property_path']) ?>
                            <?php if (!empty($contract['department_name'])): ?><small class="d-block text-muted"><?= e($contract['department_name']) ?></small><?php endif; ?>
                        </td>
                        <?php for ($day = 1; $day <= $month['days']; $day++): ?>
                            <?php
                            $date = sprintf('%s-%02d', $month['month'], $day);
                            $entry = $entries[$contractId][$date] ?? null;
                            $entryColor = $entry === null ? null : ScheduleService::safeTemplateColor($entry['color_hex'] ?? null);
                            $withinContract = $date >= $contract['start_date'] && ($contract['end_date'] === null || $date <= $contract['end_date']);
                            $weekend = (int) date('N', strtotime($date)) >= 6;
                            ?>
                            <td class="schedule-day <?= $weekend ? 'table-light' : '' ?>">
                                <?php if ($canManage && $withinContract): ?>
                                    <input class="schedule-assignment" type="hidden" name="assignments[<?= $contractId ?>][<?= e($date) ?>]" value="<?= (int) ($entry['template_id'] ?? 0) ?>">
                                    <button
                                        class="schedule-cell-button <?= $entry === null ? 'is-empty' : 'is-assigned' ?>"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#schedulePickerModal"
                                        data-employee="<?= e($contract['full_name'] ?: $contract['username']) ?>"
                                        data-date="<?= e($date) ?>"
                                        style="<?= $entryColor === null ? '' : ' --schedule-color: ' . e($entryColor) . ';' ?>"
                                        aria-label="<?= e(($contract['full_name'] ?: $contract['username']) . ' ' . $date . ' ' . ($entry['code'] ?? 'Off')) ?>">
                                        <?php if ($entry === null): ?><span aria-hidden="true">−</span><?php else: ?><?= e($entry['code']) ?><?php endif; ?>
                                    </button>
                                <?php elseif ($entry !== null): ?>
                                    <span class="text-nowrap" title="<?= e($entry['template_name']) ?>"><?= e(ScheduleService::formatTemplate($entry)) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Off</span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <?php
                        $monthBalance = round((float) $contract['planned_hours'] - (float) $contract['required_hours'], 2);
                        $trimesterBalance = (float) $contract['trimester_balance_hours'];
                        $monthBalanceClass = $monthBalance > 0 ? 'text-danger' : ($monthBalance < 0 ? 'text-primary' : 'text-muted');
                        $trimesterBalanceClass = $trimesterBalance > 0 ? 'text-danger' : ($trimesterBalance < 0 ? 'text-primary' : 'text-muted');
                        ?>
                        <td class="schedule-total-column"><span class="schedule-planned-hours"><?= e(ScheduleService::formatHours((float) $contract['planned_hours'])) ?></span></td>
                        <td class="schedule-total-column"><?= e(ScheduleService::formatHours((float) $contract['required_hours'])) ?></td>
                        <td class="schedule-total-column"><strong class="<?= e($monthBalanceClass) ?>"><?= e(ScheduleService::formatBalance($monthBalance)) ?></strong></td>
                        <td class="schedule-total-column"><strong class="<?= e($trimesterBalanceClass) ?>"><?= e(ScheduleService::formatBalance($trimesterBalance)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
    <?php if ($canManage): ?>
        <div class="modal fade" id="schedulePickerModal" tabindex="-1" aria-labelledby="schedulePickerTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title h5 mb-1" id="schedulePickerTitle">Choose shift or exception</h2>
                            <p class="small text-muted mb-0" id="schedulePickerContext">Select a schedule value.</p>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <h3 class="h6">Day off</h3>
                            <button class="btn btn-outline-secondary schedule-choice" type="button" data-schedule-value="0" data-schedule-code="−" aria-pressed="false">Off</button>
                        </div>
                        <?php foreach (['shift' => 'Shifts', 'exception' => 'Exceptions'] as $type => $label): ?>
                            <?php $typeTemplates = array_values(array_filter($templates, static fn (array $template): bool => $template['template_type'] === $type && $template['status'] === 'active')); ?>
                            <?php if ($typeTemplates !== []): ?>
                                <div class="mb-4">
                                    <h3 class="h6"><?= e($label) ?></h3>
                                    <div class="row row-cols-2 row-cols-sm-3 g-2">
                                        <?php foreach ($typeTemplates as $template): ?>
                                            <div class="col">
                                                <button
                                                    class="btn btn-outline-primary schedule-choice w-100 text-start"
                                                    type="button"
                                                    data-schedule-value="<?= (int) $template['id'] ?>"
                                                    data-schedule-code="<?= e($template['code']) ?>"
                                                    data-schedule-color="<?= e(ScheduleService::safeTemplateColor($template['color_hex'] ?? null)) ?>"
                                                    style="--schedule-color: <?= e(ScheduleService::safeTemplateColor($template['color_hex'] ?? null)) ?>"
                                                    aria-pressed="false">
                                                    <span class="d-block fw-semibold"><?= e($template['code']) ?></span>
                                                    <small><?= e(substr(ScheduleService::formatTemplate($template), strlen($template['code']) + 3)) ?></small>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            (() => {
                const modal = document.getElementById('schedulePickerModal');
                const context = document.getElementById('schedulePickerContext');
                const form = document.querySelector('.schedule-form');
                const status = document.getElementById('scheduleSaveStatus');
                let activeCellButton = null;
                let saveAfterClose = false;
                let isSaving = false;

                form.querySelectorAll('.schedule-group-toggle').forEach((button) => {
                    button.addEventListener('click', () => {
                        const isExpanded = button.getAttribute('aria-expanded') === 'true';
                        const shouldExpand = !isExpanded;
                        const group = button.dataset.scheduleGroup;
                        form.querySelectorAll(`tr[data-schedule-group="${group}"]`).forEach((row) => {
                            row.hidden = !shouldExpand;
                        });
                        button.setAttribute('aria-expanded', String(shouldExpand));
                        button.querySelector('.schedule-group-indicator').textContent = shouldExpand ? '▾' : '▸';
                        button.querySelector('.visually-hidden').textContent = shouldExpand ? 'Collapse group' : 'Expand group';
                    });
                });

                function showSaveStatus(message, kind) {
                    status.className = `small mb-2 text-${kind}`;
                    status.textContent = message;
                }

                async function saveSchedule() {
                    if (isSaving) {
                        return;
                    }

                    isSaving = true;
                    form.querySelectorAll('.schedule-cell-button').forEach((button) => {
                        button.disabled = true;
                    });
                    showSaveStatus('Saving schedule...', 'muted');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const contentType = response.headers.get('content-type') || '';
                        if (!contentType.includes('application/json')) {
                            throw new Error('The server returned an unexpected response.');
                        }

                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.message || 'Could not save the monthly schedule.');
                        }

                        Object.entries(result.plannedHours || {}).forEach(([contractId, hours]) => {
                            const row = form.querySelector(`[data-contract-id="${contractId}"]`);
                            const plannedHours = row?.querySelector('.schedule-planned-hours');
                            if (plannedHours) {
                                plannedHours.textContent = `${Number(hours).toFixed(2)}h planned`;
                            }
                        });
                        showSaveStatus(result.message, 'success');
                    } catch (error) {
                        showSaveStatus(error.message || 'Could not save the monthly schedule.', 'danger');
                    } finally {
                        form.querySelectorAll('.schedule-cell-button').forEach((button) => {
                            button.disabled = false;
                        });
                        isSaving = false;
                    }
                }

                modal.addEventListener('show.bs.modal', (event) => {
                    activeCellButton = event.relatedTarget;
                    const assignment = activeCellButton.parentElement.querySelector('.schedule-assignment');
                    const currentValue = assignment.value;
                    context.textContent = `${activeCellButton.dataset.employee} · ${activeCellButton.dataset.date}`;

                    modal.querySelectorAll('.schedule-choice').forEach((choice) => {
                        const isCurrent = choice.dataset.scheduleValue === currentValue;
                        choice.classList.toggle('active', isCurrent);
                        choice.setAttribute('aria-pressed', String(isCurrent));
                    });
                });

                modal.addEventListener('click', (event) => {
                    const choice = event.target.closest('.schedule-choice');
                    if (!choice || !activeCellButton) {
                        return;
                    }

                    const assignment = activeCellButton.parentElement.querySelector('.schedule-assignment');
                    assignment.value = choice.dataset.scheduleValue;
                    const isOff = assignment.value === '0';
                    activeCellButton.textContent = isOff ? '−' : choice.dataset.scheduleCode;
                    activeCellButton.classList.toggle('is-empty', isOff);
                    activeCellButton.classList.toggle('is-assigned', !isOff);
                    if (isOff) {
                        activeCellButton.style.removeProperty('--schedule-color');
                    } else {
                        activeCellButton.style.setProperty('--schedule-color', choice.dataset.scheduleColor);
                    }
                    activeCellButton.setAttribute(
                        'aria-label',
                        `${activeCellButton.dataset.employee} ${activeCellButton.dataset.date} ${isOff ? 'Off' : choice.dataset.scheduleCode}`
                    );
                    saveAfterClose = true;
                    bootstrap.Modal.getOrCreateInstance(modal).hide();
                });

                modal.addEventListener('hidden.bs.modal', () => {
                    activeCellButton = null;
                    if (saveAfterClose) {
                        saveAfterClose = false;
                        saveSchedule();
                    }
                });
            })();
        </script>
    <?php endif; ?>
<?php endif; ?>

<style>
.schedule-grid { width: 100%; min-width: 0; table-layout: fixed; }
.schedule-employee { width: 145px; min-width: 0; overflow-wrap: anywhere; }
.schedule-location { width: 155px; min-width: 0; overflow-wrap: anywhere; }
.schedule-day { min-width: 0; padding: 0 !important; text-align: center; }
.schedule-total-column { width: 72px; padding: .25rem .15rem !important; text-align: right; white-space: nowrap; }
.schedule-total-column:nth-last-child(2) { width: 56px; }
.schedule-total-column:last-child { width: 68px; }
.schedule-day small { font-size: .65rem; line-height: 1; }
.schedule-group-toggle { display: flex; align-items: center; gap: .5rem; width: 100%; min-height: 38px; padding: .25rem .5rem; border: 0; background: transparent; color: var(--bs-body-color); font-weight: 600; text-align: left; }
.schedule-group-toggle:hover { background: var(--bs-tertiary-bg); }
.schedule-group-indicator { width: 1rem; color: var(--bs-secondary-color); }
.schedule-cell-button { display: block; width: 100%; min-width: 0; min-height: 40px; overflow: hidden; padding: .15rem 0; border: 0; border-radius: 0; background: transparent; color: var(--bs-secondary-color); font-size: .72rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
.schedule-cell-button.is-empty { font-size: 1.25rem; font-weight: 400; }
.schedule-cell-button.is-assigned { background: color-mix(in srgb, var(--schedule-color, #64748B) 18%, white); box-shadow: inset 0 -4px 0 var(--schedule-color, #64748B); color: var(--bs-body-color); }
.schedule-cell-button:hover { background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
.schedule-cell-button.is-assigned:hover { background: color-mix(in srgb, var(--schedule-color, #64748B) 28%, white); color: var(--bs-body-color); }
.schedule-cell-button:focus-visible { position: relative; z-index: 1; outline: 2px solid var(--bs-primary); outline-offset: -2px; }
.schedule-choice[data-schedule-value]:not([data-schedule-value="0"]) { border-color: var(--schedule-color, #64748B); border-left-width: .35rem; }
.schedule-choice[data-schedule-value]:not([data-schedule-value="0"]):hover,
.schedule-choice[data-schedule-value]:not([data-schedule-value="0"]).active { background: color-mix(in srgb, var(--schedule-color, #64748B) 16%, white); color: var(--bs-body-color); }
@media (max-width: 1200px) {
    .schedule-employee { width: 115px; font-size: .78rem; }
    .schedule-location { width: 125px; font-size: .75rem; }
    .schedule-total-column { width: 64px; font-size: .72rem; }
    .schedule-total-column:nth-last-child(2) { width: 48px; }
    .schedule-total-column:last-child { width: 60px; }
    .schedule-cell-button { font-size: .64rem; }
}
</style>