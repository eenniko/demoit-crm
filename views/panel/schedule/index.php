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
    <form method="post" action="/panel/schedule" class="table-responsive">
        <?= Csrf::field() ?>
        <input type="hidden" name="month" value="<?= e($month['month']) ?>">
        <table class="table table-sm table-bordered align-middle schedule-grid">
            <thead>
                <tr>
                    <th class="schedule-employee">Employee</th>
                    <th class="schedule-location">Floor / department</th>
                    <th>Workload</th>
                    <th>Required / planned</th>
                    <?php for ($day = 1; $day <= $month['days']; $day++): ?>
                        <?php $date = sprintf('%s-%02d', $month['month'], $day); ?>
                        <th class="text-center schedule-day <?= (int) date('N', strtotime($date)) >= 6 ? 'table-light' : '' ?>">
                            <?= $day ?><br><small><?= e(date('D', strtotime($date))) ?></small>
                        </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php $previousGroup = null; ?>
                <?php foreach ($contracts as $contract): ?>
                    <?php
                    $contractId = (int) $contract['contract_id'];
                    $groupLabel = $contract['property_path'] . ' · ' . ($contract['department_name'] ?: 'No department');
                    if ($groupLabel !== $previousGroup):
                        $previousGroup = $groupLabel;
                    ?>
                        <tr class="table-light"><th colspan="<?= 4 + (int) $month['days'] ?>"><?= e($groupLabel) ?></th></tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row" class="schedule-employee">
                            <?= e($contract['full_name'] ?: $contract['username']) ?>
                            <small class="d-block text-muted"><?= e($contract['username']) ?></small>
                        </th>
                        <td class="schedule-location">
                            <?= e($contract['property_path']) ?>
                            <?php if (!empty($contract['department_name'])): ?><small class="d-block text-muted"><?= e($contract['department_name']) ?></small><?php endif; ?>
                        </td>
                        <td><?= e(number_format((float) $contract['workload_percent'], 2)) ?>%</td>
                        <td class="text-nowrap">
                            <strong><?= e(number_format((float) $contract['required_hours'], 2)) ?>h</strong>
                            <small class="d-block text-muted"><?= e(number_format((float) $contract['planned_hours'], 2)) ?>h planned</small>
                        </td>
                        <?php for ($day = 1; $day <= $month['days']; $day++): ?>
                            <?php
                            $date = sprintf('%s-%02d', $month['month'], $day);
                            $entry = $entries[$contractId][$date] ?? null;
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($canManage): ?>
            <button class="btn btn-primary" type="submit">Save schedule</button>
        <?php endif; ?>
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
                            <button class="btn btn-outline-secondary schedule-choice" type="button" data-schedule-value="0" data-schedule-code="−" data-schedule-active="active" aria-pressed="false">Off</button>
                        </div>
                        <?php foreach (['shift' => 'Shifts', 'exception' => 'Exceptions'] as $type => $label): ?>
                            <?php $typeTemplates = array_values(array_filter($templates, static fn (array $template): bool => $template['template_type'] === $type)); ?>
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
                                                    data-schedule-active="<?= e($template['status']) ?>"
                                                    aria-pressed="false">
                                                    <span class="d-block fw-semibold"><?= e($template['code']) ?><?= $template['status'] === 'inactive' ? ' · inactive' : '' ?></span>
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
                let activeCellButton = null;

                modal.addEventListener('show.bs.modal', (event) => {
                    activeCellButton = event.relatedTarget;
                    const assignment = activeCellButton.parentElement.querySelector('.schedule-assignment');
                    const currentValue = assignment.value;
                    context.textContent = `${activeCellButton.dataset.employee} · ${activeCellButton.dataset.date}`;

                    modal.querySelectorAll('.schedule-choice').forEach((choice) => {
                        const isCurrent = choice.dataset.scheduleValue === currentValue;
                        choice.disabled = choice.dataset.scheduleActive !== 'active' && !isCurrent;
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
                    activeCellButton.setAttribute(
                        'aria-label',
                        `${activeCellButton.dataset.employee} ${activeCellButton.dataset.date} ${isOff ? 'Off' : choice.dataset.scheduleCode}`
                    );
                    bootstrap.Modal.getOrCreateInstance(modal).hide();
                });

                modal.addEventListener('hidden.bs.modal', () => {
                    activeCellButton = null;
                });
            })();
        </script>
    <?php endif; ?>
<?php endif; ?>

<style>
.schedule-grid { min-width: 1900px; }
.schedule-employee { min-width: 190px; }
.schedule-location { min-width: 210px; }
.schedule-day { min-width: 66px; padding: 0 !important; }
.schedule-cell-button { display: block; width: 100%; min-height: 48px; padding: .25rem; border: 0; border-radius: 0; background: transparent; color: var(--bs-secondary-color); font-weight: 600; }
.schedule-cell-button.is-empty { font-size: 1.5rem; font-weight: 400; }
.schedule-cell-button.is-assigned { background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
.schedule-cell-button:hover { background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
.schedule-cell-button:focus-visible { position: relative; z-index: 1; outline: 2px solid var(--bs-primary); outline-offset: -2px; }
</style>