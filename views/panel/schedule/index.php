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
                        <th class="text-center schedule-day <?= ScheduleService::isNonWorkingDay($date) ? 'schedule-weekend' : '' ?>">
                            <?= $day ?><br><small><?= e(date('D', strtotime($date))) ?></small>
                        </th>
                    <?php endfor; ?>
                    <th class="schedule-total-column">Planned h</th>
                    <th class="schedule-total-column">Min h</th>
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
                            <?php if ($contract['contract_type'] === 'temporary'): ?>
                                <small class="d-block text-muted">Temporary assignment</small>
                            <?php elseif ($canManage): ?>
                                <button
                                    class="btn btn-outline-secondary btn-sm w-100 mt-1 schedule-workload-button"
                                    type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#scheduleWorkloadModal"
                                    data-contract-id="<?= $contractId ?>"
                                    data-employee="<?= e($contract['full_name'] ?: $contract['username']) ?>"
                                    data-month="<?= e($month['month']) ?>"
                                    data-workload-id="<?= $contract['monthly_workload_id'] === null ? '' : (int) $contract['monthly_workload_id'] ?>"
                                    data-saved-value="<?= $contract['monthly_workload_id'] === null ? '' : (int) $contract['monthly_workload_id'] ?>"
                                    data-workload-percent="<?= e(number_format((float) $contract['workload_percent'], 2, '.', '')) ?>"
                                    data-base-workload-percent="<?= e(number_format((float) $contract['base_workload_percent'], 2, '.', '')) ?>"
                                    data-workload-label="<?= e(t('panel.monthly_workload', 'Monthly workload')) ?>"
                                    aria-label="<?= e(t('panel.monthly_workload', 'Monthly workload') . ' ' . number_format((float) $contract['workload_percent'], 2) . '%') ?>">
                                    <?= e(number_format((float) $contract['workload_percent'], 2)) ?>%
                                </button>
                            <?php else: ?>
                                <small class="d-block text-muted"><?= e(number_format((float) $contract['workload_percent'], 2) . '%') ?></small>
                            <?php endif; ?>
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
                            $nonWorkingDay = ScheduleService::isNonWorkingDay($date);
                            ?>
                            <td class="schedule-day <?= $nonWorkingDay ? 'schedule-weekend' : '' ?>">
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
                                        <?php if ($entry === null): ?><span aria-hidden="true"><?= $day ?></span><?php else: ?><?= e($entry['code']) ?><?php endif; ?>
                                    </button>
                                <?php elseif ($entry !== null): ?>
                                    <span class="text-nowrap" title="<?= e($entry['template_name']) ?>"><?= e(ScheduleService::formatTemplate($entry)) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Off</span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <?php
                        $monthBalance = $contract['monthly_balance_hours'];
                        $trimesterBalance = $contract['trimester_balance_hours'];
                        $monthBalanceClass = $monthBalance === null ? 'text-muted' : ($monthBalance > 0 ? 'text-danger' : ($monthBalance < 0 ? 'text-primary' : 'text-muted'));
                        $trimesterBalanceClass = $trimesterBalance === null ? 'text-muted' : ($trimesterBalance > 0 ? 'text-danger' : ($trimesterBalance < 0 ? 'text-primary' : 'text-muted'));
                        ?>
                        <td class="schedule-total-column">
                            <span class="schedule-planned-hours"><?= e(ScheduleService::formatHours((float) $contract['planned_hours'])) ?></span>
                            <?php if ($contract['contract_type'] === 'primary'): ?>
                                    <small class="schedule-temp-breakdown d-block <?= (float) $contract['temporary_planned_hours'] > 0 ? '' : 'd-none' ?>"><?= (float) $contract['temporary_planned_hours'] > 0 ? e('(+' . ScheduleService::formatHours((float) $contract['temporary_planned_hours']) . ')') : '' ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="schedule-total-column"><span class="schedule-required-hours"><?= $contract['required_hours'] === null ? '—' : e(ScheduleService::formatHours((float) $contract['required_hours'])) ?></span></td>
                        <td class="schedule-total-column"><strong class="schedule-month-ot <?= e($monthBalanceClass) ?>"><?= $monthBalance === null ? '—' : e(ScheduleService::formatBalance((float) $monthBalance)) ?></strong></td>
                        <td class="schedule-total-column"><strong class="schedule-tri-ot <?= e($trimesterBalanceClass) ?>"><?= $trimesterBalance === null ? '—' : e(ScheduleService::formatBalance((float) $trimesterBalance)) ?></strong></td>
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
                            <h2 class="modal-title h5 mb-1" id="schedulePickerTitle">Choose shift, exception or block</h2>
                            <p class="small text-muted mb-0" id="schedulePickerContext">Select a schedule value.</p>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <h3 class="h6">Day off</h3>
                            <button class="btn btn-outline-secondary schedule-choice" type="button" data-schedule-value="0" data-schedule-code="−" aria-pressed="false">Off</button>
                        </div>
                        <?php foreach (['shift' => 'Shifts', 'exception' => 'Exceptions', 'block' => 'Blocks'] as $type => $label): ?>
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
        <div class="modal fade" id="scheduleWorkloadModal" tabindex="-1" aria-labelledby="scheduleWorkloadTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title h5 mb-1" id="scheduleWorkloadTitle"><?= e(t('panel.monthly_workload', 'Monthly workload')) ?></h2>
                            <p class="small text-muted mb-0" id="scheduleWorkloadContext"></p>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row row-cols-2 row-cols-sm-3 g-2">
                            <div class="col">
                                <button class="btn btn-outline-secondary schedule-workload-choice w-100 text-start" type="button" data-workload-id="" aria-pressed="false">
                                    <span class="d-block fw-semibold"><?= e(t('panel.contract_default_workload', 'Contract default')) ?></span>
                                    <small id="scheduleWorkloadDefaultPercent"></small>
                                </button>
                            </div>
                            <?php foreach ($workloads as $workload): ?>
                                <?php if ($workload['status'] === 'active'): ?>
                                    <div class="col">
                                        <button class="btn btn-outline-primary schedule-workload-choice w-100 text-start" type="button" data-workload-id="<?= (int) $workload['id'] ?>" data-workload-percent="<?= e(number_format((float) $workload['workload_percent'], 2, '.', '')) ?>" aria-pressed="false">
                                            <span class="d-block fw-semibold"><?= e($workload['name']) ?></span>
                                            <small><?= e(number_format((float) $workload['workload_percent'], 2)) ?>%</small>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="scheduleOverlapWarningModal" tabindex="-1" aria-labelledby="scheduleOverlapWarningTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="scheduleOverlapWarningTitle">Schedule conflict</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="alert alert-warning" id="scheduleOverlapWarningMessage" role="alert"></p>
                        <p class="text-muted mb-0">Nothing was saved. Choose a non-overlapping schedule value.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="button" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            (() => {
                const modal = document.getElementById('schedulePickerModal');
                const workloadModal = document.getElementById('scheduleWorkloadModal');
                const overlapWarningModal = document.getElementById('scheduleOverlapWarningModal');
                const overlapWarningMessage = document.getElementById('scheduleOverlapWarningMessage');
                const context = document.getElementById('schedulePickerContext');
                const workloadContext = document.getElementById('scheduleWorkloadContext');
                const workloadDefaultPercent = document.getElementById('scheduleWorkloadDefaultPercent');
                const form = document.querySelector('.schedule-form');
                const status = document.getElementById('scheduleSaveStatus');
                let activeCellButton = null;
                let activeWorkloadButton = null;
                let saveAfterClose = false;
                let saveWorkloadAfterClose = false;
                let pendingAssignment = null;
                let pendingWorkloadChange = null;
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

                function showOverlapWarning(message) {
                    status.classList.add('d-none');
                    status.textContent = '';
                    overlapWarningMessage.textContent = message;
                    bootstrap.Modal.getOrCreateInstance(overlapWarningModal).show();
                }

                function restoreAssignment(assignmentToSave) {
                    const assignment = Array.from(form.querySelectorAll('.schedule-assignment'))
                        .find((input) => input.name === assignmentToSave.name);
                    const button = assignment?.parentElement.querySelector('.schedule-cell-button');
                    const previous = assignmentToSave.previousState;
                    if (!assignment || !button || !previous) {
                        return;
                    }

                    assignment.value = previous.value;
                    button.textContent = previous.text;
                    button.classList.toggle('is-empty', previous.isEmpty);
                    button.classList.toggle('is-assigned', !previous.isEmpty);
                    if (previous.color) {
                        button.style.setProperty('--schedule-color', previous.color);
                    } else {
                        button.style.removeProperty('--schedule-color');
                    }
                    button.setAttribute('aria-label', previous.ariaLabel);
                }

                function formatHours(value) {
                    const rounded = Math.round(Number(value) * 100) / 100;
                    return `${Number.isInteger(rounded) ? rounded.toFixed(0) : rounded.toFixed(2)}h`;
                }

                function formatBalance(value) {
                    const rounded = Math.round(Number(value) * 100) / 100;
                    return `${rounded > 0 ? '+' : ''}${formatHours(rounded)}`;
                }

                function updateBalance(row, selector, value) {
                    const balance = row.querySelector(selector);
                    if (!balance) {
                        return;
                    }

                    if (value === null) {
                        balance.textContent = '—';
                        balance.classList.remove('text-danger', 'text-primary');
                        balance.classList.add('text-muted');
                        return;
                    }

                    balance.textContent = formatBalance(value);
                    balance.classList.remove('text-danger', 'text-primary', 'text-muted');
                    balance.classList.add(value > 0 ? 'text-danger' : (value < 0 ? 'text-primary' : 'text-muted'));
                }

                function updateHourSummary(hourSummary) {
                    Object.entries(hourSummary || {}).forEach(([contractId, summary]) => {
                        const row = form.querySelector(`[data-contract-id="${contractId}"]`);
                        if (!row) {
                            return;
                        }

                        row.querySelector('.schedule-planned-hours').textContent = formatHours(summary.plannedHours);
                        const requiredHours = row.querySelector('.schedule-required-hours');
                        if (requiredHours) {
                            requiredHours.textContent = summary.requiredHours === null ? '—' : formatHours(summary.requiredHours);
                        }
                        const temporaryBreakdown = row.querySelector('.schedule-temp-breakdown');
                        if (temporaryBreakdown) {
                            const temporaryHours = Number(summary.temporaryPlannedHours || 0);
                            temporaryBreakdown.textContent = temporaryHours > 0 ? `(+${formatHours(temporaryHours)})` : '';
                            temporaryBreakdown.classList.toggle('d-none', temporaryHours <= 0);
                        }
                        const workloadButton = row.querySelector('.schedule-workload-button');
                        if (workloadButton && Object.hasOwn(summary, 'monthlyWorkloadId')) {
                            const selectedWorkload = summary.monthlyWorkloadId === null ? '' : String(summary.monthlyWorkloadId);
                            workloadButton.dataset.workloadId = selectedWorkload;
                            workloadButton.dataset.savedValue = selectedWorkload;
                        }
                        updateBalance(row, '.schedule-month-ot', summary.monthlyBalance);
                        updateBalance(row, '.schedule-tri-ot', summary.trimesterBalance);
                    });
                }

                async function saveSchedule(assignmentToSave) {
                    if (isSaving) {
                        return;
                    }

                    isSaving = true;
                    form.querySelectorAll('.schedule-cell-button, .schedule-workload-button').forEach((button) => {
                        button.disabled = true;
                    });
                    showSaveStatus('Saving schedule...', 'muted');

                    try {
                        const formData = new FormData(form);
                        for (const name of Array.from(formData.keys())) {
                            if (name.startsWith('assignments[')) {
                                formData.delete(name);
                            }
                        }
                        formData.set(assignmentToSave.name, assignmentToSave.value);
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
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

                        updateHourSummary(result.hourSummary);
                        showSaveStatus(result.message, 'success');
                    } catch (error) {
                        const message = error.message || 'Could not save the monthly schedule.';
                        restoreAssignment(assignmentToSave);
                        if (message.startsWith('This employee already has another shift during that time on ')) {
                            showOverlapWarning(message);
                        } else {
                            showSaveStatus(message, 'danger');
                        }
                    } finally {
                        form.querySelectorAll('.schedule-cell-button, .schedule-workload-button').forEach((button) => {
                            button.disabled = false;
                        });
                        isSaving = false;
                    }
                }

                async function saveMonthlyWorkload(change) {
                    const button = change.button;
                    const previous = change.previousState;
                    if (isSaving) {
                        button.dataset.workloadId = previous.workloadId;
                        button.dataset.savedValue = previous.workloadId;
                        button.dataset.workloadPercent = previous.workloadPercent;
                        button.textContent = previous.text;
                        button.setAttribute('aria-label', previous.ariaLabel);
                        return;
                    }

                    isSaving = true;
                    form.querySelectorAll('.schedule-cell-button, .schedule-workload-button').forEach((control) => {
                        control.disabled = true;
                    });
                    showSaveStatus('Saving schedule...', 'muted');

                    try {
                        const formData = new FormData();
                        formData.set('csrf_token', form.querySelector('[name="csrf_token"]').value);
                        formData.set('month', change.month);
                        formData.set('contract_id', change.contractId);
                        formData.set('workload_id', change.workloadId);
                        const response = await fetch('/panel/schedule/workload', {
                            method: 'POST',
                            body: formData,
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
                            throw new Error(result.message || 'Could not save the monthly workload.');
                        }

                        updateHourSummary(result.hourSummary);
                        button.dataset.savedValue = change.workloadId;
                        showSaveStatus(result.message, 'success');
                    } catch (error) {
                        button.dataset.workloadId = previous.workloadId;
                        button.dataset.savedValue = previous.workloadId;
                        button.dataset.workloadPercent = previous.workloadPercent;
                        button.textContent = previous.text;
                        button.setAttribute('aria-label', previous.ariaLabel);
                        showSaveStatus(error.message || 'Could not save the monthly workload.', 'danger');
                    } finally {
                        form.querySelectorAll('.schedule-cell-button, .schedule-workload-button').forEach((control) => {
                            control.disabled = false;
                        });
                        isSaving = false;
                    }
                }

                workloadModal.addEventListener('show.bs.modal', (event) => {
                    activeWorkloadButton = event.relatedTarget;
                    workloadContext.textContent = `${activeWorkloadButton.dataset.employee} · ${activeWorkloadButton.dataset.month}`;
                    workloadDefaultPercent.textContent = `${activeWorkloadButton.dataset.baseWorkloadPercent}%`;
                    const currentWorkloadId = activeWorkloadButton.dataset.workloadId || '';
                    workloadModal.querySelectorAll('.schedule-workload-choice').forEach((choice) => {
                        const isCurrent = choice.dataset.workloadId === currentWorkloadId;
                        choice.classList.toggle('active', isCurrent);
                        choice.setAttribute('aria-pressed', String(isCurrent));
                    });
                });

                workloadModal.addEventListener('click', (event) => {
                    const choice = event.target.closest('.schedule-workload-choice');
                    if (!choice || !activeWorkloadButton) {
                        return;
                    }

                    const workloadId = choice.dataset.workloadId || '';
                    const previousWorkloadId = activeWorkloadButton.dataset.workloadId || '';
                    if (workloadId === previousWorkloadId) {
                        bootstrap.Modal.getOrCreateInstance(workloadModal).hide();
                        return;
                    }

                    const workloadPercent = workloadId === ''
                        ? activeWorkloadButton.dataset.baseWorkloadPercent
                        : choice.dataset.workloadPercent;
                    const previousState = {
                        workloadId: previousWorkloadId,
                        workloadPercent: activeWorkloadButton.dataset.workloadPercent,
                        text: activeWorkloadButton.textContent.trim(),
                        ariaLabel: activeWorkloadButton.getAttribute('aria-label'),
                    };
                    activeWorkloadButton.dataset.workloadId = workloadId;
                    activeWorkloadButton.dataset.workloadPercent = workloadPercent;
                    activeWorkloadButton.textContent = `${Number(workloadPercent).toFixed(2)}%`;
                    activeWorkloadButton.setAttribute(
                        'aria-label',
                        `${activeWorkloadButton.dataset.workloadLabel} ${Number(workloadPercent).toFixed(2)}%`
                    );
                    pendingWorkloadChange = {
                        button: activeWorkloadButton,
                        contractId: activeWorkloadButton.dataset.contractId,
                        month: activeWorkloadButton.dataset.month,
                        workloadId,
                        workloadPercent,
                        previousState,
                    };
                    saveWorkloadAfterClose = true;
                    bootstrap.Modal.getOrCreateInstance(workloadModal).hide();
                });

                workloadModal.addEventListener('hidden.bs.modal', () => {
                    activeWorkloadButton = null;
                    if (!saveWorkloadAfterClose) {
                        return;
                    }

                    saveWorkloadAfterClose = false;
                    const workloadChange = pendingWorkloadChange;
                    pendingWorkloadChange = null;
                    if (workloadChange !== null) {
                        saveMonthlyWorkload(workloadChange);
                    }
                });

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
                    const previousState = {
                        value: assignment.value,
                        text: activeCellButton.textContent.trim(),
                        isEmpty: activeCellButton.classList.contains('is-empty'),
                        color: activeCellButton.style.getPropertyValue('--schedule-color'),
                        ariaLabel: activeCellButton.getAttribute('aria-label'),
                    };
                    assignment.value = choice.dataset.scheduleValue;
                    pendingAssignment = { name: assignment.name, value: assignment.value, previousState };
                    const isOff = assignment.value === '0';
                    activeCellButton.textContent = isOff ? String(Number(activeCellButton.dataset.date.slice(-2))) : choice.dataset.scheduleCode;
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
                        const assignmentToSave = pendingAssignment;
                        pendingAssignment = null;
                        if (assignmentToSave !== null) {
                            saveSchedule(assignmentToSave);
                        }
                    }
                });
            })();
        </script>
    <?php endif; ?>
<?php endif; ?>

<style>
.schedule-grid { width: 100%; min-width: 0; table-layout: fixed; }
.schedule-weekend { --bs-table-bg: rgb(255 128 128 / 50%); --bs-table-accent-bg: transparent; }
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
.schedule-cell-button.is-empty { opacity: .5; }
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