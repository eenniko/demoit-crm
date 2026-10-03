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
                                    <select class="form-select form-select-sm schedule-cell" name="assignments[<?= $contractId ?>][<?= e($date) ?>]" aria-label="<?= e(($contract['full_name'] ?: $contract['username']) . ' ' . $date) ?>">
                                        <option value="0" <?= $entry === null ? 'selected' : '' ?>>Off</option>
                                        <?php foreach (['shift' => 'Shifts', 'exception' => 'Exceptions'] as $type => $label): ?>
                                            <?php
                                            $typeTemplates = array_values(array_filter($templates, static fn (array $template): bool => $template['template_type'] === $type && ($template['status'] === 'active' || ((int) ($entry['template_id'] ?? 0) === (int) $template['id']))));
                                            if ($typeTemplates !== []):
                                            ?>
                                                <optgroup label="<?= e($label) ?>">
                                                    <?php foreach ($typeTemplates as $template): ?>
                                                        <option value="<?= (int) $template['id'] ?>" <?= (int) ($entry['template_id'] ?? 0) === (int) $template['id'] ? 'selected' : '' ?>><?= e(ScheduleService::formatTemplate($template) . ($template['status'] === 'inactive' ? ' (inactive)' : '')) ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
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
<?php endif; ?>

<style>
.schedule-grid { min-width: 1900px; }
.schedule-employee { min-width: 190px; }
.schedule-location { min-width: 210px; }
.schedule-day { min-width: 66px; }
.schedule-cell { min-width: 64px; padding: .25rem; }
</style>