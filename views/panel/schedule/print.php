<?php $scheduleSection = 'print'; require __DIR__ . '/nav.php'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3 no-print">
    <div>
        <h1 class="h4 mb-1"><?= e(t('ui.' . hash('sha256', 'Print schedule'), 'Print schedule')) ?></h1>
        <p class="text-muted mb-0"><?= e($month['month']) ?></p>
    </div>
    <form method="get" action="/panel/schedule/print" class="d-flex align-items-end gap-2">
        <div>
            <label class="form-label mb-1" for="print-month"><?= e(t('ui.' . hash('sha256', 'Month'), 'Month')) ?></label>
            <input class="form-control" type="month" id="print-month" name="month" value="<?= e($month['month']) ?>" required>
        </div>
        <button class="btn btn-outline-primary" type="submit"><?= e(t('ui.' . hash('sha256', 'Show'), 'Show')) ?></button>
    </form>
</div>

<?php if (empty($locations)): ?>
    <p class="text-muted"><?= e(t('ui.' . hash('sha256', 'No locations available for this month.'), 'No locations available for this month.')) ?></p>
<?php else: ?>
    <section class="mb-4 no-print" aria-labelledby="print-location-heading">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <h2 class="h6 mb-0" id="print-location-heading"><?= e(t('ui.' . hash('sha256', 'Choose locations to print'), 'Choose locations to print')) ?></h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="select-all-locations" type="button"><?= e(t('ui.' . hash('sha256', 'Select all'), 'Select all')) ?></button>
                <button class="btn btn-outline-secondary btn-sm" id="clear-locations" type="button"><?= e(t('ui.' . hash('sha256', 'Clear selection'), 'Clear selection')) ?></button>
                <button class="btn btn-primary btn-sm" id="print-schedule" type="button"><?= e(t('ui.' . hash('sha256', 'Print / Save as PDF'), 'Print / Save as PDF')) ?></button>
            </div>
        </div>
        <div class="print-location-options">
            <?php foreach ($locations as $locationKey => $location): ?>
                <label class="form-check print-location-option">
                    <input class="form-check-input" type="checkbox" value="<?= e($locationKey) ?>" checked>
                    <span class="form-check-label"><?= e($location['label']) ?> <small class="text-muted">(<?= count($location['contracts']) ?>)</small></span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <div id="schedule-print-pages">
        <?php foreach ($locations as $locationKey => $location): ?>
            <section class="print-location-page" data-location-key="<?= e($locationKey) ?>">
                <header class="print-page-heading">
                    <div>
                        <h1><?= e(t('ui.' . hash('sha256', 'Work schedule'), 'Work schedule')) ?> · <?= e($month['month']) ?></h1>
                        <h2><?= e($location['label']) ?></h2>
                    </div>
                    <time class="print-date" datetime="<?= e(date('Y-m-d')) ?>"><?= e(date('d.m.Y')) ?></time>
                </header>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered schedule-print-grid">
                        <thead>
                            <tr>
                                <th class="print-employee-column"><?= e(t('ui.' . hash('sha256', 'Employee'), 'Employee')) ?></th>
                                <?php for ($day = 1; $day <= $month['days']; $day++): ?>
                                    <?php $date = sprintf('%s-%02d', $month['month'], $day); ?>
                                    <th class="print-day-column <?= ScheduleService::isNonWorkingDay($date) ? 'schedule-weekend' : '' ?>">
                                        <?= $day ?><small><?= e(date('D', strtotime($date))) ?></small>
                                    </th>
                                <?php endfor; ?>
                                <th class="print-total-column"><?= e(t('ui.' . hash('sha256', 'Sum h'), 'Sum h')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($location['contracts'] as $contract): ?>
                                <?php $contractId = (int) $contract['contract_id']; ?>
                                <tr>
                                    <th scope="row" class="print-employee-column">
                                        <?= e($contract['full_name'] ?: $contract['username']) ?>
                                        <?php if ($contract['contract_type'] === 'temporary'): ?><small class="print-secondary-label"><?= e(t('ui.' . hash('sha256', 'Temporary assignment'), 'Temporary assignment')) ?></small><?php endif; ?>
                                    </th>
                                    <?php for ($day = 1; $day <= $month['days']; $day++): ?>
                                        <?php
                                        $date = sprintf('%s-%02d', $month['month'], $day);
                                        $entry = $entries[$contractId][$date] ?? null;
                                        $entryColor = $entry === null ? null : ScheduleService::safeTemplateColor($entry['color_hex'] ?? null);
                                        ?>
                                        <td class="print-day-column <?= ScheduleService::isNonWorkingDay($date) ? 'schedule-weekend' : '' ?> <?= $entry === null ? '' : 'is-assigned' ?>" style="<?= $entryColor === null ? '' : ' --schedule-color: ' . e($entryColor) . ';' ?>" title="<?= $entry === null ? '' : e(ScheduleService::formatTemplate($entry)) ?>">
                                            <?= $entry === null ? '' : e($entry['code']) ?>
                                        </td>
                                    <?php endfor; ?>
                                    <td class="print-total-column">
                                        <?= e(ScheduleService::formatHours((float) $contract['planned_hours'])) ?>
                                        <?php if ($contract['contract_type'] === 'primary' && (float) $contract['temporary_planned_hours'] > 0): ?>
                                            <small class="print-secondary-label">(+<?= e(ScheduleService::formatHours((float) $contract['temporary_planned_hours'])) ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.print-location-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: .25rem 1rem; }
.print-location-option { display: flex; align-items: flex-start; gap: .5rem; padding: .35rem .5rem; border-bottom: 1px solid var(--bs-border-color); }
.print-page-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.print-page-heading h1 { font-size: 1rem; margin: 0 0 .15rem; }
.print-page-heading h2 { font-size: .9rem; margin: 0 0 .5rem; }
.print-date { display: block; margin-left: auto; text-align: right; white-space: nowrap; }
.schedule-print-grid { width: 100%; table-layout: fixed; }
.schedule-print-grid th, .schedule-print-grid td { padding: .15rem .05rem; text-align: center; vertical-align: middle; font-size: .58rem; overflow-wrap: anywhere; }
.schedule-print-grid .print-employee-column { width: 115px; text-align: left; }
.schedule-print-grid .print-day-column { width: 24px; }
.schedule-print-grid .print-day-column small, .print-secondary-label { display: block; font-size: .48rem; font-weight: 400; }
.schedule-print-grid .print-total-column { width: 40px; white-space: nowrap; }
.schedule-print-grid .schedule-weekend { --bs-table-bg: rgb(255 128 128 / 50%); --bs-table-accent-bg: transparent; }
.schedule-print-grid .print-day-column.is-assigned { background: color-mix(in srgb, var(--schedule-color, #64748B) 18%, white); box-shadow: inset 0 -4px 0 var(--schedule-color, #64748B); color: var(--bs-body-color); print-color-adjust: exact; -webkit-print-color-adjust: exact; }
@media print {
    @page { size: A4 landscape; margin: 6mm; }
    html, body { height: auto !important; min-height: 0 !important; }
    body { display: block !important; background: #fff !important; }
    .app-header, .app-footer { display: none !important; }
    .app-main { display: block !important; flex: none !important; margin: 0 !important; padding: 0 !important; }
    .app-main > .container-fluid { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
    .app-main > .container-fluid > .row { display: block !important; margin: 0 !important; }
    .app-main > .container-fluid > .row > nav { display: none !important; }
    .app-main > .container-fluid > .row > section { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .app-main > .container-fluid > .row > section > .nav-tabs { display: none !important; }
    .no-print, .print-location-page[hidden] { display: none !important; }
    #schedule-print-pages { display: block !important; }
    .print-location-page { break-after: page; page-break-after: always; }
    .print-location-page:last-child { break-after: auto; page-break-after: auto; }
    .print-page-heading h1 { font-size: 10pt; }
    .print-page-heading h2 { font-size: 9pt; }
    .print-date { font-size: 8pt; white-space: nowrap; }
    .schedule-print-grid { margin: 0; }
    .schedule-print-grid th, .schedule-print-grid td { padding: .6mm .2mm; font-size: 6pt; }
    .schedule-print-grid tbody tr > * { height: 7.3mm; }
    .schedule-print-grid .print-employee-column { width: 30mm; }
    .schedule-print-grid .print-day-column { width: auto; }
    .schedule-print-grid .print-day-column small, .print-secondary-label { font-size: 5pt; }
    .schedule-print-grid .print-total-column { width: 12mm; }
    .schedule-print-grid .schedule-weekend { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    tr { break-inside: avoid; page-break-inside: avoid; }
}
</style>

<?php if (!empty($locations)): ?>
    <script>
        (() => {
            const locationInputs = [...document.querySelectorAll('.print-location-option input')];
            const locationPages = [...document.querySelectorAll('.print-location-page')];
            const printButton = document.getElementById('print-schedule');
            const syncSelection = () => {
                const selectedLocations = new Set(locationInputs.filter(input => input.checked).map(input => input.value));
                locationPages.forEach(page => {
                    page.hidden = !selectedLocations.has(page.dataset.locationKey);
                });
                printButton.disabled = selectedLocations.size === 0;
            };

            locationInputs.forEach(input => input.addEventListener('change', syncSelection));
            document.getElementById('select-all-locations').addEventListener('click', () => {
                locationInputs.forEach(input => { input.checked = true; });
                syncSelection();
            });
            document.getElementById('clear-locations').addEventListener('click', () => {
                locationInputs.forEach(input => { input.checked = false; });
                syncSelection();
            });
            printButton.addEventListener('click', () => window.print());
            syncSelection();
        })();
    </script>
<?php endif; ?>