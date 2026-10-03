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
    <?php $typeLabels = ['building' => 'Building', 'wing' => 'Wing', 'floor' => 'Floor', 'room' => 'Room']; ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Type</th>
                    <?php if ($canManage): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($nodes as $node): ?>
                    <tr>
                        <td class="ps-<?= min((int) $node['depth'] + 2, 5) ?>"><?= e($node['name']) ?></td>
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
<?php endif; ?>