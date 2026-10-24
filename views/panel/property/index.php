<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Property structure</h1>
    <?php if ($canManage): ?>
        <a class="btn btn-primary btn-sm" href="/panel/property/create">Add building</a>
    <?php endif; ?>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>

<?php if (empty($nodes)): ?>
    <p class="text-muted">No buildings yet.</p>
<?php else: ?>
    <?php
    $typeLabels = ['building' => 'Building', 'wing' => 'Wing', 'floor' => 'Floor', 'room' => 'Room'];
    $childIds = [];
    foreach ($nodes as $node) {
        if ($node['parent_id'] !== null) {
            $childIds[(int) $node['parent_id']][] = 'property-node-' . (int) $node['id'];
        }
    }
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="property-tree">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Type</th>
                    <?php if ($canManage): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($nodes as $node): ?>
                    <tr id="property-node-<?= (int) $node['id'] ?>" data-parent="<?= (int) $node['parent_id'] ?>"
                        <?= $node['parent_id'] !== null ? 'hidden' : '' ?>>
                        <td class="ps-<?= min((int) $node['depth'] + 2, 5) ?>">
                            <?php if (isset($childIds[(int) $node['id']])): ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm me-2" data-property-toggle
                                        aria-expanded="false" aria-controls="<?= e(implode(' ', $childIds[(int) $node['id']])) ?>"
                                        aria-label="<?= e($node['name']) ?>"><span data-no-translate>+</span></button>
                            <?php endif; ?>
                            <?= e($node['name']) ?>
                        </td>
                        <td><?= e($typeLabels[$node['node_type']]) ?></td>
                        <?php if ($canManage): ?>
                            <td class="text-end text-nowrap">
                                <form method="post" action="/panel/property/reorder" class="d-inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $node['id'] ?>">
                                    <button class="btn btn-outline-secondary btn-sm" type="submit" name="direction" value="up" aria-label="Move <?= e(strtolower($typeLabels[$node['node_type']])) ?> up" <?= (int) $node['sibling_position'] === 0 ? 'disabled' : '' ?>>Up</button>
                                    <button class="btn btn-outline-secondary btn-sm" type="submit" name="direction" value="down" aria-label="Move <?= e(strtolower($typeLabels[$node['node_type']])) ?> down" <?= (int) $node['sibling_position'] >= (int) $node['sibling_count'] - 1 ? 'disabled' : '' ?>>Down</button>
                                </form>
                                <a class="btn btn-outline-secondary btn-sm" href="/panel/property/edit?id=<?= (int) $node['id'] ?>">Edit name</a>
                                <?php if (PropertyService::allowedChildTypes($node['node_type']) !== []): ?>
                                    <a class="btn btn-outline-secondary btn-sm" href="/panel/property/create?parent=<?= (int) $node['id'] ?>">Add inside</a>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        (() => {
            const table = document.getElementById('property-tree');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const rowsById = new Map(rows.map(row => [row.id, row]));
            table.addEventListener('click', event => {
                const button = event.target.closest('[data-property-toggle]');
                if (!button) return;
                const expanded = button.getAttribute('aria-expanded') !== 'true';
                button.setAttribute('aria-expanded', String(expanded));
                button.querySelector('span').textContent = expanded ? '-' : '+';
                rows.forEach(row => {
                    const parent = rowsById.get('property-node-' + row.dataset.parent);
                    if (!parent) return;
                    row.hidden = parent.hidden || parent.querySelector('[data-property-toggle]').getAttribute('aria-expanded') !== 'true';
                });
            });
        })();
    </script>
<?php endif; ?>